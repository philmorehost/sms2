<?php
$page_title = 'Live Delivery Reports';
require_once 'app/bootstrap.php';
include 'includes/header.php';

// Pagination variables
$limit = 25; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total number of records for the current user
$total_records_stmt = $conn->prepare("SELECT COUNT(*) FROM message_recipients mr JOIN messages m ON mr.message_id = m.id WHERE m.user_id = ?");
$total_records_stmt->bind_param("i", $current_user['id']);
$total_records_stmt->execute();
$total_records = $total_records_stmt->get_result()->fetch_row()[0];
$total_pages = ceil($total_records / $limit);
$total_records_stmt->close();

// Fetch records for the current page
$stmt = $conn->prepare("SELECT mr.recipient_number, mr.status, mr.failure_reason, mr.updated_at, m.message, m.sender_id, m.status as message_status, m.api_response
                        FROM message_recipients mr
                        JOIN messages m ON mr.message_id = m.id
                        WHERE m.user_id = ?
                        ORDER BY mr.updated_at DESC
                        LIMIT ? OFFSET ?");
$stmt->bind_param("iii", $current_user['id'], $limit, $offset);
$stmt->execute();
$reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

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
    </div>
    <div class="card-body">
        <p>This page shows the real-time delivery status of your sent messages. Statuses are updated as they are received from the network.</p>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
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
                            <td colspan="6" class="text-center">No delivery reports found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reports as $report): ?>
                            <tr>
                                <td><?php echo format_date_for_display($report['updated_at']); ?></td>
                                <td><?php echo htmlspecialchars($report['sender_id']); ?></td>
                                <td><?php echo htmlspecialchars($report['recipient_number']); ?></td>
                                <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($report['message']); ?>">
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
