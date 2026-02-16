<?php
require_once '../../app/bootstrap.php';

if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

$search_term = $_POST['search'] ?? '';
$status_filter = $_POST['status'] ?? '';
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$sql = "SELECT s.id, s.sender_id, s.sample_message, s.status, s.created_at, u.username
        FROM sender_ids s
        JOIN users u ON s.user_id = u.id";
$count_sql = "SELECT COUNT(s.id) as total FROM sender_ids s JOIN users u ON s.user_id = u.id";

$where_clauses = [];
$params = [];
$types = '';

if (!empty($search_term)) {
    $where_clauses[] = "(u.username LIKE ? OR s.sender_id LIKE ?)";
    $search_param = "%{$search_term}%";
    $params = array_merge($params, [$search_param, $search_param]);
    $types .= 'ss';
}

if (!empty($status_filter)) {
    $where_clauses[] = "s.status = ?";
    $params[] = $status_filter;
    $types .= 's';
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

$sql .= " ORDER BY s.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$submissions = [];
while ($row = $result->fetch_assoc()) {
    $submissions[] = $row;
}
$stmt->close();

$html = '';
if (empty($submissions)) {
    $html = '<tr><td colspan="6" class="text-center">No Sender ID submissions found.</td></tr>';
} else {
    foreach ($submissions as $sub) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($sub['username']) . '</td>';
        $html .= '<td>' . htmlspecialchars($sub['sender_id']) . '</td>';
        $html .= '<td>' . htmlspecialchars($sub['sample_message']) . '</td>';
        $html .= '<td>' . date('Y-m-d H:i', strtotime($sub['created_at'])) . '</td>';

        $status = htmlspecialchars($sub['status']);
        $badge_class = 'badge-secondary';
        if ($status == 'approved') $badge_class = 'badge-success';
        if ($status == 'rejected') $badge_class = 'badge-danger';
        if ($status == 'pending') $badge_class = 'badge-warning';
        $html .= '<td><span class="badge ' . $badge_class . '">' . ucfirst($status) . '</span></td>';

        $html .= '<td>';
        if ($sub['status'] == 'pending') {
            $html .= '<form action="sender-ids.php" method="POST" class="d-inline">';
            $html .= '<input type="hidden" name="id" value="' . $sub['id'] . '">';
            $html .= '<input type="hidden" name="status" value="approved">';
            $html .= '<button type="submit" name="update_status" class="btn btn-success btn-sm">Approve</button>';
            $html .= '</form>';
            $html .= '<form action="sender-ids.php" method="POST" class="d-inline">';
            $html .= '<input type="hidden" name="id" value="' . $sub['id'] . '">';
            $html .= '<input type="hidden" name="status" value="rejected">';
            $html .= '<button type="submit" name="update_status" class="btn btn-danger btn-sm">Reject</button>';
            $html .= '</form>';
        } else {
            $html .= '<span>No actions</span>';
        }
        $html .= '</td>';
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
