<?php
require_once __DIR__ . '/../includes/connect.php';

$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';
$redirect = isset($_POST['redirect']) ? trim($_POST['redirect']) : '../index.php';

if (empty($username) || empty($password)) {
    header("Location: ../login.php?error=empty_fields");
    exit;
}

$stmt = $con->prepare("SELECT * FROM users WHERE username = ? AND deleted = 0");
$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();

$authenticated = false;
$user_data = null;

if ($row = $res->fetch_assoc()) {
    // Check both hashed password and legacy plain-text password
    if (password_verify($password, $row['password']) || $password === $row['password']) {
        $authenticated = true;
        $user_data = $row;
    }
}

if ($authenticated && $user_data) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['user_id'] = $user_data['id'];
    $_SESSION['name'] = $user_data['name'];
    $_SESSION['username'] = $user_data['username'];
    $_SESSION['role'] = $user_data['role'];

    if ($user_data['role'] === 'Administrator') {
        $_SESSION['admin_sid'] = session_id();
        unset($_SESSION['customer_sid']);
        header("Location: ../admin-page.php");
        exit;
    } else {
        $_SESSION['customer_sid'] = session_id();
        unset($_SESSION['admin_sid']);
        if (!empty($redirect) && strpos($redirect, 'login.php') === false && strpos($redirect, 'register.php') === false) {
            header("Location: " . (strpos($redirect, '../') === 0 ? $redirect : '../' . $redirect));
        } else {
            header("Location: ../index.php");
        }
        exit;
    }
} else {
    header("Location: ../login.php?error=invalid_credentials");
    exit;
}
?>