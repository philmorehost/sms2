<?php
require_once __DIR__ . '/../app/bootstrap.php';

header('Content-Type: application/json');

// Check if user is an admin. is_admin() is defined in helpers.php and is the central authority.
if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['user_id']) || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

$user_id_to_manage = (int)$_POST['user_id'];
$action = $_POST['action'];
$new_status = '';
$new_api_key = null;

switch ($action) {
    case 'approve':
        $new_status = 'approved';
        $new_api_key = bin2hex(random_bytes(32));
        $stmt = $conn->prepare("UPDATE users SET api_access_status = ?, api_key = ? WHERE id = ?");
        $stmt->bind_param("ssi", $new_status, $new_api_key, $user_id_to_manage);
        break;

    case 'deny':
        $new_status = 'denied';
        $stmt = $conn->prepare("UPDATE users SET api_access_status = ?, api_key = NULL WHERE id = ?");
        $stmt->bind_param("si", $new_status, $user_id_to_manage);
        break;

    case 'revoke':
        $new_status = 'none'; // Revoking access sets them back to the start
        $stmt = $conn->prepare("UPDATE users SET api_access_status = ?, api_key = NULL WHERE id = ?");
        $stmt->bind_param("si", $new_status, $user_id_to_manage);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        exit();
}

if ($stmt->execute()) {
    // Fetch user's email to send notification
    $user_stmt = $conn->prepare("SELECT email, username FROM users WHERE id = ?");
    $user_stmt->bind_param("i", $user_id_to_manage);
    $user_stmt->execute();
    $user_info = $user_stmt->get_result()->fetch_assoc();
    $user_stmt->close();

    if ($user_info) {
        $user_email = $user_info['email'];
        $user_username = $user_info['username'];
        $subject = "Update on your API Access Request";
        $message = "";

        if ($action === 'approve') {
            $message = "<p>Hello " . htmlspecialchars($user_username) . ",</p><p>Good news! Your request for API access has been approved. You can now find your API key in the Developer API section of your dashboard.</p>";
        } elseif ($action === 'deny') {
            $message = "<p>Hello " . htmlspecialchars($user_username) . ",</p><p>We have reviewed your request for API access, and unfortunately, it has been denied at this time.</p>";
        }

        if (!empty($message)) {
            send_email($user_email, $subject, $message);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'User API status updated successfully.',
        'new_status' => $new_status,
        'user_id' => $user_id_to_manage
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update user status.']);
}

$stmt->close();
?>
