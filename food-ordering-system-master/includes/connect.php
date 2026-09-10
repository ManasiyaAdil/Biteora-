<?php
// CampusBite Database Connection & Auto-Migration Layer
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/payment_config.php';

$db_host = "127.0.0.1";
$db_user = "root";
$db_pass = "";
$db_name = "food";
$db_ports = [3307, 3306];

$con = null;
$pdo = null;
$db_error = null;

foreach ($db_ports as $port) {
    try {
        $test_con = @new mysqli($db_host, $db_user, $db_pass, "", $port);
        if (!$test_con->connect_error) {
            $test_con->query("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            $test_con->close();
            
            $con = @new mysqli($db_host, $db_user, $db_pass, $db_name, $port);
            if (!$con->connect_error) {
                $con->set_charset("utf8mb4");
                $pdo = new PDO("mysql:host=$db_host;port=$port;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
                break;
            }
        }
    } catch (Throwable $e) {
        $db_error = $e->getMessage();
    }
}

// Fallback to SQLite if MySQL is unavailable
if (!$con || $con->connect_error) {
    try {
        $sqlite_file = __DIR__ . '/../campusbite.sqlite';
        $pdo = new PDO("sqlite:" . $sqlite_file);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        die("Database connection error: " . $e->getMessage());
    }
}

// Global user session variables
$user_id = $_SESSION['user_id'] ?? null;
$name = $_SESSION['name'] ?? 'Guest';
$role = $_SESSION['role'] ?? 'Guest';
$username = $_SESSION['username'] ?? '';

// Run auto migration and demo data seeding
require_once __DIR__ . '/db_setup.php';
campusbite_init_db($con, $pdo);
?>