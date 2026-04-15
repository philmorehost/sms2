<?php
require_once '../../app/bootstrap.php';

// Authorize administrator
if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$user_id = (int)$_POST['user_id'];
$wallet_type = $_POST['wallet_type']; // 'local' or 'global'
$action_type = $_POST['action_type']; // 'credit' or 'debit'
if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

$amount = (float)$_POST['amount'];
$description = trim($_POST['description']);

if ($user_id <= 0 || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user or amount.']);
    exit;
}

$conn->begin_transaction();
try {
    if ($wallet_type === 'local') {
        $operator = ($action_type === 'credit') ? '+' : '-';
        $stmt = $conn->prepare("UPDATE users SET balance = balance $operator ? WHERE id = ?");
        $stmt->bind_param("di", $amount, $user_id);
        $stmt->execute();
        $stmt->close();
    } else {
        // Global Wallet
        $operator = ($action_type === 'credit') ? '+' : '-';

        // Ensure global wallet record exists
        $stmt_check = $conn->prepare("SELECT id FROM global_wallets WHERE user_id = ?");
        $stmt_check->bind_param("i", $user_id);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows === 0) {
            $stmt_init = $conn->prepare("INSERT INTO global_wallets (user_id, balance) VALUES (?, 0)");
            $stmt_init->bind_param("i", $user_id);
            $stmt_init->execute();
            $stmt_init->close();
        }
        $stmt_check->close();

        $stmt = $conn->prepare("UPDATE global_wallets SET balance = balance $operator ? WHERE user_id = ?");
        $stmt->bind_param("di", $amount, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    // Log the transaction
    $type = ($action_type === 'credit') ? 'admin_credit' : 'admin_debit';
    $gateway = 'admin';
    $reference = 'ADM' . time() . rand(10, 99);
    $final_desc = ($wallet_type === 'global' ? '[GLOBAL] ' : '') . $description;

    $stmt_log = $conn->prepare("INSERT INTO transactions (user_id, type, amount, total_amount, status, gateway, reference, description) VALUES (?, ?, ?, ?, 'completed', ?, ?, ?)");
    $stmt_log->bind_param("isddsss", $user_id, $type, $amount, $amount, $gateway, $reference, $final_desc);
    $stmt_log->execute();
    $stmt_log->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Wallet updated successfully.']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
