<?php
// Kunin ang mga configuration mula sa Environment Variables
$host     = getenv('DB_HOST') ?: 'localhost'; 
$dbname   = getenv('DB_NAME') ?: 'gso_db'; // Siguraduhing 'gso_database' ito kung ito ang pangalan sa Cloud SQL
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: 'sept2904'; 

try {
    // Kung ang host ay nagsisimula sa /cloudsql/, gamitin ang unix_socket
    if (strpos($host, '/cloudsql/') === 0) {
        // TAMA: unix_socket ang gamit, walang 'host' sa DSN
        $dsn = "mysql:unix_socket=$host;dbname=$dbname;charset=utf8mb4";
    } else {
        // TAMA: Para sa XAMPP/Local, gamitin ang host
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    }

    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Pakitanggal ang $e->getMessage() sa production para safe, 
    // pero panatilihin muna habang nag-aayos tayo ng error.
    die("Database connection failed: " . $e->getMessage());
}

require_once __DIR__ . "/../includes/schema_helper.php";
require_once __DIR__ . "/../includes/maintenance_helper.php";

$runMigrations = getenv("GSO_RUN_MIGRATIONS");
if ($runMigrations !== "0") {
    ensureSystemSchema($pdo);
}

syncMaintenanceSchedules($pdo);
?>