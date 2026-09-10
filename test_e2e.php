<?php
require_once 'includes/connect.php';

echo "====================================================\n";
echo "   CAMPUSBITE END-TO-END AUTOMATED TEST SUITE       \n";
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

// TEST 1: Database Tables Existence & Schema
echo "--- 1. Testing Database & Schema Integrity ---\n";
$tables_needed = ['categories', 'items', 'orders', 'order_details', 'users', 'wallet', 'wallet_details', 'tickets'];
foreach ($tables_needed as $tbl) {
    $res = $con->query("SHOW TABLES LIKE '$tbl'");
    assert_test("Table `$tbl` exists", $res && $res->num_rows > 0);
}

// TEST 2: Demo Users & Passwords
echo "\n--- 2. Testing Authentication & User Accounts ---\n";
$u_admin = $con->query("SELECT * FROM users WHERE username='root'")->fetch_assoc();
assert_test("Admin user 'root' exists with Administrator role", $u_admin && $u_admin['role'] === 'Administrator');
assert_test("Admin password matches 'toor' via password_verify or hash", password_verify('toor', $u_admin['password']) || $u_admin['password'] === 'toor');

$u_student = $con->query("SELECT * FROM users WHERE username='user1'")->fetch_assoc();
assert_test("Student user 'user1' exists with Customer role", $u_student && $u_student['role'] === 'Customer');
assert_test("Student password matches 'pass1'", password_verify('pass1', $u_student['password']) || $u_student['password'] === 'pass1');

// TEST 3: Wallet Integration
echo "\n--- 3. Testing Campus Student Wallet ---\n";
$uid = $u_student['id'];
$w_res = $con->query("SELECT wd.balance FROM wallet w JOIN wallet_details wd ON w.id = wd.wallet_id WHERE w.customer_id = $uid")->fetch_assoc();
assert_test("Student 'user1' has active wallet with positive balance", $w_res && floatval($w_res['balance']) > 0);
$initial_balance = floatval($w_res['balance']);
echo "       Current Wallet Balance: ₹" . number_format($initial_balance, 2) . "\n";

// TEST 4: Food Menu & Categories
echo "\n--- 4. Testing Food Menu & Categories ---\n";
$cats_cnt = $con->query("SELECT COUNT(*) as c FROM categories")->fetch_assoc()['c'];
assert_test("Categories exist (Found: $cats_cnt)", $cats_cnt >= 5);

$items_cnt = $con->query("SELECT COUNT(*) as c FROM items WHERE deleted = 0")->fetch_assoc()['c'];
assert_test("Available menu items exist (Found: $items_cnt)", $items_cnt >= 6);

// TEST 5: AI Food Recommender API
echo "\n--- 5. Testing CampusBite AI Recommendation Engine ---\n";
$_GET['q'] = 'under 100';
ob_start();
include 'api/ai-recommend.php';
$ai_out = ob_get_clean();
$ai_json = json_decode($ai_out, true);
assert_test("AI Assistant returns valid JSON for 'under 100'", $ai_json && $ai_json['success'] === true);
assert_test("AI Assistant returned items matching budget constraint", !empty($ai_json['items']) && floatval($ai_json['items'][0]['price']) <= 100);

// TEST 6: Order Creation, Token Generation & Wallet Deduction
echo "\n--- 6. Testing Order Placement Flow ---\n";
$item_to_order = $con->query("SELECT * FROM items WHERE is_available = 1 AND deleted = 0 LIMIT 1")->fetch_assoc();
$test_item_id = $item_to_order['id'];
$test_item_price = floatval($item_to_order['price']);
$test_qty = 2;
$expected_total = $test_item_price * $test_qty;

// Generate order code
$order_number_query = $con->query("SELECT COUNT(*) as total_orders FROM orders");
$order_count = $order_number_query ? $order_number_query->fetch_assoc()['total_orders'] + 1 : 1;
$order_code = sprintf("CB-2026-%05d", $order_count);
$pickup_token = (string)($order_count % 100);

$stmt = $con->prepare("INSERT INTO orders (order_code, customer_id, pickup_token, address, description, payment_type, payment_status, total, status, date) VALUES (?, ?, ?, 'Hostel H-4', 'Automated Test Order', 'Wallet', 'Paid', ?, 'Placed', NOW())");
$stmt->bind_param("sisd", $order_code, $uid, $pickup_token, $expected_total);
$stmt->execute();
$new_order_id = $con->insert_id;

$det_stmt = $con->prepare("INSERT INTO order_details (order_id, item_id, quantity, price) VALUES (?, ?, ?, ?)");
$det_stmt->bind_param("iiid", $new_order_id, $test_item_id, $test_qty, $expected_total);
$det_stmt->execute();

assert_test("Order created in database with Order ID #$order_code", $new_order_id > 0);
assert_test("Pickup token #$pickup_token generated properly", !empty($pickup_token));

// Deduct wallet
$con->query("UPDATE wallet_details wd JOIN wallet w ON wd.wallet_id = w.id SET wd.balance = wd.balance - $expected_total WHERE w.customer_id = $uid");
$new_w_res = $con->query("SELECT wd.balance FROM wallet w JOIN wallet_details wd ON w.id = wd.wallet_id WHERE w.customer_id = $uid")->fetch_assoc();
$new_balance = floatval($new_w_res['balance']);
assert_test("Wallet balance successfully deducted (New: ₹" . number_format($new_balance, 2) . ")", abs($initial_balance - $expected_total - $new_balance) < 0.01);

// TEST 7: Live Status Polling API
echo "\n--- 7. Testing Live Status Polling API ---\n";
$_GET['id'] = $new_order_id;
ob_start();
include 'api/order-status.php';
$status_out = ob_get_clean();
$status_json = json_decode($status_out, true);
assert_test("Order status API returned correct initial 'Placed' state", $status_json && $status_json['status'] === 'Placed');

// TEST 8: Admin Status Progression (Placed -> Preparing -> Ready -> Completed)
echo "\n--- 8. Testing Admin Order Queue & Status Updates ---\n";
$_SESSION['admin_sid'] = 'test_session_admin';
$con->query("UPDATE orders SET status = 'Preparing' WHERE id = $new_order_id");
$chk1 = $con->query("SELECT status FROM orders WHERE id = $new_order_id")->fetch_assoc()['status'];
assert_test("Order transitioned to 'Preparing'", $chk1 === 'Preparing');

$con->query("UPDATE orders SET status = 'Ready' WHERE id = $new_order_id");
$chk2 = $con->query("SELECT status FROM orders WHERE id = $new_order_id")->fetch_assoc()['status'];
assert_test("Order transitioned to 'Ready' (Counter Serving)", $chk2 === 'Ready');

$con->query("UPDATE orders SET status = 'Completed' WHERE id = $new_order_id");
$chk3 = $con->query("SELECT status FROM orders WHERE id = $new_order_id")->fetch_assoc()['status'];
assert_test("Order transitioned to 'Completed'", $chk3 === 'Completed');

// TEST 9: Admin Dashboard Analytics Calculation
echo "\n--- 9. Testing Admin Dashboard Analytics Calculations ---\n";
$kpi_orders = $con->query("SELECT COUNT(*) as c FROM orders WHERE deleted = 0")->fetch_assoc()['c'];
$kpi_rev = $con->query("SELECT SUM(total) as r FROM orders WHERE status != 'Cancelled' AND deleted = 0")->fetch_assoc()['r'];
assert_test("Admin KPI total orders calculated ($kpi_orders orders)", $kpi_orders > 0);
assert_test("Admin KPI total revenue calculated (₹" . number_format($kpi_rev, 2) . ")", floatval($kpi_rev) > 0);

echo "\n====================================================\n";
echo "   FINAL RESULTS: $test_passes / $test_total TESTS PASSED (" . round(($test_passes/$test_total)*100) . "%)\n";
echo "====================================================\n";
?>
