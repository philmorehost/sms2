<?php
$page_title = 'Page Not Found';
// bootstrap.php is needed for database connection and helpers,
// which are used by header.php.
require_once 'app/bootstrap.php';
include 'includes/header.php';
?>

<div class="container text-center py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="display-1 text-primary">404</h1>
                    <h2 class="mb-4">Page Not Found</h2>
                    <p class="lead">Sorry, the page you are looking for does not exist, has been moved, or is temporarily unavailable.</p>
                    <hr>
                    <p>You can return to the dashboard or contact support if you believe this is an error.</p>
                    <a href="dashboard.php" class="btn btn-primary mt-3">
                        <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                    </a>
                    <a href="support.php" class="btn btn-secondary mt-3">
                        <i class="fas fa-life-ring"></i> Contact Support
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
