<?php
$page_title = "Food Discovery Menu";
require_once 'includes/connect.php';

$selected_cat = isset($_GET['category']) ? intval($_GET['category']) : 0;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$veg_filter = isset($_GET['veg']) ? intval($_GET['veg']) : -1;

// Helper function to build filter URLs preserving other active filters
function build_menu_url($new_params = []) {
    $params = [];
    if (isset($_GET['category']) && (int)$_GET['category'] > 0) {
        $params['category'] = (int)$_GET['category'];
    }
    if (isset($_GET['search']) && trim($_GET['search']) !== '') {
        $params['search'] = trim($_GET['search']);
    }
    if (isset($_GET['veg']) && $_GET['veg'] !== '' && (int)$_GET['veg'] !== -1) {
        $params['veg'] = (int)$_GET['veg'];
    }

    foreach ($new_params as $key => $val) {
        if ($val === -1 || $val === null || $val === '' || ($key === 'category' && $val === 0)) {
            unset($params[$key]);
        } else {
            $params[$key] = $val;
        }
    }
    return 'menu.php' . (empty($params) ? '' : '?' . http_build_query($params));
}

// Fetch Categories
$categories = [];
$c_res = $con->query("SELECT * FROM categories ORDER BY id ASC");
if ($c_res) {
    while ($r = $c_res->fetch_assoc()) {
        $categories[] = $r;
    }
}

// Fetch Menu Items
$where = ["i.deleted = 0"];

if ($selected_cat > 0) {
    $where[] = "i.category_id = $selected_cat";
}
if (!empty($search_query)) {
    $safe_search = $con->real_escape_string($search_query);
    $where[] = "(i.name LIKE '%$safe_search%' OR i.description LIKE '%$safe_search%')";
}
if ($veg_filter !== -1) {
    $where[] = "i.is_veg = $veg_filter";
}

$where_clause = implode(" AND ", $where);
$sql = "SELECT i.*, c.name as category_name, c.slug as category_slug 
        FROM items i 
        LEFT JOIN categories c ON i.category_id = c.id 
        WHERE $where_clause 
        ORDER BY i.is_available DESC, i.id ASC";

$items = [];
$res = $con->query($sql);
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $items[] = $r;
    }
}

include 'includes/header.php';
?>

<div class="container" style="padding: 2rem 1.5rem 4.5rem;">
  <!-- Header & Search Bar -->
  <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1.25rem; margin-bottom:1.75rem;">
    <div>
      <span style="color:var(--brand-primary); font-weight:700; font-size:0.8rem; text-transform:uppercase; letter-spacing:1px;">Campus Dining</span>
      <h1 style="font-family:var(--font-display); font-size:2.2rem; font-weight:800; color:var(--text-primary);">Food Discovery Menu</h1>
      <p style="color:var(--text-secondary); font-size:0.9rem;">Freshly prepared hot meals, snacks and drinks ready for speedy pickup.</p>
    </div>

    <!-- Search Form -->
    <form method="GET" action="menu.php" style="display:flex; gap:0.5rem; max-width:420px; width:100%;">
      <?php if ($selected_cat): ?><input type="hidden" name="category" value="<?php echo $selected_cat; ?>"><?php endif; ?>
      <?php if ($veg_filter !== -1): ?><input type="hidden" name="veg" value="<?php echo $veg_filter; ?>"><?php endif; ?>
      <div class="search-wrapper">
        <i class="fa-solid fa-magnifying-glass search-icon"></i>
        <input type="text" name="search" id="menu-search-input" value="<?php echo htmlspecialchars($search_query); ?>" class="search-input" placeholder="Search for dishes, snacks, drinks...">
      </div>
      <button type="submit" id="menu-search-btn" class="btn btn-primary" style="padding:0.75rem 1.15rem; border-radius:999px;">
        <i class="fa-solid fa-search"></i>
      </button>
      <?php if ($search_query || $selected_cat || $veg_filter !== -1): ?>
        <a href="menu.php" class="btn btn-secondary" style="border-radius:999px;" title="Reset Filters">✕</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- Category Pills Navigation -->
  <div class="category-bar" style="margin-bottom:1.5rem;">
    <a href="<?php echo build_menu_url(['category' => 0]); ?>" class="cat-pill <?php echo ($selected_cat === 0) ? 'active' : ''; ?>">
      <i class="fa-solid fa-border-all"></i> All Categories
    </a>
    <?php foreach ($categories as $cat): ?>
      <a href="<?php echo build_menu_url(['category' => $cat['id']]); ?>" class="cat-pill <?php echo ($selected_cat == $cat['id']) ? 'active' : ''; ?>">
        <i class="fa-solid fa-<?php echo htmlspecialchars($cat['icon'] ?? 'utensils'); ?>"></i>
        <?php echo htmlspecialchars($cat['name']); ?>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Dietary Filter & Result Count Bar -->
  <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.75rem; flex-wrap:wrap; gap:1rem; padding-bottom:0.85rem; border-bottom:1px solid var(--border-subtle);">
    <div style="display:flex; align-items:center; gap:0.6rem;">
      <span style="font-size:0.82rem; color:var(--text-secondary); font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">Dietary Filter:</span>
      <a href="<?php echo build_menu_url(['veg' => -1]); ?>" id="filter-all" class="btn btn-sm <?php echo ($veg_filter === -1) ? 'btn-primary' : 'btn-secondary'; ?>" style="font-size:0.82rem; font-weight:700; border-radius:999px; padding:0.45rem 1rem;">
        ALL
      </a>
      <a href="<?php echo build_menu_url(['veg' => 1]); ?>" id="filter-veg" class="btn btn-sm <?php echo ($veg_filter === 1) ? 'btn-primary' : 'btn-secondary'; ?>" style="font-size:0.82rem; font-weight:700; border-radius:999px; padding:0.45rem 1rem; display:inline-flex; align-items:center; gap:5px;">
        <span class="veg-icon" style="width:13px; height:13px;"></span> VEG
      </a>
      <a href="<?php echo build_menu_url(['veg' => 0]); ?>" id="filter-nonveg" class="btn btn-sm <?php echo ($veg_filter === 0) ? 'btn-primary' : 'btn-secondary'; ?>" style="font-size:0.82rem; font-weight:700; border-radius:999px; padding:0.45rem 1rem; display:inline-flex; align-items:center; gap:5px;">
        <span class="nonveg-icon" style="width:13px; height:13px;"></span> NON-VEG
      </a>
    </div>

    <div style="font-size:0.88rem; color:var(--text-secondary);">
      Showing <strong class="text-orange" id="dish-count-display"><?php echo count($items); ?></strong> cafeteria dishes
    </div>
  </div>

  <!-- Food Discovery Grid -->
  <?php if (count($items) === 0): ?>
    <div class="card" style="text-align:center; padding:3.5rem 1.5rem;">
      <div style="width:60px; height:60px; border-radius:50%; background:var(--bg-surface-2); display:flex; align-items:center; justify-content:center; font-size:1.75rem; color:var(--text-muted); margin:0 auto 1.25rem;">
        <i class="fa-solid fa-plate-wheat"></i>
      </div>
      <h3 style="font-family:var(--font-display); font-size:1.3rem; color:var(--text-primary); margin-bottom:0.4rem;">No food items found</h3>
      <p style="color:var(--text-secondary); font-size:0.9rem; margin-bottom:1.5rem;">Try searching for another dish or clear active category and diet filters.</p>
      <a href="menu.php" class="btn btn-primary">Reset All Filters</a>
    </div>
  <?php else: ?>
    <div class="food-grid" id="menu-food-grid">
      <?php foreach ($items as $item): ?>
        <div class="food-card" data-item-id="<?php echo $item['id']; ?>" data-is-veg="<?php echo $item['is_veg']; ?>">
          <div class="food-img-wrap">
            <img src="<?php echo htmlspecialchars($item['image'] ?: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=600&q=80'); ?>" 
                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                 loading="lazy"
                 onerror="this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=600&q=80';">
            
            <!-- Veg / Non-Veg badge -->
            <div class="food-diet-badge <?php echo $item['is_veg'] ? 'veg' : 'nonveg'; ?>">
              <span class="<?php echo $item['is_veg'] ? 'veg-icon' : 'nonveg-icon'; ?>"></span>
              <span><?php echo $item['is_veg'] ? 'VEG' : 'NON-VEG'; ?></span>
            </div>

            <!-- Rating badge -->
            <div class="food-badge-rating">
              <i class="fa-solid fa-star text-amber"></i> <?php echo number_format($item['rating'] ?: 4.5, 1); ?>
            </div>

            <!-- Prep time badge -->
            <div class="food-badge-prep">
              <i class="fa-solid fa-clock"></i> <?php echo htmlspecialchars($item['prep_time'] ?? '8-10m'); ?>
            </div>
          </div>

          <div class="food-card-body">
            <div class="food-card-header">
              <h3 class="food-title"><?php echo htmlspecialchars($item['name']); ?></h3>
            </div>
            
            <p class="food-desc"><?php echo htmlspecialchars($item['description'] ?: 'Fresh campus prepared meal.'); ?></p>
            
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:0.75rem;">
              <span style="font-size:0.75rem; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.5px; font-weight:700;">
                <i class="fa-solid fa-tag" style="margin-right:4px; color:var(--brand-primary);"></i>
                <?php echo htmlspecialchars($item['category_name'] ?? 'Cafeteria'); ?>
              </span>

              <!-- Availability indicator -->
              <?php if ($item['is_available']): ?>
                <span class="food-avail-pill in-stock">
                  <i class="fa-solid fa-circle-check"></i> Available
                </span>
              <?php else: ?>
                <span class="food-avail-pill sold-out">
                  <i class="fa-solid fa-circle-xmark"></i> Sold Out
                </span>
              <?php endif; ?>
            </div>

            <div class="food-card-footer">
              <div class="food-price">₹<?php echo number_format($item['price'], 2); ?></div>

              <?php if ($item['is_available']): ?>
                <button type="button" 
                        class="btn btn-primary btn-sm add-cart-btn" 
                        onclick="Biteora.addToCart(<?php echo $item['id']; ?>, '<?php echo addslashes($item['name']); ?>', <?php echo $item['price']; ?>, '<?php echo addslashes($item['image']); ?>', <?php echo $item['is_veg']; ?>);"
                        style="padding:0.55rem 1.15rem; font-weight:700; border-radius:999px; letter-spacing:0.3px; display:inline-flex; align-items:center; gap:0.4rem;">
                  <i class="fa-solid fa-cart-plus"></i> ADD TO CART
                </button>
              <?php else: ?>
                <button type="button" class="btn btn-secondary btn-sm" disabled style="padding:0.55rem 1.15rem; opacity:0.6; cursor:not-allowed; border-radius:999px;">
                  Sold Out
                </button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
