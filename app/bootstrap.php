<?php
// Start output buffering to prevent "headers already sent" errors
ob_start();

// --- Core Application Bootstrap File ---

// 1. Load Configuration
require_once __DIR__ . '/config.php';

// 2. Define Dynamic Constants
// Auto-detect Site URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
// More robust way to find the base path
$script_path = dirname($_SERVER['SCRIPT_NAME']);
$base_path = ($script_path === '/' || $script_path === '\\') ? '' : preg_replace('/\/admin$/', '', $script_path);
define('SITE_URL', rtrim($protocol . $host . $base_path, '/'));

// 3. Establish Database Connection
$conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// 4. Run Installer / Migrator
function run_installer_and_migrations($conn) {
    $lock_file = __DIR__ . '/install.lock';

    // If the lock file doesn't exist, run the main installer
    if (!file_exists($lock_file)) {
        $sql_file_path = __DIR__ . '/../sql/install.sql';
        if (!file_exists($sql_file_path)) {
            die("Fatal Error: `sql/install.sql` file not found.");
        }
        $sql_content = file_get_contents($sql_file_path);
        if ($sql_content === false) {
            die("Fatal Error: Could not read `sql/install.sql`. Check permissions.");
        }

        // Execute the main SQL file
        if (!$conn->multi_query($sql_content)) {
             die("Fatal Error during initial installation: " . $conn->error);
        }
        // Clear results from multi_query
        while ($conn->more_results() && $conn->next_result()) {;}

        // --- Programmatically create the default admin user if it doesn't exist ---
        $admin_email = 'admin@example.com';
        $admin_user = 'admin';

        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check_stmt->bind_param("ss", $admin_user, $admin_email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows === 0) {
            $admin_pass = 'password';
            $admin_pass_hashed = password_hash($admin_pass, PASSWORD_DEFAULT);
            $admin_api_key = 'admin_api_key_' . bin2hex(random_bytes(16));
            $admin_ref_code = 'ADMIN' . strtoupper(bin2hex(random_bytes(3)));

            $stmt = $conn->prepare("INSERT INTO users (username, email, password, is_admin, api_key, referral_code) VALUES (?, ?, ?, 1, ?, ?)");
            $stmt->bind_param("sssss", $admin_user, $admin_email, $admin_pass_hashed, $admin_api_key, $admin_ref_code);
            $stmt->execute();
            $stmt->close();
        }
        $check_stmt->close();
        // --- End admin creation ---

        // Create the lock file to prevent this from running again
        file_put_contents($lock_file, 'Installed on: ' . date('Y-m-d H:i:s'));
    }

    // --- Always run migrations for future updates ---

    // First, ensure the migrations table itself exists to prevent fatal errors.
    $conn->query("CREATE TABLE IF NOT EXISTS `migrations` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `migration` varchar(255) NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      UNIQUE KEY `migration` (`migration`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $migrations_ran = [];
    $stmt_migrations = $conn->prepare("SELECT migration FROM migrations");
    if ($stmt_migrations) {
        $stmt_migrations->execute();
        $result = $stmt_migrations->get_result();
        while($row = $result->fetch_assoc()) {
            $migrations_ran[] = $row['migration'];
        }
        $stmt_migrations->close();
    }

    $migration_files = glob(__DIR__ . '/../sql/migrations/*.sql');
    sort($migration_files);

    foreach ($migration_files as $file) {
        $filename = basename($file);
        if (!in_array($filename, $migrations_ran)) {
            $sql = file_get_contents($file);
            // Split the SQL file into individual queries
            $queries = explode(';', $sql);

            $all_successful = true;
            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query)) {
                    try {
                        $conn->query($query);
                    } catch (mysqli_sql_exception $e) {
                        // If an exception is caught, check if the error code is ignorable.
                        // These errors happen if a migration is re-run after a partial failure.
                        // 1060: Duplicate column name
                        // 1061: Duplicate key name
                        // 1050: Table already exists
                        // 1022: Can't write; duplicate key in table (for constraints)
                        $ignorable_errors = [1060, 1061, 1050, 1022];
                        if (!in_array($e->getCode(), $ignorable_errors)) {
                            // If it's not an ignorable error, this is a real problem.
                            $all_successful = false;
                            die("Error running migration: $filename. Query: [$query]. Error: " . $e->getMessage() . " (Code: " . $e->getCode() . ")");
                        }
                        // If the error code is in the ignorable list, we simply continue.
                    }
                }
            }

            if ($all_successful) {
                // Log the migration only if all queries were successful
                $stmt = $conn->prepare("INSERT INTO migrations (migration) VALUES (?)");
                $stmt->bind_param("s", $filename);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}
run_installer_and_migrations($conn);


// 4.5. Load Site Settings and Define Constants
$all_settings = [];
$settings_stmt = $conn->prepare("SELECT * FROM settings");
if ($settings_stmt) {
    $settings_stmt->execute();
    $settings_result = $settings_stmt->get_result();
    while ($row = $settings_result->fetch_assoc()) {
        $all_settings[$row['setting_key']] = $row['setting_value'];
    }
    $settings_stmt->close();
}
// Define SITE_NAME from the database, with a fallback
if (!defined('SITE_NAME')) {
    define('SITE_NAME', $all_settings['site_name'] ?? 'Bulk SMS Platform');
}
// Make settings globally available to avoid re-querying in helpers
$GLOBALS['app_settings'] = $all_settings;

// Set the application's default timezone based on the setting
if (!empty($all_settings['site_timezone'])) {
    date_default_timezone_set($all_settings['site_timezone']);
}


// 5. IP Ban Check
function get_user_ip() {
    foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'] as $key){
        if (array_key_exists($key, $_SERVER) === true){
            foreach (explode(',', $_SERVER[$key]) as $ip){
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false){
                    return $ip;
                }
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'];
}

$user_ip = get_user_ip();
$ban_stmt = $conn->prepare("SELECT id FROM banned WHERE type = 'ip' AND value = ?");
$ban_stmt->bind_param("s", $user_ip);
$ban_stmt->execute();
$ban_result = $ban_stmt->get_result();
if ($ban_result->num_rows > 0) {
    http_response_code(403);
    die("<h1>403 Forbidden</h1><p>Your IP address has been banned from accessing this service.</p>");
}
$ban_stmt->close();


// 6. Secure Session Start
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}
ini_set('session.cookie_samesite', 'Lax');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 7. Load Helpers
require_once __DIR__ . '/helpers.php';

// 8. Fetch Logged-in User Data (if applicable)
$current_user = null;
$user_id_to_fetch = null;

if (isset($_SESSION['user_id'])) {
    $user_id_to_fetch = $_SESSION['user_id'];
} elseif (isset($_SESSION['admin_id'])) {
    // This handles the case where an admin is logged in.
    $user_id_to_fetch = $_SESSION['admin_id'];
}

if ($user_id_to_fetch !== null) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id_to_fetch);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $current_user = $result->fetch_assoc();

        // --- OTP Verification Check ---
        // If user is not verified and not an admin, restrict access.
        $is_verification_page = in_array(basename($_SERVER['PHP_SELF']), ['verify-email.php', 'logout.php']);
        if (isset($current_user['is_email_verified']) && $current_user['is_email_verified'] == 0 && ($current_user['is_admin'] ?? 0) == 0 && !$is_verification_page) {
            // Set a flash message to inform the user why they were redirected.
            if (session_status() == PHP_SESSION_NONE) { session_start(); }
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => 'Your account is not verified. Please check your email for the OTP.'
            ];
            header("Location: " . SITE_URL . "/verify-email.php?email=" . urlencode($current_user['email']));
            exit();
        }
    } else {
        // Invalid user ID in session, destroy it
        session_destroy();
        // Redirect to the appropriate login page
        if (defined('IS_ADMIN_AREA')) {
             header("Location: " . SITE_URL . "/admin/login.php");
        } else {
             header("Location: " . SITE_URL . "/login.php");
        }
        exit();
    }
    $stmt->close();
}
?>
