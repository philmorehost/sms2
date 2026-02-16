<?php
$page_title = 'Number Extractor';
require_once 'app/bootstrap.php';

$extracted_text = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['extract'])) {
    $raw_text = '';

    // Check if text was pasted
    if (!empty($_POST['text_input'])) {
        $raw_text = $_POST['text_input'];
    }
    // Check if a file was uploaded
    elseif (isset($_FILES['file_input']) && $_FILES['file_input']['error'] == 0) {
        $file = $_FILES['file_input'];
        $allowed_types = ['text/plain', 'text/csv'];
        if (in_array($file['type'], $allowed_types)) {
            $raw_text = file_get_contents($file['tmp_name']);
        } else {
            $error = "Unsupported file type. Please upload a .txt or .csv file.";
        }
    } else {
        $error = "Please paste text or upload a file.";
    }

    if (empty($error) && !empty($raw_text)) {
        // Regex to find phone numbers (sequences of 10-15 digits, possibly with leading +)
        preg_match_all('/[+\d\s-()]{10,20}/', $raw_text, $matches);

        $numbers = [];
        if (!empty($matches[0])) {
            foreach ($matches[0] as $match) {
                // Remove non-digit characters
                $number = preg_replace('/\D/', '', $match);
                // Basic validation for length
                if (strlen($number) >= 10 && strlen($number) <= 15) {
                    $numbers[] = $number;
                }
            }
        }

        // Remove duplicates and join into a string
        if (!empty($numbers)) {
            $unique_numbers = array_unique($numbers);
            $extracted_text = implode("\n", $unique_numbers);
        } else {
            $error = "No phone numbers could be found in the provided source.";
        }
    }
}

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 m-0">Number Extractor Tool</h1>
</div>

<?php if($error): ?>
<div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form action="number-extractor.php" method="POST" enctype="multipart/form-data">
            <div class="row">
                <!-- Input Column -->
                <div class="col-md-6">
                    <h5>Step 1: Provide Your Source</h5>
                    <p>Paste text directly or upload a file to extract numbers from.</p>

                    <ul class="nav nav-tabs" id="inputTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="paste-text-tab" data-bs-toggle="tab" data-bs-target="#paste-text" type="button" role="tab">Paste Text</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="upload-file-tab" data-bs-toggle="tab" data-bs-target="#upload-file" type="button" role="tab">Upload File</button>
                        </li>
                    </ul>
                    <div class="tab-content border border-top-0 p-3" id="inputTabContent">
                        <div class="tab-pane fade show active" id="paste-text" role="tabpanel">
                            <textarea name="text_input" class="form-control" rows="10" placeholder="Paste your text containing phone numbers here..."></textarea>
                        </div>
                        <div class="tab-pane fade" id="upload-file" role="tabpanel">
                            <label for="formFile" class="form-label">Supported files: .txt, .csv</label>
                            <input class="form-control" type="file" name="file_input" id="formFile" accept=".txt,.csv">
                            <div class="form-text">Support for .pdf and .vcf is currently unavailable.</div>
                        </div>
                    </div>
                    <div class="d-grid mt-3">
                        <button type="submit" name="extract" class="btn btn-primary btn-lg">Extract Phone Numbers</button>
                    </div>
                </div>

                <!-- Output Column -->
                <div class="col-md-6">
                    <h5>Step 2: Get Your Results</h5>
                    <p>Extracted numbers will appear below. Duplicates will be removed automatically.</p>

                    <div class="form-group">
                        <textarea id="results-textarea" class="form-control" rows="12" readonly placeholder="Results will be displayed here..."><?php echo $extracted_text; ?></textarea>
                    </div>
                    <button type="button" id="copy-btn" class="btn btn-secondary" <?php if(empty($extracted_text)) echo 'disabled'; ?>>Copy to Clipboard</button>
                    <button type="button" id="email-btn" class="btn btn-info" <?php if(empty($extracted_text)) echo 'disabled'; ?>>Email Results</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const copyBtn = document.getElementById('copy-btn');
    const emailBtn = document.getElementById('email-btn');
    const resultsTextarea = document.getElementById('results-textarea');

    if (copyBtn) {
        copyBtn.addEventListener('click', function() {
            resultsTextarea.select();
            document.execCommand('copy');
            alert('Extracted numbers copied to clipboard!');
        });
    }

    if (emailBtn) {
        emailBtn.addEventListener('click', function() {
            const results = resultsTextarea.value;
            if (results.trim() === '') {
                alert('There are no results to email.');
                return;
            }

            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sending...';

            fetch('ajax/email_tool_results.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `results=${encodeURIComponent(results)}&tool_name=Number Extractor`
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
                this.textContent = 'Email Results';
            });
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
