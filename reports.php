<?php
$page_title = 'Message Reports';
require_once 'app/bootstrap.php';

// --- Search and Filter Logic ---
$search_term = $_GET['search'] ?? '';
$type_filter = $_GET['type'] ?? '';
$status_filter = $_GET['status'] ?? '';

$base_sql = "FROM messages WHERE user_id = ?";
$where_clauses = [];
$params = [$current_user['id']];
$types = 'i';

if (!empty($search_term)) {
    $where_clauses[] = "(sender_id LIKE ? OR message LIKE ?)";
    $search_param = "%{$search_term}%";
    $params = array_merge($params, [$search_param, $search_param]);
    $types .= 'ss';
}
if (!empty($type_filter)) {
    $where_clauses[] = "type = ?";
    $params[] = $type_filter;
    $types .= 's';
}
if (!empty($status_filter)) {
    $where_clauses[] = "status = ?";
    $params[] = $status_filter;
    $types .= 's';
}
if (!empty($where_clauses)) {
    $base_sql .= " AND " . implode(' AND ', $where_clauses);
}

// --- Pagination Logic ---
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total number of records
$count_sql = "SELECT COUNT(id) as total " . $base_sql;
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);
$count_stmt->close();

// Fetch message batches for the current page
$messages = [];
$message_ids = [];
$data_sql = "SELECT * " . $base_sql . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$data_stmt = $conn->prepare($data_sql);
$limit_params = array_merge($params, [$limit, $offset]);
$limit_types = $types . 'ii';
$data_stmt->bind_param($limit_types, ...$limit_params);
$data_stmt->execute();
$result = $data_stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
    $message_ids[] = $row['id'];
}
$data_stmt->close();

// Fetch all recipient details for the messages on this page in a single query
$recipients_by_message_id = [];
if (!empty($message_ids)) {
    $ids_placeholder = implode(',', array_fill(0, count($message_ids), '?'));
    $stmt_recipients = $conn->prepare("SELECT * FROM message_recipients WHERE message_id IN ($ids_placeholder) ORDER BY id ASC");
    $stmt_recipients->bind_param(str_repeat('i', count($message_ids)), ...$message_ids);
    $stmt_recipients->execute();
    $result_recipients = $stmt_recipients->get_result();
    while ($row = $result_recipients->fetch_assoc()) {
        $recipients_by_message_id[$row['message_id']][] = $row;
    }
    $stmt_recipients->close();
}

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

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 m-0">Message History</h1>
</div>

<!-- Search and Filter Form -->
<div class="card mb-4">
    <div class="card-body">
        <form action="" method="GET" class="row g-3 align-items-center">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control" placeholder="Search Sender, Message..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            </div>
            <div class="col-md-3">
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    <option value="sms" <?php if(($_GET['type'] ?? '') == 'sms') echo 'selected'; ?>>SMS</option>
                    <option value="whatsapp" <?php if(($_GET['type'] ?? '') == 'whatsapp') echo 'selected'; ?>>WhatsApp</option>
                    <option value="voice_otp" <?php if(($_GET['type'] ?? '') == 'voice_otp') echo 'selected'; ?>>Voice OTP</option>
                </select>
            </div>
            <div class="col-md-3">
                 <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="sent" <?php if(($_GET['status'] ?? '') == 'sent') echo 'selected'; ?>>Sent</option>
                    <option value="delivered" <?php if(($_GET['status'] ?? '') == 'delivered') echo 'selected'; ?>>Delivered</option>
                    <option value="failed" <?php if(($_GET['status'] ?? '') == 'failed') echo 'selected'; ?>>Failed</option>
                    <option value="queued" <?php if(($_GET['status'] ?? '') == 'queued') echo 'selected'; ?>>Queued</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<?php if (empty($messages)): ?>
    <div class="card">
        <div class="card-body text-center">
            You have not sent any messages yet.
        </div>
    </div>
<?php else: ?>
    <?php foreach ($messages as $msg): ?>
        <div class="card report-card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        Batch ID: <?php echo $msg['id']; ?>
                        <span class="badge bg-primary ms-2"><?php echo strtoupper(str_replace('_', ' ', $msg['type'])); ?></span>
                        <?php echo get_status_badge($msg['status']); ?>
                    </h5>
                    <span class="text-muted"><?php echo date('F j, Y, g:i a', strtotime($msg['created_at'])); ?></span>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <strong>Message:</strong>
                        <p class="text-muted" style="white-space: pre-wrap; word-wrap: break-word;"><?php echo htmlspecialchars($msg['message']); ?></p>
                    </div>
                    <div class="col-md-4">
                        <strong>Sender/Caller ID:</strong>
                        <p><?php echo htmlspecialchars($msg['sender_id']); ?></p>
                        <strong>Total Cost:</strong>
                        <p>
                            <?php
                            if ($msg['wallet_type'] === 'global') {
                                echo htmlspecialchars($settings['global_wallet_currency'] ?? 'EUR') . ' ' . number_format($msg['cost'], 5);
                            } else {
                                echo get_currency_symbol() . number_format($msg['cost'], 2);
                            }
                            ?>
                        </p>
                    </div>
                </div>
                <hr>
                <h6 class="mb-3">Recipients (<?php echo count($recipients_by_message_id[$msg['id']] ?? []); ?>)</h6>
                <div class="table-responsive" style="max-height: 300px;">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Number</th>
                                <th>DLR Status</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recipients_by_message_id[$msg['id']])): ?>
                                <?php foreach ($recipients_by_message_id[$msg['id']] as $recipient): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($recipient['recipient_number']); ?></td>
                                        <td>
                                            <?php echo get_status_badge($recipient['status']); ?>
                                        </td>
                                        <td>
                                            <?php
                                            if (strtolower($msg['status']) === 'failed') {
                                                echo format_api_response($msg['api_response']);
                                            } else {
                                                echo htmlspecialchars($recipient['failure_reason']);
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center">No detailed recipient data for this batch.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="send-sms.php?resend_batch_id=<?php echo $msg['id']; ?>" class="btn btn-primary"><i class="fas fa-redo"></i> Resend to All</a>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Pagination Controls -->
<nav aria-label="Page navigation">
    <ul class="pagination justify-content-center mb-0">
        <?php if ($page > 1): ?>
            <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a></li>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?php if ($i == $page) echo 'active'; ?>"><a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
            <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a></li>
        <?php endif; ?>
    </ul>
</nav>

<?php include 'includes/footer.php'; ?>