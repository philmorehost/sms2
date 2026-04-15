<?php
// web/api/info.php
require_once __DIR__ . '/bootstrap.php';

$user = mobile_authenticate($conn);
$action = $_GET['action'] ?? '';

if ($action === 'pricing') {
    $sms_rates = [];
    $res = $conn->query("SELECT country, network, rate FROM sms_rates ORDER BY country ASC");
    while($row = $res->fetch_assoc()) $sms_rates[] = $row;
    mobile_api_success(['sms_rates' => $sms_rates]);
} elseif ($action === 'coverage') {
    $coverage = [];
    $res = $conn->query("SELECT country, code, status FROM global_coverage ORDER BY country ASC");
    while($row = $res->fetch_assoc()) $coverage[] = $row;
    mobile_api_success(['coverage' => $coverage]);
}
?>
