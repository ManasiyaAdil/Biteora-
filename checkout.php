<?php
$page_title = "Secure Checkout";
require_once 'includes/connect.php';

if (!isset($_SESSION['customer_sid']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=checkout.php");
    exit;
}

$user_id = intval($_SESSION['user_id']);
$user_res = $con->query("SELECT * FROM users WHERE id = $user_id");
$user = $user_res ? $user_res->fetch_assoc() : null;

// Fetch wallet balance
$wallet_balance = 0.00;
$w_res = $con->query("SELECT wd.balance FROM wallet w JOIN wallet_details wd ON w.id = wd.wallet_id WHERE w.customer_id = $user_id");
if ($w_res && $w_row = $w_res->fetch_assoc()) {
    $wallet_balance = floatval($w_row['balance']);
}

$delivery_fee_amount = floatval(defined('CAMPUS_DELIVERY_FEE') ? CAMPUS_DELIVERY_FEE : 20.00);

include 'includes/header.php';
?>

<div class="container" style="padding: 2.5rem 1.5rem 5rem;">
  <div style="margin-bottom:1.75rem;">
    <span style="color:var(--brand-primary); font-weight:700; font-size:0.8rem; text-transform:uppercase; letter-spacing:1px;">Final Step</span>
    <h1 style="font-family:var(--font-display); font-size:2.2rem; font-weight:800; color:var(--text-primary);">Checkout & Payment</h1>
    <p style="color:var(--text-secondary); font-size:0.9rem;">Choose cafeteria pickup or campus delivery to your classroom, then select payment.</p>
  </div>

  <form id="cb-checkout-form" onsubmit="event.preventDefault(); handleCheckoutSubmission();">
    <div style="display:grid; grid-template-columns:1.35fr 0.85fr; gap:2rem; align-items:flex-start;">
      
      <!-- Left Column: Delivery Method, Address Details & Payment Methods -->
      <div style="display:flex; flex-direction:column; gap:1.5rem;">
        
        <!-- Delivery Method Selector Card -->
        <div class="card" style="padding:1.5rem; background:#FFFFFF;">
          <h3 style="font-family:var(--font-display); font-size:1.15rem; font-weight:700; color:var(--text-primary); margin-bottom:1.15rem;">
            <i class="fa-solid fa-truck-fast text-orange" style="margin-right:0.4rem;"></i> HOW DO YOU WANT YOUR FOOD?
          </h3>

          <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1.25rem;">
            <!-- Pickup Option -->
            <label id="method-pickup-label" class="payment-method-option" style="display:flex; flex-direction:column; gap:0.5rem; padding:1.25rem; background:var(--bg-surface-2); border:2px solid var(--brand-primary); border-radius:14px; cursor:pointer; transition:0.2s ease;">
              <div style="display:flex; align-items:center; justify-content:space-between;">
                <span style="font-size:1.5rem;">🍽️</span>
                <input type="radio" name="delivery_method" value="pickup" checked style="accent-color:var(--brand-primary); width:18px; height:18px;" onchange="toggleDeliveryMethod();">
              </div>
              <div style="font-family:var(--font-display); font-weight:800; font-size:1.05rem; color:var(--text-primary);">PICKUP</div>
              <div style="font-size:0.8rem; color:var(--text-secondary);">Collect from cafeteria counter</div>
              <div style="margin-top:auto; font-size:0.82rem; font-weight:700; color:var(--success);">₹0 delivery fee</div>
            </label>

            <!-- Delivery Option -->
            <label id="method-delivery-label" class="payment-method-option" style="display:flex; flex-direction:column; gap:0.5rem; padding:1.25rem; background:var(--bg-surface-2); border:1px solid var(--border-subtle); border-radius:14px; cursor:pointer; transition:0.2s ease;">
              <div style="display:flex; align-items:center; justify-content:space-between;">
                <span style="font-size:1.5rem;">🚚</span>
                <input type="radio" name="delivery_method" value="delivery" style="accent-color:var(--brand-primary); width:18px; height:18px;" onchange="toggleDeliveryMethod();">
              </div>
              <div style="font-family:var(--font-display); font-weight:800; font-size:1.05rem; color:var(--text-primary); display:flex; align-items:center; gap:0.4rem;">
                <span>CAMPUS DELIVERY</span>
              </div>
              <div style="font-size:0.8rem; color:var(--text-secondary);">Delivered to your classroom / block</div>
              <div style="margin-top:auto; font-size:0.82rem; font-weight:700; color:var(--brand-primary);">₹<?php echo number_format($delivery_fee_amount, 0); ?> delivery fee</div>
            </label>
          </div>

          <!-- Pickup Section Details -->
          <div id="pickup-details-box">
            <div class="form-group">
              <label class="form-label" for="cb-pickup-address">Pickup Counter / Seating</label>
              <input type="text" id="cb-pickup-address" name="address" class="form-control" value="Campus Cafeteria - Express Counter 1" style="background:#FFFFFF;">
              <span style="font-size:0.75rem; color:var(--text-muted); margin-top:0.3rem; display:block;">Specify counter or cafeteria table number.</span>
            </div>

            <div class="form-group" style="margin-bottom:0;">
              <label class="form-label" for="cb-cooking-notes">Kitchen Cooking Instructions (Optional)</label>
              <input type="text" id="cb-cooking-notes" name="description" class="form-control" placeholder="e.g. Extra spicy, less oil, separate sauce pack..." style="background:#FFFFFF;">
            </div>
          </div>

          <!-- Campus Delivery Section Details (Hidden by default) -->
          <div id="delivery-details-box" style="display:none; padding-top:1rem; border-top:1px dashed var(--border-subtle);">
            <div style="font-weight:700; font-size:0.95rem; color:var(--text-primary); margin-bottom:1rem; display:flex; align-items:center; gap:0.4rem;">
              <i class="fa-solid fa-map-pin text-orange"></i> DELIVERY DETAILS
            </div>

            <div class="form-group">
              <label class="form-label" for="cb-delivery-building">
                🏫 Campus Building / Block <span style="color:var(--danger);">*</span>
              </label>
              <select id="cb-delivery-building-select" class="form-control" style="background:#FFFFFF; margin-bottom:0.5rem;" onchange="handleBuildingSelect(this);">
                <option value="">-- Select Campus Building / Block --</option>
                <option value="Main College Building" selected>Main College Building</option>
                <option value="Science & Tech Block">Science & Tech Block</option>
                <option value="Management Block">Management Block</option>
                <option value="Library & Media Centre">Library & Media Centre</option>
                <option value="Engineering Block B">Engineering Block B</option>
                <option value="Student Activity Center">Student Activity Center</option>
                <option value="Other">Other (Type custom building below)</option>
              </select>
              <input type="text" id="cb-delivery-building" name="delivery_building" class="form-control" value="Main College Building" placeholder="Enter building name" style="background:#FFFFFF;">
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
              <div class="form-group">
                <label class="form-label" for="cb-delivery-floor">
                  Floor <span style="color:var(--danger);">*</span>
                </label>
                <input type="text" id="cb-delivery-floor" name="delivery_floor" class="form-control" placeholder="e.g. 3" value="3" style="background:#FFFFFF;">
              </div>

              <div class="form-group">
                <label class="form-label" for="cb-delivery-room">
                  🚪 Room / Classroom <span style="color:var(--danger);">*</span>
                </label>
                <input type="text" id="cb-delivery-room" name="delivery_room" class="form-control" placeholder="e.g. 304 / Lab 3" value="304" style="background:#FFFFFF;">
              </div>
            </div>

            <div class="form-group" style="margin-bottom:0;">
              <label class="form-label" for="cb-delivery-note">
                📝 Delivery Instructions / Note (Optional)
              </label>
              <input type="text" id="cb-delivery-note" name="delivery_note" class="form-control" placeholder="e.g. Please deliver near Lab 3 entrance." value="Please deliver near Lab 3 entrance." style="background:#FFFFFF;">
            </div>
          </div>

        </div>

        <!-- Payment Method Selection -->
        <div class="card" style="padding:1.5rem; background:#FFFFFF;">
          <h3 style="font-family:var(--font-display); font-size:1.15rem; font-weight:700; color:var(--text-primary); margin-bottom:1.15rem;">
            <i class="fa-solid fa-credit-card text-orange" style="margin-right:0.4rem;"></i> Select Payment Method
          </h3>

          <div style="display:flex; flex-direction:column; gap:0.85rem;">
            
            <!-- Razorpay Standard Online Payment (Default) -->
            <label class="payment-method-option" style="display:flex; align-items:center; justify-content:space-between; padding:1.1rem; background:var(--bg-surface-2); border:1.5px solid var(--brand-primary); border-radius:12px; cursor:pointer; transition:0.2s ease;">
              <div style="display:flex; align-items:center; gap:0.9rem;">
                <input type="radio" name="payment_type" value="Online Payment" checked style="accent-color:var(--brand-primary); width:18px; height:18px;" onchange="updatePayButtonLabel();">
                <div>
                  <div style="font-weight:700; font-size:0.95rem; color:var(--text-primary); display:flex; align-items:center; gap:0.5rem;">
                    <span>Online Payment (Razorpay Standard)</span>
                    <span class="badge badge-brand" style="font-size:0.65rem;">Official Gateway</span>
                  </div>
                  <div style="font-size:0.8rem; color:var(--text-secondary); margin-top:2px;">UPI (GPay / PhonePe / Paytm), Credit / Debit Cards, NetBanking</div>
                </div>
              </div>
              <div style="font-size:1.2rem; color:var(--brand-primary);"><i class="fa-solid fa-bolt"></i></div>
            </label>

            <!-- Scan & Pay QR -->
            <label class="payment-method-option" style="display:flex; align-items:center; justify-content:space-between; padding:1.1rem; background:var(--bg-surface-2); border:1px solid var(--border-subtle); border-radius:12px; cursor:pointer; transition:0.2s ease;">
              <div style="display:flex; align-items:center; gap:0.9rem;">
                <input type="radio" name="payment_type" value="Scan & Pay QR" style="accent-color:var(--brand-primary); width:18px; height:18px;" onchange="updatePayButtonLabel();">
                <div>
                  <div style="font-weight:700; font-size:0.95rem; color:var(--text-primary);">Scan & Pay on Mobile (QR)</div>
                  <div style="font-size:0.8rem; color:var(--text-secondary); margin-top:2px;">Scan with phone camera or Google Lens to pay from your smartphone</div>
                </div>
              </div>
              <div style="font-size:1.2rem; color:var(--text-secondary);"><i class="fa-solid fa-qrcode"></i></div>
            </label>

            <!-- Cash On Delivery / Pickup -->
            <label class="payment-method-option" style="display:flex; align-items:center; justify-content:space-between; padding:1.1rem; background:var(--bg-surface-2); border:1px solid var(--border-subtle); border-radius:12px; cursor:pointer; transition:0.2s ease;">
              <div style="display:flex; align-items:center; gap:0.9rem;">
                <input type="radio" name="payment_type" value="Cash On Delivery" style="accent-color:var(--brand-primary); width:18px; height:18px;" onchange="updatePayButtonLabel();">
                <div>
                  <div style="font-weight:700; font-size:0.95rem; color:var(--text-primary);" id="cash-payment-title">Cash on Pickup</div>
                  <div style="font-size:0.8rem; color:var(--text-secondary); margin-top:2px;" id="cash-payment-desc">Pay directly during food collection</div>
                </div>
              </div>
              <div style="font-size:1.2rem; color:var(--text-secondary);"><i class="fa-solid fa-money-bill-wave"></i></div>
            </label>

            <!-- Campus Wallet -->
            <label class="payment-method-option" style="display:flex; align-items:center; justify-content:space-between; padding:1.1rem; background:var(--bg-surface-2); border:1px solid var(--border-subtle); border-radius:12px; cursor:pointer; transition:0.2s ease;">
              <div style="display:flex; align-items:center; gap:0.9rem;">
                <input type="radio" name="payment_type" value="Wallet" style="accent-color:var(--brand-primary); width:18px; height:18px;" onchange="updatePayButtonLabel();">
                <div>
                  <div style="font-weight:700; font-size:0.95rem; color:var(--text-primary);">Campus Student Wallet</div>
                  <div style="font-size:0.8rem; color:var(--text-secondary); margin-top:2px;">Balance: ₹<?php echo number_format($wallet_balance, 2); ?></div>
                </div>
              </div>
              <div style="font-size:1.2rem; color:var(--brand-primary);"><i class="fa-solid fa-wallet"></i></div>
            </label>

          </div>
        </div>

      </div>

      <!-- Right Column: Order Summary & Pay CTA (Sticky) -->
      <div class="card" style="padding:1.5rem; position:sticky; top:85px; background:#FFFFFF; border:1px solid var(--border-subtle);">
        <h3 style="font-family:var(--font-display); font-size:1.2rem; font-weight:700; color:var(--text-primary); margin-bottom:1.25rem;">Order Items</h3>

        <div id="cb-checkout-items-list" style="margin-bottom:1.25rem; max-height:220px; overflow-y:auto; padding-right:4px;">
          <!-- Populated from localStorage cart -->
        </div>

        <div style="display:flex; flex-direction:column; gap:0.75rem; margin-bottom:1.5rem; font-size:0.9rem;">
          <div style="display:flex; justify-content:space-between; color:var(--text-secondary);">
            <span>Subtotal</span>
            <span id="cb-summary-subtotal" style="font-weight:700; color:var(--text-primary);">₹0.00</span>
          </div>
          <div style="display:flex; justify-content:space-between; color:var(--text-secondary);">
            <span id="cb-summary-delivery-label">Delivery Fee (Pickup)</span>
            <span id="cb-summary-delivery" class="text-emerald" style="font-weight:700;">₹0.00</span>
          </div>
          <div style="display:flex; justify-content:space-between; color:var(--text-secondary);">
            <span>Kitchen Preparation Fee</span>
            <span class="text-emerald" style="font-weight:700;">FREE</span>
          </div>
          <div style="border-top:1px solid var(--border-subtle); padding-top:0.85rem; display:flex; justify-content:space-between; align-items:center;">
            <span style="font-weight:800; font-size:1.05rem; color:var(--text-primary);">Grand Total</span>
            <span id="cb-summary-grandtotal" style="font-family:var(--font-display); font-weight:900; font-size:1.5rem; color:var(--brand-primary);">₹0.00</span>
          </div>
        </div>

        <button type="submit" id="cb-checkout-submit-btn" class="btn btn-primary" style="width:100%; padding:0.9rem; font-size:1.05rem; letter-spacing:0.5px;">
          <i class="fa-solid fa-lock"></i> PAY ₹0.00
        </button>

        <div id="checkout-error-banner" style="display:none; color:var(--danger); font-size:0.85rem; margin-top:0.75rem; text-align:center; background:var(--danger-bg); padding:0.65rem; border-radius:8px; border:1px solid rgba(239,68,68,0.3);"></div>

        <div style="margin-top:1.25rem; text-align:center; font-size:0.75rem; color:var(--text-muted);">
          <i class="fa-solid fa-shield-halved text-emerald"></i> Server-side amount verification & HMAC-SHA256 signature check
        </div>
      </div>

    </div>
  </form>
</div>

<!-- Mobile Payment QR Modal for Scan & Pay -->
<div id="payment-qr-modal" class="modal-overlay">
  <div class="modal-card" style="text-align:center; max-width:440px; background:#FFFFFF;">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem;">
      <h3 style="font-family:var(--font-display); font-size:1.25rem; font-weight:800; color:var(--text-primary);">
        <i class="fa-solid fa-qrcode text-orange"></i> Scan & Pay on Phone
      </h3>
      <button type="button" onclick="document.getElementById('payment-qr-modal').classList.remove('active');" style="background:none; border:none; color:var(--text-secondary); font-size:1.2rem; cursor:pointer;">✕</button>
    </div>

    <p style="font-size:0.85rem; color:var(--text-secondary); margin-bottom:1.25rem;">
      Scan with your iPhone / Android Camera, Google Lens, or UPI App to open the payment page on your phone.
    </p>

    <div style="background:#FFFFFF; padding:0.85rem; border-radius:14px; display:inline-block; margin-bottom:1rem; box-shadow:var(--shadow-md); border:1px solid var(--border-subtle);">
      <img id="payment-qr-img" src="" alt="Payment QR Code" style="width:170px; height:170px;">
    </div>

    <div style="font-family:var(--font-display); font-size:1.4rem; font-weight:900; color:var(--brand-primary); margin-bottom:0.4rem;" id="payment-qr-amount">
      ₹0.00
    </div>

    <div style="font-size:0.82rem; color:var(--text-secondary); margin-bottom:0.75rem;" id="payment-qr-status">
      <i class="fa-solid fa-spinner fa-spin text-orange"></i> Waiting for mobile payment verification...
    </div>

    <div style="font-size:0.72rem; color:var(--text-muted); background:var(--bg-surface-2); padding:0.5rem; border-radius:8px; border:1px solid var(--border-subtle); word-break:break-all;" id="payment-qr-dev-url"></div>

    <div style="margin-top:1rem;">
      <a id="payment-qr-direct-link" href="#" target="_blank" class="btn btn-secondary btn-sm" style="font-size:0.78rem;">
        <i class="fa-solid fa-arrow-up-right-from-square"></i> Open Mobile Checkout in Tab
      </a>
    </div>
  </div>
</div>

<script>
const CAMPUS_DELIVERY_FEE = <?php echo json_encode($delivery_fee_amount); ?>;

function getActiveDeliveryMethod() {
  return document.querySelector('input[name="delivery_method"]:checked')?.value || 'pickup';
}

function getCalculatedTotal() {
  const subtotal = Biteora.getCartTotal();
  const isDelivery = (getActiveDeliveryMethod() === 'delivery');
  const fee = isDelivery ? CAMPUS_DELIVERY_FEE : 0.00;
  return subtotal + fee;
}

function handleBuildingSelect(selectEl) {
  const customInput = document.getElementById('cb-delivery-building');
  if (selectEl.value === 'Other') {
    customInput.value = '';
    customInput.placeholder = 'Enter custom building or department';
    customInput.focus();
  } else if (selectEl.value) {
    customInput.value = selectEl.value;
  }
}

function toggleDeliveryMethod() {
  const method = getActiveDeliveryMethod();
  const pickupLabel = document.getElementById('method-pickup-label');
  const deliveryLabel = document.getElementById('method-delivery-label');
  const pickupBox = document.getElementById('pickup-details-box');
  const deliveryBox = document.getElementById('delivery-details-box');
  const deliverySummaryLabel = document.getElementById('cb-summary-delivery-label');
  const deliverySummaryFee = document.getElementById('cb-summary-delivery');
  const grandtotalEl = document.getElementById('cb-summary-grandtotal');
  const cashTitle = document.getElementById('cash-payment-title');
  const cashDesc = document.getElementById('cash-payment-desc');

  if (method === 'delivery') {
    pickupLabel.style.borderColor = 'var(--border-subtle)';
    deliveryLabel.style.borderColor = 'var(--brand-primary)';
    deliveryLabel.style.borderWidth = '2px';
    pickupLabel.style.borderWidth = '1px';

    pickupBox.style.display = 'none';
    deliveryBox.style.display = 'block';

    deliverySummaryLabel.textContent = 'Campus Delivery Fee';
    deliverySummaryFee.textContent = `₹${CAMPUS_DELIVERY_FEE.toFixed(2)}`;
    deliverySummaryFee.className = 'text-orange';

    if (cashTitle) cashTitle.textContent = 'Cash On Delivery';
    if (cashDesc) cashDesc.textContent = 'Pay cash to delivery personnel upon arrival';
  } else {
    pickupLabel.style.borderColor = 'var(--brand-primary)';
    deliveryLabel.style.borderColor = 'var(--border-subtle)';
    pickupLabel.style.borderWidth = '2px';
    deliveryLabel.style.borderWidth = '1px';

    pickupBox.style.display = 'block';
    deliveryBox.style.display = 'none';

    deliverySummaryLabel.textContent = 'Delivery Fee (Pickup)';
    deliverySummaryFee.textContent = '₹0.00';
    deliverySummaryFee.className = 'text-emerald';

    if (cashTitle) cashTitle.textContent = 'Cash on Pickup';
    if (cashDesc) cashDesc.textContent = 'Pay directly during token collection';
  }

  const grandTotal = getCalculatedTotal();
  grandtotalEl.textContent = `₹${grandTotal.toFixed(2)}`;
  updatePayButtonLabel();
}

function updatePayButtonLabel() {
  const submitBtn = document.getElementById('cb-checkout-submit-btn');
  const grandTotal = getCalculatedTotal();
  const paymentMethod = document.querySelector('input[name="payment_type"]:checked')?.value || 'Online Payment';
  
  if (paymentMethod === 'Online Payment') {
    submitBtn.innerHTML = `<i class="fa-solid fa-lock"></i> PAY ₹${grandTotal.toFixed(2)}`;
  } else if (paymentMethod === 'Scan & Pay QR') {
    submitBtn.innerHTML = `<i class="fa-solid fa-qrcode"></i> GENERATE PAYMENT QR (₹${grandTotal.toFixed(2)})`;
  } else if (paymentMethod === 'Wallet') {
    submitBtn.innerHTML = `<i class="fa-solid fa-wallet"></i> PAY ₹${grandTotal.toFixed(2)} FROM WALLET`;
  } else {
    submitBtn.innerHTML = `<i class="fa-solid fa-bag-shopping"></i> CONFIRM ORDER (₹${grandTotal.toFixed(2)})`;
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const cart = Biteora.getCart();
  const itemsContainer = document.getElementById('cb-checkout-items-list');
  const subtotalEl = document.getElementById('cb-summary-subtotal');
  const grandtotalEl = document.getElementById('cb-summary-grandtotal');
  const submitBtn = document.getElementById('cb-checkout-submit-btn');

  if (cart.length === 0) {
    itemsContainer.innerHTML = '<div style="color:var(--danger); text-align:center; padding:1.5rem;">Your cart is empty. Please add dishes first.</div>';
    if (submitBtn) submitBtn.setAttribute('disabled', 'disabled');
    return;
  }

  let html = '';
  let subtotal = 0;

  cart.forEach(item => {
    const lineTotal = item.price * item.qty;
    subtotal += lineTotal;
    html += `
      <div style="display:flex; justify-content:space-between; align-items:center; padding:0.5rem 0; border-bottom:1px solid var(--border-subtle); font-size:0.88rem;">
        <div>
          <div style="font-weight:700; color:var(--text-primary);">${item.name}</div>
          <div style="font-size:0.78rem; color:var(--text-secondary);">Qty: ${item.qty} × ₹${item.price.toFixed(2)}</div>
        </div>
        <div style="font-weight:800; font-family:var(--font-display); color:var(--text-primary);">₹${lineTotal.toFixed(2)}</div>
      </div>
    `;
  });

  itemsContainer.innerHTML = html;
  subtotalEl.textContent = `₹${subtotal.toFixed(2)}`;
  toggleDeliveryMethod();
});

function handleCheckoutSubmission() {
  const cart = Biteora.getCart();
  const method = getActiveDeliveryMethod();
  const paymentMethod = document.querySelector('input[name="payment_type"]:checked').value;
  const submitBtn = document.getElementById('cb-checkout-submit-btn');
  const errorBanner = document.getElementById('checkout-error-banner');

  errorBanner.style.display = 'none';

  const formData = new FormData();
  formData.append('delivery_method', method);
  formData.append('payment_type', paymentMethod);
  formData.append('items_json', JSON.stringify(cart));

  if (method === 'delivery') {
    const building = document.getElementById('cb-delivery-building').value.trim();
    const floor = document.getElementById('cb-delivery-floor').value.trim();
    const room = document.getElementById('cb-delivery-room').value.trim();
    const note = document.getElementById('cb-delivery-note').value.trim();

    if (!building) {
      errorBanner.textContent = 'Please enter or select a Campus Building / Block.';
      errorBanner.style.display = 'block';
      return;
    }
    if (!floor) {
      errorBanner.textContent = 'Please specify the floor number (e.g. 3).';
      errorBanner.style.display = 'block';
      return;
    }
    if (!room) {
      errorBanner.textContent = 'Please specify your room or classroom number (e.g. 304).';
      errorBanner.style.display = 'block';
      return;
    }

    formData.append('delivery_building', building);
    formData.append('delivery_floor', floor);
    formData.append('delivery_room', room);
    formData.append('delivery_note', note);
    formData.append('address', `Campus Delivery: ${building}, Floor ${floor}, Room ${room}`);
    formData.append('description', note);
  } else {
    const pickupAddress = document.getElementById('cb-pickup-address').value.trim();
    const cookingNotes = document.getElementById('cb-cooking-notes').value.trim();
    formData.append('address', pickupAddress || 'Campus Cafeteria Counter 1');
    formData.append('description', cookingNotes);
  }

  // If student already has an active pending order created for this session and chooses retry
  if (activePendingOrder && paymentMethod === 'Online Payment') {
    launchRazorpayCheckout(activePendingOrder);
    return;
  }

  submitBtn.setAttribute('disabled', 'disabled');
  submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Initializing Order...';

  fetch('api/create-payment-order.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (!data.success) {
      errorBanner.textContent = data.error || 'Failed to initialize order';
      errorBanner.style.display = 'block';
      submitBtn.removeAttribute('disabled');
      updatePayButtonLabel();
      return;
    }

    // Direct redirect for Wallet & Cash on delivery
    if (paymentMethod === 'Wallet' || paymentMethod === 'Cash On Delivery') {
      Biteora.clearCart();
      window.location.href = data.redirect;
      return;
    }

    // Scan & Pay QR Flow
    if (paymentMethod === 'Scan & Pay QR') {
      const qrModal = document.getElementById('payment-qr-modal');
      const qrImg = document.getElementById('payment-qr-img');
      const qrAmt = document.getElementById('payment-qr-amount');
      const qrDevUrl = document.getElementById('payment-qr-dev-url');
      const qrDirectLink = document.getElementById('payment-qr-direct-link');

      qrImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=${encodeURIComponent(data.payment_qr_url)}`;
      qrAmt.textContent = `₹${data.amount_formatted}`;
      if (qrDevUrl) qrDevUrl.textContent = data.payment_qr_url;
      qrDirectLink.href = data.payment_qr_url;
      qrModal.classList.add('active');

      submitBtn.removeAttribute('disabled');
      updatePayButtonLabel();

      // Poll order status until paid on mobile
      const pollInterval = setInterval(() => {
        fetch(`api/order-status.php?id=${data.order_id}`)
          .then(r => r.json())
          .then(statusData => {
            if (statusData && statusData.payment_status === 'Paid') {
              clearInterval(pollInterval);
              Biteora.clearCart();
              window.location.href = `order-confirmation.php?id=${data.order_id}`;
            }
          })
          .catch(() => {});
      }, 2500);
      return;
    }

    // Online Payment via Official Razorpay Standard Checkout SDK
    activePendingOrder = data;
    launchRazorpayCheckout(data);
  })
  .catch(err => {
    errorBanner.textContent = 'Network or server error while placing order.';
    errorBanner.style.display = 'block';
    submitBtn.removeAttribute('disabled');
    updatePayButtonLabel();
  });
}

let activePendingOrder = null;

function resetPendingOrder(e) {
  if (e) e.preventDefault();
  activePendingOrder = null;
  const errorBanner = document.getElementById('checkout-error-banner');
  errorBanner.style.display = 'none';
  updatePayButtonLabel();
}

function launchRazorpayCheckout(data) {
  const submitBtn = document.getElementById('cb-checkout-submit-btn');
  const errorBanner = document.getElementById('checkout-error-banner');

  if (typeof Razorpay === 'undefined') {
    errorBanner.textContent = 'Razorpay Checkout SDK failed to load. Please check your internet connection.';
    errorBanner.style.display = 'block';
    submitBtn.removeAttribute('disabled');
    updatePayButtonLabel();
    return;
  }

  submitBtn.setAttribute('disabled', 'disabled');
  submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Opening Razorpay Checkout...';

  const rzpOptions = {
    key: data.razorpay_key_id,
    amount: data.amount,
    currency: data.currency || "INR",
    name: "Biteora",
    description: "Order #" + data.order_code,
    order_id: data.razorpay_order_id,
    prefill: {
      name: data.student.name,
      email: data.student.email,
      contact: data.student.contact
    },
    theme: {
      color: "#FF5A36"
    },
    handler: function (response) {
      submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verifying HMAC Signature...';
      
      const verifyData = new FormData();
      verifyData.append('order_id', data.order_id);
      verifyData.append('razorpay_payment_id', response.razorpay_payment_id);
      verifyData.append('razorpay_order_id', response.razorpay_order_id);
      verifyData.append('razorpay_signature', response.razorpay_signature);

      fetch('api/verify-payment.php', {
        method: 'POST',
        body: verifyData
      })
      .then(r => r.json())
      .then(vData => {
        if (vData.success) {
          activePendingOrder = null;
          Biteora.clearCart();
          window.location.href = vData.redirect || `order-confirmation.php?id=${data.order_id}`;
        } else {
          errorBanner.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i> ${vData.error || 'Payment verification failed.'}`;
          errorBanner.style.display = 'block';
          submitBtn.removeAttribute('disabled');
          submitBtn.innerHTML = `<i class="fa-solid fa-rotate-right"></i> RETRY PAYMENT FOR #${data.order_code} (₹${data.amount_formatted})`;
        }
      })
      .catch(err => {
        errorBanner.textContent = 'Network error while verifying payment signature.';
        errorBanner.style.display = 'block';
        submitBtn.removeAttribute('disabled');
        submitBtn.innerHTML = `<i class="fa-solid fa-rotate-right"></i> RETRY PAYMENT FOR #${data.order_code} (₹${data.amount_formatted})`;
      });
    },
    modal: {
      ondismiss: function () {
        // Record dismissal/cancellation event
        const fd = new FormData();
        fd.append('order_id', data.order_id);
        fd.append('event', 'cancel');
        fetch('api/record-payment-event.php', { method: 'POST', body: fd }).catch(() => {});

        errorBanner.innerHTML = `
          <div style="font-weight:700; margin-bottom:4px;">⚠️ Payment Incomplete / Cancelled</div>
          <div>Order #${data.order_code} is saved in your orders. You can retry paying now:</div>
          <div style="margin-top:6px;"><a href="#" onclick="resetPendingOrder(event)" style="color:var(--text-secondary); text-decoration:underline; font-size:0.78rem;">Or start a new checkout instead</a></div>
        `;
        errorBanner.style.display = 'block';
        errorBanner.style.background = '#FFFBEB';
        errorBanner.style.borderColor = '#FDE68A';
        errorBanner.style.color = '#B45309';

        submitBtn.removeAttribute('disabled');
        submitBtn.innerHTML = `<i class="fa-solid fa-rotate-right"></i> RETRY PAYMENT FOR #${data.order_code} (₹${data.amount_formatted})`;
      }
    }
  };

  try {
    const rzp = new Razorpay(rzpOptions);
    rzp.on('payment.failed', function (resp) {
      const errDesc = resp.error ? (resp.error.description || resp.error.reason) : 'Payment failed';
      const fd = new FormData();
      fd.append('order_id', data.order_id);
      fd.append('event', 'fail');
      fd.append('reason', errDesc);
      fetch('api/record-payment-event.php', { method: 'POST', body: fd }).catch(() => {});

      errorBanner.innerHTML = `
        <div style="font-weight:700; margin-bottom:4px;">❌ Payment Failed</div>
        <div>${errDesc}. Your order is saved as Failed. Please retry below.</div>
      `;
      errorBanner.style.display = 'block';
      errorBanner.style.background = '#FEF2F2';
      errorBanner.style.borderColor = '#FECACA';
      errorBanner.style.color = '#DC2626';

      submitBtn.removeAttribute('disabled');
      submitBtn.innerHTML = `<i class="fa-solid fa-rotate-right"></i> RETRY PAYMENT (₹${data.amount_formatted})`;
    });
    rzp.open();
  } catch (e) {
    errorBanner.textContent = 'Could not launch Razorpay checkout: ' + e.message;
    errorBanner.style.display = 'block';
    submitBtn.removeAttribute('disabled');
    updatePayButtonLabel();
  }
}
</script>


<?php include 'includes/footer.php'; ?>
