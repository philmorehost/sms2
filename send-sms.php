<?php
$page_title = 'Send SMS';
include 'includes/header.php';

// All the PHP logic from before remains the same...
$errors = [];
$success = '';

// Pre-fill form from URL parameters
$prefill_sender = '';
$prefill_recipients = '';
$prefill_message = '';
$prefill_schedule_time = '';
$editing_task_id = null;

if (isset($_GET['sender']) && isset($_GET['recipients']) && isset($_GET['message'])) {
    $prefill_sender = htmlspecialchars($_GET['sender']);
    $prefill_recipients = base64_decode(strtr($_GET['recipients'], '-_', '+/'));
    $prefill_message = base64_decode(strtr($_GET['message'], '-_', '+/'));
}

// Handle Edit Scheduled Task
if (isset($_GET['edit_task_id'])) {
    $task_id = (int)$_GET['edit_task_id'];
    $stmt_task = $conn->prepare("SELECT * FROM scheduled_tasks WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt_task->bind_param("ii", $task_id, $current_user['id']);
    $stmt_task->execute();
    $task_result = $stmt_task->get_result();
    if ($task = $task_result->fetch_assoc()) {
        $editing_task_id = $task['id'];
        $payload = json_decode($task['payload'], true);
        $prefill_sender = $payload['sender_id'];
        $prefill_recipients = $payload['recipients'];
        $prefill_message = $payload['message'];

        // Convert the stored UTC time back to the site's configured timezone for display
        $site_tz_str = get_settings()['site_timezone'] ?? 'UTC';
        $site_tz = new DateTimeZone($site_tz_str);
        $utc_dt = new DateTime($task['scheduled_for'], new DateTimeZone('UTC'));
        $utc_dt->setTimezone($site_tz);
        $prefill_schedule_time = $utc_dt->format('Y-m-d\TH:i');
    }
    $stmt_task->close();
}

// Handle Resend Batch
if (isset($_GET['resend_batch_id'])) {
    $batch_id = (int)$_GET['resend_batch_id'];
    $stmt_batch = $conn->prepare("SELECT sender_id, recipients, message FROM messages WHERE id = ? AND user_id = ?");
    $stmt_batch->bind_param("ii", $batch_id, $current_user['id']);
    $stmt_batch->execute();
    $batch_result = $stmt_batch->get_result();
    if ($batch = $batch_result->fetch_assoc()) {
        $prefill_sender = $batch['sender_id'];
        $prefill_recipients = $batch['recipients'];
        $prefill_message = $batch['message'];
    }
    $stmt_batch->close();
}

// Handle Save Draft submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_draft'])) {
    $title = trim($_POST['draft_title']);
    $sender_id = trim($_POST['sender_id']);
    $recipients = trim($_POST['recipients']);
    $message = trim($_POST['message']);
    if (empty($title)) {
        $title = "Draft saved on " . date("Y-m-d H:i");
    }
    if (!empty($message)) {
        $stmt = $conn->prepare("INSERT INTO sms_drafts (user_id, title, sender_id, recipients, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $current_user['id'], $title, $sender_id, $recipients, $message);
        if ($stmt->execute()) {
            $success = "Message successfully saved as a draft.";
        } else {
            $errors[] = "Failed to save draft.";
        }
    } else {
        $errors[] = "Cannot save an empty message as a draft.";
    }
}

// Fetch user's approved sender IDs
$approved_sender_ids = [];
$stmt_ids = $conn->prepare("SELECT sender_id FROM sender_ids WHERE user_id = ? AND status = 'approved'");
$stmt_ids->bind_param("i", $current_user['id']);
$stmt_ids->execute();
$result_ids = $stmt_ids->get_result();
while ($row = $result_ids->fetch_assoc()) {
    $approved_sender_ids[] = $row['sender_id'];
}
$stmt_ids->close();

// Fetch user's drafts
$drafts = [];
$drafts_stmt = $conn->prepare("SELECT id, title, message FROM sms_drafts WHERE user_id = ? ORDER BY updated_at DESC");
$drafts_stmt->bind_param("i", $current_user['id']);
$drafts_stmt->execute();
$drafts_result = $drafts_stmt->get_result();
while ($row = $drafts_result->fetch_assoc()) {
    $drafts[] = $row;
}
$drafts_stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_sms'])) {
    $sender_id = trim($_POST['sender_id']);
    $recipients = trim($_POST['recipients']);
    $message = trim($_POST['message']);
    $route = $_POST['sms_type'] ?? 'promotional'; // Get the selected route
    $schedule_time = $_POST['schedule_time'] ?? '';
    $editing_task_id = $_POST['editing_task_id'] ?? null;

    if (!empty($schedule_time)) {
        // This is a scheduled message.
        // NOTE: Editing a scheduled message is not yet supported with the new debit-on-schedule flow.
        // The old logic for editing is removed for now to prevent creating a task without debiting.
        if ($editing_task_id) {
            $errors[] = "Editing a scheduled message is not supported. Please cancel the old one and create a new one.";
        } else {
            try {
                // Get the site's configured timezone, default to UTC if not set
                $site_tz_str = get_settings()['site_timezone'] ?? 'UTC';
                $site_tz = new DateTimeZone($site_tz_str);
                $local_dt = new DateTime($schedule_time, $site_tz);
                $local_dt->setTimezone(new DateTimeZone('UTC'));
                $scheduled_for_utc = $local_dt->format('Y-m-d H:i:s');

                // Call the new function to handle debiting and scheduling
                $result = debit_and_schedule_sms($current_user, $sender_id, $recipients, $message, $route, $scheduled_for_utc, $conn);

                if ($result['success']) {
                    $success = $result['message'];
                    // Re-fetch user data to update balance display
                    $user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                    $user_stmt->bind_param("i", $current_user['id']);
                    $user_stmt->execute();
                    $current_user = $user_stmt->get_result()->fetch_assoc();
                    $user_stmt->close();
                } else {
                    $errors[] = $result['message'];
                }
            } catch (Exception $e) {
                $errors[] = "Invalid date format for scheduling. " . $e->getMessage();
            }
        }
    } else {
        // This is an immediate message
        $result = send_bulk_sms($current_user, $sender_id, $recipients, $message, $route, $conn);
        if ($result['success']) {
            $success = $result['message'];
            // Re-fetch user data to update balance display
            $user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $user_stmt->bind_param("i", $current_user['id']);
            $user_stmt->execute();
            $current_user = $user_stmt->get_result()->fetch_assoc();
            $user_stmt->close();
        } else {
            $errors[] = $result['message'];
        }
    }
}
?>
<link rel="stylesheet" href="css/send-sms.css">

<form id="sms-form" action="send-sms.php" method="POST">
    <?php if ($editing_task_id): ?>
        <input type="hidden" name="editing_task_id" value="<?php echo $editing_task_id; ?>">
    <?php endif; ?>
    <?php
    if (isset($_SESSION['flash_message']) && $_SESSION['flash_message']['type'] === 'debug-error') {
        $flash = $_SESSION['flash_message'];
        echo '<div class="alert alert-danger"><h4>Critical Error</h4><p>The system encountered a critical error while trying to save your message report. Please provide the following message to support:</p><pre>' . htmlspecialchars($flash['message']) . '</pre></div>';
        unset($_SESSION['flash_message']);
    }
    ?>
    <div class="row">
        <!-- Main Form Column -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Compose Message</h4>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#loadDraftModal" <?php if(empty($drafts)) echo 'disabled'; ?>>
                        <i class="fas fa-folder-open"></i> Load Draft
                    </button>
                </div>
                <div class="card-body">
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

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="sender_id" class="form-label">Sender ID</label>
                            <select class="form-select" name="sender_id" required <?php if(empty($approved_sender_ids)) echo 'disabled'; ?>>
                                <option value="" disabled <?php if(empty($prefill_sender)) echo 'selected'; ?>>-- Select an Approved Sender ID --</option>
                                <?php foreach ($approved_sender_ids as $sid): ?>
                                    <option value="<?php echo htmlspecialchars($sid); ?>" <?php if($prefill_sender == $sid) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($sid); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                             <?php if (empty($approved_sender_ids)): ?>
                                <small class="form-text text-danger"><a href="sender-id.php" class="text-danger">Please register a Sender ID to begin.</a></small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="draft_title" class="form-label">Draft Title (Optional)</label>
                            <input type="text" class="form-control" name="draft_title" placeholder="e.g., Weekly Promo">
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Message Route</label>
                        <div class="d-flex">
                            <div class="form-check me-3">
                                <input class="form-check-input" type="radio" name="sms_type" id="promotional_route" value="promotional" checked>
                                <label class="form-check-label" for="promotional_route">Promotional</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="sms_type" id="corporate_route" value="corporate">
                                <label class="form-check-label" for="corporate_route">Corporate</label>
                            </div>
                        </div>
                        <small class="form-text text-muted">Promotional route is for marketing messages. Corporate route is for alerts and notifications.</small>
                    </div>

                    <div class="form-group mb-3">
                        <label for="recipients" class="form-label">Recipients</label>
                        <textarea class="form-control" id="recipients" name="recipients" rows="5" placeholder="Enter numbers, separated by commas, spaces, or on new lines."><?php echo htmlspecialchars($prefill_recipients); ?></textarea>
                        <div class="char-count text-end mt-1">
                            Total Recipients: <span id="recipient-count">0</span>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" name="message" id="message" rows="6" placeholder="Type your message here..." required><?php echo htmlspecialchars($prefill_message); ?></textarea>
                        <div id="char-count" class="char-count d-flex justify-content-between mt-1">
                            <span>Placeholders: <code>[first_name]</code>, <code>[last_name]</code></span>
                            <span>Characters: <span id="char-num">0</span> | SMS Parts: <span id="sms-parts">1</span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Sidebar Column -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Options</h5>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="schedule-switch">
                        <label class="form-check-label" for="schedule-switch"><strong>Schedule for Later</strong></label>
                    </div>
                    <div id="schedule-options" style="<?php echo $editing_task_id ? 'display: block;' : 'display: none;'; ?>">
                        <div class="form-group">
                            <label for="schedule_time">Schedule Time (Your Timezone)</label>
                            <input type="datetime-local" class="form-control" name="schedule_time" id="schedule_time" value="<?php echo htmlspecialchars($prefill_schedule_time); ?>">
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <button type="submit" name="send_sms" id="send-btn" class="btn btn-primary" <?php if(empty($approved_sender_ids)) echo 'disabled'; ?>><i class="fas fa-paper-plane"></i> Send Message</button>
                    <button type="submit" name="save_draft" class="btn btn-secondary"><i class="fas fa-save"></i> Save Draft</button>
                </div>
            </div>

             <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Phone Book</h5></div>
                <div class="card-body">
                    <p>Add recipients directly from your phone book.</p>
                    <button type="button" class="btn btn-info w-100" data-bs-toggle="modal" data-bs-target="#phonebookModal">
                        <i class="fas fa-address-book"></i> Select from Phone Book
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Phonebook Modal -->
<div class="modal fade" id="phonebookModal" tabindex="-1" aria-labelledby="phonebookModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="phonebookModalLabel">Select Contacts from Phone Book</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="phonebook-modal-body">
                <div class="text-center">
                    <div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="add-selected-contacts-btn">Add Selected Contacts</button>
            </div>
        </div>
    </div>
</div>

<!-- Banned Word Modal -->
<div class="modal fade" id="bannedWordModal" tabindex="-1" aria-labelledby="bannedWordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="bannedWordModalLabel"><i class="fas fa-exclamation-triangle"></i> Message Blocked</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Your message could not be sent because it contains a forbidden word: "<strong id="found-banned-word"></strong>".</p>
                <p>Please revise your message and try again.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Cost Confirmation Modal -->
<div class="modal fade" id="costConfirmationModal" tabindex="-1" aria-labelledby="costConfirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="costConfirmationModalLabel"><i class="fas fa-dollar-sign"></i> Confirm Send</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>You are about to send an SMS to <strong id="confirm-recipient-count">0</strong> recipients.</p>
                <p>The estimated cost for this campaign is <strong class="text-primary fs-5" id="confirm-total-cost"><?php echo get_currency_symbol(); ?>0.00</strong>.</p>
                <p>Your current balance is <strong><?php echo get_currency_symbol(); ?><?php echo number_format($current_user['balance'], 2); ?></strong>.</p>
                <hr>
                <p class="text-muted small">This cost is an estimate. The final cost will be deducted from your wallet upon sending.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirm-send-btn">Confirm & Send</button>
            </div>
        </div>
    </div>
</div>

<!-- Load Draft Modal -->
<div class="modal fade" id="loadDraftModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Load a Saved Draft</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table class="table table-hover">
                    <thead><tr><th>Title</th><th>Message Snippet</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if(empty($drafts)): ?>
                            <tr><td colspan="3" class="text-center">You have no saved drafts.</td></tr>
                        <?php else: ?>
                            <?php foreach($drafts as $draft): ?>
                                <tr id="draft-row-<?php echo $draft['id']; ?>">
                                    <td><?php echo htmlspecialchars($draft['title']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($draft['message'], 0, 50)); ?>...</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary select-draft-btn" data-draft-id="<?php echo $draft['id']; ?>" data-bs-dismiss="modal">Select</button>
                                        <button type="button" class="btn btn-sm btn-danger delete-draft-btn" data-draft-id="<?php echo $draft['id']; ?>">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// All JS from before remains the same...
document.addEventListener('DOMContentLoaded', function() {
    const messageTextarea = document.getElementById('message');
    const charNumSpan = document.getElementById('char-num');
    const smsPartsSpan = document.getElementById('sms-parts');
    const recipientsTextarea = document.getElementById('recipients');
    const recipientCountSpan = document.getElementById('recipient-count');
    function updateRecipientCount() {
        const value = recipientsTextarea.value.trim();
        if (value === '') {
            recipientCountSpan.textContent = 0;
            return;
        }
        const numbers = value.split(/[\s,;\n]+/);
        const validNumbers = numbers.filter(n => n.length > 0);
        recipientCountSpan.textContent = validNumbers.length;
    }
    recipientsTextarea.addEventListener('input', updateRecipientCount);
    messageTextarea.addEventListener('input', function() {
        const charCount = this.value.length;
        charNumSpan.textContent = charCount;
        if (charCount <= 160) {
            smsPartsSpan.textContent = 1;
        } else {
            smsPartsSpan.textContent = Math.ceil(charCount / 153);
        }
    });
    // Manually trigger count on page load for pre-filled data
    updateRecipientCount();
    messageTextarea.dispatchEvent(new Event('input'));
});

document.addEventListener('DOMContentLoaded', function() {
    const scheduleSwitch = document.getElementById('schedule-switch');
    const scheduleOptions = document.getElementById('schedule-options');
    const sendBtn = document.getElementById('send-btn');

    // If we are editing, the schedule switch should be on by default
    if (document.querySelector('[name="editing_task_id"]')) {
        scheduleSwitch.checked = true;
        sendBtn.innerHTML = '<i class="fas fa-clock"></i> Update Schedule';
    }

    scheduleSwitch.addEventListener('change', function() {
        if (this.checked) {
            scheduleOptions.style.display = 'block';
            sendBtn.innerHTML = document.querySelector('[name="editing_task_id"]') ? '<i class="fas fa-clock"></i> Update Schedule' : '<i class="fas fa-clock"></i> Schedule Message';
        } else {
            scheduleOptions.style.display = 'none';
            sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Message';
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const phonebookModal = document.getElementById('phonebookModal');
    const modalBody = document.getElementById('phonebook-modal-body');
    const addSelectedBtn = document.getElementById('add-selected-contacts-btn');
    const recipientsTextarea = document.getElementById('recipients');
    let contactsData = [];
    phonebookModal.addEventListener('show.bs.modal', function() {
        modalBody.innerHTML = `<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>`;
        fetch('ajax/get_phonebook.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    contactsData = data.phonebook.contacts;
                    let html = '<ul class="list-group">';
                    data.phonebook.groups.forEach(group => {
                        html += `<li class="list-group-item"><div class="form-check"><input class="form-check-input group-check" type="checkbox" value="${group.id}" id="group-${group.id}"><label class="form-check-label fw-bold" for="group-${group.id}">${group.group_name} (${group.contact_count} contacts)</label></div><ul class="list-group ms-4 mt-2">`;
                        const contactsInGroup = contactsData.filter(c => c.group_id == group.id);
                        contactsInGroup.forEach(contact => {
                            html += `<li class="list-group-item border-0 py-1"><div class="form-check"><input class="form-check-input contact-check" type="checkbox" value="${contact.phone_number}" id="contact-${contact.id}" data-group-id="${group.id}"><label class="form-check-label" for="contact-${contact.id}">${contact.first_name || ''} ${contact.last_name || ''} (${contact.phone_number})</label></div></li>`;
                        });
                        html += `</ul></li>`;
                    });
                    html += '</ul>';
                    modalBody.innerHTML = html;
                } else {
                    modalBody.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                }
            })
            .catch(error => {
                console.error("Error fetching phonebook:", error);
                modalBody.innerHTML = `<div class="alert alert-danger">Failed to load phonebook.</div>`;
            });
    });
    modalBody.addEventListener('change', function(e) {
        if (e.target.classList.contains('group-check')) {
            const groupId = e.target.value;
            const isChecked = e.target.checked;
            const contactCheckboxes = modalBody.querySelectorAll(`.contact-check[data-group-id="${groupId}"]`);
            contactCheckboxes.forEach(checkbox => checkbox.checked = isChecked);
        }
    });
    addSelectedBtn.addEventListener('click', function() {
        const selectedNumbers = [];
        const checkedContacts = modalBody.querySelectorAll('.contact-check:checked');
        checkedContacts.forEach(checkbox => {
            selectedNumbers.push(checkbox.value);
        });
        if (selectedNumbers.length > 0) {
            const currentRecipients = recipientsTextarea.value.trim();
            const newRecipients = selectedNumbers.join(', ');
            if (currentRecipients === '') {
                recipientsTextarea.value = newRecipients;
            } else {
                recipientsTextarea.value = currentRecipients + ', ' + newRecipients;
            }
            recipientsTextarea.dispatchEvent(new Event('input', { bubbles: true }));
        }
        var modal = bootstrap.Modal.getInstance(phonebookModal);
        modal.hide();
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const smsForm = document.getElementById('sms-form');
    let bannedWords = [];
    const bannedWordModal = new bootstrap.Modal(document.getElementById('bannedWordModal'));
    const foundBannedWordSpan = document.getElementById('found-banned-word');
    fetch('ajax/get_banned_words.php')
        .then(response => response.json()).then(data => { if (data.success) { bannedWords = data.words; } })
        .catch(error => console.error('Error fetching banned words:', error));
    const costConfirmationModal = new bootstrap.Modal(document.getElementById('costConfirmationModal'));
    const confirmRecipientCountSpan = document.getElementById('confirm-recipient-count');
    const confirmTotalCostSpan = document.getElementById('confirm-total-cost');
    const confirmSendBtn = document.getElementById('confirm-send-btn');
    smsForm.addEventListener('submit', function(e) {
        if (!e.submitter || e.submitter.name !== 'send_sms') { return; }
        const messageText = smsForm.querySelector('[name="message"]').value.toLowerCase();
        let foundWord = null;
        for (const word of bannedWords) {
            const regex = new RegExp('\\b' + word + '\\b', 'i');
            if (regex.test(messageText)) { foundWord = word; break; }
        }
        if (foundWord) {
            e.preventDefault();
            foundBannedWordSpan.textContent = foundWord;
            bannedWordModal.show();
            return;
        }
        const isCostConfirmed = smsForm.querySelector('[name="cost_confirmed"]');
        if (isCostConfirmed) { return; }
        e.preventDefault();
        const recipients = smsForm.querySelector('[name="recipients"]').value;
        if (recipients.trim() === '') { alert('Please enter at least one recipient.'); return; }
        const sendButton = e.submitter;
        const originalButtonText = sendButton.innerHTML;
        sendButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Calculating...`;
        sendButton.disabled = true;
        const route = smsForm.querySelector('input[name="sms_type"]:checked').value;
        fetch('ajax/calculate_cost.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `recipients=${encodeURIComponent(recipients)}&route=${encodeURIComponent(route)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Get the currency symbol from the balance display string
                const balanceString = document.querySelector('#costConfirmationModal .modal-body p:nth-of-type(3) strong').textContent;
                const currencySymbol = balanceString.charAt(0);

                confirmRecipientCountSpan.textContent = data.recipient_count;
                confirmTotalCostSpan.textContent = currencySymbol + data.cost;
                costConfirmationModal.show();
            } else {
                alert('Error calculating cost: ' . data.message);
            }
        })
        .catch(error => { console.error('Cost calculation error:', error); alert('An error occurred while calculating the cost.'); })
        .finally(() => { sendButton.innerHTML = originalButtonText; sendButton.disabled = false; });
    });
    confirmSendBtn.addEventListener('click', function() {
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'cost_confirmed';
        hiddenInput.value = 'true';
        smsForm.appendChild(hiddenInput);
        costConfirmationModal.hide();
        const sendButton = smsForm.querySelector('button[name="send_sms"]');
        if (sendButton) { sendButton.click(); } else { smsForm.submit(); }
    });
    const modalBody = document.querySelector('#loadDraftModal .modal-body');
    modalBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('select-draft-btn')) {
            const draftId = e.target.dataset.draftId;
            fetch(`ajax/get-draft.php?id=${draftId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const draft = data.draft;
                        smsForm.querySelector('[name="draft_title"]').value = draft.title;
                        smsForm.querySelector('[name="sender_id"]').value = draft.sender_id;
                        smsForm.querySelector('[name="recipients"]').value = draft.recipients;
                        smsForm.querySelector('[name="message"]').value = draft.message;
                        smsForm.querySelector('[name="message"]').dispatchEvent(new Event('input'));
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => { console.error('Error fetching draft:', error); alert('An error occurred while trying to load the draft.'); });
        } else if (e.target.classList.contains('delete-draft-btn')) {
            if (confirm('Are you sure you want to delete this draft?')) {
                const draftId = e.target.dataset.draftId;
                fetch('ajax/delete-draft.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${draftId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById(`draft-row-${draftId}`).remove();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => { console.error('Error deleting draft:', error); alert('An error occurred while trying to delete the draft.'); });
            }
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
