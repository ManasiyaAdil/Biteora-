<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/connect.php';

if (!isset($_SESSION['admin_sid'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0);
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

$valid_statuses = ['Placed', 'Preparing', 'Ready', 'Out for Delivery', 'Delivered', 'Completed', 'Cancelled'];

if (!$order_id || !in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

$extra_sql = "";
if ($status === 'Preparing') {
    $extra_sql = ", preparing_at = IFNULL(preparing_at, NOW())";
} elseif ($status === 'Out for Delivery') {
    $extra_sql = ", out_for_delivery_at = IFNULL(out_for_delivery_at, NOW())";
} elseif ($status === 'Delivered') {
    $extra_sql = ", delivered_at = IFNULL(delivered_at, NOW())";
} elseif ($status === 'Completed') {
    $extra_sql = ", picked_up_at = IFNULL(picked_up_at, NOW()), delivered_at = IFNULL(delivered_at, NOW())";
}

$stmt = $con->prepare("UPDATE orders SET status = ? $extra_sql WHERE id = ?");
$stmt->bind_param("si", $status, $order_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'status' => $status, 'order_id' => $order_id]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database update failed: ' . $con->error]);
}
