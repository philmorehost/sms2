<?php
$page_title = 'Phone Number Filter';
require_once 'app/bootstrap.php';

$number_groups = [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['split'])) {
    $number_list_raw = $_POST['number_list'] ?? '';
    $group_count = filter_input(INPUT_POST, 'group_count', FILTER_VALIDATE_INT);

    if (!empty($number_list_raw) && $group_count > 1) {
        // Sanitize and create a clean array of numbers
        $normalized_list = str_replace(',', "\n", $number_list_raw);
        $numbers_array = explode("\n", $normalized_list);
        $clean_numbers = array_filter(array_map('trim', $numbers_array));
        $unique_numbers = array_unique($clean_numbers);

        if (!empty($unique_numbers)) {
            // Calculate chunk size
            $total_numbers = count($unique_numbers);
            $chunk_size = ceil($total_numbers / $group_count);

            // Split the array into groups
            $number_groups = array_chunk($unique_numbers, $chunk_size);
        } else {
            $error = "No valid numbers were found in your input.";
        }
    } else {
        $error = "Please paste a list of numbers and select a valid group count (at least 2).";
    }
}


include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 m-0">Phone Number Filter / Splitter</h1>
</div>

<?php if($error): ?>
<div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <p>Paste your list of phone numbers, choose how many groups to split them into, and the tool will create smaller batches for you.</p>
        <form action="number-filter.php" method="POST">
            <div class="row">
                <!-- Input Column -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="number_list" class="form-label"><h5>Paste Phone Numbers</h5></label>
                        <textarea name="number_list" id="number_list" class="form-control" rows="15" placeholder="Paste numbers here, one per line or separated by commas..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="group_count" class="form-label"><h5>Number of Groups to Create</h5></label>
                        <input type="number" name="group_count" id="group_count" class="form-control" min="2" value="2" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" name="split" class="btn btn-primary btn-lg">Split Numbers</button>
                    </div>
                </div>

                <!-- Output Column -->
                <div class="col-md-6">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5>Results</h5>
                        <?php if (!empty($number_groups)): ?>
                            <button type="button" id="email-results-btn" class="btn btn-info btn-sm">Email All Results</button>
                        <?php endif; ?>
                    </div>

                    <div class="results-container">
                        <?php if (!empty($number_groups)): ?>
                            <?php foreach ($number_groups as $index => $group): ?>
                                <div class="mb-3 result-group">
                                    <label class="form-label"><strong>Group <?php echo $index + 1; ?></strong> (<?php echo count($group); ?> numbers)</label>
                                    <div class="input-group">
                                        <textarea class="form-control" rows="5" readonly><?php echo htmlspecialchars(implode("\n", $group)); ?></textarea>
                                        <button class="btn btn-outline-secondary copy-btn" type="button">Copy</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-secondary">
                                The split groups of numbers will appear here after you click the "Split Numbers" button.
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const copyButtons = document.querySelectorAll('.copy-btn');
    copyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const textarea = this.previousElementSibling;
            textarea.select();
            document.execCommand('copy');
            // Optional: Provide user feedback
            this.textContent = 'Copied!';
            setTimeout(() => {
                this.textContent = 'Copy';
            }, 2000);
        });
    });

    const emailBtn = document.getElementById('email-results-btn');
    if(emailBtn) {
        emailBtn.addEventListener('click', function() {
            let allResults = '';
            const resultGroups = document.querySelectorAll('.result-group');
            resultGroups.forEach((group, index) => {
                const groupLabel = group.querySelector('label').textContent;
                const groupTextarea = group.querySelector('textarea');
                allResults += `--- ${groupLabel} ---\n`;
                allResults += groupTextarea.value + '\n\n';
            });

            if (allResults.trim() === '') {
                alert('There are no results to email.');
                return;
            }

            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sending...';

            fetch('ajax/email_tool_results.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `results=${encodeURIComponent(allResults)}&tool_name=Number Filter`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Results have been emailed successfully.');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Emailing error:', error);
                alert('An error occurred while emailing the results.');
            })
            .finally(() => {
                this.disabled = false;
                this.textContent = 'Email All Results';
            });
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
