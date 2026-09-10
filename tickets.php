<?php
$page_title = "Support & Feedback";
require_once 'includes/connect.php';

if (!isset($_SESSION['customer_sid']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$uid = intval($_SESSION['user_id']);
$msg = isset($_GET['msg']) ? $_GET['msg'] : '';

// Create Ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim(htmlspecialchars($_POST['subject'] ?? ''));
    $type = trim($_POST['type'] ?? 'Support');
    $desc = trim(htmlspecialchars($_POST['description'] ?? ''));

    if (!empty($subject) && !empty($desc)) {
        $stmt = $con->prepare("INSERT INTO tickets (poster_id, subject, type, description, status, date) VALUES (?, ?, ?, ?, 'Open', NOW())");
        $stmt->bind_param("isss", $uid, $subject, $type, $desc);
        $stmt->execute();
        header("Location: tickets.php?msg=created");
        exit;
    }
}

// Fetch user tickets
$tickets = [];
$t_res = $con->query("SELECT * FROM tickets WHERE poster_id = $uid AND deleted = 0 ORDER BY id DESC");
if ($t_res) {
    while ($r = $t_res->fetch_assoc()) {
        $tickets[] = $r;
    }
}

include 'includes/header.php';
?>

<div class="container" style="padding: 3rem 1.5rem 5rem;">
  <div style="margin-bottom:2rem;">
    <span style="color:var(--brand-primary); font-weight:700; font-size:0.85rem; text-transform:uppercase; letter-spacing:1px;">Helpdesk & Feedback</span>
    <h1 style="font-family:'Outfit',sans-serif; font-size:2.3rem; font-weight:800; color:var(--text-primary);">Campus Cafeteria Support</h1>
  </div>

  <?php if ($msg === 'created'): ?>
    <div class="alert alert-success" style="margin-bottom:2rem;"><i class="fa-solid fa-circle-check"></i> Your ticket has been submitted to the cafeteria administration!</div>
  <?php endif; ?>

  <div style="display:grid; grid-template-columns:1fr 1.2fr; gap:2.5rem; align-items:flex-start;">
    <!-- Create Ticket Form -->
    <div class="card" style="padding:2rem;">
      <h3 style="font-family:'Outfit',sans-serif; font-size:1.25rem; font-weight:700; color:var(--text-primary); margin-bottom:1.5rem; display:flex; align-items:center; gap:0.5rem;">
        <i class="fa-solid fa-pen-to-square text-orange"></i> Submit New Inquiry
      </h3>

      <form method="POST" action="tickets.php">
        <div class="form-group">
          <label class="form-label">Subject</label>
          <input type="text" name="subject" class="form-control" placeholder="e.g. Issue with token or food quality" required>
        </div>

        <div class="form-group">
          <label class="form-label">Category</label>
          <select name="type" class="form-control">
            <option value="Order Issue">Order / Pickup Issue</option>
            <option value="Food Quality">Food Quality / Feedback</option>
            <option value="Wallet">Wallet / Payment</option>
            <option value="General">General Campus Cafeteria Inquiry</option>
          </select>
        </div>

        <div class="form-group" style="margin-bottom:1.75rem;">
          <label class="form-label">Description / Details</label>
          <textarea name="description" class="form-control" rows="4" placeholder="Explain your feedback or issue clearly..." required></textarea>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;">
          <i class="fa-solid fa-paper-plane"></i> Submit Ticket
        </button>
      </form>
    </div>

    <!-- Ticket History -->
    <div class="card" style="padding:2rem;">
      <h3 style="font-family:'Outfit',sans-serif; font-size:1.25rem; font-weight:700; color:var(--text-primary); margin-bottom:1.5rem;">
        Your Past Tickets (<?php echo count($tickets); ?>)
      </h3>

      <?php if (count($tickets) === 0): ?>
        <p style="color:var(--text-secondary); text-align:center; padding:2rem 0;">You haven't submitted any support tickets yet.</p>
      <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:1rem;">
          <?php foreach ($tickets as $t): ?>
            <div style="background:var(--bg-surface-2); border:1px solid var(--border-subtle); border-radius:12px; padding:1.25rem;">
              <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.4rem;">
                <h4 style="font-family:'Outfit',sans-serif; font-weight:700; font-size:1.05rem; color:var(--text-primary);"><?php echo htmlspecialchars($t['subject']); ?></h4>
                <span class="badge badge-<?php echo ($t['status'] === 'Answered' ? 'emerald' : ($t['status'] === 'Closed' ? 'gray' : 'amber')); ?>">
                  <?php echo htmlspecialchars($t['status']); ?>
                </span>
              </div>
              <p style="font-size:0.88rem; color:var(--text-primary); margin-bottom:0.75rem; line-height:1.45;"><?php echo htmlspecialchars($t['description']); ?></p>
              <div style="font-size:0.75rem; color:var(--text-secondary); display:flex; justify-content:space-between;">
                <span>Type: <?php echo htmlspecialchars($t['type']); ?></span>
                <span><?php echo date('d M Y, h:i A', strtotime($t['date'])); ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>