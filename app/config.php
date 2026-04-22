<?php
// app/config.php
// --- Database Configuration ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'bulksms');       // Updated based on install.sql
define('DB_USERNAME', 'root');      // REPLACE WITH ACTUAL DB USERNAME
define('DB_PASSWORD', '');          // REPLACE WITH ACTUAL DB PASSWORD

// --- API & Security Configuration ---
define('JWT_SECRET', 'replace_with_a_secure_random_string');

// Note: SITE_URL and other constants are auto-detected in bootstrap.php
?>
