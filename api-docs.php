<?php
$page_title = 'Developer API';
require_once 'app/bootstrap.php';

include 'includes/header.php';
?>
<style>
    .api-endpoint { border-left: 3px solid var(--primary-color); padding-left: 1rem; margin-bottom: 2rem; }
    .api-endpoint .method { font-weight: 600; }
    .api-endpoint .url { font-family: monospace; background-color: var(--light-color); padding: 0.2rem 0.5rem; border-radius: 4px; }
    pre { background-color: var(--dark-color); color: #fff; padding: 1rem; border-radius: 0.5rem; }
    code { font-family: monospace; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 m-0">Developer API Documentation</h1>
</div>

<div class="card">
    <div class="card-body">
        <h4 class="card-title">Authentication</h4>
        <p>
            All API requests must be authenticated using your unique API key.
            Your API key must be included in the body of your `POST` requests with the parameter name `token`.
        </p>
        <div class="mb-4 p-3 border rounded">
            <?php
            // We need to fetch the user's current status
            $status_stmt = $conn->prepare("SELECT api_access_status, api_key FROM users WHERE id = ?");
            $status_stmt->bind_param("i", $user['id']);
            $status_stmt->execute();
            $api_user = $status_stmt->get_result()->fetch_assoc();
            $status_stmt->close();

            switch ($api_user['api_access_status']) {
                case 'approved':
                    ?>
                    <label for="api_key" class="form-label">Your API Key</label>
                    <div class="input-group">
                        <input type="text" id="api_key" class="form-control" value="<?php echo htmlspecialchars($api_user['api_key']); ?>" readonly>
                        <button class="btn btn-outline-secondary" id="copyApiBtn">Copy</button>
                    </div>
                    <small class="form-text text-muted">Keep your API key secret. Do not share it publicly.</small>
                    <?php
                    break;
                case 'requested':
                    ?>
                    <div class="alert alert-info">
                        <h5 class="alert-heading">Request Pending</h5>
                        <p class="mb-0">Your request for API access is currently pending review by an administrator. You will be notified once it has been processed.</p>
                    </div>
                    <?php
                    break;
                case 'denied':
                    ?>
                    <div class="alert alert-danger">
                        <h5 class="alert-heading">Request Denied</h5>
                        <p>Unfortunately, your previous request for API access was denied.</p>
                        <hr>
                        <button class="btn btn-warning" id="requestApiAccessBtn">Request Access Again</button>
                    </div>
                    <?php
                    break;
                case 'none':
                default:
                    ?>
                    <div class="alert alert-warning">
                        <h5 class="alert-heading">API Access Required</h5>
                        <p>To use our API, you must first request access. An administrator will review your request.</p>
                        <hr>
                        <button class="btn btn-primary" id="requestApiAccessBtn">Request API Access</button>
                    </div>
                    <?php
                    break;
            }
            ?>
        </div>

        <hr>

        <h4 class="card-title mt-4">API Endpoints</h4>

        <!-- Check Balance Endpoint -->
        <div id="check-balance" class="api-endpoint">
            <h5>Check Wallet Balance</h5>
            <p>
                <span class="method text-success">POST</span>
                <span class="url"><?php echo SITE_URL; ?>/api/balance.php</span>
            </p>
            <p>This endpoint allows you to check your current wallet balance.</p>

            <h6>Parameters</h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                     <thead>
                        <tr><th>Parameter</th><th>Type</th><th>Description</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>token</code></td><td>string</td><td><strong>Required.</strong> Your API Key.</td></tr>
                    </tbody>
                </table>
            </div>

            <h6>Example Success Response</h6>
            <pre><code>
{
    "status": "success",
    "error_code": "000",
    "balance": "1234.50"
}
            </code></pre>
        </div>
        <!-- End Check Balance Endpoint -->

        <!-- Send SMS Endpoint -->
        <div id="send-sms" class="api-endpoint">
            <h5>Send Bulk SMS</h5>
            <p>
                <span class="method text-success">POST</span>
                <span class="url"><?php echo SITE_URL; ?>/api/sms.php</span>
            </p>
            <p>This endpoint sends a standard text message to one or more recipients.</p>

            <h6>Parameters</h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                     <thead>
                        <tr><th>Parameter</th><th>Type</th><th>Description</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>token</code></td><td>string</td><td><strong>Required.</strong> Your API Key.</td></tr>
                        <tr><td><code>senderID</code></td><td>string</td><td><strong>Required.</strong> An approved Sender ID.</td></tr>
                        <tr><td><code>recipients</code></td><td>string</td><td><strong>Required.</strong> A comma-separated list of recipient phone numbers.</td></tr>
                        <tr><td><code>message</code></td><td>string</td><td><strong>Required.</strong> The content of the SMS message.</td></tr>
                    </tbody>
                </table>
            </div>
             <h6>Example Request (cURL)</h6>
            <pre><code>
curl --location --request POST '<?php echo SITE_URL; ?>/api/sms.php' \
--header 'Content-Type: application/x-www-form-urlencoded' \
--data-urlencode 'token=<?php echo htmlspecialchars($user['api_key']); ?>' \
--data-urlencode 'senderID=YourSenderID' \
--data-urlencode 'recipients=2348012345678,2349087654321' \
--data-urlencode 'message=This is a test message from the API.'
            </code></pre>
        </div>
        <!-- End Send SMS Endpoint -->

        <!-- Send Corporate SMS Endpoint -->
        <div id="send-corporate-sms" class="api-endpoint">
            <h5>Send Bulk SMS (Corporate)</h5>
            <p>
                <span class="method text-primary">POST</span>
                <span class="url"><?php echo SITE_URL; ?>/api/corporate.php</span>
            </p>
            <p>This endpoint sends a message via the Corporate route, which is suitable for transactional messages and alerts.</p>

            <h6>Parameters</h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                     <thead>
                        <tr><th>Parameter</th><th>Type</th><th>Description</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>token</code></td><td>string</td><td><strong>Required.</strong> Your API Key.</td></tr>
                        <tr><td><code>senderID</code></td><td>string</td><td><strong>Required.</strong> An approved Sender ID.</td></tr>
                        <tr><td><code>recipients</code></td><td>string</td><td><strong>Required.</strong> A comma-separated list of recipient phone numbers.</td></tr>
                        <tr><td><code>message</code></td><td>string</td><td><strong>Required.</strong> The content of the SMS message.</td></tr>
                    </tbody>
                </table>
            </div>
             <h6>Example Request (cURL)</h6>
            <pre><code>
curl --location --request POST '<?php echo SITE_URL; ?>/api/corporate.php' \
--header 'Content-Type: application/x-www-form-urlencoded' \
--data-urlencode 'token=<?php echo htmlspecialchars($api_user['api_key']); ?>' \
--data-urlencode 'senderID=YourSenderID' \
--data-urlencode 'recipients=2348012345678' \
--data-urlencode 'message=Your one-time password is 12345.'
            </code></pre>
        </div>
        <!-- End Send Corporate SMS Endpoint -->

        <!-- Send Voice SMS Endpoint -->
        <div id="send-voice" class="api-endpoint">
            <h5>Send Voice SMS (Text-to-Speech)</h5>
            <p>
                <span class="method text-success">POST</span>
                <span class="url"><?php echo SITE_URL; ?>/api/voice.php</span>
            </p>
            <p>This endpoint converts your text message to speech and calls the recipient's phone number.</p>

            <h6>Parameters</h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                     <thead>
                        <tr><th>Parameter</th><th>Type</th><th>Description</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>token</code></td><td>string</td><td><strong>Required.</strong> Your API Key.</td></tr>
                        <tr><td><code>callerID</code></td><td>string</td><td><strong>Required.</strong> An approved Caller ID.</td></tr>
                        <tr><td><code>recipients</code></td><td>string</td><td><strong>Required.</strong> A comma-separated list of recipient phone numbers.</td></tr>
                        <tr><td><code>message</code></td><td>string</td><td><strong>Required.</strong> The text to be converted to speech.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- End Send Voice SMS Endpoint -->

        <!-- Send Voice from Audio File Endpoint -->
        <div id="send-voice-audio" class="api-endpoint">
            <h5>Send Voice Message (from Audio File)</h5>
            <p>
                <span class="method text-success">POST</span>
                <span class="url"><?php echo SITE_URL; ?>/api/voice_audio.php</span>
            </p>
            <p>This endpoint sends a voice message using a pre-recorded audio file URL.</p>

            <h6>Parameters</h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                        <thead>
                        <tr><th>Parameter</th><th>Type</th><th>Description</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>token</code></td><td>string</td><td><strong>Required.</strong> Your API Key.</td></tr>
                        <tr><td><code>callerID</code></td><td>string</td><td><strong>Required.</strong> An approved Caller ID.</td></tr>
                        <tr><td><code>recipients</code></td><td>string</td><td><strong>Required.</strong> A comma-separated list of recipient phone numbers.</td></tr>
                        <tr><td><code>audio</code></td><td>string</td><td><strong>Required.</strong> A public URL to an MP3 audio file.</td></tr>
                    </tbody>
                </table>
            </div>
            <h6>Example Request (cURL)</h6>
            <pre><code>
curl --location --request POST '<?php echo SITE_URL; ?>/api/voice_audio.php' \
--header 'Content-Type: application/x-www-form-urlencoded' \
--data-urlencode 'token=<?php echo htmlspecialchars($api_user['api_key']); ?>' \
--data-urlencode 'callerID=YourCallerID' \
--data-urlencode 'recipients=2348012345678' \
--data-urlencode 'audio=https://example.com/audio.mp3'
    </code></pre>
        </div>
        <!-- End Send Voice from Audio File Endpoint -->

        <!-- Send WhatsApp Endpoint -->
        <div id="send-whatsapp" class="api-endpoint">
            <h5>Send WhatsApp Message</h5>
            <p>
                <span class="method text-success">POST</span>
                <span class="url"><?php echo SITE_URL; ?>/api/whatsapp.php</span>
            </p>
            <p>This endpoint sends a message using a pre-approved WhatsApp template.</p>

            <h6>Parameters</h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                     <thead>
                        <tr><th>Parameter</th><th>Type</th><th>Description</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>token</code></td><td>string</td><td><strong>Required.</strong> Your API Key.</td></tr>
                        <tr><td><code>recipient</code></td><td>string</td><td><strong>Required.</strong> The recipient's phone number.</td></tr>
                        <tr><td><code>template_code</code></td><td>string</td><td><strong>Required.</strong> The code for your approved WhatsApp template.</td></tr>
                        <tr><td><code>parameters</code></td><td>string</td><td><em>Optional.</em> Comma-separated values for the message body variables.</td></tr>
                        <tr><td><code>button_parameters</code></td><td>string</td><td><em>Optional.</em> Comma-separated values for button URL variables.</td></tr>
                        <tr><td><code>header_parameters</code></td><td>string</td><td><em>Optional.</em> Comma-separated values for header variables (e.g., image URLs).</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- End Send WhatsApp Endpoint -->

        <!-- Generate and Send OTP Endpoint -->
        <div id="generate-otp" class="api-endpoint">
            <h5>Generate and Send OTP</h5>
            <p>
                <span class="method text-success">POST</span>
                <span class="url"><?php echo SITE_URL; ?>/api/sendotp.php</span>
            </p>
            <p>This endpoint generates a new OTP and sends it to the recipient, handling the generation and verification flow. This is the recommended method for most OTP use cases.</p>
            <h6>Parameters</h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr><th>Parameter</th><th>Type</th><th>Description</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>token</code></td><td>string</td><td><strong>Required.</strong> Your API Key.</td></tr>
                        <tr><td><code>senderID</code></td><td>string</td><td><strong>Required.</strong> An approved Sender ID.</td></tr>
                        <tr><td><code>recipients</code></td><td>string</td><td><strong>Required.</strong> The recipient's phone number.</td></tr>
                        <tr><td><code>appnamecode</code></td><td>string</td><td><strong>Required.</strong> Your application's name or code.</td></tr>
                        <tr><td><code>templatecode</code></td><td>string</td><td><strong>Required.</strong> The unique code for your approved OTP template.</td></tr>
                        <tr><td><code>otp_type</code></td><td>string</td><td><em>Optional.</em> Type of OTP. Can be <code>NUMERIC</code>, <code>ALPHANUMERIC</code>, or <code>ALPHABETIC</code>. Defaults to <code>NUMERIC</code>.</td></tr>
                        <tr><td><code>otp_length</code></td><td>integer</td><td><em>Optional.</em> Length of the OTP. Defaults to <code>6</code>.</td></tr>
                        <tr><td><code>otp_duration</code></td><td>integer</td><td><em>Optional.</em> Duration in minutes for which the OTP is valid. Defaults to <code>5</code>.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- End Generate and Send OTP Endpoint -->

        <!-- Send Pre-Generated OTP Endpoint -->
        <div id="send-pregenerated-otp" class="api-endpoint">
            <h5>Send Pre-Generated OTP</h5>
            <p>
                <span class="method text-success">POST</span>
                <span class="url"><?php echo SITE_URL; ?>/api/send_otp.php</span>
            </p>
            <p>This endpoint sends an OTP that you have already generated within your own application.</p>

            <h6>Parameters</h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr><th>Parameter</th><th>Type</th><th>Description</th></tr>
                </thead>
                <tbody>
                    <tr><td><code>token</code></td><td>string</td><td><strong>Required.</strong> Your API Key.</td></tr>
                    <tr><td><code>senderID</code></td><td>string</td><td><strong>Required.</strong> An approved Sender ID.</td></tr>
                    <tr><td><code>recipients</code></td><td>string</td><td><strong>Required.</strong> The recipient's phone number.</td></tr>
                    <tr><td><code>otp</code></td><td>string</td><td><strong>Required.</strong> The One-Time Password you have generated.</td></tr>
                    <tr><td><code>templatecode</code></td><td>string</td><td><strong>Required.</strong> The unique code for your approved OTP template.</td></tr>
                </tbody>
                </table>
            </div>
        </div>
        <!-- End Send Pre-Generated OTP Endpoint -->

        <!-- Verify OTP Endpoint -->
        <div id="verify-otp" class="api-endpoint">
            <h5>Verify OTP</h5>
            <p>
                <span class="method text-success">POST</span>
                <span class="url"><?php echo SITE_URL; ?>/api/verifyotp.php</span>
            </p>
            <p>This endpoint verifies an OTP that was previously sent to a recipient.</p>

            <h6>Parameters</h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr><th>Parameter</th><th>Type</th><th>Description</th></tr>
                </thead>
                <tbody>
                    <tr><td><code>token</code></td><td>string</td><td><strong>Required.</strong> Your API Key.</td></tr>
                    <tr><td><code>recipient</code></td><td>string</td><td><strong>Required.</strong> The recipient's phone number.</td></tr>
                    <tr><td><code>otp</code></td><td>string</td><td><strong>Required.</strong> The One-Time Password to be verified.</td></tr>
                </tbody>
                </table>
            </div>
        </div>
        <!-- End Verify OTP Endpoint -->

        <!-- Submit Sender ID Endpoint -->
        <div id="submit-senderid" class="api-endpoint">
            <h5>Submit Sender ID</h5>
            <p>
                <span class="method text-success">POST</span>
                <span class="url"><?php echo SITE_URL; ?>/api/senderID.php</span>
            </p>
            <p>This endpoint allows you to programmatically submit a new Sender ID for approval.</p>

            <h6>Parameters</h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                     <thead>
                        <tr><th>Parameter</th><th>Type</th><th>Description</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>token</code></td><td>string</td><td><strong>Required.</strong> Your API Key.</td></tr>
                        <tr><td><code>senderID</code></td><td>string</td><td><strong>Required.</strong> The Sender ID you want to register (max 11 characters).</td></tr>
                        <tr><td><code>message</code></td><td>string</td><td><strong>Required.</strong> A sample message you intend to send with this Sender ID.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- End Submit Sender ID Endpoint -->

        <!-- Check Sender ID Endpoint -->
        <div id="check-senderid" class="api-endpoint">
            <h5>Check Sender ID Status</h5>
            <p>
                <span class="method text-success">POST</span>
                <span class="url"><?php echo SITE_URL; ?>/api/check_senderID.php</span>
            </p>
            <p>This endpoint checks the approval status of a Sender ID you have submitted.</p>

            <h6>Parameters</h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                     <thead>
                        <tr><th>Parameter</th><th>Type</th><th>Description</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>token</code></td><td>string</td><td><strong>Required.</strong> Your API Key.</td></tr>
                        <tr><td><code>senderID</code></td><td>string</td><td><strong>Required.</strong> The Sender ID you want to check.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- End Check Sender ID Endpoint -->

        <!-- Submit Caller ID Endpoint -->
        <div id="submit-callerid" class="api-endpoint">
            <h5>Submit Caller ID</h5>
            <p>
                <span class="method text-success">POST</span>
                <span class="url"><?php echo SITE_URL; ?>/api/callerID.php</span>
            </p>
            <p>This endpoint allows you to programmatically submit a new Caller ID for approval.</p>

            <h6>Parameters</h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                     <thead>
                        <tr><th>Parameter</th><th>Type</th><th>Description</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>token</code></td><td>string</td><td><strong>Required.</strong> Your API Key.</td></tr>
                        <tr><td><code>callerID</code></td><td>string</td><td><strong>Required.</strong> The phone number you want to register as a Caller ID.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- End Submit Caller ID Endpoint -->

        <!-- Check Caller ID Endpoint -->
        <div id="check-callerid" class="api-endpoint">
            <h5>Check Caller ID Status</h5>
            <p>
                <span class="method text-success">POST</span>
                <span class="url"><?php echo SITE_URL; ?>/api/check_callerID.php</span>
            </p>
            <p>This endpoint checks the approval status of a Caller ID you have submitted.</p>

            <h6>Parameters</h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                     <thead>
                        <tr><th>Parameter</th><th>Type</th><th>Description</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>token</code></td><td>string</td><td><strong>Required.</strong> Your API Key.</td></tr>
                        <tr><td><code>callerID</code></td><td>string</td><td><strong>Required.</strong> The Caller ID you want to check.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- End Check Caller ID Endpoint -->

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const requestBtn = document.getElementById('requestApiAccessBtn');
    const copyBtn = document.getElementById('copyApiBtn');

    if (requestBtn) {
        requestBtn.addEventListener('click', function() {
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...';

            fetch('ajax/request_api_access.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Replace the button's parent container with the success message
                    const container = requestBtn.closest('.alert');
                    container.classList.remove('alert-warning', 'alert-danger');
                    container.classList.add('alert-info');
                    container.innerHTML = `
                        <h5 class="alert-heading">Request Pending</h5>
                        <p class="mb-0">Your request for API access is currently pending review by an administrator. You will be notified once it has been processed.</p>`;
                } else {
                    alert('Error: ' + data.message);
                    this.disabled = false;
                    this.textContent = 'Request API Access';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                this.disabled = false;
                this.textContent = 'Request API Access';
            });
        });
    }

    if (copyBtn) {
        copyBtn.addEventListener('click', function() {
            const apiKeyInput = document.getElementById('api_key');
            navigator.clipboard.writeText(apiKeyInput.value).then(() => {
                const originalText = this.textContent;
                this.textContent = 'Copied!';
                setTimeout(() => {
                    this.textContent = originalText;
                }, 2000);
            }, (err) => {
                alert('Failed to copy API key.');
            });
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
