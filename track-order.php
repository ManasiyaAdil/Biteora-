<?php
$page_title = "Live Order Tracking";
require_once 'includes/connect.php';

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$just_placed = isset($_GET['placed']);

if (!$order_id) {
    header("Location: orders.php");
    exit;
}

// Fetch Order Information
$stmt = $con->prepare("SELECT o.*, u.name as customer_name, u.contact as customer_contact 
                       FROM orders o 
                       JOIN users u ON o.customer_id = u.id 
                       WHERE o.id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("Order not found.");
}

// Verify authorization
if (!isset($_SESSION['admin_sid'])) {
    if (!isset($_SESSION['customer_sid']) || $_SESSION['user_id'] != $order['customer_id']) {
        header("Location: login.php");
        exit;
    }
}

// Fetch Order Items
$item_stmt = $con->prepare("SELECT od.*, i.name as item_name, i.image as item_image, i.is_veg 
                            FROM order_details od 
                            JOIN items i ON od.item_id = i.id 
                            WHERE od.order_id = ?");
$item_stmt->bind_param("i", $order_id);
$item_stmt->execute();
$order_items = $item_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$is_delivery = (isset($order['delivery_method']) && $order['delivery_method'] === 'delivery');
$current_status = $order['status'];

// Status steps according to method
if ($is_delivery) {
    $status_steps = ['Placed', 'Preparing', 'Out for Delivery', 'Delivered'];
    $step_icons = [
        'Placed' => 'receipt',
        'Preparing' => 'fire-burner',
        'Out for Delivery' => 'truck-fast',
        'Delivered' => 'circle-check'
    ];
} else {
    $status_steps = ['Placed', 'Preparing', 'Ready', 'Completed'];
    $step_icons = [
        'Placed' => 'receipt',
        'Preparing' => 'fire-burner',
        'Ready' => 'bell-concierge',
        'Completed' => 'circle-check'
    ];
}

$current_step_idx = array_search($current_status, $status_steps);
if ($current_step_idx === false) $current_step_idx = 0;
$progress_pct = ($current_step_idx / (count($status_steps) - 1)) * 100;

// Dynamic ETA
$base_time = strtotime($order['placed_at'] ?: $order['date']);
if ($is_delivery) {
    if ($current_status === 'Delivered') {
        $delivery_eta = "Delivered at " . ($order['delivered_at'] ? date('h:i A', strtotime($order['delivered_at'])) : date('h:i A'));
    } else {
        $delivery_eta = date('h:i A', $base_time + (25 * 60));
    }
} else {
    if ($current_status === 'Completed') {
        $delivery_eta = "Completed";
    } elseif ($current_status === 'Ready') {
        $delivery_eta = "Ready Now";
    } else {
        $delivery_eta = date('h:i A', $base_time + (15 * 60));
    }
}

// Pickup verification URL for QR (pickup only)
$pickup_qr_url = APP_URL . "/verify-pickup.php?token=" . urlencode($order['pickup_verification_token'] ?? '');

include 'includes/header.php';
?>

<div class="container" style="padding: 2.5rem 1.5rem 5rem;">
  
  <?php if ($is_delivery): ?>
    <!-- Out for Delivery Banner -->
    <div id="cb-delivery-out-alert" style="<?php echo ($current_status === 'Out for Delivery') ? '' : 'display:none;'; ?> padding:1.35rem 1.6rem; margin-bottom:1.75rem; border-radius:18px; border:2px solid var(--brand-primary); background:#FFF0EA; display:flex; align-items:center; gap:1.15rem; box-shadow:0 8px 25px rgba(255,90,54,0.18);">
      <div style="width:52px; height:52px; border-radius:50%; background:var(--brand-primary); color:#FFFFFF; display:flex; align-items:center; justify-content:center; font-size:1.5rem; flex-shrink:0; box-shadow:var(--shadow-orange);">
        <i class="fa-solid fa-truck-fast"></i>
      </div>
      <div>
        <h3 style="font-family:var(--font-display); font-weight:900; font-size:1.25rem; color:var(--text-primary); margin-bottom:0.25rem;">
          🚚 Your BITEORA order has left the cafeteria and is on the way!
        </h3>
        <p style="font-size:0.92rem; color:var(--text-secondary); margin:0;">
          Campus courier is heading to <strong><?php echo htmlspecialchars($order['delivery_building'] ?: 'Campus Building'); ?>, Floor <?php echo htmlspecialchars($order['delivery_floor'] ?: '3'); ?>, Room <?php echo htmlspecialchars($order['delivery_room'] ?: '304'); ?></strong>.
        </p>
      </div>
    </div>

    <!-- Delivered Celebratory Banner -->
    <div id="cb-delivered-alert" style="<?php echo ($current_status === 'Delivered') ? '' : 'display:none;'; ?> padding:1.25rem 1.5rem; margin-bottom:1.75rem; border-radius:16px; border:1.5px solid var(--success); background:#F0FDF4; display:flex; align-items:center; gap:1rem;">
      <div style="width:48px; height:48px; border-radius:50%; background:var(--success); color:#FFFFFF; display:flex; align-items:center; justify-content:center; font-size:1.35rem; flex-shrink:0; box-shadow:0 6px 16px rgba(34,160,107,0.3);">
        <i class="fa-solid fa-circle-check"></i>
      </div>
      <div>
        <h3 style="font-family:var(--font-display); font-weight:900; font-size:1.25rem; color:#166534; margin-bottom:0.15rem;">🎉 ORDER DELIVERED!</h3>
        <p style="font-size:0.9rem; color:#15803D;">Your meal has arrived. Enjoy your food!</p>
      </div>
    </div>
  <?php else: ?>
    <!-- Ready for Pickup Celebratory Banner -->
    <div id="cb-ready-alert" style="<?php echo ($current_status === 'Ready') ? '' : 'display:none;'; ?> padding:1.25rem 1.5rem; margin-bottom:1.75rem; border-radius:16px; border:1.5px solid var(--brand-primary); background:#FFF0EA; display:flex; align-items:center; gap:1rem;">
      <div style="width:48px; height:48px; border-radius:50%; background:var(--brand-primary); color:#FFFFFF; display:flex; align-items:center; justify-content:center; font-size:1.35rem; flex-shrink:0; box-shadow:var(--shadow-orange);">
        <i class="fa-solid fa-bell-concierge"></i>
      </div>
      <div>
        <h3 style="font-family:var(--font-display); font-weight:900; font-size:1.25rem; color:var(--text-primary); margin-bottom:0.15rem;">🎉 YOUR ORDER IS READY FOR PICKUP!</h3>
        <p style="font-size:0.9rem; color:var(--text-secondary);">Proceed to <strong>Counter 1</strong> and show your <strong>Token #BITP-<?php echo htmlspecialchars($order['pickup_token'] ?? $order['id']); ?></strong>.</p>
      </div>
    </div>
  <?php endif; ?>

  <!-- Prominent Live Floating Delivery Alert Container -->
  <div id="cb-floating-delivery-toast" style="display:none; position:fixed; top:20px; left:50%; transform:translateX(-50%); z-index:9999; background:linear-gradient(135deg, #FF5A36, #FF7048); color:#FFFFFF; padding:1rem 1.6rem; border-radius:16px; box-shadow:0 12px 32px rgba(255,90,54,0.4); font-weight:800; font-size:1rem; align-items:center; gap:0.75rem;">
    <i class="fa-solid fa-bell fa-shake" style="font-size:1.25rem;"></i>
    <span>🚚 Your BITEORA order has left the cafeteria and is on the way!</span>
  </div>

  <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem; margin-bottom:1.75rem;">
    <div>
      <span style="color:var(--brand-primary); font-weight:700; font-size:0.8rem; text-transform:uppercase; letter-spacing:1px;">
        <?php echo $is_delivery ? 'LIVE DELIVERY TRACKING' : 'LIVE QUEUE TRACKING'; ?>
      </span>
      <h1 style="font-family:var(--font-display); font-size:2rem; font-weight:800; color:var(--text-primary);">
        Order <span class="text-orange">#<?php echo htmlspecialchars($order['order_code'] ?: 'CB-'.$order['id']); ?></span>
      </h1>
      <p style="color:var(--text-secondary); font-size:0.85rem;">
        Placed on <?php echo date('d M Y, h:i A', strtotime($order['date'])); ?> • 
        <?php echo $is_delivery ? '🚚 Campus Classroom Delivery' : '🍽️ Cafeteria Pickup'; ?>
      </p>
    </div>

    <div style="display:flex; align-items:center; gap:0.65rem;">
      <span id="cb-status-badge" class="badge badge-<?php echo ($current_status === 'Completed' || $current_status === 'Delivered' ? 'success' : ($current_status === 'Ready' || $current_status === 'Out for Delivery' ? 'brand' : ($current_status === 'Preparing' ? 'warning' : 'brand'))); ?>" style="font-size:0.85rem; padding:0.4rem 0.95rem;">
        <?php echo htmlspecialchars($current_status); ?>
      </span>
      <a href="receipt.php?id=<?php echo $order['id']; ?>" class="btn btn-secondary btn-sm" title="Receipt">
        <i class="fa-solid fa-receipt"></i> Official Receipt
      </a>
    </div>
  </div>

  <!-- 2-Minute Demo Live Progression & Countdown Indicator -->
  <div id="demo-countdown-widget" class="card" style="padding:1.15rem 1.5rem; margin-bottom:1.5rem; background:#FFFFFF; border:1.5px solid var(--border-subtle); border-radius:18px; box-shadow:var(--shadow-sm);">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:0.75rem; margin-bottom:0.75rem;">
      <div style="display:flex; align-items:center; gap:0.6rem;">
        <span style="display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px; border-radius:8px; background:#FFF0EA; color:var(--brand-primary); font-size:0.95rem;">
          <i class="fa-solid fa-stopwatch"></i>
        </span>
        <div>
          <div style="font-weight:800; font-size:0.92rem; color:var(--text-primary); display:flex; align-items:center; gap:0.4rem;">
            <span>2-Minute Fast-Track Delivery Demo</span>
            <span class="badge badge-brand" style="font-size:0.68rem; padding:0.15rem 0.5rem;">Live Hackathon Mode</span>
          </div>
          <div style="font-size:0.76rem; color:var(--text-secondary);" id="demo-sub-label">
            Automatic real database status progression & verification
          </div>
        </div>
      </div>
      <div style="display:flex; align-items:center; gap:0.5rem; font-family:var(--font-display); font-weight:900; font-size:1.15rem; color:var(--brand-primary);" id="demo-timer-box">
        <i class="fa-regular fa-clock"></i> <span id="demo-timer-display">Calculating...</span>
      </div>
    </div>
    
    <!-- Animated Progress Bar for 2-Minute Demo Flow -->
    <div style="width:100%; height:8px; background:var(--bg-surface-2); border-radius:999px; overflow:hidden; position:relative;">
      <div id="demo-timer-bar" style="width:0%; height:100%; background:linear-gradient(90deg, #FF5A36, #22A06B); border-radius:999px; transition:width 0.8s ease;"></div>
    </div>
    
    <div style="display:flex; justify-content:space-between; font-size:0.73rem; color:var(--text-muted); margin-top:0.45rem; font-weight:600;">
      <span>0s Placed</span>
      <span>30s Kitchen Prep</span>
      <span>60s <?php echo $is_delivery ? 'Out for Delivery' : 'Ready for Pickup'; ?></span>
      <span>90s <?php echo $is_delivery ? 'Delivered' : 'Completed'; ?></span>
    </div>
  </div>

  <!-- Live Progress Stepper Card -->
  <div class="card" style="padding:1.75rem; margin-bottom:2rem; background:#FFFFFF; border:1px solid var(--border-subtle);">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem;">
      <h3 style="font-family:var(--font-display); font-weight:700; font-size:1.1rem; color:var(--text-primary);">
        <i class="fa-solid fa-tower-broadcast text-orange" style="margin-right:0.4rem;"></i> 
        <?php echo $is_delivery ? 'Live Delivery Progress' : 'Kitchen Live Progress'; ?>
      </h3>
      <div id="cb-live-indicator" style="display:flex; align-items:center; gap:0.4rem; font-size:0.78rem; color:var(--text-secondary);">
        <span id="cb-pulse-dot" style="width:8px; height:8px; border-radius:50%; background:var(--success); display:inline-block; animation:pulse 1.5s infinite;"></span>
        <span id="cb-live-text">Live Connected</span>
      </div>
    </div>

    <!-- Stepper Bar -->
    <div style="position:relative; margin:2.5rem 1rem 1.5rem;">
      <!-- Background track -->
      <div style="position:absolute; top:50%; transform:translateY(-50%); left:0; width:100%; height:4px; background:var(--bg-surface-2); z-index:1; border-radius:2px;"></div>
      <!-- Active track -->
      <div id="cb-progress-bar" style="position:absolute; top:50%; transform:translateY(-50%); left:0; width:<?php echo $progress_pct; ?>%; height:4px; background:var(--brand-primary); z-index:2; border-radius:2px; transition:width 0.4s ease;"></div>

      <!-- Step Nodes -->
      <div style="position:relative; z-index:3; display:flex; justify-content:space-between;">
        <?php 
        foreach ($status_steps as $idx => $step_name): 
            $is_passed = ($idx <= $current_step_idx);
            $is_current = ($idx === $current_step_idx);
            $is_finished = ($current_status === 'Completed' || $current_status === 'Delivered' || ($idx === count($status_steps) - 1 && $is_passed));
            
            $bg_color = 'var(--bg-surface-2)';
            $border_color = 'var(--border-subtle)';
            $icon_color = 'var(--text-muted)';
            
            if ($is_finished) {
                $bg_color = 'var(--success)';
                $border_color = 'var(--success)';
                $icon_color = '#FFFFFF';
            } elseif ($is_passed) {
                $bg_color = 'var(--brand-primary)';
                $border_color = 'var(--brand-primary)';
                $icon_color = '#FFFFFF';
            }
        ?>
          <div class="step-node" id="step-node-<?php echo str_replace(' ', '-', $step_name); ?>" style="display:flex; flex-direction:column; align-items:center; text-align:center; flex:1;">
            <div class="step-circle" style="width:42px; height:42px; border-radius:50%; background:<?php echo $bg_color; ?>; border:2.5px solid <?php echo $border_color; ?>; color:<?php echo $icon_color; ?>; display:flex; align-items:center; justify-content:center; font-size:1rem; transition:0.3s ease; box-shadow:<?php echo $is_current ? 'var(--shadow-orange)' : 'none'; ?>; margin:0 auto;">
              <i class="fa-solid fa-<?php echo $step_icons[$step_name]; ?>"></i>
            </div>
            <div class="step-label" style="margin-top:0.5rem; font-weight:700; font-size:0.84rem; color:<?php echo $is_passed ? 'var(--text-primary)' : 'var(--text-muted)'; ?>;">
              <?php echo $step_name; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <?php if ($is_delivery): ?>
    <!-- Delivery Destination & ETA Card -->
    <div class="card" style="padding:1.75rem; background:#FFFFFF; margin-bottom:2rem; border:1px solid var(--border-subtle);">
      <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem; margin-bottom:1.25rem; border-bottom:1px solid var(--border-subtle); padding-bottom:0.85rem;">
        <div style="display:flex; align-items:center; gap:0.5rem;">
          <span style="font-size:1.3rem;">🏫</span>
          <h3 style="font-family:var(--font-display); font-weight:800; font-size:1.15rem; color:var(--text-primary);">Delivering To</h3>
        </div>
        <div style="text-align:right;">
          <span style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:700;">Estimated Arrival</span>
          <div id="delivery-eta-val" style="font-family:var(--font-display); font-weight:900; font-size:1.25rem; color:var(--brand-primary);">
            <?php echo $delivery_eta; ?>
          </div>
        </div>
      </div>

      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1.25rem;">
        <div>
          <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:700;">Building / Block</div>
          <div style="font-weight:700; font-size:1rem; color:var(--text-primary); margin-top:3px;">
            <?php echo htmlspecialchars($order['delivery_building'] ?: 'Main College Building'); ?>
          </div>
        </div>

        <div>
          <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:700;">Floor</div>
          <div style="font-weight:700; font-size:1rem; color:var(--text-primary); margin-top:3px;">
            Floor <?php echo htmlspecialchars($order['delivery_floor'] ?: '3'); ?>
          </div>
        </div>

        <div>
          <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:700;">Room / Classroom</div>
          <div style="font-weight:800; font-size:1.1rem; color:var(--brand-primary); margin-top:3px;">
            Room <?php echo htmlspecialchars($order['delivery_room'] ?: '304'); ?>
          </div>
        </div>

        <div>
          <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:700;">Student Contact</div>
          <div style="font-weight:600; font-size:0.95rem; color:var(--text-secondary); margin-top:3px;">
            <?php echo htmlspecialchars($order['customer_name']); ?> (<?php echo htmlspecialchars($order['customer_contact']); ?>)
          </div>
        </div>
      </div>

      <?php if (!empty($order['delivery_note'])): ?>
        <div style="margin-top:1.25rem; padding:0.85rem 1rem; background:var(--bg-surface-2); border-radius:12px; font-size:0.88rem; color:var(--text-secondary); border-left:4px solid var(--brand-primary);">
          <strong style="color:var(--text-primary);">Delivery Instructions:</strong> "<?php echo htmlspecialchars($order['delivery_note']); ?>"
        </div>
      <?php endif; ?>
    </div>

  <?php else: ?>
    <!-- Details Grid: Pickup Token & Scannable QR (Pickup only) -->
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.75rem; margin-bottom:2rem;">
      
      <!-- Token Card -->
      <div class="card" style="padding:1.75rem; background:#FFFFFF; text-align:center;">
        <div style="font-size:0.75rem; color:var(--brand-primary); text-transform:uppercase; font-weight:800; letter-spacing:0.5px; margin-bottom:0.25rem;">YOUR PICKUP TOKEN</div>
        <div style="font-family:var(--font-display); font-size:3.5rem; font-weight:900; color:var(--brand-primary); line-height:1.1; margin:0.25rem 0 0.5rem;">
          #BITP-<?php echo htmlspecialchars($order['pickup_token'] ?? $order['id']); ?>
        </div>
        <p style="font-size:0.85rem; color:var(--text-secondary); margin-bottom:1rem;">Callout screen will display this token at Counter 1</p>
        
        <div style="background:var(--bg-surface-2); padding:0.85rem; border-radius:12px; font-size:0.88rem; color:var(--text-secondary); text-align:left;">
          <div><strong>Counter:</strong> <?php echo htmlspecialchars($order['address']); ?></div>
          <?php if (!empty($order['description'])): ?>
            <div style="margin-top:0.3rem;"><strong>Notes:</strong> <?php echo htmlspecialchars($order['description']); ?></div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Scannable Staff Pickup QR -->
      <div class="card" style="padding:1.75rem; background:#FFFFFF; text-align:center;">
        <div style="font-size:0.75rem; color:var(--brand-primary); text-transform:uppercase; font-weight:800; letter-spacing:0.5px; margin-bottom:0.25rem;">STAFF PICKUP QR</div>
        <p style="font-size:0.85rem; color:var(--text-secondary); margin-bottom:0.85rem;">Show this QR code to the cafeteria counter staff</p>
        
        <div style="background:#FFFFFF; padding:0.65rem; border-radius:12px; display:inline-block; box-shadow:var(--shadow-sm); border:1px solid var(--border-subtle);">
          <img src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=<?php echo urlencode($pickup_qr_url); ?>" alt="Staff Pickup QR" style="width:125px; height:125px;">
        </div>
      </div>

    </div>
  <?php endif; ?>

  <!-- Ordered Items Table -->
  <div class="card" style="padding:1.75rem; background:#FFFFFF;">
    <h3 style="font-family:var(--font-display); font-size:1.15rem; font-weight:700; color:var(--text-primary); margin-bottom:1rem;">Ordered Food Items</h3>

    <div style="display:flex; flex-direction:column; gap:0.75rem;">
      <?php foreach ($order_items as $item): ?>
        <div style="display:flex; align-items:center; justify-content:space-between; padding-bottom:0.75rem; border-bottom:1px solid var(--border-subtle); font-size:0.92rem;">
          <div style="display:flex; align-items:center; gap:0.75rem;">
            <span class="<?php echo $item['is_veg'] ? 'veg-icon' : 'nonveg-icon'; ?>"></span>
            <div>
              <div style="font-weight:700; color:var(--text-primary);"><?php echo htmlspecialchars($item['item_name']); ?></div>
              <div style="font-size:0.8rem; color:var(--text-secondary);">Qty: <?php echo $item['quantity']; ?> × ₹<?php echo number_format($item['price'] / $item['quantity'], 2); ?></div>
            </div>
          </div>
          <div style="font-weight:800; font-family:var(--font-display); color:var(--brand-primary);">₹<?php echo number_format($item['price'], 2); ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <div style="margin-top:1.25rem; padding-top:1rem; border-top:1px solid var(--border-subtle); font-size:0.9rem;">
      <div style="display:flex; justify-content:space-between; color:var(--text-secondary); margin-bottom:0.4rem;">
        <span>Items Subtotal</span>
        <span>₹<?php echo number_format($order['total'] - floatval($order['delivery_fee']), 2); ?></span>
      </div>
      <?php if ($is_delivery): ?>
        <div style="display:flex; justify-content:space-between; color:var(--brand-primary); margin-bottom:0.4rem; font-weight:600;">
          <span>Campus Delivery Fee</span>
          <span>₹<?php echo number_format($order['delivery_fee'], 2); ?></span>
        </div>
      <?php endif; ?>
      <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px dashed var(--border-subtle); padding-top:0.6rem; margin-top:0.6rem;">
        <span style="font-weight:800; font-size:1.1rem; color:var(--text-primary);">Total Paid</span>
        <span style="font-family:var(--font-display); font-weight:900; font-size:1.5rem; color:var(--brand-primary);">₹<?php echo number_format($order['total'], 2); ?></span>
      </div>
    </div>
  </div>

</div>

<script>
const isDelivery = <?php echo json_encode($is_delivery); ?>;
const orderId = <?php echo json_encode($order_id); ?>;
const statusSteps = <?php echo json_encode($status_steps); ?>;
let lastKnownStatus = <?php echo json_encode($current_status); ?>;

function formatDemoSeconds(sec) {
  if (sec <= 0) return '00:00';
  const m = Math.floor(sec / 60);
  const s = sec % 60;
  return `${m < 10 ? '0' : ''}${m}:${s < 10 ? '0' : ''}${s} remaining`;
}

function showFloatingDeliveryNotification(msg) {
  const toast = document.getElementById('cb-floating-delivery-toast');
  if (toast) {
    toast.style.display = 'flex';
    toast.style.animation = 'pulse 1.5s infinite';
    setTimeout(() => {
      if (toast) toast.style.display = 'none';
    }, 7000);
  }
}

// Live AJAX Polling every 2.5 seconds
function pollOrderStatus() {
  fetch(`api/order-status.php?id=${orderId}`)
    .then(r => {
      if (!r.ok) throw new Error('HTTP ' + r.status);
      return r.json();
    })
    .then(data => {
      if (data.success && data.status) {
        // Check for Out for Delivery transition to trigger notification
        if (data.status === 'Out for Delivery' && lastKnownStatus !== 'Out for Delivery') {
          showFloatingDeliveryNotification("🚚 Your BITEORA order has left the cafeteria and is on the way!");
          document.title = "🚚 On The Way! | Biteora";
        }
        lastKnownStatus = data.status;

        // Show Live Connected indicator
        const liveDot = document.getElementById('cb-pulse-dot');
        const liveText = document.getElementById('cb-live-text');
        if (liveDot) {
          liveDot.style.background = 'var(--success)';
          liveDot.style.animation = 'pulse 1.5s infinite';
        }
        if (liveText) liveText.textContent = 'Live Connected';

        // Update 2-Minute Demo Live Countdown & Progress Fill
        const timerDisplay = document.getElementById('demo-timer-display');
        const timerBar = document.getElementById('demo-timer-bar');
        const subLabel = document.getElementById('demo-sub-label');
        
        if (data.status === 'Delivered') {
          if (timerDisplay) {
            timerDisplay.innerHTML = '<span style="color:var(--success);">✅ Order Delivered!</span>';
          }
          if (timerBar) {
            timerBar.style.width = '100%';
            timerBar.style.background = 'var(--success)';
          }
          if (subLabel) subLabel.textContent = 'Meal successfully arrived at destination.';
        } else if (data.status === 'Completed') {
          if (timerDisplay) {
            timerDisplay.innerHTML = '<span style="color:var(--success);">✅ Picked Up!</span>';
          }
          if (timerBar) {
            timerBar.style.width = '100%';
            timerBar.style.background = 'var(--success)';
          }
          if (subLabel) subLabel.textContent = 'Order verified and collected at Counter 1.';
        } else {
          const secLeft = typeof data.demo_seconds_remaining !== 'undefined' ? data.demo_seconds_remaining : 0;
          const pct = typeof data.demo_progress_percent !== 'undefined' ? data.demo_progress_percent : 25;
          if (timerDisplay) timerDisplay.textContent = formatDemoSeconds(secLeft);
          if (timerBar) timerBar.style.width = Math.max(8, pct) + '%';
        }

        // Update status badge
        const badge = document.getElementById('cb-status-badge');
        if (badge) {
          badge.textContent = data.status;
          if (data.status === 'Delivered' || data.status === 'Completed') {
            badge.className = 'badge badge-success';
          } else if (data.status === 'Out for Delivery' || data.status === 'Ready') {
            badge.className = 'badge badge-brand';
          } else if (data.status === 'Preparing') {
            badge.className = 'badge badge-warning';
          }
        }

        // Update ETA if present
        const etaEl = document.getElementById('delivery-eta-val');
        if (etaEl && data.estimated_delivery) {
          etaEl.textContent = data.estimated_delivery;
        }

        // Update banners
        const outAlert = document.getElementById('cb-delivery-out-alert');
        const deliveredAlert = document.getElementById('cb-delivered-alert');
        const readyAlert = document.getElementById('cb-ready-alert');

        if (outAlert) outAlert.style.display = (data.status === 'Out for Delivery') ? 'flex' : 'none';
        if (deliveredAlert) deliveredAlert.style.display = (data.status === 'Delivered') ? 'flex' : 'none';
        if (readyAlert) readyAlert.style.display = (data.status === 'Ready') ? 'flex' : 'none';

        // Update stepper progress
        const stepIdx = statusSteps.indexOf(data.status);
        if (stepIdx !== -1) {
          const pct = (stepIdx / (statusSteps.length - 1)) * 100;
          const bar = document.getElementById('cb-progress-bar');
          if (bar) bar.style.width = pct + '%';

          // Update each node styling
          statusSteps.forEach((sName, sIdx) => {
            const nodeId = 'step-node-' + sName.replace(/\s+/g, '-');
            const nodeEl = document.getElementById(nodeId);
            if (nodeEl) {
              const circle = nodeEl.querySelector('.step-circle');
              const label = nodeEl.querySelector('.step-label');
              const isPassed = (sIdx <= stepIdx);
              const isCurrent = (sIdx === stepIdx);
              const isDone = (data.status === 'Delivered' || data.status === 'Completed' || (sIdx === statusSteps.length - 1 && isPassed));

              if (circle) {
                if (isDone) {
                  circle.style.background = 'var(--success)';
                  circle.style.borderColor = 'var(--success)';
                  circle.style.color = '#FFFFFF';
                  circle.style.boxShadow = 'none';
                } else if (isPassed) {
                  circle.style.background = 'var(--brand-primary)';
                  circle.style.borderColor = 'var(--brand-primary)';
                  circle.style.color = '#FFFFFF';
                  circle.style.boxShadow = isCurrent ? 'var(--shadow-orange)' : 'none';
                } else {
                  circle.style.background = 'var(--bg-surface-2)';
                  circle.style.borderColor = 'var(--border-subtle)';
                  circle.style.color = 'var(--text-muted)';
                  circle.style.boxShadow = 'none';
                }
              }

              if (label) {
                label.style.color = isPassed ? 'var(--text-primary)' : 'var(--text-muted)';
              }
            }
          });
        }
      }
    })
    .catch(err => {
      const liveDot = document.getElementById('cb-pulse-dot');
      const liveText = document.getElementById('cb-live-text');
      if (liveDot) {
        liveDot.style.background = 'var(--warning)';
        liveDot.style.animation = 'none';
      }
      if (liveText) liveText.textContent = 'Reconnecting...';
    });
}

// Start live poller
setInterval(pollOrderStatus, 2500);
pollOrderStatus();
</script>

<?php include 'includes/footer.php'; ?>
