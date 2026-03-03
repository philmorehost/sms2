<?php
require_once '../../app/bootstrap.php';

// Authorize administrator
if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

// --- Search and Filter Logic ---
$search_term = $_POST['search'] ?? '';
$status_filter = $_POST['status'] ?? '';
$admin_filter = $_POST['admin'] ?? '';
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$sql = "SELECT u.id, u.username, u.email, u.phone_number, u.balance, u.created_at, u.is_admin, u.is_email_verified, u.api_access_status, r.username as referrer_username, gw.balance as global_balance
        FROM users u
        LEFT JOIN users r ON u.referred_by = r.id
        LEFT JOIN global_wallets gw ON u.id = gw.user_id";
$count_sql = "SELECT COUNT(u.id) as total FROM users u";

$where_clauses = [];
$params = [];
$types = '';

if (!empty($search_term)) {
    $where_clauses[] = "(u.username LIKE ? OR u.email LIKE ? OR u.phone_number LIKE ?)";
    $search_param = "%{$search_term}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $types .= 'sss';
}

if (!empty($status_filter)) {
    $where_clauses[] = "u.api_access_status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($admin_filter !== '') {
    $where_clauses[] = "u.is_admin = ?";
    $params[] = (int)$admin_filter;
    $types .= 'i';
}


if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
    $count_sql .= " WHERE " . implode(' AND ', $where_clauses);
}

// --- Fetch Total Count ---
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);
$count_stmt->close();


$sql .= " ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
$stmt->close();

// --- Generate HTML for the table body and modals ---
$html = '';
$modals_html = '';
if (empty($users)) {
    $html = '<tr><td colspan="10" class="text-center">No users found.</td></tr>';
} else {
    foreach ($users as $user) {
        $html .= '<tr id="user-row-' . $user['id'] . '">';
        $html .= '<td>' . $user['id'] . '</td>';
        $html .= '<td>' . htmlspecialchars($user['username']) . '</td>';
        $html .= '<td>' . htmlspecialchars($user['email']) . '</td>';
        $global_bal = isset($user['global_balance']) ? ' / <span class="text-info" title="Global Wallet Balance">' . number_format($user['global_balance'], 2) . '</span>' : '';
        $html .= '<td>' . get_currency_symbol() . number_format($user['balance'], 2) . $global_bal . '</td>';
        $html .= '<td>' . ($user['is_admin'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>') . '</td>';
        $html .= '<td class="verification-status-cell">' . ($user['is_email_verified'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>') . '</td>';

        $status = $user['api_access_status'];
        $badge_class = 'bg-secondary';
        if ($status == 'approved') $badge_class = 'bg-success';
        if ($status == 'requested') $badge_class = 'bg-warning text-dark';
        if ($status == 'denied') $badge_class = 'bg-danger';
        $html .= '<td class="api-status-cell"><span class="badge ' . $badge_class . '">' . ucfirst($status) . '</span></td>';

        $html .= '<td>' . htmlspecialchars($user['referrer_username'] ?? 'N/A') . '</td>';
        $html .= '<td>' . date('Y-m-d', strtotime($user['created_at'])) . '</td>';

        $html .= '<td class="api-action-cell">';
        $html .= '<div class="btn-group">';
        $html .= '<a href="switch_user.php?id=' . $user['id'] . '" class="btn btn-secondary btn-sm" title="Login as this user"><i class="fas fa-sign-in-alt"></i></a>';
        $html .= '<button class="btn btn-info btn-sm edit-user-btn" data-user-id="' . $user['id'] . '" title="Edit user"><i class="fas fa-edit"></i></button>';
        if (!$user['is_email_verified']) {
             $html .= '<button class="btn btn-warning btn-sm verify-btn" data-user-id="' . $user['id'] . '" title="Manually verify user"><i class="fas fa-check-circle"></i></button>';
        }
        $html .= '<form action="users.php" method="POST" class="d-inline" onsubmit="return confirm(\'Are you sure you want to delete this user?\');">';
        $html .= '<input type="hidden" name="csrf_token" value="">'; // Needs CSRF token
        $html .= '<input type="hidden" name="user_id" value="' . $user['id'] . '">';
        $html .= '<button type="submit" name="delete_user" class="btn btn-danger btn-sm" title="Delete user"><i class="fas fa-trash"></i></button>';
        $html .= '</form>';
        $html .= '</div>';
        $html .= '<div class="api-actions mt-1">';
        if ($user['api_access_status'] == 'requested') {
            $html .= '<button class="btn btn-success btn-sm api-action-btn" data-action="approve" data-user-id="' . $user['id'] . '">Approve</button>';
            $html .= ' <button class="btn btn-danger btn-sm api-action-btn" data-action="deny" data-user-id="' . $user['id'] . '">Deny</button>';
        } elseif ($user['api_access_status'] == 'approved') {
            $html .= '<button class="btn btn-warning btn-sm api-action-btn" data-action="revoke" data-user-id="' . $user['id'] . '">Revoke</button>';
        } elseif ($user['api_access_status'] == 'denied') {
            $html .= '<button class="btn btn-success btn-sm api-action-btn" data-action="approve" data-user-id="' . $user['id'] . '">Approve</button>';
        }
        $html .= '</div>';
        $html .= '</td>';
        $html .= '</tr>';

        // Generate modal HTML for each user
        ob_start();
        ?>
        <div class="modal fade" id="editUserModal<?php echo $user['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="users.php" method="POST">
                        <input type="hidden" name="csrf_token" value="">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit User: <?php echo htmlspecialchars($user['username']); ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="text" name="phone_number" class="form-control" value="<?php echo htmlspecialchars($user['phone_number']); ?>" required>
                            </div>
                             <div class="form-group">
                                <label>Balance</label>
                                <input type="number" step="0.01" name="balance" class="form-control" value="<?php echo $user['balance']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Global SMS Profit (%)</label>
                                <input type="number" step="0.01" name="profit_percentage" class="form-control" value="<?php echo $user['profit_percentage'] ?? '0.00'; ?>">
                            </div>
                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="is_admin" value="1" id="is_admin_edit_<?php echo $user['id']; ?>" <?php if($user['is_admin']) echo 'checked'; ?>>
                                <label class="form-check-label" for="is_admin_edit_<?php echo $user['id']; ?>">Administrator</label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" name="edit_user" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
        $modals_html .= ob_get_clean();
    }
}

// --- Generate HTML for pagination ---
$pagination_html = '';
if ($total_pages > 1) {
    $pagination_html .= '<nav aria-label="Page navigation"><ul class="pagination justify-content-center mb-0">';
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
echo json_encode([
    'success' => true,
    'html' => $html,
    'modals_html' => $modals_html,
    'pagination' => $pagination_html,
    'csrfToken' => generate_csrf_token() // Send a new token with each request
]);