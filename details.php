<?php
$page_title = "My Account & Campus Wallet";
require_once 'includes/connect.php';

if (!isset($_SESSION['customer_sid']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$uid = intval($_SESSION['user_id']);
$msg = isset($_GET['msg']) ? $_GET['msg'] : '';

// Update profile if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim(htmlspecialchars($_POST['name'] ?? ''));
    $email = trim($_POST['email'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $address = trim(htmlspecialchars($_POST['address'] ?? ''));

    if (!empty($name) && !empty($contact)) {
        $up_stmt = $con->prepare("UPDATE users SET name = ?, email = ?, contact = ?, address = ? WHERE id = ?");
        $up_stmt->bind_param("ssssi", $name, $email, $contact, $address, $uid);
        $up_stmt->execute();
        $_SESSION['name'] = $name;
        header("Location: details.php?msg=updated");
        exit;
    }
}

// Fetch user profile & wallet
$stmt = $con->prepare("SELECT u.*, wd.number as card_num, wd.balance 
                       FROM users u 
                       LEFT JOIN wallet w ON u.id = w.customer_id 
                       LEFT JOIN wallet_details wd ON w.id = wd.wallet_id 
                       WHERE u.id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

include 'includes/header.php';
?>

<div class="container" style="padding: 2.5rem 1.5rem 5rem;">
  <div style="margin-bottom:1.75rem;">
    <span style="color:var(--brand-primary); font-weight:700; font-size:0.8rem; text-transform:uppercase; letter-spacing:1px;">Account Settings</span>
    <h1 style="font-family:var(--font-display); font-size:2.2rem; font-weight:800; color:var(--text-primary);">Student Profile & Wallet</h1>
  </div>

  <?php if ($msg === 'updated'): ?>
    <div style="background:var(--success-bg); border:1px solid rgba(34,160,107,0.3); border-radius:10px; padding:1rem; margin-bottom:1.5rem; color:var(--success); display:flex; gap:0.65rem; align-items:center;">
      <i class="fa-solid fa-circle-check"></i>
      <span>Profile details updated successfully!</span>
    </div>
  <?php endif; ?>

  <div style="display:grid; grid-template-columns:1.2fr 0.8fr; gap:2rem; align-items:flex-start;">
    <!-- Profile Form -->
    <div class="card" style="padding:1.75rem; background:#FFFFFF;">
      <h3 style="font-family:var(--font-display); font-size:1.15rem; font-weight:700; color:var(--text-primary); margin-bottom:1.25rem; display:flex; align-items:center; gap:0.5rem;">
        <i class="fa-solid fa-user-pen text-orange"></i> Personal Information
      </h3>

      <form method="POST" action="details.php">
        <div class="form-group">
          <label class="form-label">Full Name</label>
          <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required style="background:#FFFFFF;">
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
          <div class="form-group">
            <label class="form-label">Username</label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" readonly style="background:var(--bg-surface-2); color:var(--text-muted);">
          </div>
          <div class="form-group">
            <label class="form-label">Contact Phone</label>
            <input type="text" name="contact" class="form-control" value="<?php echo htmlspecialchars($user['contact'] ?? ''); ?>" required style="background:#FFFFFF;">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Campus Email</label>
          <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" style="background:#FFFFFF;">
        </div>

        <div class="form-group" style="margin-bottom:1.5rem;">
          <label class="form-label">Hostel Room / Department</label>
          <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" required style="background:#FFFFFF;">
        </div>

        <button type="submit" class="btn btn-primary" style="padding:0.75rem 1.5rem;">
          <i class="fa-solid fa-save"></i> Save Changes
        </button>
      </form>
    </div>

    <!-- Campus Wallet Card -->
    <div class="card" style="padding:1.75rem; background:#FFFFFF; border:1px solid var(--border-subtle);">
      <h3 style="font-family:var(--font-display); font-size:1.15rem; font-weight:700; color:var(--text-primary); margin-bottom:1.25rem; display:flex; align-items:center; gap:0.5rem;">
        <i class="fa-solid fa-wallet text-orange"></i> Campus Smart Wallet
      </h3>

      <div style="background:linear-gradient(135deg, #FF5A36 0%, #FF7048 100%); border-radius:16px; padding:1.5rem; color:#FFFFFF; margin-bottom:1.5rem; box-shadow:var(--shadow-orange); position:relative; overflow:hidden;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem;">
          <span style="font-family:var(--font-display); font-weight:800; font-size:1.15rem;">Biteora Smart Card</span>
          <i class="fa-solid fa-wifi" style="font-size:1.2rem; opacity:0.85;"></i>
        </div>
        <div style="font-size:0.75rem; text-transform:uppercase; letter-spacing:1px; opacity:0.85;">Available Balance</div>
        <div style="font-family:var(--font-display); font-size:2.2rem; font-weight:900; line-height:1.1; margin:0.25rem 0 1rem;">
          ₹<?php echo number_format(floatval($user['balance'] ?? 0), 2); ?>
        </div>
        <div style="display:flex; justify-content:space-between; font-size:0.8rem; font-family:monospace;">
          <span>CARD: •••• <?php echo htmlspecialchars(substr($user['card_num'] ?? '8888', -4)); ?></span>
          <span>EXP: 12/28</span>
        </div>
      </div>

      <div style="background:var(--bg-surface-2); padding:1rem; border-radius:12px; font-size:0.85rem; color:var(--text-secondary);">
        <div style="font-weight:700; color:var(--text-primary); margin-bottom:0.25rem;">Direct Cafeteria Integration</div>
        <p>Wallet balance can be used directly at checkout for 1-click zero-fee cafeteria payments.</p>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>