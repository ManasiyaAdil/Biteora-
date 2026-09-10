<?php
$page_title = "Order Confirmed | Biteora";
require_once 'includes/connect.php';

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$order_id) {
    header("Location: orders.php");
    exit;
}

$stmt = $con->prepare("SELECT o.*, u.name as customer_name, u.contact as customer_contact 
                       FROM orders o 
                       JOIN users u ON o.customer_id = u.id 
                       WHERE o.id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("Order not found");
}

// Security
if (!isset($_SESSION['admin_sid'])) {
    if (!isset($_SESSION['customer_sid']) || $_SESSION['user_id'] != $order['customer_id']) {
        header("Location: login.php");
        exit;
    }
}

$is_delivery = (isset($order['delivery_method']) && $order['delivery_method'] === 'delivery');
$base_time = strtotime($order['placed_at'] ?: $order['date']);
$estimated_time = $is_delivery ? date('h:i A', $base_time + (25 * 60)) : date('h:i A', $base_time + (15 * 60));

// Pickup verification URL for genuine QR scanning
$pickup_qr_url = APP_URL . "/verify-pickup.php?token=" . urlencode($order['pickup_verification_token'] ?? '');

include 'includes/header.php';
?>

<div class="container" style="padding: 3rem 1.5rem 5rem; display:flex; flex-direction:column; align-items:center;">
  
  <div class="card" style="width:100%; max-width:620px; padding:2.25rem 2rem; background:#FFFFFF; border:1px solid var(--border-subtle); border-radius:24px; box-shadow:var(--shadow-lg); text-align:center;">
    
    <!-- Success Icon Header -->
    <div style="width:68px; height:68px; border-radius:50%; background:var(--success); color:#FFFFFF; display:flex; align-items:center; justify-content:center; font-size:2.2rem; margin:0 auto 1.25rem; box-shadow:0 6px 20px rgba(34, 160, 107, 0.3);">
      <i class="fa-solid fa-check"></i>
    </div>

    <span class="badge badge-success" style="font-size:0.8rem; padding:0.4rem 0.95rem; margin-bottom:0.75rem;">
      <i class="fa-solid fa-check-double"></i> ✓ ORDER PLACED SUCCESSFULLY
    </span>

    <h1 style="font-family:var(--font-display); font-size:2.1rem; font-weight:900; color:var(--text-primary); margin-bottom:0.35rem;">
      Order #<?php echo htmlspecialchars($order['order_code'] ?: 'BIT-2026-'.$order['id']); ?>
    </h1>
    <p style="font-size:0.92rem; color:var(--text-secondary); margin-bottom:1.75rem;">
      Payment: <?php echo biteora_render_payment_badge($order['payment_status'] ?? 'Paid'); ?>
      <?php if (!empty($order['razorpay_payment_id'])): ?>
        &nbsp;<span style="font-size:0.75rem; color:var(--text-muted); font-family:monospace;">TXN: <?php echo htmlspecialchars($order['razorpay_payment_id']); ?></span>
      <?php endif; ?>
      &nbsp;• Placed on <?php echo date('d M Y, h:i A', strtotime($order['date'])); ?>
    </p>
    <?php if (!in_array($order['payment_status'] ?? 'Paid', ['Paid'])): ?>
    <div style="background:#FFFBEB; border:1px solid #FDE68A; border-radius:14px; padding:1rem 1.25rem; margin-bottom:1.5rem; display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
      <div style="font-weight:700; color:#B45309; font-size:0.92rem;">
        <i class="fa-solid fa-circle-exclamation"></i> Payment is not completed yet.
      </div>
      <a href="mobile-payment.php?order_id=<?php echo $order['id']; ?>" class="btn" style="background:#FF5A36; color:#fff; font-weight:800; padding:0.6rem 1.25rem; border-radius:10px;">
        <i class="fa-solid fa-credit-card"></i> Complete Payment Now
      </a>
    </div>
    <?php endif; ?>

    <?php if ($is_delivery): ?>
      <!-- CAMPUS DELIVERY DETAILS BOX -->
      <div style="background:var(--bg-surface-2); border:1.5px solid var(--brand-primary); border-radius:18px; padding:1.5rem; margin-bottom:1.75rem; text-align:left;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem; border-bottom:1px solid var(--border-subtle); padding-bottom:0.75rem;">
          <div style="display:flex; align-items:center; gap:0.5rem;">
            <span style="font-size:1.4rem;">🚚</span>
            <span style="font-family:var(--font-display); font-weight:800; font-size:1.15rem; color:var(--brand-primary);">CAMPUS DELIVERY</span>
          </div>
          <span class="badge badge-brand" style="font-size:0.75rem;">Classroom Service</span>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.2rem; font-size:0.92rem;">
          <div>
            <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:700;">Building</div>
            <strong style="color:var(--text-primary); font-size:1rem; display:block; margin-top:2px;">
              <?php echo htmlspecialchars($order['delivery_building'] ?: 'Main College Building'); ?>
            </strong>
          </div>

          <div>
            <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:700;">Floor</div>
            <strong style="color:var(--text-primary); font-size:1rem; display:block; margin-top:2px;">
              <?php echo htmlspecialchars($order['delivery_floor'] ?: '3'); ?>
            </strong>
          </div>

          <div>
            <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:700;">Room / Classroom</div>
            <strong style="color:var(--brand-primary); font-size:1.1rem; display:block; margin-top:2px;">
              <?php echo htmlspecialchars($order['delivery_room'] ?: '304'); ?>
            </strong>
          </div>

          <div>
            <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:700;">Estimated Delivery</div>
            <strong style="color:var(--text-primary); font-size:1.1rem; display:block; margin-top:2px;">
              <?php echo $estimated_time; ?>
            </strong>
          </div>
        </div>

        <?php if (!empty($order['delivery_note'])): ?>
          <div style="margin-top:1rem; padding-top:0.75rem; border-top:1px dashed var(--border-subtle); font-size:0.85rem; color:var(--text-secondary);">
            <strong style="color:var(--text-primary);">Delivery Note:</strong> "<?php echo htmlspecialchars($order['delivery_note']); ?>"
          </div>
        <?php endif; ?>

        <div style="margin-top:1.25rem; padding-top:0.85rem; border-top:1.5px solid var(--border-subtle); display:flex; justify-content:space-between; align-items:center; font-size:0.95rem;">
          <div>
            <span style="color:var(--text-secondary);">Delivery Fee: </span>
            <strong style="color:var(--brand-primary);">₹<?php echo number_format($order['delivery_fee'], 0); ?></strong>
          </div>
          <div>
            <span style="color:var(--text-secondary);">Total: </span>
            <strong style="font-family:var(--font-display); font-size:1.35rem; color:var(--brand-primary);">₹<?php echo number_format($order['total'], 2); ?></strong>
          </div>
        </div>
      </div>

    <?php else: ?>
      <!-- CAFETERIA PICKUP DETAILS BOX -->
      <div style="background:var(--bg-surface-2); border:1px solid var(--border-subtle); border-radius:18px; padding:1.5rem; margin-bottom:1.75rem; text-align:left;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem; border-bottom:1px solid var(--border-subtle); padding-bottom:0.75rem;">
          <div style="display:flex; align-items:center; gap:0.5rem;">
            <span style="font-size:1.4rem;">🍽️</span>
            <span style="font-family:var(--font-display); font-weight:800; font-size:1.15rem; color:var(--text-primary);">PICKUP</span>
          </div>
          <span class="badge badge-success" style="font-size:0.75rem;">Cafeteria Counter</span>
        </div>

        <div style="display:grid; grid-template-columns:1.2fr 0.8fr; gap:1.2rem; align-items:center;">
          <div>
            <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:700;">Counter</div>
            <strong style="color:var(--text-primary); font-size:1rem; display:block; margin-top:2px;">
              <?php echo htmlspecialchars($order['address'] ?: 'Main Cafeteria Counter'); ?>
            </strong>

            <div style="margin-top:0.75rem;">
              <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:700;">Estimated Ready</div>
              <strong style="color:var(--text-primary); font-size:1.05rem; display:block; margin-top:2px;">
                <?php echo $estimated_time; ?>
              </strong>
            </div>

            <div style="margin-top:0.75rem;">
              <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:700;">Total</div>
              <strong style="font-family:var(--font-display); font-size:1.25rem; color:var(--brand-primary); display:block; margin-top:2px;">
                ₹<?php echo number_format($order['total'], 2); ?>
              </strong>
            </div>
          </div>

          <div style="background:#FFFFFF; border:1px solid var(--border-subtle); border-radius:14px; padding:1rem; text-align:center;">
            <div style="font-size:0.7rem; color:var(--brand-primary); text-transform:uppercase; font-weight:800; letter-spacing:0.5px;">PICKUP TOKEN</div>
            <div style="font-family:var(--font-display); font-size:2.2rem; font-weight:900; color:var(--brand-primary); line-height:1.1; margin:0.2rem 0;">
              #BITP-<?php echo htmlspecialchars($order['pickup_token'] ?: $order['id']); ?>
            </div>
            <div style="font-size:0.72rem; color:var(--text-muted);">Show at Counter</div>
          </div>
        </div>

        <!-- Scannable Staff Pickup QR Display for Pickup only -->
        <div style="margin-top:1.25rem; padding-top:1rem; border-top:1px dashed var(--border-subtle); text-align:center;">
          <div style="font-size:0.8rem; color:var(--text-secondary); margin-bottom:0.65rem;">
            <i class="fa-solid fa-qrcode text-orange"></i> Show QR at counter for swift staff verification:
          </div>
          <div style="background:#FFFFFF; padding:0.6rem; border-radius:12px; display:inline-block; border:1px solid var(--border-subtle);">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=130x130&data=<?php echo urlencode($pickup_qr_url); ?>" alt="Pickup QR" style="width:110px; height:110px;">
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Actions -->
    <div style="display:flex; gap:0.85rem; justify-content:center; flex-wrap:wrap;">
      <a href="track-order.php?id=<?php echo $order['id']; ?>" class="btn btn-primary" style="flex:1; min-width:190px; padding:0.85rem 1.25rem; font-weight:700;">
        <i class="fa-solid fa-location-crosshairs"></i> Track Order Live
      </a>
      <a href="receipt.php?id=<?php echo $order['id']; ?>" class="btn btn-secondary" style="flex:1; min-width:150px; padding:0.85rem 1.25rem;">
        <i class="fa-solid fa-receipt"></i> View Receipt
      </a>
    </div>

  </div>
</div>

<?php include 'includes/footer.php'; ?>
