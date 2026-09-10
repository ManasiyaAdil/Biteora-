<?php
$page_title = "Support Tickets";
require_once 'includes/connect.php';

if (!isset($_SESSION['admin_sid'])) {
    header("Location: login.php");
    exit;
}

$msg = isset($_GET['msg']) ? $_GET['msg'] : '';

// Update Ticket Status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tid = intval($_POST['ticket_id'] ?? 0);
    $new_st = trim($_POST['status'] ?? 'Answered');
    if ($tid > 0) {
        $con->query("UPDATE tickets SET status = '$new_st' WHERE id = $tid");
        header("Location: all-tickets.php?msg=updated");
        exit;
    }
}

// Fetch all tickets with student names
$tickets = [];
$res = $con->query("SELECT t.*, u.name as poster_name, u.contact as poster_contact 
                    FROM tickets t 
                    JOIN users u ON t.poster_id = u.id 
                    WHERE t.deleted = 0 
                    ORDER BY t.id DESC");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $tickets[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Support Tickets | Biteora Admin</title>
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23FF5A36'><path d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9v-2h2v2zm0-4H9V7h2v5z'/></svg>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/campusbite.css">
</head>
<body style="background:#FFF8F3;">

  <div style="display:flex; min-height:100vh;">
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
        <li><a href="users.php" class="btn btn-secondary" style="width:100%; justify-content:flex-start;"><i class="fa-solid fa-users text-orange" style="width:20px;"></i> Students</a></li>
        <li><a href="all-tickets.php" class="btn btn-primary" style="width:100%; justify-content:flex-start;"><i class="fa-solid fa-headset" style="width:20px;"></i> Support & Tickets</a></li>
        <li style="margin-top:auto; padding-top:1.25rem; border-top:1px solid var(--border-subtle);"><a href="routers/logout.php" class="btn btn-danger" style="width:100%; justify-content:flex-start;"><i class="fa-solid fa-arrow-right-from-bracket" style="width:20px;"></i> Sign Out</a></li>
      </ul>
    </aside>

    <main style="flex-grow:1; padding:2rem 2.5rem; overflow-y:auto;">
      <div style="margin-bottom:2rem;">
        <span style="color:var(--brand-primary); font-weight:700; font-size:0.85rem; text-transform:uppercase; letter-spacing:1px;">Customer Care</span>
        <h1 style="font-family:'Outfit',sans-serif; font-size:2.2rem; font-weight:800; color:var(--text-primary);">Student Inquiries & Tickets</h1>
      </div>

      <?php if ($msg === 'updated'): ?>
        <div class="alert alert-success" style="margin-bottom:1.5rem;"><i class="fa-solid fa-circle-check"></i> Ticket status updated!</div>
      <?php endif; ?>

      <div class="cb-table-wrapper">
        <table class="cb-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Student</th>
              <th>Subject</th>
              <th>Category</th>
              <th>Message</th>
              <th>Date</th>
              <th>Status</th>
              <th>Update Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($tickets) === 0): ?>
              <tr>
                <td colspan="8" style="text-align:center; padding:3rem; color:var(--text-secondary);">No support tickets submitted yet.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($tickets as $t): ?>
                <tr>
                  <td><strong style="color:var(--text-primary);">#<?php echo $t['id']; ?></strong></td>
                  <td>
                    <div style="font-weight:700; color:var(--text-primary);"><?php echo htmlspecialchars($t['poster_name']); ?></div>
                    <div style="font-size:0.75rem; color:var(--text-secondary);"><?php echo htmlspecialchars($t['poster_contact']); ?></div>
                  </td>
                  <td><strong style="color:var(--text-primary);"><?php echo htmlspecialchars($t['subject']); ?></strong></td>
                  <td><span class="badge badge-gray"><?php echo htmlspecialchars($t['type']); ?></span></td>
                  <td style="max-width:300px;"><span style="font-size:0.85rem; color:var(--text-primary);"><?php echo htmlspecialchars($t['description']); ?></span></td>
                  <td><span style="font-size:0.8rem; color:var(--text-secondary);"><?php echo date('d M, h:i A', strtotime($t['date'])); ?></span></td>
                  <td>
                    <span class="badge badge-<?php echo ($t['status'] === 'Answered' ? 'emerald' : ($t['status'] === 'Closed' ? 'gray' : 'amber')); ?>">
                      <?php echo htmlspecialchars($t['status']); ?>
                    </span>
                  </td>
                  <td>
                    <form method="POST" action="all-tickets.php" style="margin:0;">
                      <input type="hidden" name="ticket_id" value="<?php echo $t['id']; ?>">
                      <select name="status" onchange="this.form.submit()" class="form-control" style="padding:0.35rem 0.6rem; font-size:0.85rem; width:120px; background:#070D1E;">
                        <option value="Open" <?php echo $t['status']=='Open'?'selected':''; ?>>Open</option>
                        <option value="Answered" <?php echo $t['status']=='Answered'?'selected':''; ?>>Answered</option>
                        <option value="Closed" <?php echo $t['status']=='Closed'?'selected':''; ?>>Closed</option>
                      </select>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>

  <script src="js/campusbite.js"></script>
</body>
</html>