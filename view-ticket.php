<?php
$page_title = 'View Ticket';
require_once 'app/bootstrap.php';

// Get ticket ID from URL
$ticket_id_str = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING);
if (!$ticket_id_str) {
    header("Location: support.php");
    exit();
}

// Fetch ticket details
$stmt = $conn->prepare("SELECT * FROM support_tickets WHERE ticket_id = ?");
$stmt->bind_param("s", $ticket_id_str);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Verify ownership or admin status
if (!$ticket) {
    // No ticket found with this ID
    $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'The requested ticket does not exist.'];
    header("Location: support.php");
    exit();
}

if ($ticket['user_id'] != $current_user['id'] && !is_admin()) {
    // User is not the owner and is not an admin
    $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'You do not have permission to view this ticket.'];
    header("Location: support.php");
    exit();
}

// Fetch all messages for this ticket
$messages = [];
$stmt_msgs = $conn->prepare("SELECT tm.*, u.username FROM ticket_messages tm LEFT JOIN users u ON tm.user_id = u.id WHERE tm.ticket_id = ? ORDER BY tm.created_at ASC");
$stmt_msgs->bind_param("i", $ticket['id']);
$stmt_msgs->execute();
$result_msgs = $stmt_msgs->get_result();
while($row = $result_msgs->fetch_assoc()) {
    $messages[] = $row;
}
$last_message_id = end($messages)['id'] ?? 0;

include 'includes/header.php';
?>
<link rel="stylesheet" href="css/support.css">

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <a href="support.php" class="btn btn-sm btn-outline-secondary mb-2"><i class="fas fa-arrow-left"></i> Back to Tickets</a>
        <h1 class="h3 m-0">Ticket #<?php echo htmlspecialchars($ticket['ticket_id']); ?></h1>
        <p class="text-muted m-0">Subject: <?php echo htmlspecialchars($ticket['subject']); ?></p>
    </div>
    <div id="ticket-status-badge">
        <?php
            $status = htmlspecialchars($ticket['status']);
            $status_map = [
                'open' => ['class' => 'bg-primary', 'text' => 'Open'],
                'closed' => ['class' => 'bg-secondary', 'text' => 'Closed'],
                'admin_reply' => ['class' => 'bg-success', 'text' => 'Admin Replied'],
                'user_reply' => ['class' => 'bg-warning text-dark', 'text' => 'Awaiting Admin Reply']
            ];
            $s = $status_map[$status] ?? ['class' => 'bg-light text-dark', 'text' => 'Unknown'];
            echo "Status: <span class='badge " . $s['class'] . "'>" . $s['text'] . "</span>";
        ?>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="chat-container" id="chat-container">
            <?php foreach($messages as $msg): ?>
                <div class="chat-message <?php echo $msg['is_admin_reply'] ? 'admin' : 'user'; ?>" data-message-id="<?php echo $msg['id']; ?>">
                    <div class="message-bubble">
                        <div class="message-sender">
                            <strong><?php echo $msg['is_admin_reply'] ? 'Support Team' : htmlspecialchars($msg['username']); ?></strong>
                        </div>
                        <div class="message-text">
                            <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                        </div>
                        <div class="message-time">
                            <?php echo date('M d, Y H:i', strtotime($msg['created_at'])); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <hr>

        <?php if($ticket['status'] !== 'closed'): ?>
            <div class="reply-form">
                <div id="form-error" class="alert alert-danger p-2" style="display:none;"></div>
                <form id="reply-form">
                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                    <div class="mb-3">
                        <textarea class="form-control" name="message" id="reply-message" rows="4" required placeholder="Type your reply here..."></textarea>
                    </div>
                    <button type="submit" id="send-reply-btn" class="btn btn-primary">Send Reply</button>
                </form>
            </div>
        <?php else: ?>
            <div class="alert alert-secondary text-center">This ticket has been closed.</div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatContainer = document.getElementById('chat-container');
    const replyForm = document.getElementById('reply-form');
    const sendBtn = document.getElementById('send-reply-btn');
    const errorDiv = document.getElementById('form-error');
    let lastMessageId = <?php echo $last_message_id; ?>;
    const ticketId = <?php echo $ticket['id']; ?>;

    // Scroll to the bottom of the chat
    chatContainer.scrollTop = chatContainer.scrollHeight;

    // Handle AJAX form submission
    if(replyForm) {
        replyForm.addEventListener('submit', function(e) {
            e.preventDefault();
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sending...';
            errorDiv.style.display = 'none';

            const formData = new FormData(this);
            fetch('ajax/send_chat_message.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('reply-message').value = ''; // Clear textarea
                    // The new message will be picked up by the polling function
                } else {
                    errorDiv.textContent = data.message;
                    errorDiv.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                errorDiv.textContent = 'An unexpected error occurred.';
                errorDiv.style.display = 'block';
            })
            .finally(() => {
                sendBtn.disabled = false;
                sendBtn.innerHTML = 'Send Reply';
            });
        });
    }

    // Function to render a new message
    function appendMessage(msg) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `chat-message ${msg.is_admin_reply ? 'admin' : 'user'}`;
        messageDiv.dataset.messageId = msg.id;

        const bubble = document.createElement('div');
        bubble.className = 'message-bubble';

        const sender = document.createElement('div');
        sender.className = 'message-sender';
        sender.innerHTML = `<strong>${msg.is_admin_reply ? 'Support Team' : msg.username}</strong>`;

        const text = document.createElement('div');
        text.className = 'message-text';
        text.innerHTML = msg.message.replace(/\n/g, '<br>');

        const time = document.createElement('div');
        time.className = 'message-time';
        time.textContent = new Date(msg.created_at).toLocaleString();

        bubble.appendChild(sender);
        bubble.appendChild(text);
        bubble.appendChild(time);
        messageDiv.appendChild(bubble);
        chatContainer.appendChild(messageDiv);
        chatContainer.scrollTop = chatContainer.scrollHeight; // Scroll to new message
    }

    // Polling for new messages
    setInterval(function() {
        fetch(`ajax/get_new_messages.php?ticket_id=${ticketId}&last_message_id=${lastMessageId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.messages.length > 0) {
                data.messages.forEach(msg => {
                    appendMessage(msg);
                    lastMessageId = msg.id;
                });
            }
        })
        .catch(error => console.error('Polling error:', error));
    }, 5000); // Poll every 5 seconds
});
</script>

<?php include 'includes/footer.php'; ?>
