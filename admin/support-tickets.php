<?php
$page_title = 'Support Tickets';
require_once __DIR__ . '/../app/bootstrap.php';

// Handle filtering
$status_filter = $_GET['status'] ?? 'open';
$allowed_filters = ['open', 'closed', 'all'];
if (!in_array($status_filter, $allowed_filters)) {
    $status_filter = 'open'; // Default to a safe value
}

$sql_where = "";
if ($status_filter == 'open') {
    $sql_where = "WHERE t.status IN ('open', 'user_reply')";
} elseif ($status_filter == 'closed') {
    $sql_where = "WHERE t.status = 'closed'";
}
// if 'all', $sql_where remains empty

// Fetch support tickets based on filter
$tickets = [];
$sql = "SELECT t.ticket_id, t.subject, t.status, t.updated_at, u.username
        FROM support_tickets t
        JOIN users u ON t.user_id = u.id
        $sql_where
        ORDER BY t.updated_at DESC";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $tickets[] = $row;
    }
    $stmt->close();
}

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 m-0">Support Ticket Management</h1>
</div>

<div class="card">
    <div class="card-header">
        <!-- Filter Tabs -->
        <ul class="nav nav-tabs card-header-tabs nav-tabs-responsive">
            <li class="nav-item">
                <a class="nav-link <?php if($status_filter == 'open') echo 'active'; ?>" href="?status=open">Open Tickets</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php if($status_filter == 'all') echo 'active'; ?>" href="?status=all">All Tickets</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php if($status_filter == 'closed') echo 'active'; ?>" href="?status=closed">Closed Tickets</a>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Ticket ID</th>
                        <th>User</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Last Updated</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tickets)): ?>
                        <tr><td colspan="6" class="text-center">No tickets found for this filter.</td></tr>
                    <?php else: ?>
                        <?php foreach ($tickets as $ticket): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($ticket['ticket_id']); ?></strong></td>
                            <td><?php echo htmlspecialchars($ticket['username']); ?></td>
                            <td><?php echo htmlspecialchars($ticket['subject']); ?></td>
                            <td>
                                <?php
                                    $status = htmlspecialchars($ticket['status']);
                                    $status_map = [
                                        'open' => ['class' => 'bg-primary', 'text' => 'Open'],
                                        'closed' => ['class' => 'bg-secondary', 'text' => 'Closed'],
                                        'admin_reply' => ['class' => 'bg-success', 'text' => 'Replied'],
                                        'user_reply' => ['class' => 'bg-warning text-dark', 'text' => 'User Replied']
                                    ];
                                    $s = $status_map[$status] ?? ['class' => 'bg-light text-dark', 'text' => 'Unknown'];
                                    echo "<span class='badge " . $s['class'] . "'>" . $s['text'] . "</span>";
                                ?>
                            </td>
                            <td><?php echo date('Y-m-d H:i', strtotime($ticket['updated_at'])); ?></td>
                            <td>
                                <a href="view-ticket.php?id=<?php echo htmlspecialchars($ticket['ticket_id']); ?>" class="btn btn-sm btn-info">View Ticket</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
