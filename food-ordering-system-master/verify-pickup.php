<?php
$page_title = "Cafeteria Pickup Verification";
require_once 'includes/connect.php';

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$action = isset($_POST['action']) ? $_POST['action'] : '';

$order = null;
$error = '';
$success_msg = '';

if (empty($token)) {
    $error = "Invalid or missing pickup verification QR token.";
} else {
    // 1. Retrieve order by secure pickup_verification_token
    $stmt = $con->prepare("SELECT o.*, u.name as customer_name, u.contact as customer_contact 
                           FROM orders o 
                           JOIN users u ON o.customer_id = u.id 
                           WHERE o.pickup_verification_token = ? AND o.deleted = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (!$order) {
        $error = "No matching cafeteria order found for this verification QR.";
    }
}

// 2. Handle Staff Pickup Confirmation Action
if ($order && $action === 'confirm_pickup' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Re-verify conditions
    if ($order['payment_status'] !== 'Paid') {
        $error = "Payment has not been captured. Pickup cannot be confirmed.";
    } elseif ($order['picked_up_at'] !== null || $order['status'] === 'Completed') {
        $error = "This order was already picked up on " . date('d M Y, h:i A', strtotime($order['picked_up_at'] ?: $order['updated_at']));
    } else {
        // Mark as completed and record pickup timestamp
        $up_stmt = $con->prepare("UPDATE orders SET status = 'Completed', picked_up_at = NOW() WHERE id = ?");
        $up_stmt->bind_param("i", $order['id']);
        if ($up_stmt->execute()) {
            $success_msg = "Pickup confirmed successfully! Order marked as Completed.";
            // Refresh order
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();
        }
    }
}

// Fetch item details if order exists
$order_items = [];
if ($order) {
    $it_stmt = $con->prepare("SELECT od.*, i.name as item_name, i.is_veg FROM order_details od JOIN items i ON od.item_id = i.id WHERE od.order_id = ?");
    $it_stmt->bind_param("i", $order['id']);
    $it_stmt->execute();
    $order_items = $it_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pickup Verification | CampusBite</title>
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23FF5A36'><path d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9v-2h2v2zm0-4H9V7h2v5z'/></svg>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/campusbite.css">
</head>
<body style="background:#0B0B0D; min-height:100vh; padding:2rem 1rem; display:flex; align-items:center; justify-content:center;">

  <div class="card" style="width:100%; max-width:520px; padding:2rem; background:var(--bg-surface); border:1px solid var(--border-subtle); border-radius:20px; box-shadow:var(--shadow-lg);">
    
    <!-- Top Header -->
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem; padding-bottom:0.85rem; border-bottom:1px solid var(--border-subtle);">
      <div class="cb-logo">
        <div class="cb-logo-icon" style="width:32px; height:32px; font-size:0.85rem;">
          <i class="fa-solid fa-utensils"></i>
        </div>
        <span style="font-size:1.15rem;">Campus<span class="text-orange">Bite</span></span>
      </div>
      <span class="badge badge-brand"><i class="fa-solid fa-qrcode"></i> Staff Scanner</span>
    </div>

    <?php if (!empty($error)): ?>
      <div style="background:var(--danger-bg); border:1px solid rgba(239,68,68,0.3); border-radius:10px; padding:1rem; margin-bottom:1.25rem; color:var(--danger); display:flex; gap:0.65rem; align-items:center;">
        <i class="fa-solid fa-triangle-exclamation" style="font-size:1.2rem;"></i>
        <div style="font-size:0.88rem; font-weight:600;"><?php echo htmlspecialchars($error); ?></div>
      </div>
    <?php endif; ?>

    <?php if (!empty($success_msg)): ?>
      <div style="background:var(--success-bg); border:1px solid rgba(34,197,94,0.3); border-radius:10px; padding:1rem; margin-bottom:1.25rem; color:var(--success); display:flex; gap:0.65rem; align-items:center;">
        <i class="fa-solid fa-circle-check" style="font-size:1.2rem;"></i>
        <div style="font-size:0.88rem; font-weight:600;"><?php echo htmlspecialchars($success_msg); ?></div>
      </div>
    <?php endif; ?>

    <?php if ($order): ?>
      <!-- Token Highlight -->
      <div style="background:var(--bg-surface-2); border:1px solid var(--border-subtle); border-radius:14px; padding:1.25rem; text-align:center; margin-bottom:1.25rem;">
        <div style="font-size:0.75rem; color:var(--brand-primary); text-transform:uppercase; font-weight:800;">VERIFIED PICKUP TOKEN</div>
        <div style="font-family:var(--font-display); font-size:3.2rem; font-weight:900; color:#FFFFFF; line-height:1.1; margin:0.25rem 0;">
          #<?php echo htmlspecialchars($order['pickup_token'] ?: $order['id']); ?>
        </div>
        <div style="font-size:0.85rem; color:var(--text-secondary);">
          Order #<?php echo htmlspecialchars($order['order_code'] ?: 'CB-'.$order['id']); ?> • Customer: <strong style="color:#FFFFFF;"><?php echo htmlspecialchars($order['customer_name']); ?></strong>
        </div>
      </div>

      <!-- Status Summary Badges -->
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.85rem; margin-bottom:1.25rem;">
        <div style="background:var(--bg-surface-2); padding:0.85rem; border-radius:10px; text-align:center;">
          <div style="font-size:0.72rem; color:var(--text-muted); text-transform:uppercase;">Payment Status</div>
          <div style="font-weight:800; font-size:0.95rem; color:<?php echo ($order['payment_status']=='Paid'?'var(--success)':'var(--warning)'); ?>;">
            <?php echo htmlspecialchars($order['payment_status'] ?? 'Pending'); ?>
          </div>
        </div>
        <div style="background:var(--bg-surface-2); padding:0.85rem; border-radius:10px; text-align:center;">
          <div style="font-size:0.72rem; color:var(--text-muted); text-transform:uppercase;">Order Status</div>
          <div style="font-weight:800; font-size:0.95rem; color:<?php echo ($order['status']=='Completed'?'var(--success)':($order['status']=='Ready'?'var(--brand-primary)':'var(--warning)')); ?>;">
            <?php echo htmlspecialchars($order['status']); ?>
          </div>
        </div>
      </div>

      <!-- Items List -->
      <div style="background:var(--bg-surface-2); padding:1rem; border-radius:10px; margin-bottom:1.25rem;">
        <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:700; margin-bottom:0.5rem;">Items to Hand Over</div>
        <div style="display:flex; flex-direction:column; gap:0.4rem; font-size:0.88rem;">
          <?php foreach ($order_items as $oi): ?>
            <div style="display:flex; justify-content:space-between; color:#FFFFFF;">
              <span><?php echo htmlspecialchars($oi['item_name']); ?></span>
              <strong style="color:var(--brand-primary);">Qty: <?php echo $oi['quantity']; ?></strong>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Action Button -->
      <?php if ($order['status'] !== 'Completed' && $order['picked_up_at'] === null): ?>
        <form method="POST">
          <input type="hidden" name="action" value="confirm_pickup">
          <button type="submit" class="btn btn-primary btn-lg" style="width:100%; font-size:1.05rem; padding:0.9rem;">
            <i class="fa-solid fa-check-double"></i> Confirm Pickup & Complete
          </button>
        </form>
      <?php else: ?>
        <div style="text-align:center; padding:0.75rem; background:var(--bg-surface-2); border-radius:10px; color:var(--text-muted); font-size:0.85rem;">
          <i class="fa-solid fa-circle-check text-emerald"></i> This order has already been handed over to student.
        </div>
      <?php endif; ?>

    <?php endif; ?>

  </div>

</body>
</html>
