<?php
@header('Content-Type: application/json');
require_once __DIR__ . '/../includes/connect.php';

if (!isset($_SESSION['customer_sid']) || !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$delivery_method = isset($_POST['delivery_method']) && strtolower(trim($_POST['delivery_method'])) === 'delivery' ? 'delivery' : 'pickup';

$delivery_fee = 0.00;
$delivery_building = null;
$delivery_floor = null;
$delivery_room = null;
$delivery_note = null;

if ($delivery_method === 'delivery') {
    $delivery_building = isset($_POST['delivery_building']) ? trim(htmlspecialchars($_POST['delivery_building'])) : '';
    $delivery_floor = isset($_POST['delivery_floor']) ? trim(htmlspecialchars($_POST['delivery_floor'])) : '';
    $delivery_room = isset($_POST['delivery_room']) ? trim(htmlspecialchars($_POST['delivery_room'])) : '';
    $delivery_note = isset($_POST['delivery_note']) ? trim(htmlspecialchars($_POST['delivery_note'])) : '';

    if (empty($delivery_building) || $delivery_floor === '' || empty($delivery_room)) {
        echo json_encode(['success' => false, 'error' => 'Please provide Building, Floor, and Room / Classroom for campus delivery.']);
        exit;
    }

    $delivery_fee = floatval(defined('CAMPUS_DELIVERY_FEE') ? CAMPUS_DELIVERY_FEE : 20.00);
    $address = "Campus Delivery: {$delivery_building}, Fl {$delivery_floor}, Rm {$delivery_room}";
    $description = $delivery_note;
} else {
    $delivery_fee = 0.00;
    $address = isset($_POST['address']) && !empty(trim($_POST['address'])) ? trim(htmlspecialchars($_POST['address'])) : 'Campus Cafeteria Counter 1';
    $description = isset($_POST['description']) ? trim(htmlspecialchars($_POST['description'])) : '';
}

$payment_method = isset($_POST['payment_type']) ? trim($_POST['payment_type']) : 'Online Payment';

// Parse items from JSON or POST fields
$items_input = [];
if (isset($_POST['items_json'])) {
    $items_input = json_decode($_POST['items_json'], true) ?: [];
} else {
    foreach ($_POST as $k => $v) {
        if (is_numeric($k) && intval($v) > 0) {
            $items_input[] = ['id' => intval($k), 'qty' => intval($v)];
        }
    }
}

if (empty($items_input)) {
    echo json_encode(['success' => false, 'error' => 'Your cart is empty']);
    exit;
}

// 1. Server-side validation of item availability and price calculation
$items_subtotal = 0.00;
$validated_items = [];

foreach ($items_input as $entry) {
    $item_id = intval($entry['id'] ?? 0);
    $qty = intval($entry['qty'] ?? 1);

    if ($item_id > 0 && $qty > 0) {
        $stmt = $con->prepare("SELECT id, name, price, is_available FROM items WHERE id = ? AND deleted = 0");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($item = $res->fetch_assoc()) {
            if ($item['is_available'] != 1) {
                echo json_encode(['success' => false, 'error' => "Item '{$item['name']}' is currently sold out"]);
                exit;
            }
            $unit_price = floatval($item['price']);
            $line_total = $unit_price * $qty;
            $items_subtotal += $line_total;

            $validated_items[] = [
                'id' => $item_id,
                'name' => $item['name'],
                'qty' => $qty,
                'price' => $line_total
            ];
        }
    }
}

if (empty($validated_items) || $items_subtotal <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid order calculation']);
    exit;
}

// Grand Total = Subtotal + Delivery Fee (calculated strictly on server!)
$grand_total = $items_subtotal + $delivery_fee;

// 2. Generate unique Order ID & Pickup Verification Token
$order_number_query = $con->query("SELECT MAX(id) as max_id FROM orders");
$order_count = $order_number_query ? intval($order_number_query->fetch_assoc()['max_id']) + 1 : 1;
$order_code = sprintf("CB-2026-%05d", $order_count);

// Generate Daily Pickup Token
$today_start = date('Y-m-d 00:00:00');
$today_count_query = $con->query("SELECT COUNT(*) as today_count FROM orders WHERE date >= '$today_start'");
$today_count = $today_count_query ? intval($today_count_query->fetch_assoc()['today_count']) + 1 : 1;
$pickup_token = (string)($today_count % 100 == 0 ? 100 : $today_count % 100);

// Crypto-secure 64-char pickup verification token
$pickup_verification_token = bin2hex(random_bytes(32));

// Initial status
$initial_payment_status = ($payment_method === 'Cash On Delivery') ? 'Pending' : 'Pending';

// 3. Create Pending Order Record in Database
$stmt = $con->prepare("INSERT INTO orders (order_code, customer_id, delivery_method, delivery_fee, delivery_building, delivery_floor, delivery_room, delivery_note, pickup_token, address, description, payment_type, payment_status, pickup_verification_token, total, status, date, placed_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Placed', NOW(), NOW())");
$stmt->bind_param("sisdssssssssssd", $order_code, $user_id, $delivery_method, $delivery_fee, $delivery_building, $delivery_floor, $delivery_room, $delivery_note, $pickup_token, $address, $description, $payment_method, $initial_payment_status, $pickup_verification_token, $grand_total);

if (!$stmt->execute()) {
    // If order_code clashed, fallback with random suffix
    $order_code = sprintf("CB-2026-%05d-%03d", $order_count, mt_rand(100, 999));
    $stmt->bind_param("sisdssssssssssd", $order_code, $user_id, $delivery_method, $delivery_fee, $delivery_building, $delivery_floor, $delivery_room, $delivery_note, $pickup_token, $address, $description, $payment_method, $initial_payment_status, $pickup_verification_token, $grand_total);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'error' => 'Failed to initialize order: ' . $con->error]);
        exit;
    }
}

$order_id = $con->insert_id;

// Insert Order Details
$detail_stmt = $con->prepare("INSERT INTO order_details (order_id, item_id, quantity, price) VALUES (?, ?, ?, ?)");
foreach ($validated_items as $vi) {
    $detail_stmt->bind_param("iiid", $order_id, $vi['id'], $vi['qty'], $vi['price']);
    $detail_stmt->execute();
}

// 4. Handle based on payment method
if ($payment_method === 'Wallet') {
    // Check wallet balance
    $w_res = $con->query("SELECT wd.id as wd_id, wd.balance FROM wallet w JOIN wallet_details wd ON w.id = wd.wallet_id WHERE w.customer_id = $user_id");
    if ($w_res && $w_row = $w_res->fetch_assoc()) {
        $cur_bal = floatval($w_row['balance']);
        if ($cur_bal < $grand_total) {
            $con->query("UPDATE orders SET payment_status = 'Failed' WHERE id = $order_id");
            echo json_encode(['success' => false, 'error' => 'Insufficient campus wallet balance (Total: ₹' . number_format($grand_total, 2) . ', Balance: ₹' . number_format($cur_bal, 2) . ')']);
            exit;
        }
        // Deduct
        $new_bal = $cur_bal - $grand_total;
        $wd_id = $w_row['wd_id'];
        $con->query("UPDATE wallet_details SET balance = $new_bal WHERE id = $wd_id");
        $con->query("UPDATE orders SET payment_status = 'Paid', payment_verified_at = NOW() WHERE id = $order_id");

        echo json_encode([
            'success' => true,
            'payment_method' => 'Wallet',
            'order_id' => $order_id,
            'order_code' => $order_code,
            'delivery_method' => $delivery_method,
            'delivery_fee' => $delivery_fee,
            'subtotal' => $items_subtotal,
            'total' => $grand_total,
            'pickup_token' => $pickup_token,
            'redirect' => "order-confirmation.php?id=$order_id"
        ]);
        exit;
    }
} elseif ($payment_method === 'Cash On Delivery') {
    // Cash on pickup / delivery
    $con->query("UPDATE orders SET payment_status = 'Pending' WHERE id = $order_id");
    echo json_encode([
        'success' => true,
        'payment_method' => 'Cash On Delivery',
        'order_id' => $order_id,
        'order_code' => $order_code,
        'delivery_method' => $delivery_method,
        'delivery_fee' => $delivery_fee,
        'subtotal' => $items_subtotal,
        'total' => $grand_total,
        'pickup_token' => $pickup_token,
        'redirect' => "order-confirmation.php?id=$order_id"
    ]);
    exit;
}

// 5. Razorpay Online Payment Flow - Uses Server-Calculated Grand Total
$rzp_order = razorpay_create_order($grand_total, $order_code, [
    'order_id' => $order_id,
    'customer_id' => $user_id,
    'order_code' => $order_code,
    'delivery_method' => $delivery_method,
    'delivery_fee' => $delivery_fee
]);

if (empty($rzp_order['success']) || empty($rzp_order['order_id'])) {
    $err_msg = $rzp_order['error'] ?? 'Failed to create genuine Razorpay payment order';
    $http_code = $rzp_order['http_code'] ?? 500;
    http_response_code($http_code >= 400 && $http_code < 600 ? $http_code : 500);
    echo json_encode([
        'success' => false,
        'error' => $err_msg,
        'http_status' => $http_code,
        'order_id' => $order_id
    ]);
    exit;
}

$razorpay_order_id = $rzp_order['order_id'];
$con->query("UPDATE orders SET razorpay_order_id = '$razorpay_order_id' WHERE id = $order_id");

// Fetch student info
$u_stmt = $con->prepare("SELECT name, email, contact FROM users WHERE id = ?");
$u_stmt->bind_param("i", $user_id);
$u_stmt->execute();
$student = $u_stmt->get_result()->fetch_assoc();

// Construct mobile checkout payment link / QR data pointing to mobile-payment.php
$payment_qr_target = APP_URL . "/mobile-payment.php?order_id={$order_id}";

echo json_encode([
    'success' => true,
    'payment_method' => 'Online Payment',
    'order_id' => $order_id,
    'order_code' => $order_code,
    'delivery_method' => $delivery_method,
    'delivery_fee' => $delivery_fee,
    'subtotal' => $items_subtotal,
    'pickup_token' => $pickup_token,
    'razorpay_order_id' => $razorpay_order_id,
    'razorpay_key_id' => RAZORPAY_KEY_ID,
    'amount' => intval(round($grand_total * 100)),
    'amount_formatted' => number_format($grand_total, 2),
    'currency' => 'INR',
    'student' => [
        'name' => $student['name'] ?? 'Student',
        'email' => $student['email'] ?? 'student@campusbite.edu',
        'contact' => $student['contact'] ?? '9876543210'
    ],
    'payment_qr_url' => $payment_qr_target,
    'is_test_mode' => (PAYMENT_ENV === 'test')
]);
