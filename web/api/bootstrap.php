<?php
// web/api/bootstrap.php
require_once __DIR__ . '/../../app/bootstrap.php';

header('Content-Type: application/json');

function mobile_api_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'status' => 'error',
        'message' => $message
    ]);
    exit;
}

function mobile_api_success($data = [], $message = 'Success') {
    http_response_code(200);
    $response = [
        'status' => 'success',
        'message' => $message,
        'data' => $data
    ];
    // Merge $data into top level as well to support legacy fields like 'token', 'stats' etc.
    echo json_encode(array_merge($response, $data));
    exit;
}

function mobile_authenticate($conn) {
    $token = $_SERVER['HTTP_AUTHORIZATION'] ?? $_POST['token'] ?? $_GET['token'] ?? '';
    if (strpos($token, 'Bearer ') === 0) {
        $token = substr($token, 7);
    }

    if (empty($token)) {
        mobile_api_error('Authentication token required', 401);
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE api_key = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        mobile_api_error('Invalid authentication token', 401);
    }

    return $user;
}
?>
