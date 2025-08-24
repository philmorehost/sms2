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

    case 'purchase_exam_pin':
        purchase_exam_pin();
        break;

    case 'purchase_recharge_card':
        purchase_recharge_card();
        break;

    case 'purchase_data_card':
        purchase_data_card();
        break;
        purchase_exam_pin();
        break;

    case 'verify_betting_customer':
        verify_betting_customer();
        break;

    case 'purchase_betting_funding':
        purchase_betting_funding();
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

    if ($GLOBALS['current_user']['status'] === 'suspended') {
        api_response(false, 'Your account is currently suspended. Please contact support.');
    }
    if (check_transaction_limit($user_id, 'cable_tv')) {
        handle_limit_exceeded($user_id);
        api_response(false, 'You have exceeded the transaction limit for this service. Please try again later.');
    }
    $_SESSION['limit_exceeded_attempts'] = 0;

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
    $plan_type = $_POST['plan_type'] ?? '';

    if (empty($network)) {
        api_response(false, 'Network not specified.');
    }

    if (empty($plan_type)) {
        // Scenario 1: Fetch unique plan types for the network
        $stmt = $conn->prepare("SELECT DISTINCT plan_type FROM vtu_products WHERE service_type = 'data' AND network = ? AND is_active = 1 AND plan_type IS NOT NULL ORDER BY plan_type ASC");
        $stmt->bind_param("s", $network);
        $stmt->execute();
        $result = $stmt->get_result();
        $types = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        // Extract just the values
        $type_values = array_column($types, 'plan_type');
        api_response(true, 'Types fetched successfully', $type_values);
    } else {
        // Scenario 2: Fetch specific plans for the network and type
        $stmt = $conn->prepare("SELECT id, name, amount, user_discount_percentage, api_product_id FROM vtu_products WHERE service_type = 'data' AND network = ? AND plan_type = ? AND is_active = 1 ORDER BY amount ASC");
        $stmt->bind_param("ss", $network, $plan_type);
        $stmt->execute();
        $result = $stmt->get_result();
        $plans = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        api_response(true, 'Plans fetched successfully', $plans);
    }
}

function purchase_recharge_card() {
    global $conn;
    $user_id = $_SESSION['user_id'];

    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $business_name = trim($_POST['business_name']);

    if (empty($product_id) || empty($quantity) || $quantity <= 0) {
        api_response(false, 'Missing required fields for purchase.');
    }

    // 1. Get product details and user balance
    $stmt = $conn->prepare("SELECT p.*, u.balance FROM vtu_products p, users u WHERE p.id = ? AND p.service_type = 'recharge_card' AND u.id = ?");
    $stmt->bind_param("ii", $product_id, $user_id);
    $stmt->execute();
    $details = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$details) {
        api_response(false, 'Invalid recharge card product selected.');
    }

    // 2. Calculate final price and check balance
    $base_price = (float)$details['amount'];
    $discount = $base_price * ((float)$details['user_discount_percentage'] / 100);
    $final_price = ($base_price - $discount) * $quantity;

    if ((float)$details['balance'] < $final_price) {
        api_response(false, 'Insufficient wallet balance.');
    }

    // 3. Fetch ClubKonnect API credentials
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
        $stmt_debit = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
        $stmt_debit->bind_param("di", $final_price, $user_id);
        $stmt_debit->execute();
        $stmt_debit->close();

        $description = "Recharge Card PINs: " . $details['name'] . " (Qty: " . $quantity . ")";
        $stmt_log = $conn->prepare("INSERT INTO transactions (user_id, type, vtu_service_type, amount, total_amount, status, gateway, description) VALUES (?, 'debit', 'recharge_card', ?, ?, 'pending', ?, ?)");
        $stmt_log->bind_param("iddss", $user_id, $base_price, $final_price, $provider, $description);
        $stmt_log->execute();
        $transaction_id = $stmt_log->insert_id;
        $stmt_log->close();

        // 5. Call ClubKonnect API
        // TODO: Implement actual ClubKonnect E-PIN API call
        $api_result = ['success' => true, 'response' => json_encode(['TXN_EPIN' => [['pin' => 'SIMULATED_PIN_1', 'sno' => 'SIM_SERIAL_1'], ['pin' => 'SIMULATED_PIN_2', 'sno' => 'SIM_SERIAL_2']]])];

        if (!$api_result || !$api_result['success']) {
            throw new Exception($api_result['message'] ?? 'E-PIN API provider failed.');
        }

        // 6. Update transaction with API response
        $api_response_data = json_decode($api_result['response'], true);
        $final_status = (isset($api_response_data['TXN_EPIN'])) ? 'completed' : 'failed';

        $stmt_update = $conn->prepare("UPDATE transactions SET status = ?, api_response = ? WHERE id = ?");
        $stmt_update->bind_param("ssi", $final_status, $api_result['response'], $transaction_id);
        $stmt_update->execute();
        $stmt_update->close();

        if ($final_status === 'failed') {
            throw new Exception('Transaction failed at API provider.');
        }

        $conn->commit();
        $print_details = ['business_name' => $business_name, 'network' => $details['network'], 'value' => (int)$details['amount']];
        api_response(true, 'Your Recharge Card PINs have been generated successfully.', ['cards' => $api_response_data['TXN_EPIN'], 'print_details' => $print_details]);

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Recharge Card Purchase Error: " . $e->getMessage());
        api_response(false, "An error occurred during the transaction. Please check your transaction history or contact support if your wallet was debited.");
    }
}

function purchase_data_card() {
    global $conn;
    $user_id = $_SESSION['user_id'];

    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $business_name = trim($_POST['business_name']);

    if (empty($product_id) || empty($quantity) || $quantity <= 0) {
        api_response(false, 'Missing required fields for purchase.');
    }

    // 1. Get product details and user balance
    $stmt = $conn->prepare("SELECT p.*, u.balance FROM vtu_products p, users u WHERE p.id = ? AND p.service_type = 'data_card' AND u.id = ?");
    $stmt->bind_param("ii", $product_id, $user_id);
    $stmt->execute();
    $details = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$details) {
        api_response(false, 'Invalid data card product selected.');
    }

    // 2. Calculate final price and check balance
    $base_price = (float)$details['amount'];
    $discount = $base_price * ((float)$details['user_discount_percentage'] / 100);
    $final_price = ($base_price - $discount) * $quantity;

    if ((float)$details['balance'] < $final_price) {
        api_response(false, 'Insufficient wallet balance.');
    }

    // 3. Fetch ClubKonnect API credentials
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
        $stmt_debit = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
        $stmt_debit->bind_param("di", $final_price, $user_id);
        $stmt_debit->execute();
        $stmt_debit->close();

        $description = "Data Card PINs: " . $details['name'] . " (Qty: " . $quantity . ")";
        $stmt_log = $conn->prepare("INSERT INTO transactions (user_id, type, vtu_service_type, amount, total_amount, status, gateway, description) VALUES (?, 'debit', 'data_card', ?, ?, 'pending', ?, ?)");
        $stmt_log->bind_param("iddss", $user_id, $base_price, $final_price, $provider, $description);
        $stmt_log->execute();
        $transaction_id = $stmt_log->insert_id;
        $stmt_log->close();

        // 5. Call ClubKonnect API
        // TODO: Implement actual ClubKonnect Data E-PIN API call
        $api_result = ['success' => true, 'response' => json_encode(['TXN_EPIN_DATABUNDLE' => [['pin' => 'SIMULATED_DATA_PIN_1', 'sno' => 'SIM_DATA_SERIAL_1'], ['pin' => 'SIMULATED_DATA_PIN_2', 'sno' => 'SIM_DATA_SERIAL_2']]])];

        if (!$api_result || !$api_result['success']) {
            throw new Exception($api_result['message'] ?? 'Data E-PIN API provider failed.');
        }

        // 6. Update transaction with API response
        $api_response_data = json_decode($api_result['response'], true);
        $final_status = (isset($api_response_data['TXN_EPIN_DATABUNDLE'])) ? 'completed' : 'failed';

        $stmt_update = $conn->prepare("UPDATE transactions SET status = ?, api_response = ? WHERE id = ?");
        $stmt_update->bind_param("ssi", $final_status, $api_result['response'], $transaction_id);
        $stmt_update->execute();
        $stmt_update->close();

        if ($final_status === 'failed') {
            throw new Exception('Transaction failed at API provider.');
        }

        $conn->commit();
        $print_details = ['business_name' => $business_name, 'network' => $details['network'], 'plan_name' => $details['name']];
        api_response(true, 'Your Data Card PINs have been generated successfully.', ['cards' => $api_response_data['TXN_EPIN_DATABUNDLE'], 'print_details' => $print_details]);

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Data Card Purchase Error: " . $e->getMessage());
        api_response(false, "An error occurred during the transaction. Please check your transaction history or contact support if your wallet was debited.");
    }
}

function purchase_exam_pin() {
    global $conn;
    $user_id = $_SESSION['user_id'];

    if ($GLOBALS['current_user']['status'] === 'suspended') {
        api_response(false, 'Your account is currently suspended. Please contact support.');
    }
    if (check_transaction_limit($user_id, 'exam_pin')) {
        handle_limit_exceeded($user_id);
        api_response(false, 'You have exceeded the transaction limit for this service. Please try again later.');
    }
    $_SESSION['limit_exceeded_attempts'] = 0;

    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

    if (empty($product_id) || empty($quantity) || $quantity <= 0) {
        api_response(false, 'Missing required fields for purchase.');
    }

    // 1. Get product details and user balance
    $stmt = $conn->prepare("SELECT p.amount, p.user_discount_percentage, p.api_provider, p.api_product_id, p.name, u.balance FROM vtu_products p, users u WHERE p.id = ? AND p.service_type = 'exam_pin' AND u.id = ?");
    $stmt->bind_param("ii", $product_id, $user_id);
    $stmt->execute();
    $details = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$details) {
        api_response(false, 'Invalid exam PIN product selected.');
    }

    // 2. Calculate final price and check balance
    $base_price = (float)$details['amount'];
    $discount = $base_price * ((float)$details['user_discount_percentage'] / 100);
    $final_price = ($base_price - $discount) * $quantity;

    if ((float)$details['balance'] < $final_price) {
        api_response(false, 'Insufficient wallet balance.');
    }

    // 3. Fetch API credentials for the provider
    $provider = $details['api_provider'];
    $stmt_api = $conn->prepare("SELECT * FROM vtu_apis WHERE provider_name = ?");
    $stmt_api->bind_param("s", $provider);
    $stmt_api->execute();
    $api_details = $stmt_api->get_result()->fetch_assoc();
    $stmt_api->close();

    if (empty($api_details) || empty($api_details['api_key'])) {
        api_response(false, "API for {$provider} is not configured by the administrator.");
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
        $description = "Exam PIN Purchase: " . $details['name'] . " (Qty: " . $quantity . ")";
        $stmt_log = $conn->prepare("INSERT INTO transactions (user_id, type, vtu_service_type, amount, total_amount, status, gateway, description) VALUES (?, 'debit', 'exam_pin', ?, ?, 'pending', ?, ?)");
        $stmt_log->bind_param("iddss", $user_id, $base_price, $final_price, $provider, $description);
        $stmt_log->execute();
        $transaction_id = $stmt_log->insert_id;
        $stmt_log->close();

        // 5. Call the selected API provider
        $api_result = null;
        if ($provider === 'NaijaResultPins') {
            // TODO: Implement NaijaResultPins API call
            $api_result = ['success' => true, 'response' => json_encode(['status' => true, 'code' => '000', 'cards' => [['pin' => 'PIN1234567890', 'serial_no' => 'SERIAL123']]])];
        } elseif ($provider === 'VTPass') {
            // TODO: Implement VTPass Exam PIN API call
            api_response(false, "VTPass for Exam PINs is not yet implemented.");
        }

        if (!$api_result || !$api_result['success']) {
            throw new Exception($api_result['message'] ?? 'Exam PIN API provider failed.');
        }

        // 6. Update transaction with API response
        $api_response_data = json_decode($api_result['response'], true);
        $final_status = (isset($api_response_data['status']) && $api_response_data['status'] === true) ? 'completed' : 'failed';

        $stmt_update = $conn->prepare("UPDATE transactions SET status = ?, api_response = ? WHERE id = ?");
        $stmt_update->bind_param("ssi", $final_status, $api_result['response'], $transaction_id);
        $stmt_update->execute();
        $stmt_update->close();

        if ($final_status === 'failed') {
            throw new Exception('Transaction failed at API provider.');
        }

        $conn->commit();
        api_response(true, 'Your Exam PINs have been generated successfully.', ['cards' => $api_response_data['cards']]);

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Exam PIN Purchase Error: " . $e->getMessage());
        api_response(false, "An error occurred during the transaction. Please check your transaction history or contact support if your wallet was debited.");
    }
}

function purchase_betting_funding() {
    global $conn;
    $user_id = $_SESSION['user_id'];

    if ($GLOBALS['current_user']['status'] === 'suspended') {
        api_response(false, 'Your account is currently suspended. Please contact support.');
    }
    if (check_transaction_limit($user_id, 'betting')) {
        handle_limit_exceeded($user_id);
        api_response(false, 'You have exceeded the transaction limit for this service. Please try again later.');
    }
    $_SESSION['limit_exceeded_attempts'] = 0;

    $betting_company = $_POST['betting_company'] ?? '';
    $customer_id = $_POST['customer_id'] ?? '';
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);

    if (empty($betting_company) || empty($customer_id) || $amount === false || $amount <= 0) {
        api_response(false, 'Missing required fields for purchase.');
    }

    // 1. Get product details and user balance
    $stmt = $conn->prepare("SELECT p.user_discount_percentage, u.balance FROM vtu_products p, users u WHERE p.api_product_id = ? AND p.service_type = 'betting' AND u.id = ?");
    $stmt->bind_param("si", $betting_company, $user_id);
    $stmt->execute();
    $details = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$details) {
        api_response(false, 'Invalid betting company selected.');
    }

    // 2. Calculate final price and check balance
    $discount_percent = (float)($details['user_discount_percentage'] ?? 0);
    $final_price = $amount - ($amount * ($discount_percent / 100));

    if ((float)$details['balance'] < $final_price) {
        api_response(false, 'Insufficient wallet balance.');
    }

    // 3. Fetch ClubKonnect API credentials
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
        $description = "Betting Wallet Funding: " . ucfirst($betting_company);
        $stmt_log = $conn->prepare("INSERT INTO transactions (user_id, type, vtu_service_type, amount, total_amount, status, gateway, description, vtu_recipient) VALUES (?, 'debit', 'betting', ?, ?, 'pending', ?, ?, ?)");
        $stmt_log->bind_param("iddsss", $user_id, $amount, $final_price, $provider, $description, $customer_id);
        $stmt_log->execute();
        $transaction_id = $stmt_log->insert_id;
        $stmt_log->close();

        // 5. Call ClubKonnect API
        // TODO: Implement actual ClubKonnect Betting API call
        $api_result = ['success' => true, 'response' => json_encode(['statuscode' => '100', 'status' => 'ORDER_RECEIVED', 'orderid' => $transaction_id])];

        if (!$api_result || !$api_result['success']) {
            throw new Exception($api_result['message'] ?? 'Betting API provider failed.');
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
        api_response(true, 'Your betting wallet funding request has been submitted successfully.');

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Betting Funding Error: " . $e->getMessage());
        api_response(false, "An error occurred during the transaction. Please check your transaction history or contact support if your wallet was debited.");
    }
}

function verify_betting_customer() {
    global $conn;
    $betting_company = $_POST['betting_company'] ?? '';
    $customer_id = $_POST['customer_id'] ?? '';

    if (empty($betting_company) || empty($customer_id)) {
        api_response(false, 'Betting company and customer ID are required.');
    }

    // Fetch ClubKonnect API credentials
    $provider = 'ClubKonnect';
    $stmt_api = $conn->prepare("SELECT * FROM vtu_apis WHERE provider_name = ?");
    $stmt_api->bind_param("s", $provider);
    $stmt_api->execute();
    $api_details = $stmt_api->get_result()->fetch_assoc();
    $stmt_api->close();

    if (empty($api_details) || empty($api_details['username']) || empty($api_details['api_key'])) {
        api_response(false, 'ClubKonnect API is not configured by the administrator.');
    }

    // Call ClubKonnect API
    $api_url = "https://www.nellobytesystems.com/APIVerifyBettingV1.asp";
    $params = [
        'UserID' => $api_details['username'],
        'APIKey' => $api_details['api_key'],
        'BettingCompany' => $betting_company,
        'CustomerID' => $customer_id
    ];
    $url_with_params = $api_url . '?' . http_build_query($params);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url_with_params);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) {
        api_response(false, 'API call failed.');
    }

    $api_result = json_decode($response, true);

    if (isset($api_result['customer_name']) && !str_contains(strtolower($api_result['customer_name']), 'error')) {
        api_response(true, 'Verification successful', $api_result);
    } else {
        api_response(false, $api_result['customer_name'] ?? 'Invalid Customer ID.');
    }
}

function purchase_electricity() {
    global $conn;
    $user_id = $_SESSION['user_id'];

    if ($GLOBALS['current_user']['status'] === 'suspended') {
        api_response(false, 'Your account is currently suspended. Please contact support.');
    }
    if (check_transaction_limit($user_id, 'electricity')) {
        handle_limit_exceeded($user_id);
        api_response(false, 'You have exceeded the transaction limit for this service. Please try again later.');
    }
    $_SESSION['limit_exceeded_attempts'] = 0;

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

    if ($GLOBALS['current_user']['status'] === 'suspended') {
        api_response(false, 'Your account is currently suspended. Please contact support.');
    }
    if (check_transaction_limit($user_id, 'data')) {
        handle_limit_exceeded($user_id);
        api_response(false, 'You have exceeded the transaction limit for this service. Please try again later.');
    }
    $_SESSION['limit_exceeded_attempts'] = 0;

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

    // Check if user is suspended
    if ($GLOBALS['current_user']['status'] === 'suspended') {
        api_response(false, 'Your account is currently suspended. Please contact support.');
    }

    // Check transaction limit
    if (check_transaction_limit($user_id, 'airtime')) {
        handle_limit_exceeded($user_id);
        api_response(false, 'You have exceeded the transaction limit for this service. Please try again later.');
    }
    $_SESSION['limit_exceeded_attempts'] = 0; // Reset on successful attempt

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
