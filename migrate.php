<?php
// A simple PHP migration script

// Only allow running from the command line
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.");
}

require_once __DIR__ . '/app/bootstrap.php';

echo "Starting migration process...\n";

// 1. Check if migrations table exists, if not, create it.
$result = $conn->query("SHOW TABLES LIKE 'migrations'");
if ($result->num_rows == 0) {
    echo "Migrations table not found. Creating it...\n";
    $create_table_sql = "
        CREATE TABLE `migrations` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `migration` varchar(255) NOT NULL,
          `batch` int(11) NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    if ($conn->query($create_table_sql) === TRUE) {
        echo "Migrations table created successfully.\n";
    } else {
        die("Error creating migrations table: " . $conn->error . "\n");
    }
}

// 2. Get all migration files from the directory
$migration_files_path = __DIR__ . '/sql/migrations';
$all_files = scandir($migration_files_path);
$migration_files = array_filter($all_files, function($file) {
    return strpos($file, '.sql') !== false;
});
sort($migration_files); // Ensure they run in chronological order

// 3. Get all migrations that have already been run
$ran_migrations_result = $conn->query("SELECT migration FROM migrations");
$ran_migrations = [];
if ($ran_migrations_result->num_rows > 0) {
    while($row = $ran_migrations_result->fetch_assoc()) {
        $ran_migrations[] = $row['migration'];
    }
}

// 4. Determine which migrations to run
$migrations_to_run = array_diff($migration_files, $ran_migrations);

if (empty($migrations_to_run)) {
    echo "No new migrations to run. Database is up to date.\n";
    exit;
}

// 5. Run the new migrations
echo "Found " . count($migrations_to_run) . " new migrations to run.\n";

// Get the next batch number
$batch_result = $conn->query("SELECT MAX(batch) as max_batch FROM migrations");
$max_batch = $batch_result->fetch_assoc()['max_batch'] ?? 0;
$next_batch = $max_batch + 1;

$conn->begin_transaction();
try {
    $stmt = $conn->prepare("INSERT INTO migrations (migration, batch) VALUES (?, ?)");

    foreach ($migrations_to_run as $migration) {
        echo "Running migration: {$migration}...\n";
        $sql = file_get_contents($migration_files_path . '/' . $migration);

        // Execute the SQL from the migration file
        if ($conn->multi_query($sql)) {
            // It's important to clear results from multi_query
            while ($conn->next_result()) {
                if ($conn->more_results()) {
                    $conn->next_result();
                }
            }

            // Record the migration in the database
            $stmt->bind_param("si", $migration, $next_batch);
            $stmt->execute();
            echo "SUCCESS: {$migration}\n";
        } else {
            throw new Exception("Error running migration {$migration}: " . $conn->error);
        }
    }

    $stmt->close();
    $conn->commit();
    echo "All migrations completed successfully.\n";

} catch (Exception $e) {
    $conn->rollback();
    die("Migration failed: " . $e->getMessage() . "\n");
}

$conn->close();
?>
