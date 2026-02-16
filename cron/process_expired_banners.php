<?php
// This script should be run by a cron job to remove expired banner ads.
require_once __DIR__ . '/../app/bootstrap.php';

// Find expired banners
$stmt = $conn->prepare("SELECT id, image_path FROM banner_ads WHERE expires_at IS NOT NULL AND expires_at < NOW()");
$stmt->execute();
$result = $stmt->get_result();
$expired_banners = [];
while ($row = $result->fetch_assoc()) {
    $expired_banners[] = $row;
}
$stmt->close();

if (empty($expired_banners)) {
    echo "No expired banners to process.\n";
    exit;
}

// Delete images and database records
$deleted_count = 0;
foreach ($expired_banners as $banner) {
    // Delete the image file
    if (file_exists(__DIR__ . '/../' . $banner['image_path'])) {
        unlink(__DIR__ . '/../' . $banner['image_path']);
    }

    // Delete the database record
    $delete_stmt = $conn->prepare("DELETE FROM banner_ads WHERE id = ?");
    $delete_stmt->bind_param("i", $banner['id']);
    if ($delete_stmt->execute()) {
        $deleted_count++;
    }
    $delete_stmt->close();
}

echo "Processed and deleted $deleted_count expired banner(s).\n";
?>
