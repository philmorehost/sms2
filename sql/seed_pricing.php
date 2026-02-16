<?php
// Only allow running from the command line
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.");
}

require_once __DIR__ . '/../app/bootstrap.php';

// Only allow running from the command line
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.");
}

echo "Seeding global pricing data...\n";
$result = seed_global_pricing_data($conn);

if ($result['success']) {
    echo "SUCCESS: " . $result['message'] . "\n";
} else {
    echo "ERROR: " . $result['message'] . "\n";
}
?>
