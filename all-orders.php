<?php
$page_title = "Manage All Orders";
require_once 'includes/connect.php';

if (!isset($_SESSION['admin_sid'])) {
    header("Location: login.php");
    exit;
}

$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$payment_filter = isset($_GET['payment_status']) ? trim($_GET['payment_status']) : '';
$method_filter = isset($_GET['delivery_method']) ? trim($_GET['delivery_method']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$where = ["o.deleted = 0"];
if (!empty($status_filter) && $status_filter !== 'all') {
    $safe_st = $con->real_escape_string($status_filter);
    $where[] = "o.status = '$safe_st'";
}
if (!empty($payment_filter) && $payment_filter !== 'all') {
    $safe_pst = $con->real_escape_string($payment_filter);
    $where[] = "o.payment_status = '$safe_pst'";
}
if (!empty($method_filter) && $method_filter !== 'all') {
    $safe_m = $con->real_escape_string($method_filter);
    $where[] = "o.delivery_method = '$safe_m'";
}
if (!empty($search)) {
    $safe_s = $con->real_escape_string($search);
    $where[] = "(o.order_code LIKE '%$safe_s%' OR o.pickup_token LIKE '%$safe_s%' OR o.delivery_building LIKE '%$safe_s%' OR o.delivery_room LIKE '%$safe_s%' OR u.name LIKE '%$safe_s%' OR u.contact LIKE '%$safe_s%')";
}

$where_sql = implode(" AND ", $where);
$sql = "SELECT o.*, u.name as customer_name, u.contact as customer_contact,
        (SELECT GROUP_CONCAT(CONCAT(od.quantity, 'x ', i.name) SEPARATOR ', ') 
         FROM order_details od 
         JOIN items i ON od.item_id = i.id 
         WHERE od.order_id = o.id) as items_summary
        FROM orders o 
        JOIN users u ON o.customer_id = u.id 
        WHERE $where_sql 
        ORDER BY o.id DESC";

$orders = [];
$res = $con->query($sql);
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $orders[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>All Orders Queue | Biteora Admin</title>
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23FF5A36'><path d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9v-2h2v2zm0-4H9V7h2v5z'/></svg>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/campusbite.css">
</head>
<body style="background:#FFF8F3;">

  <div style="display:flex; min-height:100vh;">
    <!-- Admin Sidebar -->
    <aside style="width:260px; background:#FFFFFF; border-right:1px solid var(--border-subtle); padding:1.5rem 1rem; display:flex; flex-direction:column; flex-shrink:0;">
      <div class="cb-logo" style="margin-bottom:1.5rem; padding:0 0.5rem;">
        <div class="cb-logo-icon">
          <i class="fa-solid fa-utensils"></i>
        </div>
        <span>Bite<span class="text-orange">ora</span></span>
      </div>

      <ul style="list-style:none; display:flex; flex-direction:column; gap:0.4rem; flex-grow:1;">
        <li><a href="admin-page.php" class="btn btn-secondary" style="width:100%; justify-content:flex-start;"><i class="fa-solid fa-chart-pie text-orange" style="width:20px;"></i> Dashboard</a></li>
        <li><a href="all-orders.php" class="btn btn-primary" style="width:100%; justify-content:flex-start;"><i class="fa-solid fa-receipt" style="width:20px;"></i> Orders Queue</a></li>
        <li><a href="admin-menu.php" class="btn btn-secondary" style="width:100%; justify-content:flex-start;"><i class="fa-solid fa-bowl-food text-orange" style="width:20px;"></i> Food Menu</a></li>
        <li><a href="admin-categories.php" class="btn btn-secondary" style="width:100%; justify-content:flex-start;"><i class="fa-solid fa-layer-group text-orange" style="width:20px;"></i> Categories</a></li>
        <li><a href="users.php" class="btn btn-secondary" style="width:100%; justify-content:flex-start;"><i class="fa-solid fa-users text-orange" style="width:20px;"></i> Students</a></li>
        <li style="margin-top:auto; padding-top:1.25rem; border-top:1px solid var(--border-subtle);"><a href="routers/logout.php" class="btn btn-danger" style="width:100%; justify-content:flex-start;"><i class="fa-solid fa-arrow-right-from-bracket" style="width:20px;"></i> Sign Out</a></li>
      </ul>
    </aside>

    <!-- Main Content Area -->
    <main style="flex-grow:1; padding:2rem 2.5rem; overflow-y:auto;">
      <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1.25rem; margin-bottom:1.75rem;">
        <div>
          <span style="color:var(--brand-primary); font-weight:700; font-size:0.8rem; text-transform:uppercase; letter-spacing:1px;">Operations</span>
          <h1 style="font-family:var(--font-display); font-size:2.1rem; font-weight:800; color:var(--text-primary);">All Orders Queue</h1>
        </div>

        <form method="GET" action="all-orders.php" style="display:flex; gap:0.5rem; max-width:380px; width:100%;">
          <div class="search-wrapper">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="search-input" placeholder="Search order, room, student...">
          </div>
          <button type="submit" class="btn btn-primary btn-sm" style="border-radius:999px;"><i class="fa-solid fa-search"></i></button>
          <?php if ($search || $status_filter || $payment_filter || $method_filter): ?>
            <a href="all-orders.php" class="btn btn-secondary btn-sm" style="border-radius:999px;" title="Clear">✕</a>
          <?php endif; ?>
        </form>
      </div>

      <!-- Filters Bar -->
      <div class="category-bar" style="margin-bottom:1.75rem; flex-wrap:wrap;">
        <a href="all-orders.php" class="cat-pill <?php echo empty($status_filter) && empty($payment_filter) && empty($method_filter) ? 'active' : ''; ?>">All (<?php echo count($orders); ?>)</a>
        <a href="all-orders.php?status=Placed" class="cat-pill <?php echo ($status_filter=='Placed') ? 'active' : ''; ?>">Placed</a>
        <a href="all-orders.php?status=Preparing" class="cat-pill <?php echo ($status_filter=='Preparing') ? 'active' : ''; ?>">Preparing</a>
        <a href="all-orders.php?status=Out+for+Delivery" class="cat-pill <?php echo ($status_filter=='Out for Delivery') ? 'active' : ''; ?>">🚚 Out for Delivery</a>
        <a href="all-orders.php?status=Delivered" class="cat-pill <?php echo ($status_filter=='Delivered') ? 'active' : ''; ?>">✓ Delivered</a>
        <a href="all-orders.php?status=Ready" class="cat-pill <?php echo ($status_filter=='Ready') ? 'active' : ''; ?>">🍽️ Ready for Pickup</a>
        <a href="all-orders.php?status=Completed" class="cat-pill <?php echo ($status_filter=='Completed') ? 'active' : ''; ?>">Completed</a>
        <a href="all-orders.php?delivery_method=delivery" class="cat-pill <?php echo ($method_filter=='delivery') ? 'active' : ''; ?>">🚚 Delivery Only</a>
        <a href="all-orders.php?delivery_method=pickup" class="cat-pill <?php echo ($method_filter=='pickup') ? 'active' : ''; ?>">🍽️ Pickup Only</a>
      </div>

      <!-- Orders Table Card -->
      <div class="card" style="padding:1.5rem; background:#FFFFFF; border:1px solid var(--border-subtle);">
        <?php if (count($orders) === 0): ?>
          <div style="text-align:center; padding:3rem; color:var(--text-secondary);">
            <i class="fa-solid fa-inbox" style="font-size:2rem; margin-bottom:0.5rem; display:block; color:var(--text-muted);"></i>
            No orders match the selected filters.
          </div>
        <?php else: ?>
          <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:0.88rem;">
              <thead>
                <tr style="border-bottom:1.5px solid var(--border-subtle); color:var(--text-muted); text-align:left; font-size:0.75rem; text-transform:uppercase;">
                  <th style="padding:0.75rem 0.5rem;">Order</th>
                  <th style="padding:0.75rem 0.5rem;">Student</th>
                  <th style="padding:0.75rem 0.5rem;">Type</th>
                  <th style="padding:0.75rem 0.5rem;">Location</th>
                  <th style="padding:0.75rem 0.5rem;">Total</th>
                  <th style="padding:0.75rem 0.5rem;">Payment</th>
                  <th style="padding:0.75rem 0.5rem;">Status</th>
                  <th style="padding:0.75rem 0.5rem; text-align:right;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($orders as $o): 
                  $is_del = ($o['delivery_method'] === 'delivery');
                ?>
                  <tr style="border-bottom:1px solid var(--border-subtle); color:var(--text-primary);">
                    <!-- ORDER -->
                    <td style="padding:0.85rem 0.5rem;">
                      <div style="font-family:var(--font-display); font-weight:800; color:var(--text-primary);">
                        #<?php echo htmlspecialchars($o['order_code'] ?: 'CB-'.$o['id']); ?>
                      </div>
                      <?php if (!$is_del && !empty($o['pickup_token'])): ?>
                        <span class="badge badge-brand" style="font-size:0.72rem; padding:1px 6px;">Token #<?php echo htmlspecialchars($o['pickup_token']); ?></span>
                      <?php endif; ?>
                    </td>

                    <!-- STUDENT -->
                    <td style="padding:0.85rem 0.5rem;">
                      <div><strong><?php echo htmlspecialchars($o['customer_name']); ?></strong></div>
                      <div style="font-size:0.75rem; color:var(--text-muted);"><i class="fa-solid fa-phone" style="font-size:0.68rem;"></i> <?php echo htmlspecialchars($o['customer_contact']); ?></div>
                    </td>

                    <!-- TYPE -->
                    <td style="padding:0.85rem 0.5rem;">
                      <?php if ($is_del): ?>
                        <span class="badge badge-brand" style="font-size:0.78rem; font-weight:700; display:inline-flex; align-items:center; gap:4px;">
                          🚚 DELIVERY
                        </span>
                      <?php else: ?>
                        <span class="badge badge-secondary" style="font-size:0.78rem; font-weight:700; display:inline-flex; align-items:center; gap:4px;">
                          🍽️ PICKUP
                        </span>
                      <?php endif; ?>
                    </td>

                    <!-- LOCATION -->
                    <td style="padding:0.85rem 0.5rem; max-width:210px;">
                      <?php if ($is_del): ?>
                        <div style="font-weight:700; color:var(--text-primary); font-size:0.85rem;">
                          <i class="fa-solid fa-building" style="color:var(--brand-primary); margin-right:3px;"></i>
                          <?php echo htmlspecialchars($o['delivery_building'] ?: 'Campus'); ?>
                        </div>
                        <div style="font-size:0.78rem; color:var(--text-secondary);">
                          Floor <?php echo htmlspecialchars($o['delivery_floor'] ?: '1'); ?>, <strong>Room <?php echo htmlspecialchars($o['delivery_room'] ?: '-'); ?></strong>
                        </div>
                        <?php if (!empty($o['delivery_note'])): ?>
                          <div style="font-size:0.72rem; color:var(--text-muted); font-style:italic;" title="<?php echo htmlspecialchars($o['delivery_note']); ?>">
                            Note: "<?php echo htmlspecialchars(mb_strimwidth($o['delivery_note'], 0, 28, "...")); ?>"
                          </div>
                        <?php endif; ?>
                      <?php else: ?>
                        <div style="font-size:0.82rem; color:var(--text-secondary);">
                          <i class="fa-solid fa-utensils" style="color:var(--text-muted); margin-right:3px;"></i>
                          <?php echo htmlspecialchars($o['address'] ?: 'Cafeteria Counter 1'); ?>
                        </div>
                      <?php endif; ?>
                    </td>

                    <!-- TOTAL -->
                    <td style="padding:0.85rem 0.5rem; font-weight:800; font-family:var(--font-display); color:var(--brand-primary);">
                      ₹<?php echo number_format($o['total'], 2); ?>
                      <?php if ($is_del && floatval($o['delivery_fee']) > 0): ?>
                        <div style="font-size:0.68rem; color:var(--text-muted); font-family:var(--font-sans); font-weight:normal;">incl. ₹<?php echo number_format($o['delivery_fee'], 0); ?> fee</div>
                      <?php endif; ?>
                    </td>

                    <!-- PAYMENT -->
                    <td style="padding:0.85rem 0.5rem;">
                      <?php
                        require_once __DIR__ . '/includes/payment_config.php';
                        echo biteora_render_payment_badge($o['payment_status'] ?? 'Pending');
                      ?>
                    </td>

                    <!-- STATUS -->
                    <td style="padding:0.85rem 0.5rem;">
                      <?php
                        $st = $o['status'];
                        $b_cls = 'brand';
                        if ($st === 'Completed' || $st === 'Delivered') $b_cls = 'success';
                        elseif ($st === 'Preparing') $b_cls = 'warning';
                      ?>
                      <span class="badge badge-<?php echo $b_cls; ?>" style="font-size:0.78rem;">
                        <?php echo htmlspecialchars($st); ?>
                      </span>
                    </td>

                    <!-- ACTIONS -->
                    <td style="padding:0.85rem 0.5rem; text-align:right;">
                      <div style="display:inline-flex; align-items:center; gap:0.4rem; justify-content:flex-end;">
                        <form method="POST" action="routers/edit-orders.php" style="display:inline-flex; gap:0.35rem;">
                          <input type="hidden" name="id" value="<?php echo $o['id']; ?>">
                          
                          <?php if ($is_del): ?>
                            <!-- Delivery Status Transitions -->
                            <?php if ($o['status'] === 'Placed'): ?>
                              <button type="submit" name="status" value="Preparing" class="btn btn-sm btn-secondary" style="font-size:0.76rem; color:#F59E0B; border-color:rgba(245,158,11,0.4); padding:0.35rem 0.65rem;">
                                <i class="fa-solid fa-fire"></i> Start Prep
                              </button>
                            <?php elseif ($o['status'] === 'Preparing'): ?>
                              <button type="submit" name="status" value="Out for Delivery" class="btn btn-sm btn-primary" style="font-size:0.76rem; padding:0.35rem 0.65rem;">
                                <i class="fa-solid fa-truck-fast"></i> Out for Delivery
                              </button>
                            <?php elseif ($o['status'] === 'Out for Delivery'): ?>
                              <button type="submit" name="status" value="Delivered" class="btn btn-sm btn-secondary" style="font-size:0.76rem; color:var(--success); border-color:rgba(34,160,107,0.4); padding:0.35rem 0.65rem;">
                                <i class="fa-solid fa-check-double"></i> Mark Delivered
                              </button>
                            <?php elseif ($o['status'] === 'Delivered'): ?>
                              <span style="font-size:0.75rem; color:var(--success); font-weight:700;"><i class="fa-solid fa-check"></i> Delivered</span>
                            <?php endif; ?>

                          <?php else: ?>
                            <!-- Pickup Status Transitions -->
                            <?php if ($o['status'] === 'Placed'): ?>
                              <button type="submit" name="status" value="Preparing" class="btn btn-sm btn-secondary" style="font-size:0.76rem; color:#F59E0B; border-color:rgba(245,158,11,0.4); padding:0.35rem 0.65rem;">
                                Prep
                              </button>
                            <?php elseif ($o['status'] === 'Preparing'): ?>
                              <button type="submit" name="status" value="Ready" class="btn btn-sm btn-primary" style="font-size:0.76rem; padding:0.35rem 0.65rem;">
                                Ready
                              </button>
                            <?php elseif ($o['status'] === 'Ready'): ?>
                              <button type="submit" name="status" value="Completed" class="btn btn-sm btn-secondary" style="font-size:0.76rem; color:var(--success); border-color:rgba(34,160,107,0.4); padding:0.35rem 0.65rem;">
                                Complete
                              </button>
                            <?php elseif ($o['status'] === 'Completed'): ?>
                              <span style="font-size:0.75rem; color:var(--success); font-weight:700;"><i class="fa-solid fa-check"></i> Completed</span>
                            <?php endif; ?>
                          <?php endif; ?>
                        </form>

                        <a href="receipt.php?id=<?php echo $o['id']; ?>" class="btn btn-secondary btn-sm" style="padding:0.35rem 0.6rem;" title="Receipt">
                          <i class="fa-solid fa-receipt"></i>
                        </a>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </main>
  </div>

</body>
</html>