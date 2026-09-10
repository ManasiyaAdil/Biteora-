<?php
$page_title = "Student Registration";
require_once 'includes/connect.php';

if (isset($_SESSION['admin_sid']) || isset($_SESSION['customer_sid'])) {
    header("Location: index.php");
    exit;
}

$error = isset($_GET['error']) ? $_GET['error'] : '';

include 'includes/header.php';
?>

<div class="container" style="padding: 3.5rem 1.5rem 5rem; display:flex; align-items:center; justify-content:center;">
  <div class="card" style="width:100%; max-width:480px; padding:2.25rem 2rem; background:#FFFFFF; border:1px solid var(--border-subtle); border-radius:24px; box-shadow:var(--shadow-lg);">
    <div style="text-align:center; margin-bottom:1.75rem;">
      <div class="cb-logo-icon" style="margin:0 auto 0.85rem; width:46px; height:46px; font-size:1.35rem;">
        <i class="fa-solid fa-user-plus"></i>
      </div>
      <h2 style="font-family:var(--font-display); font-size:1.75rem; font-weight:800; color:var(--text-primary);">Student Registration</h2>
      <p style="font-size:0.85rem; color:var(--text-secondary); margin-top:0.25rem;">Create your Biteora account to start digital ordering.</p>
    </div>

    <?php if ($error): ?>
      <div style="background:var(--danger-bg); border:1px solid rgba(239,68,68,0.3); border-radius:8px; padding:0.75rem; margin-bottom:1.25rem; font-size:0.85rem; color:var(--danger); display:flex; gap:0.5rem; align-items:center;">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <span><?php echo htmlspecialchars($error); ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" action="routers/register-router.php">
      <div class="form-group">
        <label class="form-label">Full Name</label>
        <div style="position:relative;">
          <i class="fa-solid fa-signature" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--text-muted);"></i>
          <input type="text" name="name" class="form-control" placeholder="e.g. Rahul Sharma" required style="padding-left:2.5rem; background:#FFFFFF;">
        </div>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.85rem;">
        <div class="form-group">
          <label class="form-label">Username</label>
          <div style="position:relative;">
            <i class="fa-solid fa-user" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--text-muted);"></i>
            <input type="text" name="username" class="form-control" placeholder="e.g. rahul123" required style="padding-left:2.5rem; background:#FFFFFF;">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Phone Number</label>
          <div style="position:relative;">
            <i class="fa-solid fa-phone" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--text-muted);"></i>
            <input type="tel" name="phone" class="form-control" placeholder="e.g. 9876543210" required style="padding-left:2.5rem; background:#FFFFFF;">
          </div>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Campus Email (Optional)</label>
        <div style="position:relative;">
          <i class="fa-solid fa-envelope" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--text-muted);"></i>
          <input type="email" name="email" class="form-control" placeholder="e.g. rahul@campus.edu" style="padding-left:2.5rem; background:#FFFFFF;">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Hostel Room / Department</label>
        <div style="position:relative;">
          <i class="fa-solid fa-building" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--text-muted);"></i>
          <input type="text" name="address" class="form-control" placeholder="e.g. Hostel Block B, Room 204" required style="padding-left:2.5rem; background:#FFFFFF;">
        </div>
      </div>

      <div class="form-group" style="margin-bottom:1.5rem;">
        <label class="form-label">Password</label>
        <div style="position:relative;">
          <i class="fa-solid fa-lock" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--text-muted);"></i>
          <input type="password" name="password" class="form-control" placeholder="Choose a secure password" required minlength="4" style="padding-left:2.5rem; background:#FFFFFF;">
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-lg" style="width:100%; margin-bottom:1.25rem;">
        <i class="fa-solid fa-user-plus"></i> Create Account & Get ₹1,500 Wallet
      </button>

      <div style="text-align:center; font-size:0.85rem; color:var(--text-secondary);">
        Already registered? <a href="login.php" style="color:var(--brand-primary); font-weight:700;">Sign In here</a>
      </div>
    </form>
  </div>
</div>

<?php include 'includes/footer.php'; ?>