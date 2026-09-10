<?php
$page_title = "Cafeteria Admin Dashboard";
require_once 'includes/connect.php';

if (!isset($_SESSION['admin_sid'])) {
    header("Location: login.php");
    exit;
}

// 1. Calculate Real KPIs
$today_start = date('Y-m-d 00:00:00');

// Today's Orders
$t_orders_res = $con->query("SELECT COUNT(*) as cnt FROM orders WHERE date >= '$today_start' AND deleted = 0");
$today_orders = $t_orders_res ? $t_orders_res->fetch_assoc()['cnt'] : 0;

// Today's Revenue
$t_rev_res = $con->query("SELECT SUM(total) as rev FROM orders WHERE date >= '$today_start' AND status != 'Cancelled' AND deleted = 0");
$today_revenue = ($t_rev_res && $row = $t_rev_res->fetch_assoc()) ? floatval($row['rev'] ?? 0) : 0.00;

// Pending / Active Orders
$pending_res = $con->query("SELECT COUNT(*) as cnt FROM orders WHERE status IN ('Placed', 'Preparing') AND deleted = 0");
$pending_orders = $pending_res ? $pending_res->fetch_assoc()['cnt'] : 0;

// Completed Orders
$comp_res = $con->query("SELECT COUNT(*) as cnt FROM orders WHERE status = 'Completed' AND deleted = 0");
$completed_orders = $comp_res ? $comp_res->fetch_assoc()['cnt'] : 0;

// 2. Fetch Live Active Queue
$live_orders_res = $con->query("SELECT o.*, u.name as customer_name, u.contact as customer_contact,
                               (SELECT GROUP_CONCAT(CONCAT(od.quantity, 'x ', i.name) SEPARATOR ', ') 
                                FROM order_details od 
                                JOIN items i ON od.item_id = i.id 
                                WHERE od.order_id = o.id) as items_summary
                                FROM orders o 
                                JOIN users u ON o.customer_id = u.id 
                                WHERE o.status IN ('Placed', 'Preparing', 'Ready') AND o.deleted = 0 
                                ORDER BY o.id ASC");
$live_orders = [];
if ($live_orders_res) {
    while ($r = $live_orders_res->fetch_assoc()) {
        $live_orders[] = $r;
    }
}

// 3. Top Bestselling Food Items
$top_items_res = $con->query("SELECT i.name, i.image, i.price, SUM(od.quantity) as units_sold, SUM(od.price) as revenue_generated 
                              FROM order_details od 
                              JOIN items i ON od.item_id = i.id 
                              GROUP BY od.item_id 
                              ORDER BY units_sold DESC 
                              LIMIT 5");
$top_items = [];
if ($top_items_res) {
    while ($r = $top_items_res->fetch_assoc()) {
        $top_items[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | Biteora</title>
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

      <div style="padding:0.75rem 1rem; background:var(--brand-subtle); border:1px solid rgba(255,90,54,0.25); border-radius:12px; margin-bottom:1.25rem; display:flex; align-items:center; gap:0.65rem;">
        <div style="width:34px; height:34px; border-radius:50%; background:var(--brand-primary); color:#FFFFFF; display:flex; align-items:center; justify-content:center; font-weight:bold; font-size:0.85rem;">
          <i class="fa-solid fa-user-shield"></i>
        </div>
        <div>
          <div style="font-weight:700; font-size:0.88rem; color:var(--text-primary);"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></div>
          <div style="font-size:0.72rem; color:var(--brand-primary); font-weight:700;">Cafeteria Manager</div>
        </div>
      </div>

      <ul style="list-style:none; display:flex; flex-direction:column; gap:0.4rem; flex-grow:1;">
        <li><a href="admin-page.php" class="btn btn-primary" style="width:100%; justify-content:flex-start;"><i class="fa-solid fa-chart-pie" style="width:20px;"></i> Dashboard</a></li>
        <li><a href="all-orders.php" class="btn btn-secondary" style="width:100%; justify-content:flex-start;"><i class="fa-solid fa-receipt text-orange" style="width:20px;"></i> Orders Queue</a></li>
        <li><a href="admin-menu.php" class="btn btn-secondary" style="width:100%; justify-content:flex-start;"><i class="fa-solid fa-bowl-food text-orange" style="width:20px;"></i> Food Menu</a></li>
        <li><a href="admin-categories.php" class="btn btn-secondary" style="width:100%; justify-content:flex-start;"><i class="fa-solid fa-layer-group text-orange" style="width:20px;"></i> Categories</a></li>
        <li><a href="users.php" class="btn btn-secondary" style="width:100%; justify-content:flex-start;"><i class="fa-solid fa-users text-orange" style="width:20px;"></i> Students</a></li>
        <li style="margin-top:auto; padding-top:1.25rem; border-top:1px solid var(--border-subtle);"><a href="routers/logout.php" class="btn btn-danger" style="width:100%; justify-content:flex-start;"><i class="fa-solid fa-arrow-right-from-bracket" style="width:20px;"></i> Sign Out</a></li>
      </ul>
    </aside>

    <!-- Admin Main Content Area -->
    <main style="flex-grow:1; padding:2rem 2.5rem; overflow-y:auto;">
      <!-- Header Bar -->
      <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1.25rem; margin-bottom:2rem;">
        <div>
          <span style="color:var(--brand-primary); font-weight:700; font-size:0.8rem; text-transform:uppercase; letter-spacing:1px;">Command Center</span>
          <h1 style="font-family:var(--font-display); font-size:2.1rem; font-weight:800; color:var(--text-primary);">Cafeteria Dashboard</h1>
        </div>

        <div style="display:flex; align-items:center; gap:0.75rem;">
          <a href="index.php" target="_blank" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-arrow-up-right-from-square"></i> Student View
          </a>
          <a href="admin-menu.php?action=add" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus"></i> Add Food Item
          </a>
        </div>
      </div>

      <!-- KPI Metric Cards Grid -->
      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1.25rem; margin-bottom:2.25rem;">
        <div class="card" style="padding:1.35rem; background:#FFFFFF; border:1px solid var(--border-subtle);">
          <div style="display:flex; justify-content:space-between; align-items:center; color:var(--text-secondary); font-size:0.82rem; margin-bottom:0.5rem;">
            <span>Today's Total Orders</span>
            <i class="fa-solid fa-bag-shopping text-orange"></i>
          </div>
          <div style="font-family:var(--font-display); font-size:2.2rem; font-weight:800; color:var(--text-primary);"><?php echo $today_orders; ?></div>
        </div>

        <div class="card" style="padding:1.35rem; background:#FFFFFF; border:1px solid var(--border-subtle);">
          <div style="display:flex; justify-content:space-between; align-items:center; color:var(--text-secondary); font-size:0.82rem; margin-bottom:0.5rem;">
            <span>Today's Revenue</span>
            <i class="fa-solid fa-indian-rupee-sign text-orange"></i>
          </div>
          <div style="font-family:var(--font-display); font-size:2.2rem; font-weight:800; color:var(--brand-primary);">₹<?php echo number_format($today_revenue, 2); ?></div>
        </div>

        <div class="card" style="padding:1.35rem; background:#FFFFFF; border:1px solid var(--border-subtle);">
          <div style="display:flex; justify-content:space-between; align-items:center; color:var(--text-secondary); font-size:0.82rem; margin-bottom:0.5rem;">
            <span>Kitchen Queue</span>
            <i class="fa-solid fa-fire text-amber"></i>
          </div>
          <div style="font-family:var(--font-display); font-size:2.2rem; font-weight:800; color:#F59E0B;"><?php echo $pending_orders; ?></div>
        </div>

        <div class="card" style="padding:1.35rem; background:#FFFFFF; border:1px solid var(--border-subtle);">
          <div style="display:flex; justify-content:space-between; align-items:center; color:var(--text-secondary); font-size:0.82rem; margin-bottom:0.5rem;">
            <span>Total Completed</span>
            <i class="fa-solid fa-check-double text-emerald"></i>
          </div>
          <div style="font-family:var(--font-display); font-size:2.2rem; font-weight:800; color:var(--success);"><?php echo $completed_orders; ?></div>
        </div>
      </div>

      <!-- Live Queue Stream -->
      <div class="card" style="padding:1.75rem; background:#FFFFFF; border:1px solid var(--border-subtle); margin-bottom:2rem;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem;">
          <h3 style="font-family:var(--font-display); font-size:1.2rem; font-weight:800; color:var(--text-primary);">
            <i class="fa-solid fa-bell-concierge text-orange" style="margin-right:0.4rem;"></i> Live Kitchen Queue
          </h3>
          <a href="all-orders.php" class="btn btn-secondary btn-sm">View All Orders</a>
        </div>

        <?php if (count($live_orders) === 0): ?>
          <div style="text-align:center; padding:2.5rem; color:var(--text-secondary);">
            <i class="fa-solid fa-circle-check text-emerald" style="font-size:2rem; margin-bottom:0.5rem; display:block;"></i>
            Kitchen queue is clear! All orders have been prepared and completed.
          </div>
        <?php else: ?>
          <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:0.88rem;">
              <thead>
                <tr style="border-bottom:1.5px solid var(--border-subtle); color:var(--text-muted); text-align:left; font-size:0.75rem; text-transform:uppercase;">
                  <th style="padding:0.75rem 0.5rem;">Token</th>
                  <th style="padding:0.75rem 0.5rem;">Order Ref</th>
                  <th style="padding:0.75rem 0.5rem;">Student</th>
                  <th style="padding:0.75rem 0.5rem;">Items</th>
                  <th style="padding:0.75rem 0.5rem;">Status</th>
                  <th style="padding:0.75rem 0.5rem; text-align:right;">Kitchen Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($live_orders as $lo): ?>
                  <tr style="border-bottom:1px solid var(--border-subtle); color:var(--text-primary);">
                    <td style="padding:0.85rem 0.5rem;">
                      <span class="badge badge-brand" style="font-size:0.85rem; font-weight:800;">#<?php echo htmlspecialchars($lo['pickup_token'] ?: $lo['id']); ?></span>
                    </td>
                    <td style="padding:0.85rem 0.5rem; font-family:var(--font-display); font-weight:700;">
                      #<?php echo htmlspecialchars($lo['order_code'] ?: 'CB-'.$lo['id']); ?>
                    </td>
                    <td style="padding:0.85rem 0.5rem;">
                      <div><strong><?php echo htmlspecialchars($lo['customer_name']); ?></strong></div>
                      <div style="font-size:0.75rem; color:var(--text-muted);"><?php echo htmlspecialchars($lo['customer_contact']); ?></div>
                    </td>
                    <td style="padding:0.85rem 0.5rem; max-width:260px; font-size:0.84rem; color:var(--text-secondary);">
                      <?php echo htmlspecialchars($lo['items_summary']); ?>
                    </td>
                    <td style="padding:0.85rem 0.5rem;">
                      <span class="badge badge-<?php echo ($lo['status']=='Ready'?'success':($lo['status']=='Preparing'?'warning':'brand')); ?>">
                        <?php echo htmlspecialchars($lo['status']); ?>
                      </span>
                    </td>
                    <td style="padding:0.85rem 0.5rem; text-align:right;">
                      <form method="POST" action="routers/order-router.php" style="display:inline-flex; gap:0.4rem;">
                        <input type="hidden" name="order_id" value="<?php echo $lo['id']; ?>">
                        <?php if ($lo['status'] === 'Placed'): ?>
                          <button type="submit" name="status" value="Preparing" class="btn btn-sm btn-secondary" style="color:#F59E0B; border-color:rgba(245,158,11,0.3);">
                            <i class="fa-solid fa-fire"></i> Start Prep
                          </button>
                        <?php elseif ($lo['status'] === 'Preparing'): ?>
                          <button type="submit" name="status" value="Ready" class="btn btn-sm btn-primary">
                            <i class="fa-solid fa-bell-concierge"></i> Mark Ready
                          </button>
                        <?php elseif ($lo['status'] === 'Ready'): ?>
                          <button type="submit" name="status" value="Completed" class="btn btn-sm btn-secondary" style="color:var(--success); border-color:rgba(34,160,107,0.3);">
                            <i class="fa-solid fa-check"></i> Complete Pickup
                          </button>
                        <?php endif; ?>
                      </form>
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