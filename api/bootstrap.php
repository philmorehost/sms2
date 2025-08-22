<?php
// api/bootstrap.php

// Main application bootstrap
require_once __DIR__ . '/../app/bootstrap.php';

// Set common headers
header('Content-Type: application/json');

/**
 * Finds a user by their API key and checks if their access is approved.
 * @param mysqli $conn The database connection.
 * @param string $api_key The user's API key.
 * @return array|null The user data or null if not found/approved.
 */
function get_user_by_api_key($conn, $api_key) {
    if (empty($api_key)) {
        return null;
    }
    $stmt = $conn->prepare("SELECT * FROM users WHERE api_key = ? AND api_access_status = 'approved'");
    $stmt->bind_param("s", $api_key);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user;
}

/**
 * Standardized API error response.
 * @param string $message The error message.
 * @param int $http_status_code The HTTP status code to send.
 * @param string $error_code A custom error code for the response body.
 */
function api_error($message, $http_status_code = 401, $error_code = '401') {
    http_response_code($http_status_code);
    echo json_encode(['status' => 'error', 'error_code' => $error_code, 'msg' => $message]);
    exit();
}

/**
 * Authenticates the request and returns the user record.
 * @param mysqli $conn
 * @return array The authenticated user's data.
 */
function api_authenticate($conn) {
    $api_key = $_POST['token'] ?? $_GET['token'] ?? '';
    $user = get_user_by_api_key($conn, $api_key);

    if (!$user) {
        api_error("Authentication failed. Invalid API token or access not approved.");
    }
    return $user;
}
