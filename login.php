<?php
$page_title = "Login";
require_once 'includes/connect.php';

if (isset($_SESSION['admin_sid'])) {
    header("Location: admin-page.php");
    exit;
} elseif (isset($_SESSION['customer_sid'])) {
    header("Location: index.php");
    exit;
}

$error = isset($_GET['error']) ? $_GET['error'] : '';
$redirect = isset($_GET['redirect']) ? htmlspecialchars($_GET['redirect']) : 'index.php';

include 'includes/header.php';
?>

<div class="container" style="padding: 3.5rem 1.5rem 5rem; display:flex; align-items:center; justify-content:center;">
  <div class="card" style="width:100%; max-width:440px; padding:2.25rem 2rem; background:#FFFFFF; border:1px solid var(--border-subtle); border-radius:24px; box-shadow:var(--shadow-lg);">
    <div style="text-align:center; margin-bottom:1.75rem;">
      <div class="cb-logo-icon" style="margin:0 auto 0.85rem; width:46px; height:46px; font-size:1.35rem;">
        <i class="fa-solid fa-utensils"></i>
      </div>
      <h2 style="font-family:var(--font-display); font-size:1.75rem; font-weight:800; color:var(--text-primary);">Welcome to Biteora</h2>
      <p style="font-size:0.85rem; color:var(--text-secondary); margin-top:0.25rem;">Sign in to order food, track queues & use campus wallet.</p>
    </div>

    <?php if ($error): ?>
      <div style="background:var(--danger-bg); border:1px solid rgba(239,68,68,0.3); border-radius:8px; padding:0.75rem; margin-bottom:1.25rem; font-size:0.85rem; color:var(--danger); display:flex; gap:0.5rem; align-items:center;">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <span>Invalid username or password. Please try again.</span>
      </div>
    <?php endif; ?>

    <form method="POST" action="routers/router.php">
      <input type="hidden" name="redirect" value="<?php echo $redirect; ?>">

      <div class="form-group">
        <label class="form-label">Username</label>
        <div style="position:relative;">
          <i class="fa-solid fa-user" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--text-muted);"></i>
          <input type="text" name="username" id="cb-login-username" class="form-control" placeholder="Enter your username" required style="padding-left:2.5rem; background:#FFFFFF;">
        </div>
      </div>

      <div class="form-group" style="margin-bottom:1.25rem;">
        <label class="form-label">Password</label>
        <div style="position:relative;">
          <i class="fa-solid fa-lock" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--text-muted);"></i>
          <input type="password" name="password" id="cb-login-password" class="form-control" placeholder="Enter your password" required style="padding-left:2.5rem; background:#FFFFFF;">
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-lg" style="width:100%; margin-bottom:1.25rem;">
        <i class="fa-solid fa-arrow-right-to-bracket"></i> Sign In
      </button>

      <!-- Instant Demo Credentials -->
      <div style="background:var(--bg-surface-2); border:1px dashed var(--border-subtle); border-radius:12px; padding:0.85rem; margin-bottom:1.25rem;">
        <div style="font-size:0.72rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:0.5rem; text-align:center;">
          ⚡ Quick Demo Accounts
        </div>
        <div style="display:flex; gap:0.5rem;">
          <button type="button" class="btn btn-secondary btn-sm" style="flex:1; font-size:0.78rem;" onclick="document.getElementById('cb-login-username').value='user1'; document.getElementById('cb-login-password').value='pass1';">
            👤 Student
          </button>
          <button type="button" class="btn btn-secondary btn-sm" style="flex:1; font-size:0.78rem; color:#F59E0B;" onclick="document.getElementById('cb-login-username').value='root'; document.getElementById('cb-login-password').value='toor';">
            🛡️ Admin
          </button>
        </div>
      </div>

      <div style="text-align:center; font-size:0.85rem; color:var(--text-secondary);">
        New to Biteora? <a href="register.php" style="color:var(--brand-primary); font-weight:700;">Create an Account</a>
      </div>
    </form>
  </div>
</div>

<?php include 'includes/footer.php'; ?>