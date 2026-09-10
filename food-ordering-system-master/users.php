<?php
$page_title = "Manage Student Accounts";
require_once 'includes/connect.php';

if (!isset($_SESSION['admin_sid'])) {
    header("Location: login.php");
    exit;
}

$msg = isset($_GET['msg']) ? $_GET['msg'] : '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_verify') {
        $id = intval($_POST['id'] ?? 0);
        $curr = intval($_POST['current'] ?? 0);
        $new_v = ($curr == 1) ? 0 : 1;
        $con->query("UPDATE users SET verified = $new_v WHERE id = $id");
        header("Location: users.php?msg=verified");
        exit;
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id != $_SESSION['user_id']) {
            $con->query("UPDATE users SET deleted = 1 WHERE id = $id");
            header("Location: users.php?msg=deleted");
            exit;
        }
    }
}

// Fetch users with wallet balance
$users = [];
$res = $con->query("SELECT u.*, wd.balance 
                    FROM users u 
                    LEFT JOIN wallet w ON u.id = w.customer_id 
                    LEFT JOIN wallet_details wd ON w.id = wd.wallet_id 
                    WHERE u.deleted = 0 
                    ORDER BY u.role ASC, u.id DESC");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $users[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student & Staff Accounts | CampusBite Admin</title>
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2310B981'><path d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9v-2h2v2zm0-4H9V7h2v5z'/></svg>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/campusbite.css">
</head>
<body style="background:#070D1E;">

  <div class="admin-layout">
    <!-- Admin Sidebar -->
    <aside class="admin-sidebar">
      <div class="cb-logo" style="margin-bottom:1.5rem; padding:0 0.5rem;">
        <div class="cb-logo-icon">
          <i class="fa-solid fa-utensils"></i>
        </div>
        <span>Campus<span class="text-emerald">Bite</span></span>
      </div>

      <ul class="admin-nav">
        <li><a href="admin-page.php" class="admin-nav-link"><i class="fa-solid fa-chart-pie"></i> Dashboard & Analytics</a></li>
        <li><a href="all-orders.php" class="admin-nav-link"><i class="fa-solid fa-receipt"></i> All Orders Queue</a></li>
        <li><a href="admin-menu.php" class="admin-nav-link"><i class="fa-solid fa-bowl-food"></i> Food Menu CRUD</a></li>
        <li><a href="admin-categories.php" class="admin-nav-link"><i class="fa-solid fa-layer-group"></i> Category Manager</a></li>
        <li><a href="users.php" class="admin-nav-link active"><i class="fa-solid fa-users"></i> Student Accounts</a></li>
        <li><a href="all-tickets.php" class="admin-nav-link"><i class="fa-solid fa-headset"></i> Support & Tickets</a></li>
        <li style="margin-top:auto; padding-top:1.5rem; border-top:1px solid var(--border-subtle);"><a href="routers/logout.php" class="admin-nav-link" style="color:#F43F5E;"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sign Out</a></li>
      </ul>
    </aside>

    <!-- Main Content Area -->
    <main class="admin-main">
      <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1.5rem; margin-bottom:2rem;">
        <div>
          <span style="color:#10B981; font-weight:700; font-size:0.85rem; text-transform:uppercase; letter-spacing:1px;">Campus User Directory</span>
          <h1 style="font-family:'Outfit',sans-serif; font-size:2.2rem; font-weight:800; color:#F8FAFC;">Student & Staff Accounts</h1>
        </div>
      </div>

      <?php if ($msg === 'verified'): ?>
        <div class="alert alert-success" style="margin-bottom:1.5rem;"><i class="fa-solid fa-circle-check"></i> User verification status updated!</div>
      <?php elseif ($msg === 'deleted'): ?>
        <div class="alert alert-success" style="margin-bottom:1.5rem;"><i class="fa-solid fa-circle-check"></i> Account removed!</div>
      <?php endif; ?>

      <div class="cb-table-wrapper">
        <table class="cb-table">
          <thead>
            <tr>
              <th>User</th>
              <th>Username</th>
              <th>Role</th>
              <th>Contact / Email</th>
              <th>Hostel / Address</th>
              <th>Wallet Balance</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
              <tr>
                <td>
                  <div style="font-weight:700; color:#F8FAFC;"><?php echo htmlspecialchars($u['name']); ?></div>
                  <div style="font-size:0.75rem; color:#64748B;">ID: #<?php echo $u['id']; ?></div>
                </td>
                <td><code style="color:#34D399; background:rgba(255,255,255,0.04); padding:2px 6px; border-radius:4px;"><?php echo htmlspecialchars($u['username']); ?></code></td>
                <td>
                  <span class="badge badge-<?php echo ($u['role'] === 'Administrator' ? 'amber' : 'emerald'); ?>">
                    <?php echo htmlspecialchars($u['role']); ?>
                  </span>
                </td>
                <td>
                  <div style="font-size:0.85rem; color:#CBD5E1;"><?php echo htmlspecialchars($u['contact']); ?></div>
                  <div style="font-size:0.75rem; color:#94A3B8;"><?php echo htmlspecialchars($u['email'] ?? 'No email'); ?></div>
                </td>
                <td><span style="font-size:0.85rem; color:#94A3B8;"><?php echo htmlspecialchars($u['address'] ?? 'Campus'); ?></span></td>
                <td>
                  <strong style="font-family:'Outfit',sans-serif; color:#10B981;">
                    ₹<?php echo number_format($u['balance'] ?? 0, 2); ?>
                  </strong>
                </td>
                <td>
                  <span class="badge badge-<?php echo $u['verified'] ? 'emerald' : 'gray'; ?>">
                    <?php echo $u['verified'] ? 'Verified' : 'Pending'; ?>
                  </span>
                </td>
                <td>
                  <form method="POST" action="users.php" onsubmit="return confirm('Delete this account?');" style="margin:0;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                    <button type="submit" class="btn btn-danger btn-sm" <?php echo ($u['id'] == $_SESSION['user_id']) ? 'disabled style="opacity:0.4;"' : ''; ?>>
                      <i class="fa-solid fa-trash-can"></i>
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>

  <script src="js/campusbite.js"></script>
</body>
</html>