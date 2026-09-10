<?php
$page_title = "Complete Mobile Payment";
require_once 'includes/connect.php';

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);

if (!$order_id) {
    die("Invalid mobile payment request. Missing order identifier.");
}

// 1. Fetch Order securely from MySQL (Server-side price & status authority)
$stmt = $con->prepare("SELECT o.*, u.name as customer_name, u.email as customer_email, u.contact as customer_contact 
                       FROM orders o 
                       JOIN users u ON o.customer_id = u.id 
                       WHERE o.id = ? AND o.deleted = 0");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("Order not found or has been removed.");
}

// If already paid, redirect straight to confirmation
if ($order['payment_status'] === 'Paid') {
    header("Location: order-confirmation.php?id=" . $order_id);
    exit;
}

// Fetch ordered item details
$item_stmt = $con->prepare("SELECT od.*, i.name as item_name, i.is_veg 
                            FROM order_details od 
                            JOIN items i ON od.item_id = i.id 
                            WHERE od.order_id = ?");
$item_stmt->bind_param("i", $order_id);
$item_stmt->execute();
$items = $item_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Server-enforced amount in paise
$amount_paise = intval(round(floatval($order['total']) * 100));
$amount_formatted = number_format($order['total'], 2);
$rzp_order_id = $order['razorpay_order_id'] ?? ('order_cb_' . $order_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Pay Order #<?php echo htmlspecialchars($order['order_code'] ?: 'CB-'.$order['id']); ?> | CampusBite</title>
  
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23FF5A36'><path d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9v-2h2v2zm0-4H9V7h2v5z'/></svg>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/campusbite.css">
  
  <!-- Razorpay Standard Checkout SDK -->
  <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body style="background:#0B0B0D; min-height:100vh; padding:1.5rem 1rem; display:flex; flex-direction:column; align-items:center; justify-content:center;">

  <div class="card" style="width:100%; max-width:440px; padding:2rem 1.5rem; background:#151518; border:1.5px solid #29292E; border-radius:20px; box-shadow:0 16px 40px rgba(0,0,0,0.6); text-align:center;">
    
    <!-- Brand Header -->
    <div style="display:flex; align-items:center; justify-content:center; gap:0.6rem; margin-bottom:1.25rem;">
      <div class="cb-logo-icon" style="width:36px; height:36px; font-size:1.1rem; background:linear-gradient(135deg, #FF5A36, #FF7A45);">
        <i class="fa-solid fa-utensils"></i>
      </div>
      <span style="font-family:var(--font-display); font-size:1.4rem; font-weight:800; color:#FFFFFF;">Campus<span class="text-orange">Bite</span></span>
    </div>

    <div style="font-size:0.78rem; font-weight:800; color:var(--brand-primary); text-transform:uppercase; letter-spacing:1px; margin-bottom:0.25rem;">
      Mobile Payment Gateway
    </div>

    <h2 style="font-family:var(--font-display); font-size:1.6rem; font-weight:800; color:#FFFFFF; margin-bottom:1.25rem;">
      Order #<?php echo htmlspecialchars($order['order_code'] ?: 'CB-'.$order['id']); ?>
    </h2>

    <!-- Price Card -->
    <div style="background:var(--bg-surface-2); border:1px solid var(--border-subtle); border-radius:14px; padding:1.25rem; margin-bottom:1.25rem;">
      <div style="font-size:0.8rem; color:var(--text-secondary); text-transform:uppercase; font-weight:700; margin-bottom:0.25rem;">
        Total Amount Payable
      </div>
      <div style="font-family:var(--font-display); font-size:2.2rem; font-weight:900; color:var(--brand-primary); letter-spacing:-0.5px;">
        ₹<?php echo $amount_formatted; ?>
      </div>
      <div style="display:flex; align-items:center; justify-content:center; gap:0.5rem; margin-top:0.4rem; font-size:0.8rem; color:var(--text-secondary);">
        <span>Status:</span>
        <span class="badge badge-amber"><i class="fa-solid fa-clock"></i> Pending</span>
      </div>
    </div>

    <!-- Order Items Accordion/Preview -->
    <div style="background:var(--bg-surface-2); border:1px solid var(--border-subtle); border-radius:12px; padding:1rem; margin-bottom:1.5rem; text-align:left;">
      <div style="font-size:0.75rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; margin-bottom:0.6rem;">Items Ordered</div>
      <div style="display:flex; flex-direction:column; gap:0.5rem;">
        <?php foreach ($items as $item): ?>
          <div style="display:flex; justify-content:space-between; font-size:0.88rem; color:#FFFFFF;">
            <span><?php echo htmlspecialchars($item['item_name']); ?> <span style="color:var(--text-muted);">× <?php echo $item['quantity']; ?></span></span>
            <span style="font-weight:700;">₹<?php echo number_format($item['price'], 2); ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div id="payment-error-box" style="display:none; color:var(--danger); font-size:0.85rem; margin-bottom:1rem; background:var(--danger-bg); padding:0.6rem; border-radius:8px; border:1px solid rgba(239,68,68,0.3);"></div>

    <!-- PAY NOW Large Touch Button -->
    <button type="button" id="pay-now-btn" onclick="startMobilePayment();" class="btn btn-primary btn-lg" style="width:100%; font-size:1.15rem; font-weight:800; padding:1rem; border-radius:12px; box-shadow:var(--shadow-orange); margin-bottom:1rem;">
      <i class="fa-solid fa-lock"></i> PAY ₹<?php echo $amount_formatted; ?>
    </button>

    <div style="font-size:0.75rem; color:var(--text-muted); display:flex; align-items:center; justify-content:center; gap:0.4rem;">
      <i class="fa-solid fa-shield-halved text-emerald"></i> Official Razorpay Standard Checkout & 256-bit Encryption
    </div>

  </div>

  <script>
  function startMobilePayment() {
    const btn = document.getElementById('pay-now-btn');
    const errBox = document.getElementById('payment-error-box');
    errBox.style.display = 'none';
    btn.setAttribute('disabled', 'disabled');
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Initializing Payment...';

    if (typeof Razorpay === 'undefined') {
      errBox.textContent = 'Razorpay Checkout SDK failed to load. Please check your connection.';
      errBox.style.display = 'block';
      btn.removeAttribute('disabled');
      btn.innerHTML = '<i class="fa-solid fa-lock"></i> PAY ₹<?php echo $amount_formatted; ?>';
      return;
    }

    const rzpOptions = {
      key: "<?php echo RAZORPAY_KEY_ID; ?>",
      amount: <?php echo $amount_paise; ?>,
      currency: "INR",
      name: "CampusBite",
      description: "Order #<?php echo htmlspecialchars($order['order_code'] ?: 'CB-'.$order['id']); ?>",
      order_id: "<?php echo $rzp_order_id; ?>",
      prefill: {
        name: "<?php echo addslashes($order['customer_name'] ?? 'Student'); ?>",
        email: "<?php echo addslashes($order['customer_email'] ?? 'student@campusbite.edu'); ?>",
        contact: "<?php echo addslashes($order['customer_contact'] ?? '9876543210'); ?>"
      },
      theme: {
        color: "#FF5A36"
      },
      handler: function (response) {
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verifying Signature...';
        
        const fd = new FormData();
        fd.append('order_id', "<?php echo $order_id; ?>");
        fd.append('razorpay_payment_id', response.razorpay_payment_id);
        fd.append('razorpay_order_id', response.razorpay_order_id);
        fd.append('razorpay_signature', response.razorpay_signature);

        fetch('api/verify-payment.php', {
          method: 'POST',
          body: fd
        })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            window.location.href = data.redirect || "order-confirmation.php?id=<?php echo $order_id; ?>";
          } else {
            errBox.textContent = data.error || 'Payment signature verification failed';
            errBox.style.display = 'block';
            btn.removeAttribute('disabled');
            btn.innerHTML = '<i class="fa-solid fa-lock"></i> Retry Payment';
          }
        })
        .catch(e => {
          errBox.textContent = 'Network error verifying payment signature';
          errBox.style.display = 'block';
          btn.removeAttribute('disabled');
          btn.innerHTML = '<i class="fa-solid fa-lock"></i> Retry Payment';
        });
      },
      modal: {
        ondismiss: function () {
          btn.removeAttribute('disabled');
          btn.innerHTML = '<i class="fa-solid fa-lock"></i> PAY ₹<?php echo $amount_formatted; ?>';
          errBox.textContent = 'Payment cancelled. Your order remains pending.';
          errBox.style.display = 'block';
        }
      }
    };

    try {
      const rzp = new Razorpay(rzpOptions);
      rzp.on('payment.failed', function (resp) {
        btn.removeAttribute('disabled');
        btn.innerHTML = '<i class="fa-solid fa-lock"></i> PAY ₹<?php echo $amount_formatted; ?>';
        errBox.textContent = resp.error.description || 'Payment failed';
        errBox.style.display = 'block';
      });
      rzp.open();
    } catch (e) {
      btn.removeAttribute('disabled');
      btn.innerHTML = '<i class="fa-solid fa-lock"></i> PAY ₹<?php echo $amount_formatted; ?>';
      errBox.textContent = 'Unable to launch checkout: ' + e.message;
      errBox.style.display = 'block';
    }
  }
  </script>

</body>
</html>
