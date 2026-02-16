<?php
$page_title = 'Support Center';
require_once 'app/bootstrap.php';
require_once 'app/helpers.php';

$errors = [];
$success = '';

// Pre-fill from URL parameters
$prefill_subject = '';
$prefill_message = '';
if (isset($_GET['subject']) && isset($_GET['message'])) {
    $prefill_subject = htmlspecialchars($_GET['subject']);
    $prefill_message = htmlspecialchars($_GET['message']);
}

// Handle new ticket creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_ticket'])) {
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    if (empty($subject) || empty($message)) {
        $errors[] = "Subject and message fields are required.";
    }

    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            // Generate a unique ticket ID
            $ticket_id_str = 'TICKET-' . strtoupper(bin2hex(random_bytes(4)));

            // 1. Create the ticket
            $stmt_ticket = $conn->prepare("INSERT INTO support_tickets (user_id, ticket_id, subject, status) VALUES (?, ?, ?, 'user_reply')");
            $stmt_ticket->bind_param("iss", $current_user['id'], $ticket_id_str, $subject);
            $stmt_ticket->execute();
            $new_ticket_id = $stmt_ticket->insert_id;
            $stmt_ticket->close();

            // 2. Add the first message
            $is_admin_reply = 0;
            $stmt_message = $conn->prepare("INSERT INTO ticket_messages (ticket_id, user_id, is_admin_reply, message) VALUES (?, ?, ?, ?)");
            $stmt_message->bind_param("iiis", $new_ticket_id, $current_user['id'], $is_admin_reply, $message);
            $stmt_message->execute();
            $stmt_message->close();

            $conn->commit();
            $success = "Your support ticket (#$ticket_id_str) has been created successfully. An admin will respond shortly.";

            // Send email notification to admin
            $admin_email = get_admin_email();
            $email_subject = "New Support Ticket: #" . $ticket_id_str;
            $email_message = "
                <h2>New Support Ticket Created</h2>
                <p>A new support ticket has been opened by user: " . htmlspecialchars($current_user['username']) . "</p>
                <p><strong>Subject:</strong> " . htmlspecialchars($subject) . "</p>
                <p><strong>Message:</strong></p>
                <p>" . nl2br(htmlspecialchars($message)) . "</p>
                <p>You can view and reply to this ticket here:<br>
                <a href='" . SITE_URL . "/admin/view-ticket.php?id=" . $ticket_id_str . "'>View Ticket</a></p>
            ";
            send_email($admin_email, $email_subject, $email_message);

        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "An error occurred while creating your ticket. Please try again. " . $e->getMessage();
        }
    }
}


// Fetch user's support tickets
$tickets = [];
$stmt = $conn->prepare("SELECT ticket_id, subject, status, updated_at FROM support_tickets WHERE user_id = ? ORDER BY updated_at DESC");
$stmt->bind_param("i", $current_user['id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $tickets[] = $row;
}
$stmt->close();

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 m-0">My Support Tickets</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newTicketModal">
        <i class="fas fa-plus"></i> Open New Ticket
    </button>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $error): ?><p class="mb-0"><?php echo $error; ?></p><?php endforeach; ?>
    </div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success">
        <p class="mb-0"><?php echo $success; ?></p>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Ticket ID</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Last Updated</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tickets)): ?>
                        <tr><td colspan="5" class="text-center">You have not created any support tickets yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($tickets as $ticket): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($ticket['ticket_id']); ?></strong></td>
                            <td><?php echo htmlspecialchars($ticket['subject']); ?></td>
                            <td>
                                <?php
                                    $status = htmlspecialchars($ticket['status']);
                                    $status_map = [
                                        'open' => ['class' => 'bg-primary', 'text' => 'Open'],
                                        'closed' => ['class' => 'bg-secondary', 'text' => 'Closed'],
                                        'admin_reply' => ['class' => 'bg-success', 'text' => 'Admin Replied'],
                                        'user_reply' => ['class' => 'bg-warning text-dark', 'text' => 'Awaiting Admin Reply']
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


<!-- New Ticket Modal -->
<div class="modal fade" id="newTicketModal" tabindex="-1" aria-labelledby="newTicketModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="support.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="newTicketModalLabel">Create a New Support Ticket</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="subject" name="subject" value="<?php echo $prefill_subject; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="5" required placeholder="Please describe your issue in detail..."><?php echo $prefill_message; ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="create_ticket" class="btn btn-primary">Submit Ticket</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($prefill_subject) || !empty($prefill_message)): ?>
        var newTicketModal = new bootstrap.Modal(document.getElementById('newTicketModal'));
        newTicketModal.show();
    <?php endif; ?>
});
</script>

<?php include 'includes/footer.php'; ?>
