<?php
// Kunin ang mga configuration mula sa Environment Variables
// Ang mga ito ay ise-set natin sa Cloud Run console
$host     = getenv('DB_HOST') ?: 'localhost'; 
$dbname   = getenv('DB_NAME') ?: 'gso_db';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: 'sept2904'; 

try {
    // Kung tayo ay nasa Cloud Run, gagamit tayo ng unix_socket
    // Ang DB_HOST sa Cloud Run ay karaniwang: /cloudsql/CONNECTION_NAME
    if (strpos($host, '/cloudsql/') === 0) {
        $dsn = "mysql:unix_socket=$host;dbname=$dbname;charset=utf8mb4";
    } else {
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    }

    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Para sa debugging: huwag i-display ang buong error sa live site kung maaari
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