<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/connect.php';

// Fetch wallet balance if logged in as customer
$wallet_balance = 0.00;
if (isset($_SESSION['customer_sid']) && isset($_SESSION['user_id'])) {
    $uid = intval($_SESSION['user_id']);
    $w_res = $con->query("SELECT wd.balance FROM wallet w JOIN wallet_details wd ON w.id = wd.wallet_id WHERE w.customer_id = $uid");
    if ($w_res && $w_row = $w_res->fetch_assoc()) {
        $wallet_balance = floatval($w_row['balance']);
    }
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="referrer" content="no-referrer">
  <title><?php echo isset($page_title) ? $page_title . ' | Biteora' : 'Biteora - Smart Campus Dining | Skip the Queue. Eat Smarter.'; ?></title>
  
  <!-- Favicon -->
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23FF5A36'><path d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9v-2h2v2zm0-4H9V7h2v5z'/></svg>">
  
  <!-- FontAwesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <!-- Razorpay Standard Checkout SDK -->
  <script src="https://checkout.razorpay.com/v1/checkout.js"></script>

  <!-- Biteora Modern CSS -->
  <link rel="stylesheet" href="css/campusbite.css">
</head>
<body>

  <!-- Navigation Bar -->
  <header class="cb-navbar">
    <div class="container cb-navbar-inner">
      <a href="index.php" class="cb-logo">
        <div class="cb-logo-icon">
          <i class="fa-solid fa-utensils"></i>
        </div>
        <span>Bite<span class="text-orange">ora</span></span>
      </a>

      <ul class="cb-nav-links">
        <li><a href="index.php" class="cb-nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">Home</a></li>
        <li><a href="menu.php" class="cb-nav-link <?php echo ($current_page == 'menu.php') ? 'active' : ''; ?>">Menu</a></li>
        <li><a href="index.php#how-it-works" class="cb-nav-link">How It Works</a></li>
        <li><a href="index.php#why-biteora" class="cb-nav-link">Why Biteora</a></li>
        <?php if (isset($_SESSION['customer_sid'])): ?>
          <li><a href="orders.php" class="cb-nav-link <?php echo ($current_page == 'orders.php' || $current_page == 'track-order.php') ? 'active' : ''; ?>">My Orders</a></li>
        <?php endif; ?>
        <?php if (isset($_SESSION['admin_sid'])): ?>
          <li><a href="admin-page.php" class="cb-nav-link text-orange"><i class="fa-solid fa-chart-line"></i> Admin Panel</a></li>
        <?php endif; ?>
      </ul>

      <div class="cb-nav-actions">
        <?php if (isset($_SESSION['customer_sid'])): ?>
          <!-- Wallet Balance Pill -->
          <a href="cart.php" class="btn btn-secondary btn-sm" style="background: rgba(255,90,54,0.08); border-color: rgba(255,90,54,0.25); color: var(--brand-primary);" title="Campus Wallet">
            <i class="fa-solid fa-wallet"></i>
            <span>₹<?php echo number_format($wallet_balance, 2); ?></span>
          </a>

          <!-- Cart Button with Counter Badge -->
          <a href="cart.php" class="btn btn-secondary btn-sm" style="position: relative;" title="Shopping Cart">
            <i class="fa-solid fa-bag-shopping"></i>
            <span id="cb-cart-badge" class="badge badge-brand" style="display:none; position:absolute; top:-6px; right:-6px; padding:2px 6px; font-size:0.68rem;">0</span>
          </a>

          <!-- User Profile Dropdown -->
          <div style="display:flex; align-items:center; gap:0.5rem;">
            <a href="details.php" class="btn btn-secondary btn-sm" title="My Profile">
              <i class="fa-solid fa-user-graduate text-orange"></i>
              <span><?php echo htmlspecialchars($_SESSION['name'] ?? 'Student'); ?></span>
            </a>
            <a href="routers/logout.php" class="btn btn-danger btn-sm" title="Logout">
              <i class="fa-solid fa-arrow-right-from-bracket"></i>
            </a>
          </div>

        <?php elseif (isset($_SESSION['admin_sid'])): ?>
          <a href="admin-page.php" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-shield-halved"></i> Admin
          </a>
          <a href="routers/logout.php" class="btn btn-danger btn-sm">Logout</a>

        <?php else: ?>
          <!-- Guest Navigation -->
          <a href="cart.php" class="btn btn-secondary btn-sm" style="position: relative;">
            <i class="fa-solid fa-bag-shopping"></i>
            <span id="cb-cart-badge" class="badge badge-brand" style="display:none; position:absolute; top:-6px; right:-6px; padding:2px 6px; font-size:0.68rem;">0</span>
          </a>
          <a href="login.php" class="btn btn-secondary btn-sm">Login</a>
          <a href="menu.php" class="btn btn-primary btn-sm">Order Now</a>
        <?php endif; ?>
      </div>
    </div>
  </header>
