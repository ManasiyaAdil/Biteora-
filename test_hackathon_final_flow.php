<?php
require_once __DIR__ . '/includes/connect.php';

echo "=================================================================\n";
echo "       BITEORA HACKATHON FINAL UPDATE VERIFICATION SUITE         \n";
echo "=================================================================\n\n";

$pass = 0; $total = 0;
function test($label, $cond, $detail='') {
    global $pass, $total;
    $total++;
    if ($cond) {
        $pass++;
        echo "  [PASS] $label\n";
        if ($detail) echo "         $detail\n";
    } else {
        echo "  [FAIL] $label\n";
        if ($detail) echo "         $detail\n";
    }
}

$base = "http://127.0.0.1:8000";
$cookie = __DIR__ . '/final_test_cookie.txt';
if (file_exists($cookie)) unlink($cookie);

function postReq($url, $data, $cookie) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

function getReq($url, $cookie) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

// Ensure user1 has wallet
$con->query("UPDATE wallet_details wd JOIN wallet w ON wd.wallet_id = w.id JOIN users u ON w.customer_id = u.id SET wd.balance = 5000.00 WHERE u.username = 'user1'");

// 1. Authenticate user1
echo "--- 1. Authenticating Student Session ---\n";
postReq("$base/routers/router.php", http_build_query(['username'=>'user1','password'=>'pass1']), $cookie);
$orders_page = getReq("$base/orders.php", $cookie);
test("Student session authenticated", strpos($orders_page, 'My Orders') !== false);

// 2. Tagline Verification
echo "\n--- 2. Brand Tagline: 'Verified By Tukaram Mundhe' ---\n";
$home_html = getReq("$base/index.php", $cookie);
test("Tagline present on homepage", strpos($home_html, 'Verified By Tukaram Mundhe') !== false);
$footer_content = file_get_contents(__DIR__ . '/includes/footer.php');
test("Tagline present in includes/footer.php", strpos($footer_content, 'Verified By Tukaram Mundhe') !== false);

// 3. Security: Key Secret NEVER exposed
echo "\n--- 3. Razorpay Key Secret Security Isolation ---\n";
test("Secret NOT exposed in .env read via API", strpos($orders_page, RAZORPAY_KEY_SECRET) === false);
test("Secret NOT present in home HTML", strpos($home_html, RAZORPAY_KEY_SECRET) === false);
$checkout_html = getReq("$base/checkout.php", $cookie);
test("Secret NOT exposed in checkout.php HTML/JS", strpos($checkout_html, RAZORPAY_KEY_SECRET) === false);

// 4. Test Campus Delivery Order with Real Razorpay Order Creation & Payment QR Flow
echo "\n--- 4. Razorpay Test Payment & QR Flow for Campus Delivery ---\n";
$item = $con->query("SELECT id, name, price FROM items WHERE is_available=1 AND deleted=0 LIMIT 1")->fetch_assoc();
$del_payload = [
    'delivery_method' => 'delivery',
    'delivery_building' => 'Computing Block C',
    'delivery_floor' => '2',
    'delivery_room' => 'Lab 204',
    'delivery_note' => 'Deliver to front desk',
    'payment_type' => 'Scan & Pay QR',
    'items_json' => json_encode([['id' => $item['id'], 'qty' => 1]])
];

$raw_del = postReq("$base/api/create-payment-order.php", http_build_query($del_payload), $cookie);
$del_order = json_decode($raw_del, true);

test("Campus Delivery order created via API", !empty($del_order['success']), "order_id=" . ($del_order['order_id'] ?? 'none'));
test("Payment method is Scan & Pay QR / Online Payment", ($del_order['payment_method'] ?? '') === 'Online Payment');
test("Genuine Razorpay order_id created", !empty($del_order['razorpay_order_id']), "rzp_id=" . ($del_order['razorpay_order_id'] ?? ''));
test("Payment QR URL generated pointing to mobile-payment.php", strpos($del_order['payment_qr_url'] ?? '', 'mobile-payment.php?order_id=') !== false, "qr_url=" . ($del_order['payment_qr_url'] ?? ''));
test("Delivery fee ₹20 included in server amount", ($del_order['amount'] ?? 0) === intval(round(((float)$item['price'] + 20.0) * 100)));

$oid = $del_order['order_id'] ?? 0;
$mob_html = getReq("$base/mobile-payment.php?order_id=$oid", $cookie);
test("Mobile payment page loads successfully", strpos($mob_html, 'Mobile Payment Gateway') !== false);
test("Mobile page includes Razorpay SDK", strpos($mob_html, 'checkout.razorpay.com/v1/checkout.js') !== false);
test("Mobile page displays correct amount", strpos($mob_html, number_format((float)$item['price'] + 20.0, 2)) !== false);

// 5. Server-side HMAC-SHA256 Signature Verification
echo "\n--- 5. Server-side HMAC-SHA256 Signature Verification ---\n";
$rzp_order_id = $del_order['razorpay_order_id'];
$test_pay_id = 'pay_hackathon_' . bin2hex(random_bytes(6));
$valid_sig = hash_hmac('sha256', $rzp_order_id . '|' . $test_pay_id, RAZORPAY_KEY_SECRET);

$raw_verify = postReq("$base/api/verify-payment.php", http_build_query([
    'order_id' => $oid,
    'razorpay_order_id' => $rzp_order_id,
    'razorpay_payment_id' => $test_pay_id,
    'razorpay_signature' => $valid_sig
]), $cookie);
$v_res = json_decode($raw_verify, true);
test("HMAC-SHA256 signature verified server-side", !empty($v_res['success']));

$db_rec = $con->query("SELECT payment_status, payment_verified_at, razorpay_payment_id, status FROM orders WHERE id = $oid")->fetch_assoc();
test("DB marked payment_status = 'Paid'", $db_rec['payment_status'] === 'Paid');
test("DB saved payment_verified_at timestamp", !empty($db_rec['payment_verified_at']));
test("DB saved razorpay_payment_id", $db_rec['razorpay_payment_id'] === $test_pay_id);

// 6. Test 2-Minute Demo Lifecycle & Real Database Status Transitions
echo "\n--- 6. 2-Minute Demo Lifecycle & Real DB Progression ---\n";
// Set placed_at to 35 seconds ago -> should advance to Preparing
$con->query("UPDATE orders SET status = 'Placed', placed_at = DATE_SUB(NOW(), INTERVAL 35 SECOND) WHERE id = $oid");
$st1 = json_decode(getReq("$base/api/order-status.php?id=$oid", $cookie), true);
test("At 35s: DB auto-advances to 'Preparing'", ($st1['status'] ?? '') === 'Preparing');
$db1 = $con->query("SELECT status, preparing_at FROM orders WHERE id = $oid")->fetch_assoc();
test("DB status is 'Preparing' with preparing_at timestamp", $db1['status'] === 'Preparing' && !empty($db1['preparing_at']));

// Set placed_at to 65 seconds ago -> should advance to Out for Delivery
$con->query("UPDATE orders SET status = 'Preparing', placed_at = DATE_SUB(NOW(), INTERVAL 65 SECOND) WHERE id = $oid");
$st2 = json_decode(getReq("$base/api/order-status.php?id=$oid", $cookie), true);
test("At 65s: DB auto-advances to 'Out for Delivery'", ($st2['status'] ?? '') === 'Out for Delivery');
$db2 = $con->query("SELECT status, out_for_delivery_at FROM orders WHERE id = $oid")->fetch_assoc();
test("DB status is 'Out for Delivery' with out_for_delivery_at timestamp", $db2['status'] === 'Out for Delivery' && !empty($db2['out_for_delivery_at']));

// 7. Student Delivery Notification Verification
echo "\n--- 7. Student Delivery Notification ---\n";
test("API status_message has exact notification string", ($st2['status_message'] ?? '') === '🚚 Your BITEORA order has left the cafeteria and is on the way!');

$track_page = getReq("$base/track-order.php?id=$oid", $cookie);
test("Track Order shows prominent Out for Delivery alert", strpos($track_page, '🚚 Your BITEORA order has left the cafeteria and is on the way!') !== false);
test("Track Order shows 2-Minute Fast-Track Delivery Demo countdown", strpos($track_page, '2-Minute Fast-Track Delivery Demo') !== false);
test("Track Order contains floating notification toast", strpos($track_page, 'cb-floating-delivery-toast') !== false);

$home_active = getReq("$base/index.php", $cookie);
test("Student homepage displays Out for Delivery notification banner", strpos($home_active, '🚚 Your BITEORA order has left the cafeteria and is on the way!') !== false);

$orders_active = getReq("$base/orders.php", $cookie);
test("Student orders page displays Out for Delivery notification banner", strpos($orders_active, '🚚 Your BITEORA order has left the cafeteria and is on the way!') !== false);

// 8. At 95 seconds -> Delivered
echo "\n--- 8. 2-Minute Demo Final Delivery ---\n";
$con->query("UPDATE orders SET status = 'Out for Delivery', placed_at = DATE_SUB(NOW(), INTERVAL 95 SECOND) WHERE id = $oid");
$st3 = json_decode(getReq("$base/api/order-status.php?id=$oid", $cookie), true);
test("At 95s: DB auto-advances to 'Delivered'", ($st3['status'] ?? '') === 'Delivered');
$db3 = $con->query("SELECT status, delivered_at FROM orders WHERE id = $oid")->fetch_assoc();
test("DB status is 'Delivered' with delivered_at timestamp", $db3['status'] === 'Delivered' && !empty($db3['delivered_at']));
test("Countdown reaches 0s when Delivered", ($st3['demo_seconds_remaining'] ?? -1) === 0);
test("Progress percent reaches 100% when Delivered", ($st3['demo_progress_percent'] ?? 0) === 100);

// 9. Pickup Flow 2-Minute Lifecycle
echo "\n--- 9. Cafeteria Pickup 2-Minute Lifecycle ---\n";
$pick_payload = [
    'delivery_method' => 'pickup',
    'address' => 'Counter 1',
    'payment_type' => 'Wallet',
    'items_json' => json_encode([['id' => $item['id'], 'qty' => 1]])
];
$raw_pick = postReq("$base/api/create-payment-order.php", http_build_query($pick_payload), $cookie);
$pick_order = json_decode($raw_pick, true);
$p_id = $pick_order['order_id'] ?? 0;

test("Pickup order created", $p_id > 0);
$con->query("UPDATE orders SET status = 'Placed', placed_at = DATE_SUB(NOW(), INTERVAL 65 SECOND) WHERE id = $p_id");
$pst2 = json_decode(getReq("$base/api/order-status.php?id=$p_id", $cookie), true);
test("Pickup at 65s advances to 'Ready'", ($pst2['status'] ?? '') === 'Ready');

$con->query("UPDATE orders SET status = 'Ready', placed_at = DATE_SUB(NOW(), INTERVAL 95 SECOND) WHERE id = $p_id");
$pst3 = json_decode(getReq("$base/api/order-status.php?id=$p_id", $cookie), true);
test("Pickup at 95s advances to 'Completed'", ($pst3['status'] ?? '') === 'Completed');
$pdb3 = $con->query("SELECT status, picked_up_at FROM orders WHERE id = $p_id")->fetch_assoc();
test("Pickup DB status is 'Completed' with picked_up_at timestamp", $pdb3['status'] === 'Completed' && !empty($pdb3['picked_up_at']));

echo "\n=================================================================\n";
echo "   FINAL RESULTS: $pass / $total TESTS PASSED (" . round($pass/$total*100) . "%)\n";
echo "=================================================================\n";
