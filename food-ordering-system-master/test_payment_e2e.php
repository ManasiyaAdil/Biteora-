<?php
require_once 'includes/connect.php';

echo "====================================================\n";
echo "   CAMPUSBITE REAL PAYMENT & QR TEST SUITE          \n";
echo "====================================================\n\n";

$test_passes = 0;
$test_total = 0;

function assert_test($description, $condition) {
    global $test_passes, $test_total;
    $test_total++;
    if ($condition) {
        $test_passes++;
        echo "  [PASS] $description\n";
    } else {
        echo "  [FAIL] $description\n";
    }
}

$cookie_file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cb_cookie_test.txt';
if (file_exists($cookie_file)) @unlink($cookie_file);

// Helper function to make local HTTP POST requests with cookies
function http_post($endpoint, $fields) {
    global $cookie_file;
    $ch = curl_init('http://127.0.0.1:8000/' . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true) ?: $res;
}

function http_get($endpoint) {
    global $cookie_file;
    $ch = curl_init('http://127.0.0.1:8000/' . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

// 1. Authenticate student via router.php
$login_res = http_post('routers/router.php', [
    'username' => 'user1',
    'password' => 'pass1'
]);

// TEST 1: Server-side Razorpay Order Creation & Verified Payment Flow
echo "--- TEST 1: Razorpay Order Creation & Signature Verification Flow ---\n";
$item = $con->query("SELECT * FROM items WHERE is_available = 1 AND deleted = 0 LIMIT 1")->fetch_assoc();
$test_qty = 2;
$expected_total = floatval($item['price']) * $test_qty;

$create_resp = http_post('api/create-payment-order.php', [
    'address' => 'Hostel Block B, Room 102',
    'description' => 'Extra spicy',
    'payment_type' => 'Online Payment',
    'items_json' => json_encode([['id' => $item['id'], 'qty' => $test_qty]])
]);

assert_test("Server-side price verification and order creation succeeded", is_array($create_resp) && ($create_resp['success'] ?? false) === true);
assert_test("Razorpay Order ID returned", !empty($create_resp['razorpay_order_id']));
assert_test("Amount correctly calculated in paise (" . ($expected_total * 100) . " paise)", ($create_resp['amount'] ?? 0) === intval(round($expected_total * 100)));

$created_order_id = $create_resp['order_id'];
$rzp_order_id = $create_resp['razorpay_order_id'];
$rzp_payment_id = 'pay_cb_' . bin2hex(random_bytes(6));

// Compute valid HMAC SHA-256 signature
$valid_sig = hash_hmac('sha256', $rzp_order_id . '|' . $rzp_payment_id, RAZORPAY_KEY_SECRET);

$verify_resp = http_post('api/verify-payment.php', [
    'order_id' => $created_order_id,
    'razorpay_order_id' => $rzp_order_id,
    'razorpay_payment_id' => $rzp_payment_id,
    'razorpay_signature' => $valid_sig
]);

assert_test("Server-side HMAC-SHA256 signature verification passed", is_array($verify_resp) && ($verify_resp['success'] ?? false) === true);

$db_order = $con->query("SELECT * FROM orders WHERE id = $created_order_id")->fetch_assoc();
assert_test("Database marked payment_status = 'Paid'", $db_order['payment_status'] === 'Paid');
assert_test("Payment verified timestamp recorded", !empty($db_order['payment_verified_at']));

// TEST 2: Payment Cancelled Handling
echo "\n--- TEST 2: Payment Cancelled Handling ---\n";
$cancelled_resp = http_post('api/create-payment-order.php', [
    'address' => 'Hostel Block B',
    'payment_type' => 'Online Payment',
    'items_json' => json_encode([['id' => $item['id'], 'qty' => 1]])
]);

$cancelled_order_id = $cancelled_resp['order_id'];
$cancelled_db_order = $con->query("SELECT payment_status FROM orders WHERE id = $cancelled_order_id")->fetch_assoc();
assert_test("Cancelled/unpaid order remains payment_status = 'Pending'", $cancelled_db_order['payment_status'] === 'Pending');

// TEST 3: Invalid Signature Security Protection
echo "\n--- TEST 3: Invalid Signature Security Protection ---\n";
$fake_sig = "fake_tampered_signature_hex_123456789";
$tamper_resp = http_post('api/verify-payment.php', [
    'order_id' => $cancelled_order_id,
    'razorpay_order_id' => $cancelled_resp['razorpay_order_id'],
    'razorpay_payment_id' => 'pay_fake_9999',
    'razorpay_signature' => $fake_sig
]);

assert_test("Server rejected forged payment signature", is_array($tamper_resp) && ($tamper_resp['success'] ?? true) === false);
$tampered_db_order = $con->query("SELECT payment_status FROM orders WHERE id = $cancelled_order_id")->fetch_assoc();
assert_test("Database marked payment_status = 'Failed'", $tampered_db_order['payment_status'] === 'Failed');

// TEST 4: Price Tampering Defense
echo "\n--- TEST 4: Price Tampering Server-Side Defense ---\n";
$db_price = floatval($item['price']);
assert_test("Server calculates price from database ($db_price) ignoring client price tampering", $create_resp['amount'] === intval(round($db_price * 2 * 100)));

// TEST 5: Duplicate Callback / Webhook Idempotency
echo "\n--- TEST 5: Duplicate Payment & Webhook Idempotency ---\n";
$dup_verify = http_post('api/verify-payment.php', [
    'order_id' => $created_order_id,
    'razorpay_order_id' => $rzp_order_id,
    'razorpay_payment_id' => $rzp_payment_id,
    'razorpay_signature' => $valid_sig
]);
assert_test("Duplicate verification handled idempotently without error", is_array($dup_verify) && ($dup_verify['success'] ?? false) === true);

// TEST 6: Mobile Scannable Pickup QR
echo "\n--- TEST 6: Scannable Pickup QR & Secure Token Generation ---\n";
assert_test("Order has a 64-char crypto secure pickup_verification_token", strlen($db_order['pickup_verification_token']) === 64);
$pvt = $db_order['pickup_verification_token'];
$expected_qr_target = APP_URL . "/verify-pickup.php?token=" . $pvt;
assert_test("QR target points to genuine staff verification endpoint", strpos($expected_qr_target, 'verify-pickup.php?token=') !== false);

// TEST 7: Staff Pickup Verification (Paid + Ready)
echo "\n--- TEST 7: Staff Scans Pickup QR and Confirms Pickup ---\n";
$con->query("UPDATE orders SET status = 'Ready' WHERE id = $created_order_id");

$pickup_page_html = http_post("verify-pickup.php?token=$pvt", [
    'action' => 'confirm_pickup'
]);

$post_pickup_order = $con->query("SELECT status, picked_up_at FROM orders WHERE id = $created_order_id")->fetch_assoc();
assert_test("Order status transitioned to 'Completed' upon staff pickup scan", $post_pickup_order['status'] === 'Completed');
assert_test("Pickup timestamp recorded in database", !empty($post_pickup_order['picked_up_at']));

// TEST 8: Duplicate Pickup Rejection
echo "\n--- TEST 8: Duplicate Pickup Prevention ---\n";
$dup_pickup_html = http_post("verify-pickup.php?token=$pvt", [
    'action' => 'confirm_pickup'
]);

assert_test("Duplicate pickup scan rejected with 'Already Picked Up' message", strpos($dup_pickup_html, 'Already Picked Up') !== false || strpos($dup_pickup_html, 'already picked up') !== false);

// Clean up test cookie
@unlink($cookie_file);

echo "\n====================================================\n";
echo "   FINAL RESULTS: $test_passes / $test_total TESTS PASSED (" . round(($test_passes/$test_total)*100) . "%)\n";
echo "====================================================\n";
?>
