<?php
// Kunin ang mga configuration mula sa Environment Variables
$host     = getenv('DB_HOST') ?: 'localhost'; 
$dbname   = getenv('DB_NAME') ?: 'gso_db'; 
$username = getenv('DB_USER') ?: 'gso_admin';
$password = getenv('DB_PASS') ?: 'sept2904'; 

// Initialize DSN variable para accessible sa catch block
$dsn = "";

try {
    // Kung ang host ay nagsisimula sa /cloudsql/, gamitin ang unix_socket
    if (strpos($host, '/cloudsql/') === 0) {
        $dsn = "mysql:unix_socket=$host;dbname=$dbname;charset=utf8mb4";
    } else {
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    }

    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // DITO: Ipi-print nito ang DSN na ginamit at ang mismong error message
    die("Database connection failed (DSN: $dsn): " . $e->getMessage());
}

require_once __DIR__ . "/../includes/schema_helper.php";
require_once __DIR__ . "/../includes/maintenance_helper.php";

$runMigrations = getenv("GSO_RUN_MIGRATIONS");
if ($runMigrations !== "0") {
    ensureSystemSchema($pdo);
}

syncMaintenanceSchedules($pdo);
?>