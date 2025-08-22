<?php
require_once __DIR__ . '/../app/bootstrap.php';

// Ensure only admins can access this script
if (!is_admin()) {
    die("Access denied.");
}

$format = $_GET['format'] ?? 'full';
$filename = "users_export_" . date('Y-m-d') . ".csv";

// Set headers to force download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Fetch users from the database
$users_result = $conn->query("SELECT * FROM users ORDER BY id ASC");

switch ($format) {
    case 'emails':
        fputcsv($output, ['Email']);
        while ($user = $users_result->fetch_assoc()) {
            fputcsv($output, [$user['email']]);
        }
        break;

    case 'phones':
        fputcsv($output, ['PhoneNumber']);
        while ($user = $users_result->fetch_assoc()) {
            fputcsv($output, [$user['phone_number']]);
        }
        break;

    case 'full':
    default:
        // Output headers
        fputcsv($output, [
            'ID', 'Username', 'Email', 'PhoneNumber', 'Balance',
            'ReferralCode', 'ReferredBy', 'ReferralBalance',
            'IsAdmin', 'IsEmailVerified', 'ApiAccessStatus', 'CreatedAt'
        ]);
        // Output data rows
        while ($user = $users_result->fetch_assoc()) {
            fputcsv($output, [
                $user['id'],
                $user['username'],
                $user['email'],
                $user['phone_number'],
                $user['balance'],
                $user['referral_code'],
                $user['referred_by'],
                $user['referral_balance'],
                $user['is_admin'] ? 'Yes' : 'No',
                $user['is_email_verified'] ? 'Yes' : 'No',
                $user['api_access_status'],
                $user['created_at']
            ]);
        }
        break;
}

fclose($output);
exit();
?>
