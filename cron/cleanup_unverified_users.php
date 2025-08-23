<?php
// This script is intended to be run from the command line via a cron job.
// It deletes users who registered but did not verify their email within 24 hours.

require_once __DIR__ . '/../app/bootstrap.php';

echo "Cron Job: Cleaning up unverified users...\n";

$delete_query = "DELETE FROM users WHERE is_email_verified = 0 AND created_at < NOW() - INTERVAL 24 HOUR";

$stmt = $conn->prepare($delete_query);

if ($stmt) {
    $stmt->execute();
    $deleted_count = $stmt->affected_rows;
    echo "Successfully deleted " . $deleted_count . " unverified user(s).\n";
    $stmt->close();
} else {
    echo "An error occurred while trying to delete unverified users: " . $conn->error . "\n";
    error_log("Cron job failed: cleanup_unverified_users.php - " . $conn->error);
}

echo "Cron job finished.\n";
?>
