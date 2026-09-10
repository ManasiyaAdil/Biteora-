<?php
$page_title = "My Orders";
require_once 'includes/connect.php';

if (!isset($_SESSION['customer_sid']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$uid = intval($_SESSION['user_id']);
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';

// Build Query
$sql = "SELECT o.*, 
        (SELECT GROUP_CONCAT(CONCAT(od.quantity, 'x ', i.name) SEPARATOR ', ') 
         FROM order_details od 
         JOIN items i ON od.item_id = i.id 
         WHERE od.order_id = o.id) as items_summary 
        FROM orders o 
        WHERE o.customer_id = $uid AND o.deleted = 0";

if ($status_filter === 'active') {
    $sql .= " AND o.status IN ('Placed', 'Preparing', 'Ready', 'Out for Delivery')";
} elseif (!empty($status_filter) && $status_filter !== 'all') {
    $safe_status = $con->real_escape_string($status_filter);
    $sql .= " AND o.status = '$safe_status'";
}

$sql .= " ORDER BY o.id DESC";

$orders = [];
$res = $con->query($sql);
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $orders[] = $r;
    }
}

include 'includes/header.php';
?>

<div class="container" style="padding: 2.5rem 1.5rem 5rem;">
  <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1.25rem; margin-bottom:1.75rem;">
    <div>
      <span style="color:var(--brand-primary); font-weight:700; font-size:0.8rem; text-transform:uppercase; letter-spacing:1px;">Campus Dining</span>
      <h1 style="font-family:var(--font-display); font-size:2.2rem; font-weight:800; color:var(--text-primary);">My Orders & History</h1>
      <p style="color:var(--text-secondary); font-size:0.9rem;">Review past meals, active pickup tokens, classroom deliveries and receipts.</p>
    </div>

    <a href="menu.php" class="btn btn-primary">
      <i class="fa-solid fa-plus"></i> Place New Order
    </a>
  </div>

  <!-- Filter Pills -->
  <div class="category-bar" style="margin-bottom:1.75rem;">
    <a href="orders.php" class="cat-pill <?php echo (empty($status_filter) || $status_filter == 'all') ? 'active' : ''; ?>">
      All Orders
    </a>
    <a href="orders.php?status=active" class="cat-pill <?php echo ($status_filter == 'active') ? 'active' : ''; ?>">
      🔥 Active & In Progress
    </a>
    <a href="orders.php?status=Completed" class="cat-pill <?php echo ($status_filter == 'Completed') ? 'active' : ''; ?>">
      ✓ Completed
    </a>
    <a href="orders.php?status=Delivered" class="cat-pill <?php echo ($status_filter == 'Delivered') ? 'active' : ''; ?>">
      🚚 Delivered
    </a>
    <a href="orders.php?status=Cancelled" class="cat-pill <?php echo ($status_filter == 'Cancelled') ? 'active' : ''; ?>">
      ✕ Cancelled
    </a>
  </div>

  <!-- Orders List -->
  <?php if (count($orders) === 0): ?>
    <div class="card" style="text-align:center; padding:3.5rem 1.5rem; background:#FFFFFF;">
      <div style="width:60px; height:60px; border-radius:50%; background:var(--bg-surface-2); display:flex; align-items:center; justify-content:center; font-size:1.75rem; color:var(--text-muted); margin:0 auto 1.25rem;">
        <i class="fa-solid fa-receipt"></i>
      </div>
      <h3 style="font-family:var(--font-display); font-size:1.3rem; color:var(--text-primary); margin-bottom:0.4rem;">No orders found</h3>
      <p style="color:var(--text-secondary); font-size:0.9rem; margin-bottom:1.5rem;">You haven't placed any orders matching this filter yet.</p>
      <a href="menu.php" class="btn btn-primary">Order Food Now</a>
    </div>
  <?php else: ?>
    <div style="display:flex; flex-direction:column; gap:1.25rem;">
      <?php foreach ($orders as $o): 
        $is_del = ($o['delivery_method'] === 'delivery');
        $is_active = in_array($o['status'], ['Placed', 'Preparing', 'Ready', 'Out for Delivery']);
        
        $badge_cls = 'brand';
        if ($o['status'] === 'Completed' || $o['status'] === 'Delivered') $badge_cls = 'success';
        elseif ($o['status'] === 'Preparing') $badge_cls = 'warning';
      ?>
        <div class="card" style="padding:1.5rem; background:#FFFFFF; border:1px solid <?php echo $is_active ? 'var(--brand-primary)' : 'var(--border-subtle)'; ?>;">
          <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem; margin-bottom:1rem; padding-bottom:0.85rem; border-bottom:1px solid var(--border-subtle);">
            <div style="display:flex; align-items:center; gap:0.75rem;">
              <span class="badge badge-<?php echo $badge_cls; ?>" style="font-size:0.8rem; padding:0.35rem 0.85rem;">
                <?php echo htmlspecialchars($o['status']); ?>
              </span>
              
              <?php if ($is_del): ?>
                <span class="badge badge-brand" style="font-size:0.75rem; font-weight:700;">
                  🚚 Campus Delivery
                </span>
              <?php else: ?>
                <span class="badge badge-secondary" style="font-size:0.75rem; font-weight:700;">
                  🍽️ Cafeteria Pickup
                </span>
              <?php endif; ?>

              <span style="font-family:var(--font-display); font-weight:800; font-size:1.15rem; color:var(--text-primary);">
                #<?php echo htmlspecialchars($o['order_code'] ?: 'CB-'.$o['id']); ?>
              </span>
            </div>

            <div style="font-size:0.82rem; color:var(--text-secondary);">
              <i class="fa-regular fa-clock"></i> <?php echo date('d M Y, h:i A', strtotime($o['date'])); ?>
            </div>
          </div>

          <div style="display:grid; grid-template-columns:1.4fr 0.6fr; gap:1.5rem; align-items:center;">
            <div>
              <div style="font-weight:600; font-size:0.92rem; color:var(--text-primary); margin-bottom:0.35rem;">
                <?php echo htmlspecialchars($o['items_summary'] ?: 'Cafeteria food items'); ?>
              </div>

              <?php if ($is_del): ?>
                <div style="font-size:0.82rem; color:var(--text-secondary); margin-bottom:0.25rem;">
                  <i class="fa-solid fa-location-dot text-orange" style="margin-right:4px;"></i>
                  <strong><?php echo htmlspecialchars($o['delivery_building'] ?: 'Main Campus Building'); ?></strong>, Floor <?php echo htmlspecialchars($o['delivery_floor'] ?: '3'); ?>, <strong>Room <?php echo htmlspecialchars($o['delivery_room'] ?: '304'); ?></strong>
                </div>
              <?php else: ?>
                <div style="font-size:0.82rem; color:var(--text-secondary); margin-bottom:0.25rem;">
                  <i class="fa-solid fa-utensils text-orange" style="margin-right:4px;"></i>
                  <span>Counter: <?php echo htmlspecialchars($o['address'] ?: 'Cafeteria Counter 1'); ?></span>
                  <?php if (!empty($o['pickup_token'])): ?>
                    <span style="margin-left:0.5rem;">Token: <strong class="text-orange">#BITP-<?php echo htmlspecialchars($o['pickup_token']); ?></strong></span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>

              <div style="font-size:0.8rem; color:var(--text-secondary); display:flex; align-items:center; gap:1rem;">
                <span>Payment: <strong style="color:<?php echo ($o['payment_status']=='Paid'?'var(--success)':'var(--warning)'); ?>;"><?php echo htmlspecialchars($o['payment_status'] ?? 'Pending'); ?></strong></span>
              </div>
            </div>

            <div style="display:flex; align-items:center; justify-content:flex-end; gap:0.75rem; flex-wrap:wrap;">
              <div style="text-align:right; margin-right:0.5rem;">
                <span style="font-size:0.75rem; color:var(--text-muted); display:block;">Total</span>
                <span style="font-family:var(--font-display); font-weight:800; font-size:1.25rem; color:var(--brand-primary);">₹<?php echo number_format($o['total'], 2); ?></span>
              </div>

              <a href="track-order.php?id=<?php echo $o['id']; ?>" class="btn btn-primary btn-sm" style="font-weight:700;">
                <i class="fa-solid fa-location-crosshairs"></i> Track Order
              </a>
              <a href="receipt.php?id=<?php echo $o['id']; ?>" class="btn btn-secondary btn-sm" title="Receipt">
                <i class="fa-solid fa-receipt"></i>
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>