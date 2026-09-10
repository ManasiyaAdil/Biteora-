<?php
// CampusBite Universal Database Connection & Auto-Migration Layer
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Kolkata');

require_once __DIR__ . '/payment_config.php';

// Universal SQLite wrapper providing complete mysqli compatibility for zero-config cloud deployments
if (!class_exists('BiteoraSQLiteResult')) {
    class BiteoraSQLiteResult {
        private $rows;
        private $cursor = 0;
        public $num_rows = 0;

        public function __construct($rows = []) {
            $this->rows = is_array($rows) ? $rows : [];
            $this->num_rows = count($this->rows);
        }

        public function fetch_assoc() {
            if ($this->cursor < $this->num_rows) {
                return $this->rows[$this->cursor++];
            }
            return null;
        }

        public function fetch_row() {
            if ($this->cursor < $this->num_rows) {
                return array_values($this->rows[$this->cursor++]);
            }
            return null;
        }

        public function fetch_array($mode = 3) {
            if ($this->cursor < $this->num_rows) {
                $row = $this->rows[$this->cursor++];
                if ($mode === 2) return array_values($row);
                if ($mode === 1) return $row;
                return array_merge($row, array_values($row));
            }
            return null;
        }

        public function fetch_all($mode = 2) {
            $res = [];
            while ($r = $this->fetch_assoc()) {
                $res[] = ($mode === 1) ? $r : array_values($r);
            }
            return $res;
        }

        public function free() {}
        public function close() {}
    }

    class BiteoraSQLiteStmt {
        private $pdo;
        private $sql;
        private $params = [];
        private $boundResults = [];
        private $lastStmt = null;
        public $insert_id = 0;
        public $error = '';
        public $errno = 0;

        public function __construct($pdo, $sql) {
            $this->pdo = $pdo;
            $this->sql = $sql;
        }

        public function bind_param($types, ...$params) {
            $this->params = $params;
            return true;
        }

        public function execute() {
            try {
                $stmt = $this->pdo->prepare($this->sql);
                $flat = [];
                foreach ($this->params as $p) {
                    $flat[] = $p;
                }
                $stmt->execute($flat);
                $this->lastStmt = $stmt;
                $this->insert_id = (int)$this->pdo->lastInsertId();
                return true;
            } catch (Throwable $e) {
                $this->error = $e->getMessage();
                return false;
            }
        }

        public function get_result() {
            if ($this->lastStmt) {
                $rows = $this->lastStmt->fetchAll(PDO::FETCH_ASSOC);
                return new BiteoraSQLiteResult($rows);
            }
            return new BiteoraSQLiteResult([]);
        }

        public function bind_result(&...$vars) {
            $this->boundResults = &$vars;
            return true;
        }

        public function fetch() {
            if ($this->lastStmt) {
                $row = $this->lastStmt->fetch(PDO::FETCH_NUM);
                if ($row) {
                    foreach ($row as $i => $val) {
                        if (isset($this->boundResults[$i])) {
                            $this->boundResults[$i] = $val;
                        }
                    }
                    return true;
                }
            }
            return false;
        }

        public function close() {
            $this->lastStmt = null;
        }
    }

    class BiteoraSQLiteWrapper {
        public $pdo;
        public $insert_id = 0;
        public $error = '';
        public $errno = 0;
        public $connect_error = null;

        public function __construct($pdo) {
            $this->pdo = $pdo;
        }

        public function query($sql) {
            try {
                $trimmed = trim($sql);
                if (stripos($trimmed, 'SHOW COLUMNS FROM') === 0 || stripos($trimmed, 'SHOW INDEX FROM') === 0) {
                    return new BiteoraSQLiteResult([]);
                }
                // Convert MySQL-specific syntax to SQLite compatible syntax
                $trans = preg_replace('/INSERT\s+IGNORE\s+INTO/i', 'INSERT OR IGNORE INTO', $sql);
                $trans = preg_replace('/AUTO_INCREMENT/i', 'AUTOINCREMENT', $trans);
                $trans = preg_replace('/ENGINE\s*=\s*[a-zA-Z0-9]+/i', '', $trans);
                $trans = preg_replace('/DEFAULT\s+CHARSET\s*=\s*[a-zA-Z0-9]+/i', '', $trans);
                $trans = preg_replace('/COLLATE\s*=\s*[a-zA-Z0-9_]+/i', '', $trans);
                $trans = preg_replace('/ON\s+UPDATE\s+CURRENT_TIMESTAMP/i', '', $trans);

                $stmt = $this->pdo->query($trans);
                if ($stmt) {
                    $this->insert_id = (int)$this->pdo->lastInsertId();
                    if (stripos($trimmed, 'SELECT') === 0 || stripos($trimmed, 'PRAGMA') === 0) {
                        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        return new BiteoraSQLiteResult($rows);
                    }
                    return true;
                }
                return false;
            } catch (Throwable $e) {
                $this->error = $e->getMessage();
                return false;
            }
        }

        public function prepare($sql) {
            return new BiteoraSQLiteStmt($this->pdo, $sql);
        }

        public function real_escape_string($str) {
            return addslashes((string)$str);
        }

        public function escape_string($str) {
            return addslashes((string)$str);
        }

        public function set_charset($charset) {
            return true;
        }

        public function close() {
            return true;
        }

        public function begin_transaction() {
            return $this->pdo->beginTransaction();
        }

        public function commit() {
            return $this->pdo->commit();
        }

        public function rollback() {
            return $this->pdo->rollBack();
        }
    }
}

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

// Fallback to SQLite wrapper if MySQL is unavailable
if (!$con || $con->connect_error) {
    try {
        $sqlite_file = __DIR__ . '/../campusbite.sqlite';
        $pdo = new PDO("sqlite:" . $sqlite_file);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $con = new BiteoraSQLiteWrapper($pdo);
    } catch (Throwable $e) {
        $db_error = $e->getMessage();
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