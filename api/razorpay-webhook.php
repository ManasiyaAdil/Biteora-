<?php
@header('Content-Type: application/json');
require_once __DIR__ . '/../includes/connect.php';

$rawBody = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

if (empty($rawBody)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Empty webhook payload']);
    exit;
}

// 1. Signature Verification
$is_valid = razorpay_verify_webhook($rawBody, $signature);
if (!$is_valid && PAYMENT_ENV !== 'test') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid webhook signature']);
    exit;
}

$event = json_decode($rawBody, true);
$eventType = $event['event'] ?? '';
$payload = $event['payload'] ?? [];

// 2. Idempotent Event Handlers
if ($eventType === 'payment.captured') {
    $payment = $payload['payment']['entity'] ?? [];
    $razorpay_payment_id = $payment['id'] ?? '';
    $razorpay_order_id = $payment['order_id'] ?? '';
    $amount = ($payment['amount'] ?? 0) / 100;

    if (!empty($razorpay_order_id)) {
        // Find matching order
        $stmt = $con->prepare("SELECT id, payment_status FROM orders WHERE razorpay_order_id = ?");
        $stmt->bind_param("s", $razorpay_order_id);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($order = $res->fetch_assoc()) {
            if ($order['payment_status'] !== 'Paid') {
                $order_id = $order['id'];
                $up = $con->prepare("UPDATE orders SET payment_status = 'Paid', payment_verified_at = NOW(), razorpay_payment_id = ? WHERE id = ?");
                $up->bind_param("si", $razorpay_payment_id, $order_id);
                $up->execute();
            }
        }
    }
} elseif ($eventType === 'payment.failed') {
    $payment = $payload['payment']['entity'] ?? [];
    $razorpay_order_id = $payment['order_id'] ?? '';
    if (!empty($razorpay_order_id)) {
        $con->query("UPDATE orders SET payment_status = 'Failed' WHERE razorpay_order_id = '$razorpay_order_id' AND payment_status != 'Paid'");
    }
} elseif ($eventType === 'refund.processed') {
    $refund = $payload['refund']['entity'] ?? [];
    $refund_id = $refund['id'] ?? '';
    $payment_id = $refund['payment_id'] ?? '';
    $refund_amount = ($refund['amount'] ?? 0) / 100;

    if (!empty($payment_id)) {
        $up = $con->prepare("UPDATE orders SET payment_status = 'Refunded', refund_id = ?, refund_amount = ?, refund_status = 'processed', refund_at = NOW() WHERE razorpay_payment_id = ?");
        $up->bind_param("sds", $refund_id, $refund_amount, $payment_id);
        $up->execute();
    }
}

http_response_code(200);
echo json_encode(['status' => 'ok', 'event' => $eventType]);
?>
