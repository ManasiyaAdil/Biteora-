<?php
$page_title = "Order Receipt";
require_once 'includes/connect.php';

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$order_id) {
    header("Location: orders.php");
    exit;
}

$stmt = $con->prepare("SELECT o.*, u.name as customer_name, u.contact as customer_contact, u.email as customer_email 
                       FROM orders o 
                       JOIN users u ON o.customer_id = u.id 
                       WHERE o.id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("Receipt not found");
}

// Security: User must own order or be admin
if (!isset($_SESSION['admin_sid'])) {
    if (!isset($_SESSION['customer_sid']) || $_SESSION['user_id'] != $order['customer_id']) {
        header("Location: login.php");
        exit;
    }
}

// Fetch items
$item_stmt = $con->prepare("SELECT od.*, i.name as item_name, i.price as unit_price, i.is_veg 
                            FROM order_details od 
                            JOIN items i ON od.item_id = i.id 
                            WHERE od.order_id = ?");
$item_stmt->bind_param("i", $order_id);
$item_stmt->execute();
$items = $item_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$is_delivery = (isset($order['delivery_method']) && $order['delivery_method'] === 'delivery');
$items_subtotal = floatval($order['total']) - floatval($order['delivery_fee']);

// Pickup verification URL for QR (pickup only)
$pickup_qr_url = APP_URL . "/verify-pickup.php?token=" . urlencode($order['pickup_verification_token'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Receipt #<?php echo htmlspecialchars($order['order_code'] ?: 'BO-'.$order['id']); ?> | Biteora</title>
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23FF5A36'><path d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9v-2h2v2zm0-4H9V7h2v5z'/></svg>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/campusbite.css">
  <style>
    @media print {
      body { background: #fff !important; color: #000 !important; }
      .no-print { display: none !important; }
      .receipt-box { border: 1px solid #ddd !important; box-shadow: none !important; background: #fff !important; color: #000 !important; }
      .text-orange { color: #FF5A36 !important; }
      .badge { border: 1px solid #000 !important; }
    }
  </style>
</head>
<body style="background:#FFF8F3; min-height:100vh; padding:2.5rem 1rem; display:flex; flex-direction:column; align-items:center;">

  <div class="no-print" style="width:100%; max-width:540px; display:flex; justify-content:space-between; margin-bottom:1.25rem;">
    <a href="track-order.php?id=<?php echo $order_id; ?>" class="btn btn-secondary btn-sm">
      <i class="fa-solid fa-arrow-left"></i> Back to Tracking
    </a>
    <button onclick="window.print()" class="btn btn-primary btn-sm">
      <i class="fa-solid fa-print"></i> Print Official Receipt
    </button>
  </div>

  <div class="receipt-box card" style="width:100%; max-width:540px; padding:2rem; background:#FFFFFF; border:1px solid var(--border-subtle); border-radius:24px; box-shadow:var(--shadow-md);">
    
    <!-- Receipt Header -->
    <div style="text-align:center; padding-bottom:1.25rem; border-bottom:1px dashed var(--border-subtle); margin-bottom:1.25rem;">
      <div class="cb-logo" style="justify-content:center; margin-bottom:0.4rem;">
        <div class="cb-logo-icon">
          <i class="fa-solid fa-utensils"></i>
        </div>
        <span>Bite<span class="text-orange">ora</span></span>
      </div>
      <p style="font-size:0.82rem; color:var(--text-secondary);">Smart Campus Dining Platform • Skip the Queue. Eat Smarter.</p>
      <div style="font-size:0.75rem; color:var(--text-muted); margin-top:0.2rem;">
        <?php echo $is_delivery ? 'Classroom Delivery Service' : 'Student Activity Center • Cafeteria Counter 1'; ?>
      </div>
    </div>

    <!-- Receipt Meta Grid -->
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; font-size:0.85rem; margin-bottom:1.25rem; color:var(--text-secondary);">
      <div>
        <div style="color:var(--text-muted); font-size:0.72rem; text-transform:uppercase;">Order ID</div>
        <strong style="font-family:var(--font-display); font-size:1rem; color:var(--text-primary);">
          #<?php echo htmlspecialchars($order['order_code'] ?: 'CB-'.$order['id']); ?>
        </strong>
      </div>

      <div style="text-align:right;">
        <div style="color:var(--text-muted); font-size:0.72rem; text-transform:uppercase;">Delivery Method</div>
        <span class="badge <?php echo $is_delivery ? 'badge-brand' : 'badge-secondary'; ?>" style="font-size:0.75rem; font-weight:700;">
          <?php echo $is_delivery ? '🚚 Campus Delivery' : '🍽️ Cafeteria Pickup'; ?>
        </span>
      </div>

      <div>
        <div style="color:var(--text-muted); font-size:0.72rem; text-transform:uppercase;">Customer</div>
        <div style="color:var(--text-primary); font-weight:600;"><?php echo htmlspecialchars($order['customer_name']); ?></div>
        <div style="font-size:0.75rem; color:var(--text-muted);"><?php echo htmlspecialchars($order['customer_contact']); ?></div>
      </div>

      <div style="text-align:right;">
        <div style="color:var(--text-muted); font-size:0.72rem; text-transform:uppercase;">Date & Time</div>
        <div style="color:var(--text-primary);"><?php echo date('d M Y, h:i A', strtotime($order['date'])); ?></div>
      </div>
    </div>

    <!-- Destination / Counter Details Box -->
    <div style="background:var(--bg-surface-2); padding:0.85rem 1rem; border-radius:12px; margin-bottom:1.25rem; font-size:0.85rem;">
      <?php if ($is_delivery): ?>
        <div style="font-weight:700; color:var(--text-primary); margin-bottom:0.25rem;">
          <i class="fa-solid fa-location-dot text-orange" style="margin-right:4px;"></i> Deliver To:
        </div>
        <div style="color:var(--text-secondary);">
          Building: <strong><?php echo htmlspecialchars($order['delivery_building'] ?: 'Main College Building'); ?></strong><br>
          Floor: <strong><?php echo htmlspecialchars($order['delivery_floor'] ?: '3'); ?></strong> • Room / Classroom: <strong style="color:var(--brand-primary);"><?php echo htmlspecialchars($order['delivery_room'] ?: '304'); ?></strong>
        </div>
        <?php if (!empty($order['delivery_note'])): ?>
          <div style="font-size:0.78rem; color:var(--text-muted); margin-top:0.35rem; font-style:italic;">
            Note: "<?php echo htmlspecialchars($order['delivery_note']); ?>"
          </div>
        <?php endif; ?>
      <?php else: ?>
        <div style="display:flex; justify-content:space-between; align-items:center;">
          <div>
            <div style="font-weight:700; color:var(--text-primary); margin-bottom:0.15rem;">
              <i class="fa-solid fa-utensils text-orange" style="margin-right:4px;"></i> Pickup Counter:
            </div>
            <div style="color:var(--text-secondary);">
              <?php echo htmlspecialchars($order['address'] ?: 'Main Cafeteria Counter 1'); ?>
            </div>
          </div>
          <?php if (!empty($order['pickup_token'])): ?>
            <div style="text-align:right;">
              <span style="font-size:0.7rem; color:var(--text-muted); text-transform:uppercase; font-weight:700;">Pickup Token</span>
              <div style="font-family:var(--font-display); font-size:1.5rem; font-weight:900; color:var(--brand-primary);">
                #BITP-<?php echo htmlspecialchars($order['pickup_token']); ?>
              </div>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Items Table -->
    <table style="width:100%; border-collapse:collapse; margin-bottom:1.25rem; font-size:0.88rem;">
      <thead>
        <tr style="border-bottom:1.5px solid var(--border-subtle); color:var(--text-muted); text-align:left; font-size:0.75rem; text-transform:uppercase;">
          <th style="padding:0.6rem 0;">Item</th>
          <th style="padding:0.6rem 0; text-align:center;">Qty</th>
          <th style="padding:0.6rem 0; text-align:right;">Price</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $item): ?>
          <tr style="border-bottom:1px solid var(--border-subtle); color:var(--text-primary);">
            <td style="padding:0.7rem 0;">
              <span class="<?php echo $item['is_veg'] ? 'veg-icon' : 'nonveg-icon'; ?>" style="margin-right:6px;"></span>
              <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
            </td>
            <td style="padding:0.7rem 0; text-align:center; color:var(--text-secondary);"><?php echo $item['quantity']; ?></td>
            <td style="padding:0.7rem 0; text-align:right; font-weight:700;">₹<?php echo number_format($item['price'], 2); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <!-- Totals & Delivery Fee -->
    <div style="background:var(--bg-surface-2); padding:1rem; border-radius:12px; margin-bottom:1.25rem; font-size:0.88rem;">
      <div style="display:flex; justify-content:space-between; margin-bottom:0.35rem; color:var(--text-secondary);">
        <span>Subtotal</span>
        <span style="font-weight:700; color:var(--text-primary);">₹<?php echo number_format($items_subtotal, 2); ?></span>
      </div>

      <div style="display:flex; justify-content:space-between; margin-bottom:0.35rem; color:var(--text-secondary);">
        <span>Delivery Fee (<?php echo $is_delivery ? 'Campus Delivery' : 'Pickup'; ?>)</span>
        <?php if ($is_delivery): ?>
          <span style="font-weight:700; color:var(--brand-primary);">₹<?php echo number_format($order['delivery_fee'], 2); ?></span>
        <?php else: ?>
          <span class="text-emerald" style="font-weight:700;">₹0.00 (FREE)</span>
        <?php endif; ?>
      </div>

      <div style="border-top:1px solid var(--border-subtle); padding-top:0.65rem; display:flex; justify-content:space-between; align-items:center;">
        <span style="font-weight:800; font-size:1rem; color:var(--text-primary);">Grand Total Paid</span>
        <span style="font-family:var(--font-display); font-weight:900; font-size:1.4rem; color:var(--brand-primary);">₹<?php echo number_format($order['total'], 2); ?></span>
      </div>
    </div>

    <!-- Security & Verification Stamp -->
    <div style="display:flex; align-items:center; justify-content:space-between; border-top:1px dashed var(--border-subtle); padding-top:1.25rem; font-size:0.78rem;">
      <div>
        <div style="color:var(--text-muted);">Payment Method: <strong style="color:var(--text-primary);"><?php echo htmlspecialchars($order['payment_type'] ?? 'Online'); ?></strong></div>
        <div style="color:var(--text-muted); margin-top:2px;">Payment Status: <strong class="text-emerald"><?php echo htmlspecialchars($order['payment_status'] ?? 'Paid'); ?> ✓</strong></div>
        <?php if (!empty($order['razorpay_payment_id'])): ?>
          <div style="color:var(--text-muted); font-size:0.7rem; font-family:monospace; margin-top:2px;">TXN: <?php echo htmlspecialchars($order['razorpay_payment_id']); ?></div>
        <?php endif; ?>
      </div>

      <?php if (!$is_delivery): ?>
        <div style="text-align:center;">
          <img src="https://api.qrserver.com/v1/create-qr-code/?size=90x90&data=<?php echo urlencode($pickup_qr_url); ?>" alt="Receipt QR" style="width:70px; height:70px; border-radius:6px; border:1px solid var(--border-subtle);">
          <span style="display:block; font-size:0.65rem; color:var(--text-muted); margin-top:2px;">Pickup QR</span>
        </div>
      <?php else: ?>
        <div style="text-align:right;">
          <span class="badge badge-brand" style="font-size:0.72rem; padding:0.3rem 0.65rem;">
            <i class="fa-solid fa-shield-check"></i> Verified Campus Delivery
          </span>
        </div>
      <?php endif; ?>
    </div>

  </div>

</body>
</html>
