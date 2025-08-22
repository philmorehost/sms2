<?php
/**
 * Main Configuration File
 *
 * This file should contain all your site's configuration variables.
 * It's recommended to only use define() for constants in this file.
 */

// --- Database Credentials ---
// Your database host (usually 'localhost')
define('DB_HOST', 'localhost');

// Your database username
define('DB_USERNAME', 'root');

// Your database password
define('DB_PASSWORD', '');

// The name of your database
define('DB_NAME', 'bulksms');


// --- Site Settings ---
// The name of your website is now fetched from the database in bootstrap.php


// --- Payment Gateway Settings ---
// These are now managed in the admin settings panel.
// They will be fetched from the database when needed.
?>
