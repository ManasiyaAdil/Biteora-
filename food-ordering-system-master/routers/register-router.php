<?php
require_once __DIR__ . '/../includes/connect.php';

$name = isset($_POST['name']) ? trim(htmlspecialchars($_POST['name'])) : '';
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$address = isset($_POST['address']) ? trim(htmlspecialchars($_POST['address'])) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

if (empty($name) || empty($username) || empty($phone) || empty($password)) {
    header("Location: ../register.php?error=" . urlencode("Please fill in all required fields."));
    exit;
}

// Check if username already exists
$stmt = $con->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    header("Location: ../register.php?error=" . urlencode("Username '$username' is already taken. Please choose another."));
    exit;
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$insert_stmt = $con->prepare("INSERT INTO users (role, name, username, password, email, address, contact, verified, deleted) VALUES ('Customer', ?, ?, ?, ?, ?, ?, 1, 0)");
$insert_stmt->bind_param("ssssss", $name, $username, $hashed_password, $email, $address, $phone);

if ($insert_stmt->execute()) {
    $new_user_id = $con->insert_id;

    // Provision campus wallet with demo ₹1500 balance
    $con->query("INSERT INTO wallet (customer_id) VALUES ($new_user_id)");
    $wallet_id = $con->insert_id;
    $card_num = '55' . rand(1000, 9999) . rand(1000, 9999) . rand(1000, 9999);
    $con->query("INSERT INTO wallet_details (wallet_id, number, cvv, balance) VALUES ($wallet_id, '$card_num', 321, 1500.00)");

    // Auto login
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['customer_sid'] = session_id();
    $_SESSION['user_id'] = $new_user_id;
    $_SESSION['name'] = $name;
    $_SESSION['username'] = $username;
    $_SESSION['role'] = 'Customer';

    header("Location: ../index.php");
    exit;
} else {
    header("Location: ../register.php?error=" . urlencode("Registration failed. Please try again."));
    exit;
}
?>