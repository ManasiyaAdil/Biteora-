<?php
$page_title = "Your Campus. Your Food. Your Way.";
require_once 'includes/connect.php';

// If student is logged in, check for active order
$active_order = null;
if (isset($_SESSION['customer_sid']) && isset($_SESSION['user_id'])) {
    $uid = intval($_SESSION['user_id']);
    $stmt = $con->prepare("SELECT id, order_code, pickup_token, status, total, date FROM orders WHERE customer_id = ? AND status IN ('Placed', 'Preparing', 'Ready') AND deleted = 0 ORDER BY id DESC LIMIT 1");
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
  <!-- Active Order Banner for Logged-In Student -->
  <?php if ($active_order): ?>
    <section style="background: rgba(255, 90, 54, 0.1); border-bottom: 1px solid rgba(255, 90, 54, 0.25); padding: 0.85rem 0;">
      <div class="container" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem;">
        <div style="display:flex; align-items:center; gap:0.85rem;">
          <div style="width:38px; height:38px; border-radius:8px; background:var(--brand-primary); color:#FFFFFF; display:flex; align-items:center; justify-content:center; font-size:1rem; box-shadow:var(--shadow-orange);">
            <i class="fa-solid fa-bell-concierge"></i>
          </div>
          <div>
            <div style="font-weight:700; font-size:0.95rem; color:#FFFFFF;">
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

  <!-- Hero Section -->
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
        <div class="card" style="padding:1.25rem; border-radius:20px; box-shadow:var(--shadow-lg);">
          <div style="position:relative; height:230px; border-radius:12px; overflow:hidden; margin-bottom:1.15rem;">
            <img src="https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=800&q=80" alt="Campus Cafeteria" style="width:100%; height:100%; object-fit:cover;">
            <div style="position:absolute; inset:0; background:linear-gradient(180deg, transparent 40%, rgba(11,11,13,0.85) 100%);"></div>
            <div style="position:absolute; bottom:10px; left:12px; display:flex; align-items:center; gap:0.5rem;">
              <span class="badge badge-success"><i class="fa-solid fa-circle" style="font-size:0.45rem;"></i> Cafeteria Open</span>
              <span class="badge badge-brand">Counter 1 & 2 Live</span>
            </div>
          </div>

          <!-- Live Token Preview -->
          <div style="background:var(--bg-surface-2); border:1px solid var(--border-subtle); border-radius:12px; padding:0.9rem; display:flex; align-items:center; justify-content:space-between;">
            <div style="display:flex; align-items:center; gap:0.75rem;">
              <div style="width:38px; height:38px; border-radius:8px; background:var(--brand-subtle); color:var(--brand-primary); display:flex; align-items:center; justify-content:center; font-size:1.1rem;">
                <i class="fa-solid fa-ticket"></i>
              </div>
              <div>
                <div style="font-size:0.72rem; color:var(--text-muted); text-transform:uppercase; font-weight:700;">Live Kitchen Queue</div>
                <div style="font-family:var(--font-display); font-weight:800; font-size:1.05rem; color:#FFFFFF;">Now Serving: <span class="text-orange">#42</span></div>
              </div>
            </div>
            <a href="menu.php" class="btn btn-primary btn-sm" style="font-size:0.78rem; padding:0.4rem 0.75rem;">Order Now</a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Categories Showcase -->
  <section id="categories" style="padding: 3rem 0; border-bottom: 1px solid var(--border-subtle); background: var(--bg-surface);">
    <div class="container">
      <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem;">
        <div>
          <span style="color:var(--brand-primary); font-weight:700; font-size:0.8rem; text-transform:uppercase; letter-spacing:1px;">Categories</span>
          <h2 style="font-family:var(--font-display); font-size:1.75rem; font-weight:800; color:#FFFFFF;">Explore Menu Categories</h2>
        </div>
        <a href="menu.php" class="btn btn-secondary btn-sm">View All Menu <i class="fa-solid fa-arrow-right"></i></a>
      </div>

      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:1rem;">
        <?php foreach ($categories as $cat): ?>
          <a href="menu.php?category=<?php echo $cat['id']; ?>" class="card" style="text-align:center; padding:1.15rem 0.85rem; background:var(--bg-surface-2);">
            <div style="width:46px; height:46px; border-radius:10px; background:var(--brand-subtle); color:var(--brand-primary); display:flex; align-items:center; justify-content:center; font-size:1.3rem; margin:0 auto 0.65rem;">
              <i class="fa-solid fa-<?php echo htmlspecialchars($cat['icon'] ?? 'utensils'); ?>"></i>
            </div>
            <h4 style="font-family:var(--font-display); font-weight:700; font-size:0.95rem; color:#FFFFFF; margin-bottom:0.2rem;"><?php echo htmlspecialchars($cat['name']); ?></h4>
            <span style="font-size:0.75rem; color:var(--text-secondary);"><?php echo $cat['item_count']; ?> Dishes</span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- Popular Today Section -->
  <section id="popular-today" style="padding: 3.5rem 0;">
    <div class="container">
      <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:2rem; flex-wrap:wrap; gap:1rem;">
        <div>
          <span style="color:var(--brand-primary); font-weight:700; font-size:0.8rem; text-transform:uppercase; letter-spacing:1px;">Campus Favorites</span>
          <h2 style="font-family:var(--font-display); font-size:2rem; font-weight:800; color:#FFFFFF;">Popular Today</h2>
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
                  <button type="button" class="btn btn-primary btn-sm" onclick="CampusBite.addToCart(<?php echo $item['id']; ?>, '<?php echo addslashes($item['name']); ?>', <?php echo $item['price']; ?>, '<?php echo addslashes($item['image']); ?>', <?php echo $item['is_veg']; ?>);">
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

  <!-- How It Works Section -->
  <section id="how-it-works" style="padding: 4rem 0; background: var(--bg-surface); border-top: 1px solid var(--border-subtle); border-bottom: 1px solid var(--border-subtle);">
    <div class="container">
      <div style="text-align:center; max-width:580px; margin:0 auto 3rem;">
        <span style="color:var(--brand-primary); font-weight:700; font-size:0.8rem; text-transform:uppercase; letter-spacing:1px;">Seamless Flow</span>
        <h2 style="font-family:var(--font-display); font-size:2.1rem; font-weight:800; color:#FFFFFF; margin-bottom:0.5rem;">How It Works</h2>
        <p style="color:var(--text-secondary); font-size:0.95rem;">Skip the long cafeteria lines in three effortless steps.</p>
      </div>

      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:1.75rem;">
        <!-- Step 1 -->
        <div class="card" style="padding:1.75rem 1.5rem; position:relative; background:var(--bg-surface-2);">
          <div style="width:48px; height:48px; border-radius:10px; background:var(--brand-subtle); color:var(--brand-primary); display:flex; align-items:center; justify-content:center; font-size:1.3rem; margin-bottom:1.25rem;">
            <i class="fa-solid fa-utensils"></i>
          </div>
          <h4 style="font-family:var(--font-display); font-weight:700; font-size:1.15rem; color:#FFFFFF; margin-bottom:0.4rem;">1. Select Your Food</h4>
          <p style="font-size:0.88rem; color:var(--text-secondary);">Browse live cafeteria menus, discover daily specials, and customize your cart.</p>
        </div>

        <!-- Step 2 -->
        <div class="card" style="padding:1.75rem 1.5rem; position:relative; background:var(--bg-surface-2);">
          <div style="width:48px; height:48px; border-radius:10px; background:var(--brand-subtle); color:var(--brand-primary); display:flex; align-items:center; justify-content:center; font-size:1.3rem; margin-bottom:1.25rem;">
            <i class="fa-solid fa-credit-card"></i>
          </div>
          <h4 style="font-family:var(--font-display); font-weight:700; font-size:1.15rem; color:#FFFFFF; margin-bottom:0.4rem;">2. Pay & Get Token</h4>
          <p style="font-size:0.88rem; color:var(--text-secondary);">Pay securely via Razorpay, UPI QR, or Cash on Pickup and get your unique Token #.</p>
        </div>

        <!-- Step 3 -->
        <div class="card" style="padding:1.75rem 1.5rem; position:relative; background:var(--bg-surface-2);">
          <div style="width:48px; height:48px; border-radius:10px; background:var(--brand-subtle); color:var(--brand-primary); display:flex; align-items:center; justify-content:center; font-size:1.3rem; margin-bottom:1.25rem;">
            <i class="fa-solid fa-bag-shopping"></i>
          </div>
          <h4 style="font-family:var(--font-display); font-weight:700; font-size:1.15rem; color:#FFFFFF; margin-bottom:0.4rem;">3. Instant Pickup</h4>
          <p style="font-size:0.88rem; color:var(--text-secondary);">Walk to the counter when your token is called. Zero queue, hot food ready for you.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Why CampusBite Section -->
  <section id="why-campusbite" style="padding: 4.5rem 0;">
    <div class="container">
      <div style="display:grid; grid-template-columns:1.1fr 0.9fr; gap:3.5rem; align-items:center;">
        <div>
          <span style="color:var(--brand-primary); font-weight:700; font-size:0.8rem; text-transform:uppercase; letter-spacing:1px;">Campus Cafeteria 2.0</span>
          <h2 style="font-family:var(--font-display); font-size:2.2rem; font-weight:800; color:#FFFFFF; line-height:1.2; margin-bottom:1.25rem;">
            Why CampusBite is Loved by Students
          </h2>
          <p style="color:var(--text-secondary); font-size:0.95rem; margin-bottom:2rem;">
            Never waste another 20 minutes standing in line. CampusBite simplifies your dining experience with digital orders, live tracking, and fast pickups.
          </p>

          <div style="display:flex; flex-direction:column; gap:1.25rem;">
            <div style="display:flex; align-items:flex-start; gap:1rem;">
              <div style="width:36px; height:36px; border-radius:8px; background:var(--brand-subtle); color:var(--brand-primary); display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0;">
                <i class="fa-solid fa-bolt"></i>
              </div>
              <div>
                <h4 style="font-family:var(--font-display); font-weight:700; font-size:1rem; color:#FFFFFF;">Lightning Fast Pickups</h4>
                <p style="font-size:0.85rem; color:var(--text-secondary);">Order ahead from hostel or library and grab food immediately upon arrival.</p>
              </div>
            </div>

            <div style="display:flex; align-items:flex-start; gap:1rem;">
              <div style="width:36px; height:36px; border-radius:8px; background:var(--brand-subtle); color:var(--brand-primary); display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0;">
                <i class="fa-solid fa-qrcode"></i>
              </div>
              <div>
                <h4 style="font-family:var(--font-display); font-weight:700; font-size:1rem; color:#FFFFFF;">Secure QR & Token Verification</h4>
                <p style="font-size:0.85rem; color:var(--text-secondary);">Every order generates a verified token preventing order mix-ups.</p>
              </div>
            </div>

            <div style="display:flex; align-items:flex-start; gap:1rem;">
              <div style="width:36px; height:36px; border-radius:8px; background:var(--brand-subtle); color:var(--brand-primary); display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0;">
                <i class="fa-solid fa-shield-halved"></i>
              </div>
              <div>
                <h4 style="font-family:var(--font-display); font-weight:700; font-size:1rem; color:#FFFFFF;">Integrated Payment Gateway</h4>
                <p style="font-size:0.85rem; color:var(--text-secondary);">Razorpay standard checkout with UPI, Cards, NetBanking, and Campus Wallet.</p>
              </div>
            </div>
          </div>
        </div>

        <div class="card" style="padding:2rem; background:var(--bg-surface); border:1px solid var(--border-subtle); border-radius:20px;">
          <div style="background:var(--bg-surface-2); border:1px solid var(--border-subtle); border-radius:14px; padding:1.5rem; text-align:center; margin-bottom:1.5rem;">
            <div style="font-size:0.75rem; color:var(--brand-primary); font-weight:800; text-transform:uppercase; letter-spacing:1px; margin-bottom:0.25rem;">LIVE TOKEN STATUS</div>
            <div style="font-family:var(--font-display); font-size:3.5rem; font-weight:900; color:#FFFFFF; line-height:1;">42</div>
            <p style="font-size:0.82rem; color:var(--text-secondary); margin-top:0.4rem;">Counter 1 • Ready for Pickup</p>
          </div>

          <div style="display:flex; align-items:center; justify-content:space-between; background:var(--bg-surface-2); padding:0.85rem 1rem; border-radius:10px; margin-bottom:1.25rem;">
            <div style="display:flex; align-items:center; gap:0.65rem;">
              <i class="fa-solid fa-circle-check text-emerald" style="font-size:1.1rem;"></i>
              <span style="font-size:0.88rem; font-weight:600; color:#FFFFFF;">Average wait: 0 minutes</span>
            </div>
            <span class="badge badge-success">Active</span>
          </div>

          <a href="menu.php" class="btn btn-primary" style="width:100%; font-size:0.95rem; padding:0.8rem;">
            Order Food Now
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- Student Reviews Section -->
  <section style="padding: 4rem 0; background: var(--bg-surface); border-top: 1px solid var(--border-subtle);">
    <div class="container">
      <div style="text-align:center; max-width:540px; margin:0 auto 2.5rem;">
        <span style="color:var(--brand-primary); font-weight:700; font-size:0.8rem; text-transform:uppercase; letter-spacing:1px;">Campus Voice</span>
        <h2 style="font-family:var(--font-display); font-size:2rem; font-weight:800; color:#FFFFFF;">Student Reviews</h2>
      </div>

      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:1.5rem;">
        <div class="card" style="padding:1.5rem; background:var(--bg-surface-2);">
          <div style="color:#F59E0B; margin-bottom:0.75rem; font-size:0.9rem;">
            <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
          </div>
          <p style="font-size:0.9rem; color:var(--text-secondary); margin-bottom:1rem; line-height:1.5;">
            "CampusBite changed our lunch breaks completely. I order from class and pick up warm food in literally 30 seconds."
          </p>
          <div style="font-weight:700; font-size:0.88rem; color:#FFFFFF;">Aarav Sharma <span style="font-size:0.78rem; color:var(--text-muted); font-weight:400;">• CSE 3rd Year</span></div>
        </div>

        <div class="card" style="padding:1.5rem; background:var(--bg-surface-2);">
          <div style="color:#F59E0B; margin-bottom:0.75rem; font-size:0.9rem;">
            <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
          </div>
          <p style="font-size:0.9rem; color:var(--text-secondary); margin-bottom:1rem; line-height:1.5;">
            "The UPI Scan & Pay QR flow works like a charm. No need to carry cash or wait for change at the cafeteria counter."
          </p>
          <div style="font-weight:700; font-size:0.88rem; color:#FFFFFF;">Pooja Patel <span style="font-size:0.78rem; color:var(--text-muted); font-weight:400;">• ECE 2nd Year</span></div>
        </div>

        <div class="card" style="padding:1.5rem; background:var(--bg-surface-2);">
          <div style="color:#F59E0B; margin-bottom:0.75rem; font-size:0.9rem;">
            <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
          </div>
          <p style="font-size:0.9rem; color:var(--text-secondary); margin-bottom:1rem; line-height:1.5;">
            "The token board and live order status tracking is super accurate. Best software on our campus!"
          </p>
          <div style="font-weight:700; font-size:0.88rem; color:#FFFFFF;">Rohan Verma <span style="font-size:0.78rem; color:var(--text-muted); font-weight:400;">• MBA 1st Year</span></div>
        </div>
      </div>
    </div>
  </section>
</main>

<?php include 'includes/footer.php'; ?>