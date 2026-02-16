<?php
$page_title = 'All Live Delivery Reports';
require_once __DIR__ . '/../app/bootstrap.php';
include 'includes/header.php';

// --- Search and Filter Logic ---
$search_term = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

$base_sql = "FROM message_recipients mr JOIN messages m ON mr.message_id = m.id JOIN users u ON m.user_id = u.id";
$where_clauses = [];
$params = [];
$types = '';

if (!empty($search_term)) {
    $where_clauses[] = "(u.username LIKE ? OR m.sender_id LIKE ? OR mr.recipient_number LIKE ?)";
    $search_param = "%{$search_term}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $types .= 'sss';
}
if (!empty($status_filter)) {
    $where_clauses[] = "m.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}
if (!empty($where_clauses)) {
    $base_sql .= " WHERE " . implode(' AND ', $where_clauses);
}

// Pagination variables
$limit = 50; // Show more records for admin
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total number of records
$count_sql = "SELECT COUNT(mr.id) as total " . $base_sql;
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);
$count_stmt->close();

// Fetch records for the current page
$data_sql = "SELECT mr.recipient_number, mr.status, mr.failure_reason, mr.updated_at, m.message, m.sender_id, u.username, m.status as message_status, m.api_response " . $base_sql . " ORDER BY mr.updated_at DESC LIMIT ? OFFSET ?";
$data_stmt = $conn->prepare($data_sql);
$limit_params = array_merge($params, [$limit, $offset]);
$limit_types = $types . 'ii';
$data_stmt->bind_param($limit_types, ...$limit_params);
$data_stmt->execute();
$reports = $data_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$data_stmt->close();

function get_status_badge($status) {
    $status = strtolower($status);
    $badge_class = 'bg-warning'; // Default
    if (in_array($status, ['delivered', 'success', 'sent'])) {
        $badge_class = 'bg-success';
    } elseif (in_array($status, ['failed', 'error'])) {
        $badge_class = 'bg-danger';
    } elseif (in_array($status, ['queued', 'pending', 'scheduled'])) {
        $badge_class = 'bg-info';
    }
    return '<span class="badge ' . $badge_class . '">' . htmlspecialchars(ucfirst($status)) . '</span>';
}

function format_api_response($response_json) {
    if (empty($response_json)) {
        return 'N/A';
    }
    $response = json_decode($response_json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return htmlspecialchars($response_json); // Not a valid JSON, show raw
    }
    if (isset($response['msg'])) {
        return htmlspecialchars($response['msg']);
    }
    if (isset($response['error_code']) && isset($response['error_description'])) {
        return "Code: " . htmlspecialchars($response['error_code']) . " - " . htmlspecialchars($response['error_description']);
    }
    return 'N/A';
}
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><?php echo $page_title; ?></h3>
        <div class="card-tools">
            <form action="" method="GET" class="form-inline">
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Search User, Sender, Recipient..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    <select name="status" class="form-select form-select-sm" style="max-width: 150px;">
                        <option value="">All Statuses</option>
                        <option value="delivered" <?php if(($_GET['status'] ?? '') == 'delivered') echo 'selected'; ?>>Delivered</option>
                        <option value="sent" <?php if(($_GET['status'] ?? '') == 'sent') echo 'selected'; ?>>Sent</option>
                        <option value="failed" <?php if(($_GET['status'] ?? '') == 'failed') echo 'selected'; ?>>Failed</option>
                        <option value="queued" <?php if(($_GET['status'] ?? '') == 'queued') echo 'selected'; ?>>Queued</option>
                    </select>
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-default"><i class="fas fa-search"></i></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="card-body">
        <p>This page shows the real-time delivery status for all messages sent across the platform.</p>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>User</th>
                        <th>Sender ID</th>
                        <th>Recipient</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>API Response / Reason</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reports)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No delivery reports found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reports as $report): ?>
                            <tr>
                                <td><?php echo format_date_for_display($report['updated_at']); ?></td>
                                <td><?php echo htmlspecialchars($report['username']); ?></td>
                                <td><?php echo htmlspecialchars($report['sender_id']); ?></td>
                                <td><?php echo htmlspecialchars($report['recipient_number']); ?></td>
                                <td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($report['message']); ?>">
                                    <?php echo htmlspecialchars($report['message']); ?>
                                </td>
                                <td><?php echo get_status_badge($report['message_status']); ?></td>
                                <td>
                                    <?php
                                    // If the message failed and has a specific failure reason, show that. Otherwise, show the API response.
                                    if (!empty($report['failure_reason'])) {
                                        echo htmlspecialchars($report['failure_reason']);
                                    } else {
                                        echo format_api_response($report['api_response']);
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav>
            <ul class="pagination justify-content-center">
                <li class="page-item <?php if($page <= 1){ echo 'disabled'; } ?>">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                </li>
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php if($page == $i) {echo 'active'; } ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?php if($page >= $total_pages){ echo 'disabled'; } ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
