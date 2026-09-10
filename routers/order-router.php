<?php
require_once __DIR__ . '/../includes/connect.php';

if (!isset($_SESSION['customer_sid']) || !isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = intval($_SESSION['user_id']);
$address = isset($_POST['address']) ? trim(htmlspecialchars($_POST['address'])) : 'Campus Cafeteria Counter 1';
$description = isset($_POST['description']) ? trim(htmlspecialchars($_POST['description'])) : '';
$payment_type = isset($_POST['payment_type']) ? trim($_POST['payment_type']) : 'Wallet';

// 1. Process items and calculate server-side validated total
$ordered_items = [];
$calculated_total = 0.00;

foreach ($_POST as $key => $value) {
    if (is_numeric($key)) {
        $item_id = intval($key);
        $qty = intval($value);

        if ($qty > 0) {
            $stmt = $con->prepare("SELECT id, name, price, is_available FROM items WHERE id = ? AND deleted = 0");
            $stmt->bind_param("i", $item_id);
            $stmt->execute();
            $item_res = $stmt->get_result();

            if ($item_row = $item_res->fetch_assoc()) {
                if ($item_row['is_available'] != 1) {
                    die("Sorry, item '" . htmlspecialchars($item_row['name']) . "' is currently sold out.");
                }
                $price = floatval($item_row['price']);
                $item_total = $price * $qty;
                $calculated_total += $item_total;

                $ordered_items[] = [
                    'id' => $item_id,
                    'qty' => $qty,
                    'price' => $item_total
                ];
            }
        }
    }
}

if (empty($ordered_items)) {
    header("Location: ../cart.php?error=empty_cart");
    exit;
}

$delivery_method = isset($_POST['delivery_method']) && strtolower(trim($_POST['delivery_method'])) === 'delivery' ? 'delivery' : 'pickup';
$delivery_fee = 0.00;
$delivery_building = null;
$delivery_floor = null;
$delivery_room = null;
$delivery_note = null;

if ($delivery_method === 'delivery') {
    $delivery_fee = floatval(defined('CAMPUS_DELIVERY_FEE') ? CAMPUS_DELIVERY_FEE : 20.00);
    $delivery_building = isset($_POST['delivery_building']) ? trim(htmlspecialchars($_POST['delivery_building'])) : '';
    $delivery_floor = isset($_POST['delivery_floor']) ? trim(htmlspecialchars($_POST['delivery_floor'])) : '';
    $delivery_room = isset($_POST['delivery_room']) ? trim(htmlspecialchars($_POST['delivery_room'])) : '';
    $delivery_note = isset($_POST['delivery_note']) ? trim(htmlspecialchars($_POST['delivery_note'])) : '';
    $address = "Campus Delivery: {$delivery_building}, Floor {$delivery_floor}, Room {$delivery_room}";
    $description = $delivery_note;
}

$grand_total = $calculated_total + $delivery_fee;

// 2. If payment is Wallet, verify balance and deduct
if ($payment_type === 'Wallet') {
    $w_res = $con->query("SELECT wd.id as wd_id, wd.balance FROM wallet w JOIN wallet_details wd ON w.id = wd.wallet_id WHERE w.customer_id = $user_id");
    if ($w_res && $w_row = $w_res->fetch_assoc()) {
        $current_balance = floatval($w_row['balance']);
        if ($current_balance < $grand_total) {
            die("Insufficient campus wallet balance (Current: ₹" . number_format($current_balance, 2) . ", Order: ₹" . number_format($grand_total, 2) . "). Please choose UPI or Cash on Delivery.");
        }
        // Deduct balance
        $new_balance = $current_balance - $grand_total;
        $wd_id = $w_row['wd_id'];
        $con->query("UPDATE wallet_details SET balance = $new_balance WHERE id = $wd_id");
    }
}

// 3. Generate Unique Order ID (e.g. CB-2026-00142) and Pickup Token (e.g. 42)
$order_number_query = $con->query("SELECT COUNT(*) as total_orders FROM orders");
$order_count = $order_number_query ? $order_number_query->fetch_assoc()['total_orders'] + 1 : 1;
$order_code = sprintf("CB-2026-%05d", $order_count);

// Generate daily pickup token (1 to 99 sequence)
$today_start = date('Y-m-d 00:00:00');
$today_count_query = $con->query("SELECT COUNT(*) as today_count FROM orders WHERE date >= '$today_start'");
$today_count = $today_count_query ? $today_count_query->fetch_assoc()['today_count'] + 1 : 1;
$pickup_token = (string)($today_count % 100 == 0 ? 100 : $today_count % 100);

$payment_status = ($payment_type === 'Wallet' || $payment_type === 'UPI at Counter') ? 'Paid' : 'Pending';

// 4. Save Order
$stmt = $con->prepare("INSERT INTO orders (order_code, customer_id, delivery_method, delivery_fee, delivery_building, delivery_floor, delivery_room, delivery_note, pickup_token, address, description, payment_type, payment_status, total, status, date, placed_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Placed', NOW(), NOW())");
$stmt->bind_param("sisdsssssssssd", $order_code, $user_id, $delivery_method, $delivery_fee, $delivery_building, $delivery_floor, $delivery_room, $delivery_note, $pickup_token, $address, $description, $payment_type, $payment_status, $grand_total);

if ($stmt->execute()) {
    $order_id = $con->insert_id;

    // 5. Save Order Details
    $detail_stmt = $con->prepare("INSERT INTO order_details (order_id, item_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($ordered_items as $item) {
        $detail_stmt->bind_param("iiid", $order_id, $item['id'], $item['qty'], $item['price']);
        $detail_stmt->execute();
    }

    // Redirect to Order Tracking with cleared cart flag
    header("Location: ../track-order.php?id=" . $order_id . "&placed=1");
    exit;
} else {
    die("Failed to create order. Error: " . $con->error);
}
?>