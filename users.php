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
  <title>Student & Staff Accounts | Biteora Admin</title>
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
        <li><a href="all-orders.php" class="btn btn-secondary" style="width:100%; justify-content:flex-start;"><i class="fa-solid fa-receipt text-orange" style="width:20px;"></i> Orders Queue</a></li>
        <li><a href="admin-menu.php" class="btn btn-secondary" style="width:100%; justify-content:flex-start;"><i class="fa-solid fa-bowl-food text-orange" style="width:20px;"></i> Food Menu</a></li>
        <li><a href="admin-categories.php" class="btn btn-secondary" style="width:100%; justify-content:flex-start;"><i class="fa-solid fa-layer-group text-orange" style="width:20px;"></i> Categories</a></li>
        <li><a href="users.php" class="btn btn-primary" style="width:100%; justify-content:flex-start;"><i class="fa-solid fa-users" style="width:20px;"></i> Students</a></li>
        <li style="margin-top:auto; padding-top:1.25rem; border-top:1px solid var(--border-subtle);"><a href="routers/logout.php" class="btn btn-danger" style="width:100%; justify-content:flex-start;"><i class="fa-solid fa-arrow-right-from-bracket" style="width:20px;"></i> Sign Out</a></li>
      </ul>
    </aside>

    <!-- Main Content Area -->
    <main style="flex-grow:1; padding:2rem 2.5rem; overflow-y:auto;">
      <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1.5rem; margin-bottom:2rem;">
        <div>
          <span style="color:var(--brand-primary); font-weight:700; font-size:0.85rem; text-transform:uppercase; letter-spacing:1px;">Campus User Directory</span>
          <h1 style="font-family:'Outfit',sans-serif; font-size:2.2rem; font-weight:800; color:var(--text-primary);">Student & Staff Accounts</h1>
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
                  <div style="font-weight:700; color:var(--text-primary);"><?php echo htmlspecialchars($u['name']); ?></div>
                  <div style="font-size:0.75rem; color:var(--text-secondary);">ID: #<?php echo $u['id']; ?></div>
                </td>
                <td><code style="color:var(--brand-primary); background:var(--bg-surface-2); padding:2px 6px; border-radius:4px; font-weight:600;"><?php echo htmlspecialchars($u['username']); ?></code></td>
                <td>
                  <span class="badge badge-<?php echo ($u['role'] === 'Administrator' ? 'amber' : 'emerald'); ?>">
                    <?php echo htmlspecialchars($u['role']); ?>
                  </span>
                </td>
                <td>
                  <div style="font-size:0.85rem; color:var(--text-primary);"><?php echo htmlspecialchars($u['contact']); ?></div>
                  <div style="font-size:0.75rem; color:var(--text-secondary);"><?php echo htmlspecialchars($u['email'] ?? 'No email'); ?></div>
                </td>
                <td><span style="font-size:0.85rem; color:var(--text-secondary);"><?php echo htmlspecialchars($u['address'] ?? 'Campus'); ?></span></td>
                <td>
                  <strong style="font-family:'Outfit',sans-serif; color:var(--brand-primary);">
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