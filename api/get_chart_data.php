<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../app/bootstrap.php';

// --- Response Helper ---
function api_chart_response($success, $message, $data = []) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'chartData' => $data
    ]);
    exit();
}

// --- Authenticate Request ---
if (!isset($_SESSION['user_id'])) {
    api_chart_response(false, 'Authentication required.');
}
$user_id = $_SESSION['user_id'];

// --- Query Database for last 7 days activity ---
$sql = "
    SELECT
        DATE(created_at) as date,
        COUNT(id) as message_count
    FROM messages
    WHERE
        user_id = ?
        AND created_at >= CURDATE() - INTERVAL 7 DAY
    GROUP BY DATE(created_at)
    ORDER BY date ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$db_data = [];
while ($row = $result->fetch_assoc()) {
    $db_data[$row['date']] = $row['message_count'];
}
$stmt->close();

// --- Format Data for Chart.js ---
$labels = [];
$data_points = [];
// Create a full 7-day date range to ensure all days are present, even with 0 messages
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('M d', strtotime($date)); // Format for display (e.g., Aug 18)
    $data_points[] = $db_data[$date] ?? 0; // Use the count from DB or 0 if no messages on that day
}

$chart_data = [
    'labels' => $labels,
    'datasets' => [
        [
            'label' => 'Messages Sent',
            'data' => $data_points,
            'borderColor' => 'rgba(13, 110, 253, 1)', // primary-color
            'backgroundColor' => 'rgba(13, 110, 253, 0.1)',
            'fill' => true,
            'tension' => 0.4 // Makes the line curved
        ]
    ]
];

api_chart_response(true, 'Data fetched successfully', $chart_data);
?>
