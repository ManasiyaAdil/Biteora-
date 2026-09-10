<?php
require_once __DIR__ . '/../includes/connect.php';

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : 'Cancelled';

if ($id > 0) {
    // Fetch order to refund if paid by wallet
    $stmt = $con->prepare("SELECT customer_id, total, payment_type, status FROM orders WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if ($order && $order['status'] !== 'Cancelled') {
        $up = $con->prepare("UPDATE orders SET status = 'Cancelled' WHERE id = ?");
        $up->bind_param("i", $id);
        $up->execute();

        // If wallet, refund
        if ($order['payment_type'] === 'Wallet') {
            $cid = $order['customer_id'];
            $amt = floatval($order['total']);
            $con->query("UPDATE wallet_details wd JOIN wallet w ON wd.wallet_id = w.id SET wd.balance = wd.balance + $amt WHERE w.customer_id = $cid");
        }
    }
}

if (isset($_SESSION['admin_sid'])) {
    header("Location: ../all-orders.php?msg=cancelled");
} else {
    header("Location: ../orders.php?msg=cancelled");
}
exit;
?>