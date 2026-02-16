<?php
require_once '../../app/bootstrap.php';

if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

$search_term = $_POST['search'] ?? '';
$status_filter = $_POST['status'] ?? 'open';
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$sql = "SELECT t.ticket_id, t.subject, t.status, t.updated_at, u.username
        FROM support_tickets t
        JOIN users u ON t.user_id = u.id";
$count_sql = "SELECT COUNT(t.ticket_id) as total FROM support_tickets t JOIN users u ON t.user_id = u.id";

$where_clauses = [];
$params = [];
$types = '';

$allowed_filters = ['open', 'closed', 'all'];
if (!in_array($status_filter, $allowed_filters)) {
    $status_filter = 'open'; // Default to a safe value
}

if ($status_filter == 'open') {
    $where_clauses[] = "t.status IN ('open', 'user_reply')";
} elseif ($status_filter == 'closed') {
    $where_clauses[] = "t.status = 'closed'";
}

if (!empty($search_term)) {
    $where_clauses[] = "(t.ticket_id LIKE ? OR u.username LIKE ? OR t.subject LIKE ?)";
    $search_param = "%{$search_term}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $types .= 'sss';
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
    $count_sql .= " WHERE " . implode(' AND ', $where_clauses);
}

$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);
$count_stmt->close();

$sql .= " ORDER BY t.updated_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$tickets = [];
while ($row = $result->fetch_assoc()) {
    $tickets[] = $row;
}
$stmt->close();

function get_ticket_status_badge_ajax($status) {
    $status = htmlspecialchars($status);
    $status_map = [
        'open' => ['class' => 'bg-primary', 'text' => 'Open'],
        'closed' => ['class' => 'bg-secondary', 'text' => 'Closed'],
        'admin_reply' => ['class' => 'bg-success', 'text' => 'Replied'],
        'user_reply' => ['class' => 'bg-warning text-dark', 'text' => 'User Replied']
    ];
    $s = $status_map[$status] ?? ['class' => 'bg-light text-dark', 'text' => 'Unknown'];
    return "<span class='badge " . $s['class'] . "'>" . $s['text'] . "</span>";
}

$html = '';
if (empty($tickets)) {
    $html = '<tr><td colspan="6" class="text-center">No tickets found.</td></tr>';
} else {
    foreach ($tickets as $ticket) {
        $html .= '<tr>';
        $html .= '<td><strong>' . htmlspecialchars($ticket['ticket_id']) . '</strong></td>';
        $html .= '<td>' . htmlspecialchars($ticket['username']) . '</td>';
        $html .= '<td>' . htmlspecialchars($ticket['subject']) . '</td>';
        $html .= '<td>' . get_ticket_status_badge_ajax($ticket['status']) . '</td>';
        $html .= '<td>' . date('Y-m-d H:i', strtotime($ticket['updated_at'])) . '</td>';
        $html .= '<td><a href="view-ticket.php?id=' . htmlspecialchars($ticket['ticket_id']) . '" class="btn btn-sm btn-info">View Ticket</a></td>';
        $html .= '</tr>';
    }
}

$pagination_html = '';
if ($total_pages > 1) {
    $pagination_html .= '<nav aria-label="Page navigation"><ul class="pagination justify-content-center mt-3">';
    if ($page > 1) {
        $pagination_html .= '<li class="page-item"><a class="page-link" href="#" data-page="' . ($page - 1) . '">Previous</a></li>';
    }
    for ($i = 1; $i <= $total_pages; $i++) {
        $active_class = $i == $page ? 'active' : '';
        $pagination_html .= '<li class="page-item ' . $active_class . '"><a class="page-link" href="#" data-page="' . $i . '">' . $i . '</a></li>';
    }
    if ($page < $total_pages) {
        $pagination_html .= '<li class="page-item"><a class="page-link" href="#" data-page="' . ($page + 1) . '">Next</a></li>';
    }
    $pagination_html .= '</ul></nav>';
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'html' => $html, 'pagination' => $pagination_html]);
