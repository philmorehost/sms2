<?php
$page_title = 'Send Global SMS';
include 'includes/header.php';

// Fetch user's global wallet balance
$global_wallet_stmt = $conn->prepare("SELECT balance FROM global_wallets WHERE user_id = ?");
$global_wallet_stmt->bind_param("i", $current_user['id']);
$global_wallet_stmt->execute();
$global_wallet_result = $global_wallet_stmt->get_result();
$global_wallet = $global_wallet_result->fetch_assoc();
$global_wallet_balance = $global_wallet['balance'] ?? 0.00;
$global_wallet_stmt->close();


$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_sms'])) {
    $sender_id = trim($_POST['sender_id']);
    $recipients = trim($_POST['recipients']);
    $message = trim($_POST['message']);
    $route = 'global'; // Hardcoded to global route
    $schedule_time = $_POST['schedule_time'] ?? '';

    if (!empty($schedule_time)) {
        // This is a scheduled message.
        try {
            // Get the site's configured timezone, default to UTC if not set
            $site_tz_str = get_settings()['site_timezone'] ?? 'UTC';
            $site_tz = new DateTimeZone($site_tz_str);
            $local_dt = new DateTime($schedule_time, $site_tz);
            $local_dt->setTimezone(new DateTimeZone('UTC'));
            $scheduled_for_utc = $local_dt->format('Y-m-d H:i:s');

            // Call the new function to handle debiting and scheduling for global SMS
            $result = debit_and_schedule_global_sms($current_user, $sender_id, $recipients, $message, $scheduled_for_utc, $conn);

            if ($result['success']) {
                $success = $result['message'];
            } else {
                $errors[] = $result['message'];
            }
        } catch (Exception $e) {
            $errors[] = "Invalid date format for scheduling. " . $e->getMessage();
        }
    } else {
        // This is an immediate message
        $result = send_bulk_sms($current_user, $sender_id, $recipients, $message, $route, $conn);
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $errors[] = $result['message'];
        }
    }

    // Re-fetch balances if the request was successful
    if ($success) {
        // Re-fetch user data to update balance display
        $user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $user_stmt->bind_param("i", $current_user['id']);
        $user_stmt->execute();
        $current_user = $user_stmt->get_result()->fetch_assoc();
        $user_stmt->close();

        // Re-fetch global wallet balance
        $global_wallet_stmt = $conn->prepare("SELECT balance FROM global_wallets WHERE user_id = ?");
        $global_wallet_stmt->bind_param("i", $current_user['id']);
        $global_wallet_stmt->execute();
        $global_wallet_result = $global_wallet_stmt->get_result();
        $global_wallet = $global_wallet_result->fetch_assoc();
        $global_wallet_balance = $global_wallet['balance'] ?? 0.00;
        $global_wallet_stmt->close();
    }
}
?>
<link rel="stylesheet" href="css/send-sms.css">

<form id="sms-form" action="global-sms.php" method="POST">
    <div class="row">
        <!-- Main Form Column -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Compose Global Message</h4>
                </div>
                <div class="card-body">
                    <?php
                    $user_ip = $_SERVER['REMOTE_ADDR'];
                    $whitelist_subject = "IP Whitelist Request";
                    $whitelist_body = "Please whitelist my IP address for Global SMS access: " . $user_ip;
                    $support_url = "support.php?subject=" . urlencode($whitelist_subject) . "&message=" . urlencode($whitelist_body);
                    ?>
                    <div class="alert alert-info">
                        <strong>IP Whitelisting Required:</strong> Your IP address is <strong><?php echo $user_ip; ?></strong>.
                        <button type="button" class="btn btn-sm btn-outline-secondary ms-2" onclick="navigator.clipboard.writeText('<?php echo $user_ip; ?>').then(() => alert('IP Copied!'));">Copy IP</button>
                        <a href="<?php echo $support_url; ?>" class="btn btn-sm btn-outline-primary">Request Whitelisting</a>
                        <p class="mt-2 mb-0 small">For security, your IP address must be whitelisted to use the Global SMS API. If you have not done so, please click "Request Whitelisting" to open a support ticket.</p>
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

                    <div class="mb-3">
                        <label for="sender_id" class="form-label">Sender ID</label>
                        <input type="text" class="form-control" name="sender_id" required>
                        <small class="form-text text-muted">Max 11 alphanumeric characters or 18 numeric characters.</small>
                    </div>

                    <div class="form-group mb-3">
                        <label for="recipients" class="form-label">Recipients</label>
                        <textarea class="form-control" id="recipients" name="recipients" rows="5" placeholder="Enter numbers, separated by commas, spaces, or on new lines. Include country code e.g. +1..."></textarea>
                        <div class="char-count text-end mt-1">
                            Total Recipients: <span id="recipient-count">0</span>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" name="message" id="message" rows="6" placeholder="Type your message here..." required></textarea>
                        <div id="char-count" class="char-count d-flex justify-content-between mt-1">
                            <span></span>
                            <span>Characters: <span id="char-num">0</span> | SMS Parts: <span id="sms-parts">1</span></span>
                        </div>
                    </div>
                     <div class="card-footer d-flex justify-content-between">
                        <button type="submit" name="send_sms" id="send-btn" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send Message</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Sidebar Column -->
        <div class="col-lg-4">
             <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Phone Book</h5></div>
                <div class="card-body">
                    <p>Add recipients directly from your phone book.</p>
                    <button type="button" class="btn btn-info w-100" data-bs-toggle="modal" data-bs-target="#phonebookModal">
                        <i class="fas fa-address-book"></i> Select from Phone Book
                    </button>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Options</h5>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="schedule-switch">
                        <label class="form-check-label" for="schedule-switch"><strong>Schedule for Later</strong></label>
                    </div>
                    <div id="schedule-options" style="display: none;">
                        <div class="form-group">
                            <label for="schedule_time">Schedule Time (Your Timezone)</label>
                            <input type="datetime-local" class="form-control" name="schedule_time" id="schedule_time">
                        </div>
                    </div>
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
                <p>The estimated cost for this campaign is <strong class="text-primary fs-5" id="confirm-total-cost">0.00</strong>.</p>
                <p>Your current global wallet balance is <strong><?php echo number_format($global_wallet_balance, 2); ?> <?php echo htmlspecialchars($settings['global_wallet_currency'] ?? 'EUR'); ?></strong>.</p>
                <hr>
                <p class="text-muted small">This cost is an estimate. The final cost will be deducted from your global wallet upon sending.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirm-send-btn">Confirm & Send</button>
            </div>
        </div>
    </div>
</div>


<script>
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
    const costConfirmationModal = new bootstrap.Modal(document.getElementById('costConfirmationModal'));
    const confirmRecipientCountSpan = document.getElementById('confirm-recipient-count');
    const confirmTotalCostSpan = document.getElementById('confirm-total-cost');
    const confirmSendBtn = document.getElementById('confirm-send-btn');
    smsForm.addEventListener('submit', function(e) {
        if (!e.submitter || e.submitter.name !== 'send_sms') { return; }

        const isCostConfirmed = smsForm.querySelector('[name="cost_confirmed"]');
        if (isCostConfirmed) { return; }
        e.preventDefault();
        const recipients = smsForm.querySelector('[name="recipients"]').value;
        if (recipients.trim() === '') { alert('Please enter at least one recipient.'); return; }
        const sendButton = e.submitter;
        const originalButtonText = sendButton.innerHTML;
        sendButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Calculating...`;
        sendButton.disabled = true;
        const route = 'global';
        fetch('ajax/calculate_cost.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `recipients=${encodeURIComponent(recipients)}&route=${encodeURIComponent(route)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const currencySymbol = '<?php echo htmlspecialchars($settings['global_wallet_currency'] ?? 'EUR'); ?>';
                confirmRecipientCountSpan.textContent = data.recipient_count;
                confirmTotalCostSpan.textContent = currencySymbol + ' ' + data.cost;
                costConfirmationModal.show();
            } else {
                alert('Error calculating cost: ' + data.message);
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
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const scheduleSwitch = document.getElementById('schedule-switch');
    const scheduleOptions = document.getElementById('schedule-options');
    const sendBtn = document.getElementById('send-btn');

    scheduleSwitch.addEventListener('change', function() {
        if (this.checked) {
            scheduleOptions.style.display = 'block';
            sendBtn.innerHTML = '<i class="fas fa-clock"></i> Schedule Message';
        } else {
            scheduleOptions.style.display = 'none';
            sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Message';
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>