<?php
@header('Content-Type: application/json');
require_once __DIR__ . '/../includes/connect.php';

// Authentication check
if (!isset($_SESSION['customer_sid']) || !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
$event = isset($_POST['event']) ? strtolower(trim($_POST['event'])) : '';
$reason = isset($_POST['reason']) ? trim(htmlspecialchars($_POST['reason'])) : '';

if (!$order_id || !in_array($event, ['cancel', 'fail', 'dismiss'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

// Fetch order
$stmt = $con->prepare("SELECT id, customer_id, payment_status, total, order_code FROM orders WHERE id = ? AND deleted = 0");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    echo json_encode(['success' => false, 'error' => 'Order not found']);
    exit;
}

// Security: User must own the order
if ($order['customer_id'] != $user_id && !isset($_SESSION['admin_sid'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access to order']);
    exit;
}

// Security: Never overwrite an already Paid order
if ($order['payment_status'] === 'Paid') {
    echo json_encode(['success' => true, 'payment_status' => 'Paid', 'message' => 'Order is already marked Paid']);
    exit;
}

// Determine new payment status:
// For 'dismiss' or 'cancel': mark 'Cancelled' or keep 'Pending'
// For 'fail': mark 'Failed'
$new_status = ($event === 'fail') ? 'Failed' : 'Cancelled';

$update_stmt = $con->prepare("UPDATE orders SET payment_status = ? WHERE id = ? AND payment_status != 'Paid'");
$update_stmt->bind_param("si", $new_status, $order_id);
$update_stmt->execute();

echo json_encode([
    'success' => true,
    'order_id' => $order_id,
    'order_code' => $order['order_code'],
    'payment_status' => $new_status,
    'reason' => $reason
]);
?>
