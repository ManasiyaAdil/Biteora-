<?php
@header('Content-Type: application/json');
require_once __DIR__ . '/../includes/connect.php';

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$order_id) {
    echo json_encode(['error' => 'Missing order ID']);
    exit;
}

$stmt = $con->prepare("SELECT id, order_code, pickup_token, status, payment_status, total, date,
                              delivery_method, delivery_fee, delivery_building, delivery_floor, delivery_room, delivery_note,
                              placed_at, preparing_at, out_for_delivery_at, delivered_at, picked_up_at
                       FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    $delivery_method = $row['delivery_method'] ?: 'pickup';
    $base_time = strtotime($row['placed_at'] ?: $row['date']);
    $elapsed = time() - $base_time;

    // 2-MINUTE HACKATHON DEMO ORDER LIFECYCLE (Complete delivery progression within 120 seconds)
    // Real DB transitions and timestamps:
    // Campus Delivery: Placed (0-30s) -> Preparing (30-60s) -> Out for Delivery (60-90s) -> Delivered (90s+)
    // Cafeteria Pickup: Placed (0-30s) -> Preparing (30-60s) -> Ready (60-90s) -> Completed (90s+)
    if (!in_array($row['status'], ['Cancelled', 'Refunded'])) {
        if ($delivery_method === 'delivery') {
            if ($elapsed >= 90 && in_array($row['status'], ['Placed', 'Preparing', 'Out for Delivery'])) {
                $con->query("UPDATE orders SET status = 'Delivered', delivered_at = NOW() WHERE id = $order_id");
                $row['status'] = 'Delivered';
                $row['delivered_at'] = date('Y-m-d H:i:s');
            } elseif ($elapsed >= 60 && in_array($row['status'], ['Placed', 'Preparing'])) {
                $con->query("UPDATE orders SET status = 'Out for Delivery', out_for_delivery_at = NOW() WHERE id = $order_id");
                $row['status'] = 'Out for Delivery';
                $row['out_for_delivery_at'] = date('Y-m-d H:i:s');
            } elseif ($elapsed >= 30 && $row['status'] === 'Placed') {
                $con->query("UPDATE orders SET status = 'Preparing', preparing_at = NOW() WHERE id = $order_id");
                $row['status'] = 'Preparing';
                $row['preparing_at'] = date('Y-m-d H:i:s');
            }
        } else {
            if ($elapsed >= 90 && in_array($row['status'], ['Placed', 'Preparing', 'Ready'])) {
                $con->query("UPDATE orders SET status = 'Completed', picked_up_at = NOW() WHERE id = $order_id");
                $row['status'] = 'Completed';
                $row['picked_up_at'] = date('Y-m-d H:i:s');
            } elseif ($elapsed >= 60 && in_array($row['status'], ['Placed', 'Preparing'])) {
                $con->query("UPDATE orders SET status = 'Ready' WHERE id = $order_id");
                $row['status'] = 'Ready';
            } elseif ($elapsed >= 30 && $row['status'] === 'Placed') {
                $con->query("UPDATE orders SET status = 'Preparing', preparing_at = NOW() WHERE id = $order_id");
                $row['status'] = 'Preparing';
                $row['preparing_at'] = date('Y-m-d H:i:s');
            }
        }
    }
    
    // Calculate realistic delivery ETA & status messages
    $eta = "Delivery time will be updated shortly.";
    $status_msg = "Order placed successfully.";

    if ($delivery_method === 'delivery') {
        if ($row['status'] === 'Delivered') {
            $delivered_time = $row['delivered_at'] ? date('h:i A', strtotime($row['delivered_at'])) : date('h:i A');
            $eta = "Delivered at " . $delivered_time;
            $status_msg = "Your order has been delivered. Enjoy your meal!";
        } elseif ($row['status'] === 'Out for Delivery') {
            $eta = date('h:i A', $base_time + (20 * 60));
            $status_msg = "🚚 Your BITEORA order has left the cafeteria and is on the way!";
        } elseif ($row['status'] === 'Preparing') {
            $eta = date('h:i A', $base_time + (25 * 60));
            $status_msg = "Chef is preparing your fresh meal in the kitchen.";
        } else {
            $eta = date('h:i A', $base_time + (30 * 60));
            $status_msg = "Order queued for preparation.";
        }
    } else {
        if ($row['status'] === 'Completed') {
            $eta = "Picked up";
            $status_msg = "Order picked up. Thank you!";
        } elseif ($row['status'] === 'Ready') {
            $eta = "Ready Now";
            $status_msg = "Your order is ready at Counter 1!";
        } elseif ($row['status'] === 'Preparing') {
            $eta = date('h:i A', $base_time + (12 * 60));
            $status_msg = "Preparing your meal at the cafeteria kitchen.";
        } else {
            $eta = date('h:i A', $base_time + (15 * 60));
            $status_msg = "Order queued at cafeteria.";
        }
    }

    $is_finished = in_array($row['status'], ['Delivered', 'Completed']);
    $demo_seconds_left = $is_finished ? 0 : max(0, 120 - $elapsed);
    $demo_pct = $is_finished ? 100 : min(100, intval(($elapsed / 120) * 100));

    echo json_encode([
        'success' => true,
        'id' => $row['id'],
        'order_code' => $row['order_code'],
        'pickup_token' => $row['pickup_token'],
        'status' => $row['status'],
        'payment_status' => $row['payment_status'],
        'total' => $row['total'],
        'date' => $row['date'],
        'delivery_method' => $delivery_method,
        'delivery_fee' => floatval($row['delivery_fee']),
        'delivery_building' => $row['delivery_building'],
        'delivery_floor' => $row['delivery_floor'],
        'delivery_room' => $row['delivery_room'],
        'delivery_note' => $row['delivery_note'],
        'placed_at' => $row['placed_at'],
        'preparing_at' => $row['preparing_at'],
        'out_for_delivery_at' => $row['out_for_delivery_at'],
        'delivered_at' => $row['delivered_at'],
        'estimated_delivery' => $eta,
        'status_message' => $status_msg,
        'demo_seconds_remaining' => $demo_seconds_left,
        'demo_elapsed_seconds' => $elapsed,
        'demo_progress_percent' => $demo_pct
    ]);
} else {
    echo json_encode(['error' => 'Order not found']);
}
