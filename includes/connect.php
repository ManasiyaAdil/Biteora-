<?php
// CampusBite Database Connection & Auto-Migration Layer
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Kolkata');

require_once __DIR__ . '/payment_config.php';

$env_host = getenv('DB_HOST') ?: (getenv('MYSQLHOST') ?: (getenv('MYSQL_HOST') ?: null));
$env_user = getenv('DB_USER') ?: (getenv('MYSQLUSER') ?: (getenv('MYSQL_USER') ?: null));
$env_pass = getenv('DB_PASS') ?: (getenv('MYSQLPASSWORD') ?: (getenv('MYSQL_PASSWORD') ?: null));
$env_name = getenv('DB_NAME') ?: (getenv('MYSQLDATABASE') ?: (getenv('MYSQL_DATABASE') ?: null));
$env_port = getenv('DB_PORT') ?: (getenv('MYSQLPORT') ?: (getenv('MYSQL_PORT') ?: null));

$con = null;
$pdo = null;
$db_error = null;

if ($env_host) {
    $db_host = $env_host;
    $db_user = $env_user ?? 'root';
    $db_pass = $env_pass ?? '';
    $db_name = $env_name ?? 'food';
    $port = (int)($env_port ?: 3306);

    try {
        $con = @new mysqli($db_host, $db_user, $db_pass, $db_name, $port);
        if (!$con->connect_error) {
            $con->set_charset("utf8mb4");
            $pdo = new PDO("mysql:host=$db_host;port=$port;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } else {
            $db_error = $con->connect_error;
        }
    } catch (Throwable $e) {
        $db_error = $e->getMessage();
    }
} else {
    $db_hosts = ["127.0.0.1", "localhost"];
    $db_user = "root";
    $db_pass = "";
    $db_name = "food";
    $db_ports = [3306, 3307];

    foreach ($db_hosts as $h) {
        foreach ($db_ports as $port) {
            try {
                $test_con = @new mysqli($h, $db_user, $db_pass, "", $port);
                if (!$test_con->connect_error) {
                    $test_con->query("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                    $test_con->close();
                    
                    $con = @new mysqli($h, $db_user, $db_pass, $db_name, $port);
                    if (!$con->connect_error) {
                        $con->set_charset("utf8mb4");
                        $pdo = new PDO("mysql:host=$h;port=$port;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                        ]);
                        break 2;
                    }
                }
            } catch (Throwable $e) {
                $db_error = $e->getMessage();
            }
        }
    }
}

// Ensure $con exists before proceeding
if (!$con || $con->connect_error) {
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><title>Database Initializing - Biteora</title><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<style>body{font-family:system-ui,-apple-system,sans-serif;background:#0F172A;color:#fff;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}';
    echo '.card{background:#1E293B;padding:2.5rem;border-radius:18px;max-width:520px;text-align:center;box-shadow:0 20px 40px rgba(0,0,0,0.5);border:1px solid #334155;}';
    echo 'h1{color:#FF5A36;margin-top:0;font-size:1.6rem;}p{color:#94A3B8;line-height:1.6;font-size:0.95rem;}.btn{display:inline-block;background:#FF5A36;color:#fff;padding:0.75rem 1.5rem;border-radius:10px;text-decoration:none;font-weight:700;margin-top:1rem;}</style></head>';
    echo '<body><div class="card"><h1>⚡ Biteora Database Starting</h1>';
    echo '<p>The database server is initializing its tables and 105 cafeteria items. Please refresh this page in a few moments.</p>';
    echo '<a href="javascript:location.reload()" class="btn">Refresh Page</a>';
    echo '</div></body></html>';
    exit;
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