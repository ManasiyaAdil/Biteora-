<?php
$page_title = "Your Shopping Cart";
require_once 'includes/connect.php';

include 'includes/header.php';
?>

<div class="container" style="padding: 2.5rem 1.5rem 5rem;">
  <div style="margin-bottom:1.75rem;">
    <span style="color:var(--brand-primary); font-weight:700; font-size:0.8rem; text-transform:uppercase; letter-spacing:1px;">Review & Order</span>
    <h1 style="font-family:var(--font-display); font-size:2.2rem; font-weight:800; color:var(--text-primary);">Your Food Cart</h1>
  </div>

  <div style="display:grid; grid-template-columns:1.35fr 0.85fr; gap:2rem; align-items:flex-start;">
    <!-- Items List Card -->
    <div class="card" style="padding:1.5rem; background:#FFFFFF;">
      <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem; padding-bottom:0.85rem; border-bottom:1px solid var(--border-subtle);">
        <h3 style="font-family:var(--font-display); font-size:1.15rem; font-weight:700; color:var(--text-primary);">Selected Dishes</h3>
        <button type="button" onclick="CampusBite.clearCart(); CampusBite.renderCartUI();" class="btn btn-secondary btn-sm" style="font-size:0.78rem; color:var(--danger);">
          <i class="fa-solid fa-trash-can"></i> Clear All
        </button>
      </div>

      <!-- Empty State -->
      <div id="cb-cart-empty" style="display:none; text-align:center; padding:3.5rem 1rem;">
        <div style="width:60px; height:60px; border-radius:50%; background:var(--bg-surface-2); display:flex; align-items:center; justify-content:center; font-size:1.75rem; color:var(--text-muted); margin:0 auto 1.25rem;">
          <i class="fa-solid fa-cart-shopping"></i>
        </div>
        <h4 style="font-family:var(--font-display); font-size:1.25rem; color:var(--text-primary); margin-bottom:0.4rem;">Your cart is empty</h4>
        <p style="color:var(--text-secondary); font-size:0.9rem; margin-bottom:1.5rem;">Explore our delicious campus menu and add your favorite bites!</p>
        <a href="menu.php" class="btn btn-primary">Browse Menu Now</a>
      </div>

      <!-- Rendered Items Container -->
      <div id="cb-cart-items">
        <!-- Rendered dynamically by CampusBite.renderCartUI() -->
      </div>
    </div>

    <!-- Order Summary Card & Checkout Form (Sticky) -->
    <div class="card" style="padding:1.5rem; position:sticky; top:85px; background:#FFFFFF; border:1px solid var(--border-subtle);">
      <h3 style="font-family:var(--font-display); font-size:1.2rem; font-weight:700; color:var(--text-primary); margin-bottom:1.25rem;">Order Summary</h3>

      <!-- Pickup / Delivery Selection -->
      <div style="margin-bottom:1.25rem; background:var(--bg-surface-2); border:1px solid var(--border-subtle); border-radius:10px; padding:0.75rem;">
        <div style="font-size:0.78rem; font-weight:700; color:var(--text-secondary); text-transform:uppercase; margin-bottom:0.4rem;">Dining Option</div>
        <div style="display:flex; gap:0.5rem;">
          <label style="flex:1; display:flex; align-items:center; justify-content:center; gap:0.4rem; padding:0.5rem; background:#FFFFFF; border:1.5px solid var(--brand-primary); border-radius:8px; font-size:0.85rem; font-weight:700; color:var(--text-primary); cursor:pointer;">
            <input type="radio" name="dining_mode" value="pickup" checked style="accent-color:var(--brand-primary);"> Counter Pickup
          </label>
          <label style="flex:1; display:flex; align-items:center; justify-content:center; gap:0.4rem; padding:0.5rem; background:#FFFFFF; border:1px solid var(--border-subtle); border-radius:8px; font-size:0.85rem; font-weight:700; color:var(--text-secondary); cursor:pointer;">
            <input type="radio" name="dining_mode" value="dine_in" style="accent-color:var(--brand-primary);"> Cafeteria Table
          </label>
        </div>
      </div>

      <div style="display:flex; flex-direction:column; gap:0.75rem; margin-bottom:1.25rem; font-size:0.9rem;">
        <div style="display:flex; justify-content:space-between; color:var(--text-secondary);">
          <span>Subtotal</span>
          <span id="cb-cart-total" style="font-weight:700; color:var(--text-primary);">₹0.00</span>
        </div>
        <div style="display:flex; justify-content:space-between; color:var(--text-secondary);">
          <span>Cafeteria Packaging</span>
          <span class="text-emerald" style="font-weight:700;">FREE (₹0.00)</span>
        </div>
        <div style="display:flex; justify-content:space-between; color:var(--text-secondary);">
          <span>Kitchen Token Fee</span>
          <span class="text-emerald" style="font-weight:700;">INCLUDED</span>
        </div>
        <div style="border-top:1px solid var(--border-subtle); padding-top:0.85rem; display:flex; justify-content:space-between; align-items:center;">
          <span style="font-weight:800; font-size:1.05rem; color:var(--text-primary);">Grand Total</span>
          <span id="cb-cart-grand-total" style="font-family:var(--font-display); font-weight:900; font-size:1.5rem; color:var(--brand-primary);">₹0.00</span>
        </div>
      </div>

      <?php if (!isset($_SESSION['customer_sid'])): ?>
        <div style="background:var(--warning-bg); border:1px solid rgba(245,158,11,0.3); border-radius:8px; padding:0.75rem; margin-bottom:1.25rem; font-size:0.82rem; color:var(--warning); display:flex; gap:0.5rem; align-items:center;">
          <i class="fa-solid fa-circle-info"></i>
          <span>Please login to proceed to payment and get your token.</span>
        </div>
        <a href="login.php?redirect=checkout.php" class="btn btn-primary" style="width:100%; padding:0.8rem; font-size:0.95rem;">
          Login to Proceed <i class="fa-solid fa-arrow-right"></i>
        </a>
      <?php else: ?>
        <a href="checkout.php" id="cb-checkout-btn" class="btn btn-primary" style="width:100%; padding:0.85rem; font-size:1rem;">
          Proceed to Pay <i class="fa-solid fa-arrow-right"></i>
        </a>
      <?php endif; ?>

      <div style="margin-top:1.25rem; display:flex; align-items:center; gap:0.5rem; justify-content:center; font-size:0.78rem; color:var(--text-muted);">
        <i class="fa-solid fa-shield-halved text-emerald"></i> Safe & Secure Campus Order Verification
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  CampusBite.renderCartUI();
});
</script>

<?php include 'includes/footer.php'; ?>
