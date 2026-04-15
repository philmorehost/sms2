<?php
// web/api/tools.php
require_once __DIR__ . '/bootstrap.php';

$user = mobile_authenticate($conn);
$action = $_GET['action'] ?? '';

if ($action === 'extract') {
    $text = $_POST['text'] ?? '';
    if (empty($text)) mobile_api_error('Text is required');

    // Use the existing regex logic from the platform
    $regex = '/[+\d\s()\-]{10,20}/';
    preg_match_all($regex, $text, $matches);

    $numbers = [];
    if (!empty($matches[0])) {
        foreach ($matches[0] as $num) {
            $clean = preg_replace('/(?<!^)\+|[^\d+]/', '', trim($num));
            $digit_only = preg_replace('/\D/', '', $clean);
            if (strlen($digit_only) >= 10 && strlen($digit_only) <= 15) {
                $numbers[] = $clean;
            }
        }
    }
    $numbers = array_values(array_unique($numbers));
    mobile_api_success(['numbers' => $numbers]);
} elseif ($action === 'filter') {
    $numbers_str = $_POST['numbers'] ?? '';
    if (empty($numbers_str)) mobile_api_error('Numbers are required');

    $valid_numbers = filter_phone_numbers($numbers_str);
    mobile_api_success(['numbers' => array_values($valid_numbers)]);
}
?>
