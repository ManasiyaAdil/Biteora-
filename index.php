<?php
$page_title = "Your Campus. Your Food. Your Way.";
require_once 'includes/connect.php';

// If student is logged in, check for active order
$active_order = null;
if (isset($_SESSION['customer_sid']) && isset($_SESSION['user_id'])) {
    $uid = intval($_SESSION['user_id']);
    $stmt = $con->prepare("SELECT id, order_code, pickup_token, status, delivery_method, delivery_building, delivery_room, total, date FROM orders WHERE customer_id = ? AND status IN ('Placed', 'Preparing', 'Ready', 'Out for Delivery') AND deleted = 0 ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $active_order = $stmt->get_result()->fetch_assoc();
}

// Fetch Popular Items from database
$popular_items = [];
$res = $con->query("SELECT i.*, c.name as category_name FROM items i LEFT JOIN categories c ON i.category_id = c.id WHERE i.deleted = 0 AND i.is_available = 1 ORDER BY i.rating DESC LIMIT 6");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $popular_items[] = $r;
    }
}

// Fetch Categories
$categories = [];
$c_res = $con->query("SELECT c.*, COUNT(i.id) as item_count FROM categories c LEFT JOIN items i ON c.id = i.category_id AND i.deleted = 0 GROUP BY c.id ORDER BY c.id ASC");
if ($c_res) {
    while ($r = $c_res->fetch_assoc()) {
        $categories[] = $r;
    }
}

include 'includes/header.php';
?>

<main>
  <!-- Active Order / Out for Delivery Notification Banner for Logged-In Student -->
  <?php if ($active_order): ?>
    <?php if ($active_order['status'] === 'Out for Delivery'): ?>
      <section style="background: linear-gradient(135deg, #FF5A36, #FF7048); color:#FFFFFF; padding: 1rem 0; box-shadow:0 6px 20px rgba(255,90,54,0.3);">
        <div class="container" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem;">
          <div style="display:flex; align-items:center; gap:0.9rem;">
            <div style="width:44px; height:44px; border-radius:12px; background:rgba(255,255,255,0.2); display:flex; align-items:center; justify-content:center; font-size:1.35rem;">
              <i class="fa-solid fa-truck-fast fa-bounce"></i>
            </div>
            <div>
              <div style="font-weight:900; font-size:1.05rem; letter-spacing:0.3px;">
                🚚 Your BITEORA order has left the cafeteria and is on the way!
              </div>
              <div style="font-size:0.85rem; opacity:0.95;">
                Order #<?php echo htmlspecialchars($active_order['order_code'] ?? 'BO-'.$active_order['id']); ?> is heading to <?php echo htmlspecialchars($active_order['delivery_room'] ? 'Room ' . $active_order['delivery_room'] : 'Campus'); ?>.
              </div>
            </div>
          </div>
          <a href="track-order.php?id=<?php echo $active_order['id']; ?>" class="btn" style="background:#FFFFFF; color:var(--brand-primary); font-weight:800; border-radius:10px; padding:0.55rem 1.25rem;">
            <i class="fa-solid fa-location-arrow"></i> Track Live Location
          </a>
        </div>
      </section>
    <?php else: ?>
      <section style="background: #FFF0EA; border-bottom: 1px solid rgba(255, 90, 54, 0.25); padding: 0.85rem 0;">
        <div class="container" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem;">
          <div style="display:flex; align-items:center; gap:0.85rem;">
            <div style="width:38px; height:38px; border-radius:8px; background:var(--brand-primary); color:#FFFFFF; display:flex; align-items:center; justify-content:center; font-size:1rem; box-shadow:var(--shadow-orange);">
              <i class="fa-solid fa-bell-concierge"></i>
            </div>
            <div>
              <div style="font-weight:700; font-size:0.95rem; color:var(--text-primary);">
                Active Order: <span class="text-orange">#<?php echo htmlspecialchars($active_order['order_code'] ?? 'CB-'.$active_order['id']); ?></span> (Token #<?php echo htmlspecialchars($active_order['pickup_token'] ?? $active_order['id']); ?>)
              </div>
              <div style="font-size:0.8rem; color:var(--text-secondary);">
                Status: <span class="badge badge-<?php echo ($active_order['status']=='Ready'?'success':($active_order['status']=='Preparing'?'warning':'brand')); ?>"><?php echo htmlspecialchars($active_order['status']); ?></span>
              </div>
            </div>
          </div>
          <a href="track-order.php?id=<?php echo $active_order['id']; ?>" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-location-crosshairs"></i> Track Live Status
          </a>
        </div>
      </section>
    <?php endif; ?>
  <?php endif; ?>

  <!-- Hero Section (Warm Cream Background) -->
  <section class="hero-section">
    <div class="container hero-grid">
      <div>
        <div class="hero-tag">
          <i class="fa-solid fa-fire"></i>
          <span>Smart Campus Food-Tech Platform</span>
        </div>
        <h1 class="hero-title">
          Your Campus.<br>
          Your Food. <span>Your Way.</span>
        </h1>
        <p class="hero-desc">
          Order from your campus cafeteria, skip the queue, and enjoy your food without the wait.
        </p>
        <div class="hero-actions">
          <a href="menu.php" class="btn btn-primary btn-lg">
            <i class="fa-solid fa-utensils"></i> ORDER NOW
          </a>
          <a href="#popular-today" class="btn btn-secondary btn-lg">
            <i class="fa-solid fa-compass"></i> EXPLORE MENU
          </a>
        </div>
        <div class="hero-stats">
          <div class="hero-stat-item">
            <h4>&lt; 8 min</h4>
            <p>Average Pickup</p>
          </div>
          <div class="hero-stat-item" style="border-left:1px solid var(--border-subtle); padding-left:1.5rem;">
            <h4>0 min</h4>
            <p>Counter Wait Time</p>
          </div>
          <div class="hero-stat-item" style="border-left:1px solid var(--border-subtle); padding-left:1.5rem;">
            <h4>4.9 ★</h4>
            <p>Campus Rating</p>
          </div>
        </div>
      </div>

      <!-- Hero Visual Card & Live Queue Preview -->
      <div style="position:relative;">
        <div class="card" style="padding:1.25rem; border-radius:24px; box-shadow:var(--shadow-lg); background:#FFFFFF;">
          <div style="position:relative; height:230px; border-radius:16px; overflow:hidden; margin-bottom:1.15rem;">
            <img src="https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=800&q=80" alt="Campus Cafeteria" style="width:100%; height:100%; object-fit:cover;">
            <div style="position:absolute; inset:0; background:linear-gradient(180deg, transparent 40%, rgba(25,25,25,0.7) 100%);"></div>
            <div style="position:absolute; bottom:12px; left:12px; display:flex; align-items:center; gap:0.5rem;">
              <span class="badge badge-success"><i class="fa-solid fa-circle" style="font-size:0.45rem;"></i> Cafeteria Open</span>
              <span class="badge badge-brand">Counter 1 & 2 Live</span>
            </div>
          </div>

          <!-- Live Token Preview -->
          <div style="background:var(--bg-surface-2); border:1px solid var(--border-subtle); border-radius:14px; padding:1rem; display:flex; align-items:center; justify-content:space-between;">
            <div style="display:flex; align-items:center; gap:0.75rem;">
              <div style="width:40px; height:40px; border-radius:10px; background:var(--brand-subtle); color:var(--brand-primary); display:flex; align-items:center; justify-content:center; font-size:1.15rem;">
                <i class="fa-solid fa-ticket"></i>
              </div>
              <div>
                <div style="font-size:0.72rem; color:var(--text-secondary); text-transform:uppercase; font-weight:700;">Live Kitchen Queue</div>
                <div style="font-family:var(--font-display); font-weight:800; font-size:1.1rem; color:var(--text-primary);">Now Serving: <span class="text-orange">#42</span></div>
              </div>
            </div>
            <a href="menu.php" class="btn btn-primary btn-sm" style="font-size:0.8rem; padding:0.45rem 0.85rem;">Order Now</a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Categories Showcase (White Section) -->
  <section id="categories" style="padding: 3.5rem 0; border-bottom: 1px solid var(--border-subtle); background: #FFFFFF;">
    <div class="container">
      <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.75rem;">
        <div>
          <span style="color:var(--brand-primary); font-weight:700; font-size:0.8rem; text-transform:uppercase; letter-spacing:1px;">Categories</span>
          <h2 style="font-family:var(--font-display); font-size:1.85rem; font-weight:800; color:var(--text-primary);">Explore Menu Categories</h2>
        </div>
        <a href="menu.php" class="btn btn-secondary btn-sm">View All Menu <i class="fa-solid fa-arrow-right"></i></a>
      </div>

      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:1.25rem;">
        <?php foreach ($categories as $cat): ?>
          <a href="menu.php?category=<?php echo $cat['id']; ?>" class="card" style="text-align:center; padding:1.25rem 1rem; background:var(--bg-surface-2); border-color:var(--border-subtle); transition:0.2s ease;">
            <div style="width:48px; height:48px; border-radius:12px; background:var(--brand-subtle); color:var(--brand-primary); display:flex; align-items:center; justify-content:center; font-size:1.35rem; margin:0 auto 0.75rem;">
              <i class="fa-solid fa-<?php echo htmlspecialchars($cat['icon'] ?? 'utensils'); ?>"></i>
            </div>
            <h4 style="font-family:var(--font-display); font-weight:700; font-size:1rem; color:var(--text-primary); margin-bottom:0.25rem;"><?php echo htmlspecialchars($cat['name']); ?></h4>
            <span style="font-size:0.78rem; color:var(--text-secondary);"><?php echo $cat['item_count']; ?> Dishes</span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- Popular Today Section (Soft Peach Section) -->
  <section id="popular-today" style="padding: 4rem 0; background: #FFF1E9;">
    <div class="container">
      <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:2.25rem; flex-wrap:wrap; gap:1rem;">
        <div>
          <span style="color:var(--brand-primary); font-weight:700; font-size:0.8rem; text-transform:uppercase; letter-spacing:1px;">Campus Favorites</span>
          <h2 style="font-family:var(--font-display); font-size:2.1rem; font-weight:800; color:var(--text-primary);">Popular Today</h2>
        </div>
        <a href="menu.php" class="btn btn-primary btn-sm">Full Menu & Cart <i class="fa-solid fa-arrow-right"></i></a>
      </div>

      <div class="food-grid">
        <?php foreach ($popular_items as $item): ?>
          <div class="food-card">
            <div class="food-img-wrap">
              <img src="<?php echo htmlspecialchars($item['image'] ?: 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=600&q=80'); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
              <div class="food-badge-rating">
                <i class="fa-solid fa-star text-amber"></i> <?php echo number_format($item['rating'], 1); ?>
              </div>
              <div class="food-badge-prep">
                <i class="fa-solid fa-clock"></i> <?php echo htmlspecialchars($item['prep_time'] ?? '8-10m'); ?>
              </div>
            </div>

            <div class="food-card-body">
              <div class="food-card-header">
                <h3 class="food-title"><?php echo htmlspecialchars($item['name']); ?></h3>
                <span class="<?php echo $item['is_veg'] ? 'veg-icon' : 'nonveg-icon'; ?>" title="<?php echo $item['is_veg'] ? 'Veg' : 'Non-Veg'; ?>"></span>
              </div>
              <p class="food-desc"><?php echo htmlspecialchars($item['description'] ?: 'Freshly prepared campus delicacy.'); ?></p>
              
              <div class="food-card-footer">
                <div class="food-price">₹<?php echo number_format($item['price'], 2); ?></div>

                <?php if ($item['is_available']): ?>
                  <button type="button" class="btn btn-primary btn-sm" onclick="Biteora.addToCart(<?php echo $item['id']; ?>, '<?php echo addslashes($item['name']); ?>', <?php echo $item['price']; ?>, '<?php echo addslashes($item['image']); ?>', <?php echo $item['is_veg']; ?>);">
                    <i class="fa-solid fa-plus"></i> ADD
                  </button>
                <?php else: ?>
                  <span class="badge badge-rose">Sold Out</span>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- How It Works Section (White Section) -->
  <section id="how-it-works" style="padding: 4.5rem 0; background: #FFFFFF; border-top: 1px solid var(--border-subtle); border-bottom: 1px solid var(--border-subtle);">
    <div class="container">
      <div style="text-align:center; max-width:580px; margin:0 auto 3rem;">
        <span style="color:var(--brand-primary); font-weight:700; font-size:0.8rem; text-transform:uppercase; letter-spacing:1px;">Seamless Flow</span>
        <h2 style="font-family:var(--font-display); font-size:2.2rem; font-weight:800; color:var(--text-primary); margin-bottom:0.5rem;">How It Works</h2>
        <p style="color:var(--text-secondary); font-size:0.98rem;">Skip the long cafeteria lines in three effortless steps.</p>
      </div>

      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:1.75rem;">
        <!-- Step 1 -->
        <div class="card" style="padding:2rem 1.75rem; position:relative; background:var(--bg-surface-2); border-color:var(--border-subtle);">
          <div style="width:52px; height:52px; border-radius:12px; background:var(--brand-subtle); color:var(--brand-primary); display:flex; align-items:center; justify-content:center; font-size:1.4rem; margin-bottom:1.25rem;">
            <i class="fa-solid fa-utensils"></i>
          </div>
          <h4 style="font-family:var(--font-display); font-weight:700; font-size:1.2rem; color:var(--text-primary); margin-bottom:0.4rem;">1. Select Your Food</h4>
          <p style="font-size:0.9rem; color:var(--text-secondary); line-height:1.55;">Browse live cafeteria menus, discover daily specials, and customize your cart.</p>
        </div>

        <!-- Step 2 -->
        <div class="card" style="padding:2rem 1.75rem; position:relative; background:var(--bg-surface-2); border-color:var(--border-subtle);">
          <div style="width:52px; height:52px; border-radius:12px; background:var(--brand-subtle); color:var(--brand-primary); display:flex; align-items:center; justify-content:center; font-size:1.4rem; margin-bottom:1.25rem;">
            <i class="fa-solid fa-credit-card"></i>
          </div>
          <h4 style="font-family:var(--font-display); font-weight:700; font-size:1.2rem; color:var(--text-primary); margin-bottom:0.4rem;">2. Pay & Get Token</h4>
          <p style="font-size:0.9rem; color:var(--text-secondary); line-height:1.55;">Pay securely via Razorpay, UPI QR, or Cash on Pickup and get your unique Token #.</p>
        </div>

        <!-- Step 3 -->
        <div class="card" style="padding:2rem 1.75rem; position:relative; background:var(--bg-surface-2); border-color:var(--border-subtle);">
          <div style="width:52px; height:52px; border-radius:12px; background:var(--brand-subtle); color:var(--brand-primary); display:flex; align-items:center; justify-content:center; font-size:1.4rem; margin-bottom:1.25rem;">
            <i class="fa-solid fa-bag-shopping"></i>
          </div>
          <h4 style="font-family:var(--font-display); font-weight:700; font-size:1.2rem; color:var(--text-primary); margin-bottom:0.4rem;">3. Instant Pickup</h4>
          <p style="font-size:0.9rem; color:var(--text-secondary); line-height:1.55;">Walk to the counter when your token is called. Zero queue, hot food ready for you.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Why Biteora Section (Warm Cream Section) -->
  <section id="why-biteora" style="padding: 4.5rem 0; background: #FFF8F3;">
    <div class="container">
      <div style="display:grid; grid-template-columns:1.1fr 0.9fr; gap:3.5rem; align-items:center;">
        <div>
          <span style="color:var(--brand-primary); font-weight:700; font-size:0.8rem; text-transform:uppercase; letter-spacing:1px;">Campus Dining 2.0</span>
          <h2 style="font-family:var(--font-display); font-size:2.3rem; font-weight:800; color:var(--text-primary); line-height:1.2; margin-bottom:1.25rem;">
            Why Biteora is Loved by Students
          </h2>
          <p style="color:var(--text-secondary); font-size:0.98rem; margin-bottom:2rem; line-height:1.6;">
            Skip the Queue. Eat Smarter. Biteora simplifies your campus dining experience with digital orders, live tracking, and instant pickups.
          </p>

          <div style="display:flex; flex-direction:column; gap:1.25rem;">
            <div style="display:flex; align-items:flex-start; gap:1rem;">
              <div style="width:38px; height:38px; border-radius:10px; background:var(--brand-subtle); color:var(--brand-primary); display:flex; align-items:center; justify-content:center; font-size:1.05rem; flex-shrink:0;">
                <i class="fa-solid fa-bolt"></i>
              </div>
              <div>
                <h4 style="font-family:var(--font-display); font-weight:700; font-size:1.05rem; color:var(--text-primary);">Lightning Fast Pickups</h4>
                <p style="font-size:0.88rem; color:var(--text-secondary);">Order ahead from hostel or library and grab food immediately upon arrival.</p>
              </div>
            </div>

            <div style="display:flex; align-items:flex-start; gap:1rem;">
              <div style="width:38px; height:38px; border-radius:10px; background:var(--brand-subtle); color:var(--brand-primary); display:flex; align-items:center; justify-content:center; font-size:1.05rem; flex-shrink:0;">
                <i class="fa-solid fa-qrcode"></i>
              </div>
              <div>
                <h4 style="font-family:var(--font-display); font-weight:700; font-size:1.05rem; color:var(--text-primary);">Secure QR & Token Verification</h4>
                <p style="font-size:0.88rem; color:var(--text-secondary);">Every order generates a verified token preventing order mix-ups.</p>
              </div>
            </div>

            <div style="display:flex; align-items:flex-start; gap:1rem;">
              <div style="width:38px; height:38px; border-radius:10px; background:var(--brand-subtle); color:var(--brand-primary); display:flex; align-items:center; justify-content:center; font-size:1.05rem; flex-shrink:0;">
                <i class="fa-solid fa-shield-halved"></i>
              </div>
              <div>
                <h4 style="font-family:var(--font-display); font-weight:700; font-size:1.05rem; color:var(--text-primary);">Integrated Payment Gateway</h4>
                <p style="font-size:0.88rem; color:var(--text-secondary);">Razorpay standard checkout with UPI, Cards, NetBanking, and Campus Wallet.</p>
              </div>
            </div>
          </div>
        </div>

        <div class="card" style="padding:2.25rem 2rem; background:#FFFFFF; border:1px solid var(--border-subtle); border-radius:24px; box-shadow:var(--shadow-md);">
          <div style="background:var(--bg-surface-2); border:1px solid var(--border-subtle); border-radius:16px; padding:1.5rem; text-align:center; margin-bottom:1.5rem;">
            <div style="font-size:0.78rem; color:var(--brand-primary); font-weight:800; text-transform:uppercase; letter-spacing:1px; margin-bottom:0.25rem;">LIVE TOKEN STATUS</div>
            <div style="font-family:var(--font-display); font-size:3.5rem; font-weight:900; color:var(--brand-primary); line-height:1;">42</div>
            <p style="font-size:0.85rem; color:var(--text-secondary); margin-top:0.4rem;">Counter 1 • Ready for Pickup</p>
          </div>

          <div style="display:flex; align-items:center; justify-content:space-between; background:var(--bg-surface-2); padding:0.9rem 1.1rem; border-radius:12px; margin-bottom:1.35rem;">
            <div style="display:flex; align-items:center; gap:0.65rem;">
              <i class="fa-solid fa-circle-check text-emerald" style="font-size:1.15rem;"></i>
              <span style="font-size:0.9rem; font-weight:700; color:var(--text-primary);">Average wait: 0 minutes</span>
            </div>
            <span class="badge badge-success">Active</span>
          </div>

          <a href="menu.php" class="btn btn-primary" style="width:100%; font-size:0.98rem; padding:0.85rem;">
            Order Food Now
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- Student Reviews Section (Soft Peach Section) -->
  <section style="padding: 4.5rem 0; background: #FFF1E9; border-top: 1px solid var(--border-subtle);">
    <div class="container">
      <div style="text-align:center; max-width:540px; margin:0 auto 2.5rem;">
        <span style="color:var(--brand-primary); font-weight:700; font-size:0.8rem; text-transform:uppercase; letter-spacing:1px;">Campus Voice</span>
        <h2 style="font-family:var(--font-display); font-size:2.1rem; font-weight:800; color:var(--text-primary);">Student Reviews</h2>
      </div>

      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:1.5rem;">
        <div class="card" style="padding:1.65rem; background:#FFFFFF; border-color:var(--border-subtle);">
          <div style="color:#F59E0B; margin-bottom:0.75rem; font-size:0.95rem;">
            <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
          </div>
          <p style="font-size:0.92rem; color:var(--text-secondary); margin-bottom:1.15rem; line-height:1.55;">
            "Biteora changed our lunch breaks completely. I order from class and pick up warm food in literally 30 seconds."
          </p>
          <div style="font-weight:700; font-size:0.9rem; color:var(--text-primary);">Aarav Sharma <span style="font-size:0.8rem; color:var(--text-muted); font-weight:400;">• CSE 3rd Year</span></div>
        </div>

        <div class="card" style="padding:1.65rem; background:#FFFFFF; border-color:var(--border-subtle);">
          <div style="color:#F59E0B; margin-bottom:0.75rem; font-size:0.95rem;">
            <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
          </div>
          <p style="font-size:0.92rem; color:var(--text-secondary); margin-bottom:1.15rem; line-height:1.55;">
            "The UPI Scan & Pay QR flow works like a charm. No need to carry cash or wait for change at the cafeteria counter."
          </p>
          <div style="font-weight:700; font-size:0.9rem; color:var(--text-primary);">Pooja Patel <span style="font-size:0.8rem; color:var(--text-muted); font-weight:400;">• ECE 2nd Year</span></div>
        </div>

        <div class="card" style="padding:1.65rem; background:#FFFFFF; border-color:var(--border-subtle);">
          <div style="color:#F59E0B; margin-bottom:0.75rem; font-size:0.95rem;">
            <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
          </div>
          <p style="font-size:0.92rem; color:var(--text-secondary); margin-bottom:1.15rem; line-height:1.55;">
            "The token board and live order status tracking is super accurate. Best dining software on our campus!"
          </p>
          <div style="font-weight:700; font-size:0.9rem; color:var(--text-primary);">Rohan Verma <span style="font-size:0.8rem; color:var(--text-muted); font-weight:400;">• MBA 1st Year</span></div>
        </div>
      </div>
    </div>
  </section>
</main>

<?php include 'includes/footer.php'; ?>