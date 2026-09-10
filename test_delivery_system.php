<?php
require_once __DIR__ . '/includes/connect.php';

echo "====================================================\n";
echo "   BITEORA REAL CAMPUS DELIVERY AUTOMATED TEST SUITE \n";
echo "====================================================\n\n";

$pass_count = 0;
$total_count = 0;

function assert_test($label, $condition, $info = '') {
    global $pass_count, $total_count;
    $total_count++;
    if ($condition) {
        $pass_count++;
        echo "  [PASS] $label\n";
        if ($info) echo "         $info\n";
    } else {
        echo "  [FAIL] $label\n";
        if ($info) echo "         $info\n";
    }
}

// 1. Database Schema Checks
echo "--- 1. Testing Orders Table Delivery Columns ---\n";
$res = $con->query("SHOW COLUMNS FROM `orders`");
$cols = [];
while ($r = $res->fetch_assoc()) {
    $cols[] = $r['Field'];
}

$required_cols = [
    'delivery_method', 'delivery_fee', 'delivery_building', 
    'delivery_floor', 'delivery_room', 'delivery_note',
    'placed_at', 'preparing_at', 'out_for_delivery_at', 'delivered_at'
];

foreach ($required_cols as $rc) {
    assert_test("Column `orders`.`$rc` exists", in_array($rc, $cols));
}

// 2. Testing Pickup Order Placement Flow
echo "\n--- 2. Testing Pickup Flow Order Placement ---\n";
// Get customer user1
$u_res = $con->query("SELECT id FROM users WHERE username = 'user1'");
$user1_id = $u_res->fetch_assoc()['id'];

// Get item
$i_res = $con->query("SELECT id, price FROM items WHERE deleted = 0 AND is_available = 1 LIMIT 1");
$item = $i_res->fetch_assoc();
$subtotal = floatval($item['price']);

// Place Pickup Order via DB
$pickup_order_code = "TEST-PK-" . time();
$pickup_stmt = $con->prepare("INSERT INTO orders (order_code, customer_id, delivery_method, delivery_fee, address, total, status, date, placed_at, payment_type, payment_status) VALUES (?, ?, 'pickup', 0.00, 'Campus Cafeteria Counter 1', ?, 'Placed', NOW(), NOW(), 'Wallet', 'Paid')");
$pickup_stmt->bind_param("sid", $pickup_order_code, $user1_id, $subtotal);
$pickup_stmt->execute();
$pickup_order_id = $con->insert_id;

assert_test("Pickup order inserted successfully", $pickup_order_id > 0, "ID: $pickup_order_id, Code: $pickup_order_code");

$p_check = $con->query("SELECT * FROM orders WHERE id = $pickup_order_id")->fetch_assoc();
assert_test("Pickup delivery_method is 'pickup'", $p_check['delivery_method'] === 'pickup');
assert_test("Pickup delivery_fee is 0.00", floatval($p_check['delivery_fee']) == 0.00);
assert_test("Pickup grand total equals subtotal", floatval($p_check['total']) == $subtotal, "Total: ₹{$p_check['total']}");

// Test Pickup Status Transitions
$con->query("UPDATE orders SET status = 'Preparing', preparing_at = NOW() WHERE id = $pickup_order_id");
$p_check2 = $con->query("SELECT status, preparing_at FROM orders WHERE id = $pickup_order_id")->fetch_assoc();
assert_test("Pickup status transitioned to 'Preparing'", $p_check2['status'] === 'Preparing' && !empty($p_check2['preparing_at']));

$con->query("UPDATE orders SET status = 'Ready' WHERE id = $pickup_order_id");
$p_check3 = $con->query("SELECT status FROM orders WHERE id = $pickup_order_id")->fetch_assoc();
assert_test("Pickup status transitioned to 'Ready'", $p_check3['status'] === 'Ready');

$con->query("UPDATE orders SET status = 'Completed', picked_up_at = NOW() WHERE id = $pickup_order_id");
$p_check4 = $con->query("SELECT status, picked_up_at FROM orders WHERE id = $pickup_order_id")->fetch_assoc();
assert_test("Pickup status transitioned to 'Completed'", $p_check4['status'] === 'Completed' && !empty($p_check4['picked_up_at']));


// 3. Testing Campus Delivery Order Placement Flow
echo "\n--- 3. Testing Campus Delivery Flow ---\n";
$delivery_fee = 20.00;
$grand_total_del = $subtotal + $delivery_fee;
$del_order_code = "TEST-DEL-" . time();
$building = "Main College Building";
$floor = "3";
$room = "304";
$note = "Please deliver near Lab 3 entrance.";

$del_stmt = $con->prepare("INSERT INTO orders (order_code, customer_id, delivery_method, delivery_fee, delivery_building, delivery_floor, delivery_room, delivery_note, address, total, status, date, placed_at, payment_type, payment_status) VALUES (?, ?, 'delivery', ?, ?, ?, ?, ?, ?, ?, 'Placed', NOW(), NOW(), 'Online Payment', 'Paid')");
$del_addr = "Campus Delivery: $building, Floor $floor, Room $room";
$del_stmt->bind_param("sidsssssd", $del_order_code, $user1_id, $delivery_fee, $building, $floor, $room, $note, $del_addr, $grand_total_del);
$del_stmt->execute();
$del_order_id = $con->insert_id;

assert_test("Delivery order inserted successfully", $del_order_id > 0, "ID: $del_order_id, Code: $del_order_code");

$d_check = $con->query("SELECT * FROM orders WHERE id = $del_order_id")->fetch_assoc();
assert_test("Delivery delivery_method is 'delivery'", $d_check['delivery_method'] === 'delivery');
assert_test("Delivery delivery_fee is 20.00", floatval($d_check['delivery_fee']) == 20.00);
assert_test("Delivery grand total is Subtotal + Delivery Fee", floatval($d_check['total']) == $grand_total_del, "Total: ₹{$d_check['total']}");
assert_test("Delivery building stored: '$building'", $d_check['delivery_building'] === $building);
assert_test("Delivery floor stored: '$floor'", $d_check['delivery_floor'] === $floor);
assert_test("Delivery room stored: '$room'", $d_check['delivery_room'] === $room);
assert_test("Delivery note stored: '$note'", $d_check['delivery_note'] === $note);

// Test Delivery Status Transitions with Timestamps
echo "\n--- 4. Testing Delivery Status Transitions & Database Timestamps ---\n";

// A. Placed -> Preparing
$con->query("UPDATE orders SET status = 'Preparing', preparing_at = NOW() WHERE id = $del_order_id");
$d_s1 = $con->query("SELECT status, preparing_at FROM orders WHERE id = $del_order_id")->fetch_assoc();
assert_test("Delivery order transitioned to 'Preparing'", $d_s1['status'] === 'Preparing' && !empty($d_s1['preparing_at']), "preparing_at: {$d_s1['preparing_at']}");

// B. Preparing -> Out for Delivery
$con->query("UPDATE orders SET status = 'Out for Delivery', out_for_delivery_at = NOW() WHERE id = $del_order_id");
$d_s2 = $con->query("SELECT status, out_for_delivery_at FROM orders WHERE id = $del_order_id")->fetch_assoc();
assert_test("Delivery order transitioned to 'Out for Delivery'", $d_s2['status'] === 'Out for Delivery' && !empty($d_s2['out_for_delivery_at']), "out_for_delivery_at: {$d_s2['out_for_delivery_at']}");

// C. Out for Delivery -> Delivered
$con->query("UPDATE orders SET status = 'Delivered', delivered_at = NOW() WHERE id = $del_order_id");
$d_s3 = $con->query("SELECT status, delivered_at FROM orders WHERE id = $del_order_id")->fetch_assoc();
assert_test("Delivery order transitioned to 'Delivered'", $d_s3['status'] === 'Delivered' && !empty($d_s3['delivered_at']), "delivered_at: {$d_s3['delivered_at']}");

// 5. Testing api/order-status.php output for Delivery
echo "\n--- 5. Testing Order Status API Output ---\n";
$ch = curl_init("http://127.0.0.1:8000/api/order-status.php?id=$del_order_id");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$api_resp = curl_exec($ch);
curl_close($ch);
$api_data = json_decode($api_resp, true);

assert_test("api/order-status.php returns success", !empty($api_data['success']));
assert_test("api/order-status.php returns delivery_method = 'delivery'", ($api_data['delivery_method'] ?? '') === 'delivery');
assert_test("api/order-status.php returns delivery_room = '$room'", ($api_data['delivery_room'] ?? '') === $room);
assert_test("api/order-status.php returns dynamic estimated_delivery", !empty($api_data['estimated_delivery']), "ETA: {$api_data['estimated_delivery']}");
assert_test("api/order-status.php returns delivered message", strpos($api_data['status_message'] ?? '', 'delivered') !== false, "Message: {$api_data['status_message']}");

echo "\n====================================================\n";
echo "   DELIVERY TEST SUITE RESULTS: $pass_count / $total_count PASSED \n";
echo "====================================================\n";
