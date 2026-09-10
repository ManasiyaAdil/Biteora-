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
  <title>Support Tickets | CampusBite Admin</title>
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2310B981'><path d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9v-2h2v2zm0-4H9V7h2v5z'/></svg>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/campusbite.css">
</head>
<body style="background:#070D1E;">

  <div class="admin-layout">
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
        <li><a href="users.php" class="admin-nav-link"><i class="fa-solid fa-users"></i> Student Accounts</a></li>
        <li><a href="all-tickets.php" class="admin-nav-link active"><i class="fa-solid fa-headset"></i> Support & Tickets</a></li>
        <li style="margin-top:auto; padding-top:1.5rem; border-top:1px solid var(--border-subtle);"><a href="routers/logout.php" class="admin-nav-link" style="color:#F43F5E;"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sign Out</a></li>
      </ul>
    </aside>

    <main class="admin-main">
      <div style="margin-bottom:2rem;">
        <span style="color:#10B981; font-weight:700; font-size:0.85rem; text-transform:uppercase; letter-spacing:1px;">Customer Care</span>
        <h1 style="font-family:'Outfit',sans-serif; font-size:2.2rem; font-weight:800; color:#F8FAFC;">Student Inquiries & Tickets</h1>
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
                <td colspan="8" style="text-align:center; padding:3rem; color:#94A3B8;">No support tickets submitted yet.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($tickets as $t): ?>
                <tr>
                  <td><strong style="color:#F8FAFC;">#<?php echo $t['id']; ?></strong></td>
                  <td>
                    <div style="font-weight:700; color:#F8FAFC;"><?php echo htmlspecialchars($t['poster_name']); ?></div>
                    <div style="font-size:0.75rem; color:#94A3B8;"><?php echo htmlspecialchars($t['poster_contact']); ?></div>
                  </td>
                  <td><strong style="color:#F8FAFC;"><?php echo htmlspecialchars($t['subject']); ?></strong></td>
                  <td><span class="badge badge-gray"><?php echo htmlspecialchars($t['type']); ?></span></td>
                  <td style="max-width:300px;"><span style="font-size:0.85rem; color:#CBD5E1;"><?php echo htmlspecialchars($t['description']); ?></span></td>
                  <td><span style="font-size:0.8rem; color:#94A3B8;"><?php echo date('d M, h:i A', strtotime($t['date'])); ?></span></td>
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