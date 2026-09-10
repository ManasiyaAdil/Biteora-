<?php
$page_title = "Manage Food Menu";
require_once 'includes/connect.php';

if (!isset($_SESSION['admin_sid'])) {
    header("Location: login.php");
    exit;
}

$msg = isset($_GET['msg']) ? $_GET['msg'] : '';

// 1. Handle Add / Edit / Delete / Toggle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim(htmlspecialchars($_POST['name'] ?? ''));
        $cat_id = intval($_POST['category_id'] ?? 1);
        $price = floatval($_POST['price'] ?? 0);
        $desc = trim(htmlspecialchars($_POST['description'] ?? ''));
        $image = trim($_POST['image'] ?? '');
        $is_veg = intval($_POST['is_veg'] ?? 1);
        $prep = trim(htmlspecialchars($_POST['prep_time'] ?? '10-15 mins'));

        if (!empty($name) && $price > 0) {
            $stmt = $con->prepare("INSERT INTO items (category_id, name, price, description, image, is_veg, prep_time, is_available, deleted) VALUES (?, ?, ?, ?, ?, ?, ?, 1, 0)");
            $stmt->bind_param("isdssis", $cat_id, $name, $price, $desc, $image, $is_veg, $prep);
            $stmt->execute();
            header("Location: admin-menu.php?msg=added");
            exit;
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim(htmlspecialchars($_POST['name'] ?? ''));
        $cat_id = intval($_POST['category_id'] ?? 1);
        $price = floatval($_POST['price'] ?? 0);
        $desc = trim(htmlspecialchars($_POST['description'] ?? ''));
        $image = trim($_POST['image'] ?? '');
        $is_veg = intval($_POST['is_veg'] ?? 1);
        $prep = trim(htmlspecialchars($_POST['prep_time'] ?? '10-15 mins'));
        $avail = intval($_POST['is_available'] ?? 1);

        if ($id > 0 && !empty($name) && $price > 0) {
            $stmt = $con->prepare("UPDATE items SET category_id = ?, name = ?, price = ?, description = ?, image = ?, is_veg = ?, prep_time = ?, is_available = ? WHERE id = ?");
            $stmt->bind_param("isdssisii", $cat_id, $name, $price, $desc, $image, $is_veg, $prep, $avail, $id);
            $stmt->execute();
            header("Location: admin-menu.php?msg=updated");
            exit;
        }
    } elseif ($action === 'toggle') {
        $id = intval($_POST['id'] ?? 0);
        $current = intval($_POST['current_status'] ?? 0);
        $new_status = ($current == 1) ? 0 : 1;
        $con->query("UPDATE items SET is_available = $new_status WHERE id = $id");
        header("Location: admin-menu.php?msg=toggled");
        exit;
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $con->query("UPDATE items SET deleted = 1 WHERE id = $id");
        header("Location: admin-menu.php?msg=deleted");
        exit;
    }
}

// Fetch categories for dropdown
$categories = [];
$c_res = $con->query("SELECT * FROM categories ORDER BY id ASC");
if ($c_res) {
    while ($r = $c_res->fetch_assoc()) {
        $categories[] = $r;
    }
}

// Fetch all menu items
$items = [];
$res = $con->query("SELECT i.*, c.name as category_name 
                    FROM items i 
                    LEFT JOIN categories c ON i.category_id = c.id 
                    WHERE i.deleted = 0 
                    ORDER BY i.id DESC");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $items[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Food Menu CRUD | Biteora Admin</title>
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23FF5A36'><path d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9v-2h2v2zm0-4H9V7h2v5z'/></svg>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/campusbite.css">
</head>
<body style="background:#FFF8F3;">

  <div style="display:flex; min-height:100vh;">
    <!-- Admin Sidebar -->
    <aside style="width:260px; background:#FFFFFF; border-right:1px solid var(--border-subtle); padding:1.5rem 1rem; display:flex; flex-direction:column; flex-shrink:0;">
      <div class="cb-logo" style="margin-bottom:1.5rem; padding:0 0.5rem;">
        <div class="cb-logo-icon">
          <i class="fa-solid fa-utensils"></i>
        </div>
        <span>Bite<span class="text-orange">ora</span></span>
      </div>

      <ul style="list-style:none; display:flex; flex-direction:column; gap:0.4rem; flex-grow:1;">
        <li><a href="admin-page.php" class="btn btn-secondary" style="width:100%; justify-content:flex-start;"><i class="fa-solid fa-chart-pie text-orange" style="width:20px;"></i> Dashboard</a></li>
        <li><a href="all-orders.php" class="btn btn-secondary" style="width:100%; justify-content:flex-start;"><i class="fa-solid fa-receipt text-orange" style="width:20px;"></i> Orders Queue</a></li>
        <li><a href="admin-menu.php" class="btn btn-primary" style="width:100%; justify-content:flex-start;"><i class="fa-solid fa-bowl-food" style="width:20px;"></i> Food Menu</a></li>
        <li><a href="admin-categories.php" class="btn btn-secondary" style="width:100%; justify-content:flex-start;"><i class="fa-solid fa-layer-group text-orange" style="width:20px;"></i> Categories</a></li>
        <li><a href="users.php" class="btn btn-secondary" style="width:100%; justify-content:flex-start;"><i class="fa-solid fa-users text-orange" style="width:20px;"></i> Students</a></li>
        <li style="margin-top:auto; padding-top:1.25rem; border-top:1px solid var(--border-subtle);"><a href="routers/logout.php" class="btn btn-danger" style="width:100%; justify-content:flex-start;"><i class="fa-solid fa-arrow-right-from-bracket" style="width:20px;"></i> Sign Out</a></li>
      </ul>
    </aside>

    <!-- Main Content Area -->
    <main style="flex-grow:1; padding:2rem 2.5rem; overflow-y:auto;">
      <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1.5rem; margin-bottom:2rem;">
        <div>
          <span style="color:var(--brand-primary); font-weight:700; font-size:0.8rem; text-transform:uppercase; letter-spacing:1px;">Cafeteria Inventory</span>
          <h1 style="font-family:var(--font-display); font-size:2.1rem; font-weight:800; color:var(--text-primary);">Food Menu Management</h1>
        </div>

        <button type="button" onclick="openAddModal()" class="btn btn-primary">
          <i class="fa-solid fa-plus"></i> Add New Food Item
        </button>
      </div>

      <?php if ($msg): ?>
        <div style="background:var(--success-bg); border:1px solid rgba(34,160,107,0.3); border-radius:10px; padding:1rem; margin-bottom:1.5rem; color:var(--success); display:flex; gap:0.65rem; align-items:center;">
          <i class="fa-solid fa-circle-check"></i>
          <span>
            <?php 
              if ($msg === 'added') echo "New food item created successfully!";
              elseif ($msg === 'updated') echo "Food item updated successfully!";
              elseif ($msg === 'toggled') echo "Item availability toggled!";
              elseif ($msg === 'deleted') echo "Food item removed from active menu!";
            ?>
          </span>
        </div>
      <?php endif; ?>

      <!-- Menu Items Table Card -->
      <div class="card" style="padding:1.5rem; background:#FFFFFF; border:1px solid var(--border-subtle);">
        <div style="overflow-x:auto;">
          <table style="width:100%; border-collapse:collapse; font-size:0.88rem;">
            <thead>
              <tr style="border-bottom:1.5px solid var(--border-subtle); color:var(--text-muted); text-align:left; font-size:0.75rem; text-transform:uppercase;">
                <th style="padding:0.75rem 0.5rem;">Dish Details</th>
                <th style="padding:0.75rem 0.5rem;">Category</th>
                <th style="padding:0.75rem 0.5rem;">Type</th>
                <th style="padding:0.75rem 0.5rem;">Price</th>
                <th style="padding:0.75rem 0.5rem;">Prep Time</th>
                <th style="padding:0.75rem 0.5rem;">Availability</th>
                <th style="padding:0.75rem 0.5rem; text-align:right;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $it): ?>
                <tr style="border-bottom:1px solid var(--border-subtle); color:var(--text-primary);">
                  <td style="padding:0.85rem 0.5rem;">
                    <div style="display:flex; align-items:center; gap:0.9rem;">
                      <img src="<?php echo htmlspecialchars($it['image'] ?: 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=600&q=80'); ?>" alt="<?php echo htmlspecialchars($it['name']); ?>" style="width:48px; height:48px; object-fit:cover; border-radius:8px; border:1px solid var(--border-subtle);">
                      <div>
                        <div style="font-weight:700; font-size:0.95rem; color:var(--text-primary);"><?php echo htmlspecialchars($it['name']); ?></div>
                        <div style="font-size:0.8rem; color:var(--text-secondary); max-width:280px; text-overflow:ellipsis; overflow:hidden; white-space:nowrap;"><?php echo htmlspecialchars($it['description']); ?></div>
                      </div>
                    </div>
                  </td>
                  <td style="padding:0.85rem 0.5rem;">
                    <span class="badge badge-brand"><?php echo htmlspecialchars($it['category_name'] ?? 'Cafeteria'); ?></span>
                  </td>
                  <td style="padding:0.85rem 0.5rem;">
                    <span class="<?php echo $it['is_veg'] ? 'veg-icon' : 'nonveg-icon'; ?>" title="<?php echo $it['is_veg'] ? 'Veg' : 'Non-Veg'; ?>"></span>
                  </td>
                  <td style="padding:0.85rem 0.5rem;">
                    <strong style="font-family:var(--font-display); font-size:1.15rem; color:var(--brand-primary);">₹<?php echo number_format($it['price'], 2); ?></strong>
                  </td>
                  <td style="padding:0.85rem 0.5rem;">
                    <span style="font-size:0.85rem; color:var(--text-secondary);"><?php echo htmlspecialchars($it['prep_time'] ?? '10-15 mins'); ?></span>
                  </td>
                  <td style="padding:0.85rem 0.5rem;">
                    <form method="POST" action="admin-menu.php" style="margin:0;">
                      <input type="hidden" name="action" value="toggle">
                      <input type="hidden" name="id" value="<?php echo $it['id']; ?>">
                      <input type="hidden" name="current_status" value="<?php echo $it['is_available']; ?>">
                      <button type="submit" class="badge badge-<?php echo $it['is_available'] ? 'success' : 'danger'; ?>" style="border:none; cursor:pointer; font-size:0.8rem; padding:0.35rem 0.75rem;">
                        <?php echo $it['is_available'] ? '✓ In Stock' : '✕ Sold Out'; ?>
                      </button>
                    </form>
                  </td>
                  <td style="padding:0.85rem 0.5rem; text-align:right;">
                    <div style="display:inline-flex; gap:0.5rem;">
                      <button type="button" onclick='openEditModal(<?php echo json_encode($it); ?>)' class="btn btn-secondary btn-sm" title="Edit Item">
                        <i class="fa-solid fa-pen-to-square text-orange"></i>
                      </button>
                      <form method="POST" action="admin-menu.php" onsubmit="return confirm('Are you sure you want to delete this menu item?');" style="margin:0;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $it['id']; ?>">
                        <button type="submit" class="btn btn-danger btn-sm" title="Delete Item">
                          <i class="fa-solid fa-trash-can"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>

  <!-- Add / Edit Modal -->
  <div id="food-modal" class="modal-overlay">
    <div class="modal-card" style="background:#FFFFFF;">
      <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem;">
        <h3 id="modal-title" style="font-family:var(--font-display); font-size:1.35rem; font-weight:800; color:var(--text-primary);">Add Food Item</h3>
        <button type="button" onclick="closeModal()" style="background:none; border:none; color:var(--text-secondary); font-size:1.2rem; cursor:pointer;">✕</button>
      </div>

      <form id="food-form" method="POST" action="admin-menu.php">
        <input type="hidden" name="action" id="form-action" value="add">
        <input type="hidden" name="id" id="form-id" value="">

        <div class="form-group">
          <label class="form-label">Item Name</label>
          <input type="text" name="name" id="item-name" class="form-control" placeholder="e.g. Crispy Paneer Wrap" required style="background:#FFFFFF;">
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem;">
          <div class="form-group">
            <label class="form-label">Category</label>
            <select name="category_id" id="item-category" class="form-control" required style="background:#FFFFFF;">
              <?php foreach ($categories as $c): ?>
                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Price (₹)</label>
            <input type="number" step="0.5" name="price" id="item-price" class="form-control" placeholder="95.00" required style="background:#FFFFFF;">
          </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem;">
          <div class="form-group">
            <label class="form-label">Dietary Preference</label>
            <select name="is_veg" id="item-veg" class="form-control" style="background:#FFFFFF;">
              <option value="1">🌱 Vegetarian</option>
              <option value="0">🍗 Non-Vegetarian</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Prep Time</label>
            <input type="text" name="prep_time" id="item-prep" class="form-control" placeholder="e.g. 8-10 mins" value="10-15 mins" style="background:#FFFFFF;">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Image URL</label>
          <input type="url" name="image" id="item-image" class="form-control" placeholder="https://images.unsplash.com/..." style="background:#FFFFFF;">
        </div>

        <div class="form-group" style="margin-bottom:1.5rem;">
          <label class="form-label">Description</label>
          <textarea name="description" id="item-desc" class="form-control" rows="3" placeholder="Briefly describe the dish ingredients and flavor profile..." style="background:#FFFFFF;"></textarea>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:0.75rem;">
          <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
          <button type="submit" class="btn btn-primary" id="modal-submit-btn">Save Dish</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openAddModal() {
      document.getElementById('modal-title').textContent = 'Add Food Item';
      document.getElementById('form-action').value = 'add';
      document.getElementById('form-id').value = '';
      document.getElementById('item-name').value = '';
      document.getElementById('item-price').value = '';
      document.getElementById('item-desc').value = '';
      document.getElementById('item-image').value = '';
      document.getElementById('item-veg').value = '1';
      document.getElementById('item-prep').value = '10-15 mins';
      document.getElementById('modal-submit-btn').textContent = 'Add Food Item';
      document.getElementById('food-modal').classList.add('active');
    }

    function openEditModal(item) {
      document.getElementById('modal-title').textContent = 'Edit Food Item: ' + item.name;
      document.getElementById('form-action').value = 'edit';
      document.getElementById('form-id').value = item.id;
      document.getElementById('item-name').value = item.name;
      document.getElementById('item-category').value = item.category_id;
      document.getElementById('item-price').value = item.price;
      document.getElementById('item-desc').value = item.description || '';
      document.getElementById('item-image').value = item.image || '';
      document.getElementById('item-veg').value = item.is_veg;
      document.getElementById('item-prep').value = item.prep_time || '10-15 mins';
      document.getElementById('modal-submit-btn').textContent = 'Save Changes';
      document.getElementById('food-modal').classList.add('active');
    }

    function closeModal() {
      document.getElementById('food-modal').classList.remove('active');
    }
  </script>

</body>
</html>
