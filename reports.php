<?php
$page_title = 'Message Reports';
require_once 'app/bootstrap.php';

// --- Pagination Logic ---
$limit = 10; // Lower limit per page because the view is more detailed
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total number of message batches for the user
$count_stmt = $conn->prepare("SELECT COUNT(id) as total FROM messages WHERE user_id = ?");
$count_stmt->bind_param("i", $current_user['id']);
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);
$count_stmt->close();

// Fetch message batches for the current page
$messages = [];
$message_ids = [];
$stmt = $conn->prepare("SELECT * FROM messages WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param("iii", $current_user['id'], $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
    $message_ids[] = $row['id'];
}
$stmt->close();

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

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 m-0">Message History</h1>
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
                        <p><?php echo get_currency_symbol(); ?><?php echo number_format($msg['cost'], 2); ?></p>
                    </div>
                </div>
                <hr>
                <h6 class="mb-3">Recipients (<?php echo count($recipients_by_message_id[$msg['id']] ?? []); ?>)</h6>
                <div class="table-responsive" style="max-height: 300px;">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Number</th>
                                <th>Status</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recipients_by_message_id[$msg['id']])): ?>
                                <?php foreach ($recipients_by_message_id[$msg['id']] as $recipient): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($recipient['recipient_number']); ?></td>
                                        <td>
                                            <?php
                                                $status = strtolower($recipient['status']);
                                                $badge_class = 'bg-secondary';
                                                if (in_array($status, ['delivered', 'completed', 'success'])) $badge_class = 'bg-success';
                                                if (in_array($status, ['failed', 'undelivered'])) $badge_class = 'bg-danger';
                                                if ($status == 'sent') $badge_class = 'bg-info';
                                                if ($status == 'queued') $badge_class = 'bg-warning text-dark';
                                                echo "<span class='badge " . $badge_class . "'>" . ucfirst($recipient['status']) . "</span>";
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($recipient['failure_reason']); ?></td>
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
