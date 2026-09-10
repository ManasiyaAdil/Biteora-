<?php
@header('Content-Type: application/json');
require_once __DIR__ . '/../includes/connect.php';

$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
$razorpay_payment_id = isset($_POST['razorpay_payment_id']) ? trim($_POST['razorpay_payment_id']) : '';
$razorpay_order_id = isset($_POST['razorpay_order_id']) ? trim($_POST['razorpay_order_id']) : '';
$razorpay_signature = isset($_POST['razorpay_signature']) ? trim($_POST['razorpay_signature']) : '';

// Function for Safe Diagnostic Logging (Never log secrets)
function log_payment_diagnostic($data) {
    $log_dir = __DIR__ . '/../logs';
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0755, true);
    }
    $log_file = $log_dir . '/payment_diagnostics.log';
    $entry = date('[Y-m-d H:i:s] ') . json_encode($data) . PHP_EOL;
    @file_put_contents($log_file, $entry, FILE_APPEND | LOCK_EX);
}

if (!$order_id || empty($razorpay_payment_id) || empty($razorpay_order_id) || empty($razorpay_signature)) {
    log_payment_diagnostic([
        'order_id' => $order_id,
        'razorpay_order_id' => $razorpay_order_id,
        'razorpay_payment_id' => $razorpay_payment_id,
        'has_signature' => !empty($razorpay_signature),
        'env_mode' => PAYMENT_ENV,
        'status' => 'REJECTED_MISSING_PARAMETERS'
    ]);
    echo json_encode(['success' => false, 'error' => 'Missing payment verification parameters']);
    exit;
}

// 1. Fetch order securely from database (Source of Truth)
$stmt = $con->prepare("SELECT id, order_code, customer_id, total, payment_status, razorpay_order_id FROM orders WHERE id = ? AND deleted = 0");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    log_payment_diagnostic([
        'order_id' => $order_id,
        'status' => 'ORDER_NOT_FOUND'
    ]);
    echo json_encode(['success' => false, 'error' => 'Order not found']);
    exit;
}

// 2. Duplicate Payment / Idempotency Check
if ($order['payment_status'] === 'Paid') {
    log_payment_diagnostic([
        'order_id' => $order_id,
        'razorpay_order_id' => $order['razorpay_order_id'],
        'status' => 'IDEMPOTENT_ALREADY_PAID'
    ]);
    echo json_encode([
        'success' => true,
        'message' => 'Payment already verified and confirmed',
        'order_id' => $order['id'],
        'order_code' => $order['order_code'],
        'redirect' => "order-confirmation.php?id={$order['id']}"
    ]);
    exit;
}

// 3. Strict Database Order ID Match: DATABASE_RAZORPAY_ORDER_ID === RECEIVED_RAZORPAY_ORDER_ID
$db_razorpay_order_id = trim($order['razorpay_order_id'] ?? '');

if (empty($db_razorpay_order_id) || $db_razorpay_order_id !== $razorpay_order_id) {
    log_payment_diagnostic([
        'order_id' => $order_id,
        'db_razorpay_order_id' => $db_razorpay_order_id,
        'received_razorpay_order_id' => $razorpay_order_id,
        'status' => 'ORDER_ID_MISMATCH_REJECTED'
    ]);
    $con->query("UPDATE orders SET payment_status = 'Failed' WHERE id = $order_id");
    echo json_encode([
        'success' => false,
        'error' => 'Payment order ID mismatch. Untrusted payment response.'
    ]);
    exit;
}

// 4. Critical Signature Verification: HMAC-SHA256(ORIGINAL_SERVER_SIDE_RAZORPAY_ORDER_ID . '|' . RAZORPAY_PAYMENT_ID, KEY_SECRET)
$payload = $db_razorpay_order_id . '|' . $razorpay_payment_id;
$expected_signature = hash_hmac('sha256', $payload, trim(RAZORPAY_KEY_SECRET));

$is_valid = hash_equals($expected_signature, $razorpay_signature);

// Safe Diagnostic Logging (NO secrets logged)
log_payment_diagnostic([
    'internal_order_id' => $order_id,
    'razorpay_order_id' => $db_razorpay_order_id,
    'razorpay_payment_id' => $razorpay_payment_id,
    'has_signature' => !empty($razorpay_signature),
    'received_sig_length' => strlen($razorpay_signature),
    'generated_sig_length' => strlen($expected_signature),
    'key_id_env' => PAYMENT_ENV,
    'verification_result' => $is_valid ? 'SUCCESS' : 'FAILED'
]);

if ($is_valid) {
    // 5. Mark order as Paid and record verification timestamp
    $update_stmt = $con->prepare("UPDATE orders SET payment_status = 'Paid', payment_verified_at = NOW(), razorpay_payment_id = ?, razorpay_signature = ?, status = 'Placed' WHERE id = ?");
    $update_stmt->bind_param("ssi", $razorpay_payment_id, $razorpay_signature, $order_id);
    
    if ($update_stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Payment successfully verified and captured',
            'order_id' => $order['id'],
            'order_code' => $order['order_code'],
            'payment_id' => $razorpay_payment_id,
            'redirect' => "order-confirmation.php?id={$order['id']}"
        ]);
        exit;
    } else {
        echo json_encode(['success' => false, 'error' => 'Database update failed']);
        exit;
    }
} else {
    // Mark as Failed
    $con->query("UPDATE orders SET payment_status = 'Failed' WHERE id = $order_id");
    echo json_encode([
        'success' => false,
        'error' => 'Payment signature verification failed. Untrusted payment response.'
    ]);
    exit;
}
?>
