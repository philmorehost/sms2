<?php
require_once __DIR__ . '/../app/bootstrap.php';

header('Content-Type: application/json');

// --- Response Helper ---
function api_response($success, $message, $data = []) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

// --- Authenticate Request ---
if (!isset($_SESSION['user_id'])) {
    api_response(false, 'Authentication required.');
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'verify_smartcard':
    case 'verify_meter':
        verify_vtu_service();
        break;

    case 'purchase_cable_tv':
        purchase_cable_tv();
        break;

    case 'purchase_electricity':
        purchase_electricity();
        break;

    case 'purchase_airtime':
        purchase_airtime();
        break;

    case 'get_data_plans':
        get_data_plans();
        break;

    case 'purchase_data':
        purchase_data();
        break;

    default:
        api_response(false, 'Invalid action specified.');
        break;
}

function verify_vtu_service() {
    global $conn;

    $serviceID = $_POST['serviceID'] ?? '';
    $billersCode = $_POST['billersCode'] ?? '';
    $type = $_POST['type'] ?? null; // For electricity meter type

    if (empty($serviceID) || empty($billersCode)) {
        api_response(false, 'Service provider and account/meter number are required.');
    }

    // Fetch VTPass API details
    $stmt = $conn->prepare("SELECT * FROM vtu_apis WHERE provider_name = 'VTPass'");
    $stmt->execute();
    $api_details = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (empty($api_details)) {
        api_response(false, 'VTPass API is not configured by the administrator.');
    }

    $api_url = $api_details['is_sandbox'] ? 'https://sandbox.vtpass.com/api/merchant-verify' : 'https://vtpass.com/api/merchant-verify';

    $headers = [];
    if ($api_details['is_sandbox']) {
        // Sandbox uses Basic Auth with email and password
        if (empty($api_details['username']) || empty($api_details['secret_key'])) {
            api_response(false, 'VTPass Sandbox Email/Password are not configured.');
        }
        $headers[] = 'Authorization: Basic ' . base64_encode($api_details['username'] . ':' . $api_details['secret_key']);
    } else {
        // Live uses api-key and secret-key headers
        if (empty($api_details['api_key']) || empty($api_details['secret_key'])) {
            api_response(false, 'VTPass Live API/Secret keys are not configured.');
        }
        $headers[] = 'api-key: ' . $api_details['api_key'];
        $headers[] = 'secret-key: ' . $api_details['secret_key'];
    }

    $post_data = [
        'serviceID' => $serviceID,
        'billersCode' => $billersCode,
    ];

    if ($type) {
        $post_data['type'] = $type;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        api_response(false, 'API call failed: ' . $curl_error);
    }

    $api_result = json_decode($response, true);

    if (isset($api_result['content']['Customer_Name'])) {
        api_response(true, 'Verification successful', $api_result['content']);
    } else {
        $error_message = $api_result['response_description'] ?? ($api_result['content']['error'] ?? 'Failed to verify smartcard number.');
        api_response(false, $error_message, $api_result);
    }
}

function purchase_cable_tv() {
    global $conn;
    $user_id = $_SESSION['user_id'];

    $serviceID = $_POST['serviceID'] ?? '';
    $billersCode = $_POST['billersCode'] ?? '';
    $variation_code = $_POST['variation_code'] ?? '';

    if (empty($serviceID) || empty($billersCode) || empty($variation_code)) {
        api_response(false, 'Missing required fields for purchase.');
    }

    // 1. Get product details and user balance in one go
    $stmt = $conn->prepare("SELECT p.amount, p.user_discount_percentage, u.balance FROM vtu_products p, users u WHERE p.api_product_id = ? AND u.id = ?");
    $stmt->bind_param("si", $variation_code, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $details = $result->fetch_assoc();
    $stmt->close();

    if (!$details) {
        api_response(false, 'Invalid product selected.');
    }

    // 2. Calculate final price and check balance
    $base_price = (float)$details['amount'];
    $discount = $base_price * ((float)$details['user_discount_percentage'] / 100);
    $final_price = $base_price - $discount;

    if ((float)$details['balance'] < $final_price) {
        api_response(false, 'Insufficient wallet balance.');
    }

    // 3. Fetch VTPass API credentials
    $stmt_api = $conn->prepare("SELECT * FROM vtu_apis WHERE provider_name = 'VTPass'");
    $stmt_api->execute();
    $api_details = $stmt_api->get_result()->fetch_assoc();
    $stmt_api->close();

    if (empty($api_details)) {
        api_response(false, 'VTPass API is not configured.');
    }

    // 4. All checks passed, begin transaction
    $conn->begin_transaction();
    try {
        // Debit user
        $stmt_debit = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
        $stmt_debit->bind_param("di", $final_price, $user_id);
        $stmt_debit->execute();
        $stmt_debit->close();

        // Log transaction
        $description = "Cable TV Subscription: " . ucfirst($serviceID) . " - " . $variation_code;
        $stmt_log = $conn->prepare("INSERT INTO transactions (user_id, type, vtu_service_type, amount, total_amount, status, gateway, description, vtu_recipient) VALUES (?, 'debit', 'cable_tv', ?, ?, 'pending', 'vtpass', ?, ?)");
        $stmt_log->bind_param("iddss", $user_id, $base_price, $final_price, $description, $billersCode);
        $stmt_log->execute();
        $transaction_id = $stmt_log->insert_id;
        $stmt_log->close();

        // 5. Call VTPass API
        $api_url = $api_details['is_sandbox'] ? 'https://sandbox.vtpass.com/api/pay' : 'https://vtpass.com/api/pay';
        $request_id = date('YmdHis') . $transaction_id; // Unique request ID
        $user_phone = $GLOBALS['current_user']['phone_number'];

        $post_data = [
            'request_id' => $request_id,
            'serviceID' => $serviceID,
            'billersCode' => $billersCode,
            'variation_code' => $variation_code,
            'amount' => $base_price,
            'phone' => $user_phone,
            'subscription_type' => 'change', // Defaulting to 'change' as it works for new and renewal
        ];

        $headers = [];
        if ($api_details['is_sandbox']) {
            $headers[] = 'Authorization: Basic ' . base64_encode($api_details['username'] . ':' . $api_details['secret_key']);
        } else {
            $headers[] = 'api-key: ' . $api_details['api_key'];
            $headers[] = 'secret-key: ' . $api_details['secret_key'];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        curl_close($ch);

        $api_result = json_decode($response, true);

        // 6. Update transaction with API response
        $final_status = 'pending'; // Default to pending, requery will confirm
        if(isset($api_result['code']) && $api_result['code'] == '000') {
            $final_status = 'completed'; // Assume success if code is 000
        } elseif (isset($api_result['code']) && $api_result['code'] != '099') { // 099 is pending
            $final_status = 'failed';
        }

        $stmt_update = $conn->prepare("UPDATE transactions SET status = ?, api_response = ? WHERE id = ?");
        $stmt_update->bind_param("ssi", $final_status, $response, $transaction_id);
        $stmt_update->execute();
        $stmt_update->close();

        if ($final_status === 'failed') {
            throw new Exception('Transaction failed at API provider.');
        }

        $conn->commit();
        api_response(true, 'Your subscription request has been submitted successfully and is being processed.');

    } catch (Exception $e) {
        $conn->rollback();
        // If the failure was after debit, we need to inform the user.
        // A dedicated requery script will handle the refund to avoid race conditions.
        error_log("VTU Purchase Error: " . $e->getMessage());
        api_response(false, "An error occurred during the transaction. Please check your transaction history or contact support if your wallet was debited.");
    }
}

function get_data_plans() {
    global $conn;
    $network = $_POST['network'] ?? '';

    if (empty($network)) {
        api_response(false, 'Network not specified.');
    }

    $stmt = $conn->prepare("SELECT id, name, amount, user_discount_percentage FROM vtu_products WHERE service_type = 'data' AND network = ? AND is_active = 1 ORDER BY amount ASC");
    $stmt->bind_param("s", $network);
    $stmt->execute();
    $result = $stmt->get_result();
    $plans = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    api_response(true, 'Plans fetched successfully', $plans);
}

function purchase_electricity() {
    global $conn;
    $user_id = $_SESSION['user_id'];

    $serviceID = $_POST['serviceID'] ?? '';
    $billersCode = $_POST['billersCode'] ?? '';
    $variation_code = $_POST['variation_code'] ?? ''; // prepaid or postpaid
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);

    if (empty($serviceID) || empty($billersCode) || empty($variation_code) || $amount === false || $amount <= 0) {
        api_response(false, 'Missing required fields for purchase.');
    }

    // 1. Get product details and user balance
    $stmt = $conn->prepare("SELECT p.user_discount_percentage, u.balance FROM vtu_products p, users u WHERE p.api_product_id = ? AND u.id = ?");
    $stmt->bind_param("si", $serviceID, $user_id);
    $stmt->execute();
    $details = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$details) {
        api_response(false, 'Invalid electricity provider selected.');
    }

    // 2. Calculate final price and check balance
    $discount_percent = (float)($details['user_discount_percentage'] ?? 0);
    $final_price = $amount - ($amount * ($discount_percent / 100));

    if ((float)$details['balance'] < $final_price) {
        api_response(false, 'Insufficient wallet balance.');
    }

    // 3. Fetch VTPass API credentials
    $provider = 'VTPass';
    $stmt_api = $conn->prepare("SELECT * FROM vtu_apis WHERE provider_name = ?");
    $stmt_api->bind_param("s", $provider);
    $stmt_api->execute();
    $api_details = $stmt_api->get_result()->fetch_assoc();
    $stmt_api->close();

    if (empty($api_details)) {
        api_response(false, 'VTPass API is not configured.');
    }

    // 4. All checks passed, begin transaction
    $conn->begin_transaction();
    try {
        // Debit user
        $stmt_debit = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
        $stmt_debit->bind_param("di", $final_price, $user_id);
        $stmt_debit->execute();
        $stmt_debit->close();

        // Log transaction
        $description = "Electricity Bill: " . ucfirst($serviceID) . " (" . $variation_code . ")";
        $stmt_log = $conn->prepare("INSERT INTO transactions (user_id, type, vtu_service_type, amount, total_amount, status, gateway, description, vtu_recipient) VALUES (?, 'debit', 'electricity', ?, ?, 'pending', ?, ?, ?)");
        $stmt_log->bind_param("iddsss", $user_id, $amount, $final_price, $provider, $description, $billersCode);
        $stmt_log->execute();
        $transaction_id = $stmt_log->insert_id;
        $stmt_log->close();

        // 5. Call VTPass API
        $api_url = $api_details['is_sandbox'] ? 'https://sandbox.vtpass.com/api/pay' : 'https://vtpass.com/api/pay';
        $request_id = date('YmdHis') . $transaction_id;
        $user_phone = $GLOBALS['current_user']['phone_number'];

        $post_data = [
            'request_id' => $request_id,
            'serviceID' => $serviceID,
            'billersCode' => $billersCode,
            'variation_code' => $variation_code,
            'amount' => $amount,
            'phone' => $user_phone,
        ];

        $headers = [];
        if ($api_details['is_sandbox']) {
            $headers[] = 'Authorization: Basic ' . base64_encode($api_details['username'] . ':' . $api_details['secret_key']);
        } else {
            $headers[] = 'api-key: ' . $api_details['api_key'];
            $headers[] = 'secret-key: ' . $api_details['secret_key'];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        curl_close($ch);

        $api_result = json_decode($response, true);

        // 6. Update transaction with API response
        $final_status = 'pending';
        if(isset($api_result['code']) && $api_result['code'] == '000') {
            $final_status = 'completed';
        } elseif (isset($api_result['code']) && $api_result['code'] != '099') {
            $final_status = 'failed';
        }

        $stmt_update = $conn->prepare("UPDATE transactions SET status = ?, api_response = ? WHERE id = ?");
        $stmt_update->bind_param("ssi", $final_status, $response, $transaction_id);
        $stmt_update->execute();
        $stmt_update->close();

        if ($final_status === 'failed') {
            throw new Exception('Transaction failed at API provider.');
        }

        $conn->commit();
        api_response(true, 'Your electricity bill payment has been submitted successfully.');

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Electricity Purchase Error: " . $e->getMessage());
        api_response(false, "An error occurred during the transaction. Please check your transaction history or contact support if your wallet was debited.");
    }
}

function purchase_data() {
    global $conn;
    $user_id = $_SESSION['user_id'];

    $phone = $_POST['phone'] ?? '';
    $network = $_POST['network'] ?? '';
    $dataplan_id = filter_input(INPUT_POST, 'dataplan_id', FILTER_VALIDATE_INT);

    if (empty($phone) || empty($network) || empty($dataplan_id)) {
        api_response(false, 'Missing required fields for data purchase.');
    }

    // 1. Get product details and user balance
    $stmt = $conn->prepare("SELECT p.amount, p.user_discount_percentage, p.api_provider, p.api_product_id, u.balance FROM vtu_products p, users u WHERE p.id = ? AND u.id = ?");
    $stmt->bind_param("ii", $dataplan_id, $user_id);
    $stmt->execute();
    $details = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$details) {
        api_response(false, 'Invalid data plan selected.');
    }

    // 2. Calculate final price and check balance
    $base_price = (float)$details['amount'];
    $discount = $base_price * ((float)$details['user_discount_percentage'] / 100);
    $final_price = $base_price - $discount;

    if ((float)$details['balance'] < $final_price) {
        api_response(false, 'Insufficient wallet balance.');
    }

    // 3. Fetch API credentials for the provider (ClubKonnect)
    $provider = 'ClubKonnect';
    $stmt_api = $conn->prepare("SELECT * FROM vtu_apis WHERE provider_name = ?");
    $stmt_api->bind_param("s", $provider);
    $stmt_api->execute();
    $api_details = $stmt_api->get_result()->fetch_assoc();
    $stmt_api->close();

    if (empty($api_details) || empty($api_details['username']) || empty($api_details['api_key'])) {
        api_response(false, 'ClubKonnect API is not configured by the administrator.');
    }

    // 4. All checks passed, begin transaction
    $conn->begin_transaction();
    try {
        // Debit user
        $stmt_debit = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
        $stmt_debit->bind_param("di", $final_price, $user_id);
        $stmt_debit->execute();
        $stmt_debit->close();

        // Log transaction
        $description = "Data Purchase: " . $network . " " . $details['api_product_id'];
        $stmt_log = $conn->prepare("INSERT INTO transactions (user_id, type, vtu_service_type, amount, total_amount, status, gateway, description, vtu_recipient) VALUES (?, 'debit', 'data', ?, ?, 'pending', ?, ?, ?)");
        $stmt_log->bind_param("iddsss", $user_id, $base_price, $final_price, $provider, $description, $phone);
        $stmt_log->execute();
        $transaction_id = $stmt_log->insert_id;
        $stmt_log->close();

        // 5. Call ClubKonnect API
        // TODO: Implement actual ClubKonnect Data API call
        // For now, simulate success
        $api_result = ['success' => true, 'response' => json_encode(['statuscode' => '100', 'status' => 'ORDER_RECEIVED', 'orderid' => $transaction_id])];

        if (!$api_result || !$api_result['success']) {
            throw new Exception($api_result['message'] ?? 'Data API provider failed.');
        }

        // 6. Update transaction with API response
        $final_status = (isset($api_result['statuscode']) && $api_result['statuscode'] == '100') ? 'pending' : 'failed';

        $stmt_update = $conn->prepare("UPDATE transactions SET status = ?, api_response = ? WHERE id = ?");
        $stmt_update->bind_param("ssi", $final_status, $api_result['response'], $transaction_id);
        $stmt_update->execute();
        $stmt_update->close();

        if ($final_status === 'failed') {
            throw new Exception('Transaction failed at API provider.');
        }

        $conn->commit();
        api_response(true, 'Your data purchase request has been submitted successfully and is being processed.');

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Data Purchase Error: " . $e->getMessage());
        api_response(false, "An error occurred during the transaction. Please check your transaction history or contact support if your wallet was debited.");
    }
}

function purchase_airtime() {
    global $conn;
    $user_id = $_SESSION['user_id'];

    $phone = $_POST['phone'] ?? '';
    $network = $_POST['network'] ?? '';
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);

    if (empty($phone) || empty($network) || $amount === false || $amount <= 0) {
        api_response(false, 'Missing required fields for purchase.');
    }

    // 1. Get admin settings for this network
    $settings = get_settings();
    $provider_key = 'airtime_provider_' . strtolower($network);
    $discount_key = 'airtime_discount_' . strtolower($network);
    $provider = $settings[$provider_key] ?? null;
    $discount_percent = (float)($settings[$discount_key] ?? 0);

    if (!$provider) {
        api_response(false, "Airtime service for {$network} is not configured by the administrator.");
    }

    // 2. Calculate final price and check user balance
    $user = $GLOBALS['current_user'];
    $final_price = $amount - ($amount * ($discount_percent / 100));

    if ((float)$user['balance'] < $final_price) {
        api_response(false, 'Insufficient wallet balance.');
    }

    // 3. All checks passed, begin transaction
    $conn->begin_transaction();
    try {
        // Debit user
        $stmt_debit = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
        $stmt_debit->bind_param("di", $final_price, $user_id);
        $stmt_debit->execute();
        $stmt_debit->close();

        // Log transaction
        $description = "Airtime Top-up: " . $network . " " . $amount;
        $stmt_log = $conn->prepare("INSERT INTO transactions (user_id, type, vtu_service_type, amount, total_amount, status, gateway, description, vtu_recipient) VALUES (?, 'debit', 'airtime', ?, ?, 'pending', ?, ?, ?)");
        $stmt_log->bind_param("iddsss", $user_id, $amount, $final_price, $provider, $description, $phone);
        $stmt_log->execute();
        $transaction_id = $stmt_log->insert_id;
        $stmt_log->close();

        // 4. Call the selected API provider
        $api_result = null;
        if ($provider === 'VTPass') {
            // TODO: Implement VTPass Airtime API call
            // For now, simulate success
            $api_result = ['success' => true, 'response' => json_encode(['code' => '000', 'response_description' => 'TRANSACTION SUCCESSFUL', 'requestId' => date('YmdHis') . $transaction_id])];
        } elseif ($provider === 'ClubKonnect') {
            // TODO: Implement ClubKonnect Airtime API call
            // For now, simulate success
            $api_result = ['success' => true, 'response' => json_encode(['statuscode' => '100', 'status' => 'ORDER_RECEIVED', 'orderid' => $transaction_id])];
        }

        if (!$api_result || !$api_result['success']) {
            throw new Exception($api_result['message'] ?? 'Airtime API provider failed.');
        }

        // 5. Update transaction with API response
        $final_status = 'pending'; // Default to pending
        $api_response_data = json_decode($api_result['response'], true);

        if ($provider === 'VTPass' && isset($api_response_data['code']) && $api_response_data['code'] == '000') {
             $final_status = 'completed';
        } elseif ($provider === 'ClubKonnect' && isset($api_response_data['statuscode']) && $api_response_data['statuscode'] == '100') {
            // ClubKonnect's initial response is just "received", so it stays pending for requery.
            $final_status = 'pending';
        } else {
            $final_status = 'failed';
        }

        $stmt_update = $conn->prepare("UPDATE transactions SET status = ?, api_response = ? WHERE id = ?");
        $stmt_update->bind_param("ssi", $final_status, $api_result['response'], $transaction_id);
        $stmt_update->execute();
        $stmt_update->close();

        if ($final_status === 'failed') {
            throw new Exception('Transaction failed at API provider.');
        }

        $conn->commit();
        api_response(true, 'Your airtime request has been submitted successfully and is being processed.');

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Airtime Purchase Error: " . $e->getMessage());
        api_response(false, "An error occurred during the transaction. Please check your transaction history or contact support if your wallet was debited.");
    }
}
