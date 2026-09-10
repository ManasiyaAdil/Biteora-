<?php
$page_title = "Manage Categories";
require_once 'includes/connect.php';

if (!isset($_SESSION['admin_sid'])) {
    header("Location: login.php");
    exit;
}

$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim(htmlspecialchars($_POST['name'] ?? ''));
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $icon = trim($_POST['icon'] ?? 'utensils');
        $desc = trim(htmlspecialchars($_POST['description'] ?? ''));

        if (!empty($name)) {
            $stmt = $con->prepare("INSERT INTO categories (name, slug, icon, description) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $slug, $icon, $desc);
            if ($stmt->execute()) {
                header("Location: admin-categories.php?msg=added");
                exit;
            } else {
                header("Location: admin-categories.php?error=duplicate");
                exit;
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        // Check if items are linked
        $check = $con->query("SELECT COUNT(*) as cnt FROM items WHERE category_id = $id AND deleted = 0");
        $linked_count = $check ? $check->fetch_assoc()['cnt'] : 0;

        if ($linked_count > 0) {
            header("Location: admin-categories.php?error=linked_items");
            exit;
        } else {
            $con->query("DELETE FROM categories WHERE id = $id");
            header("Location: admin-categories.php?msg=deleted");
            exit;
        }
    }
}

// Fetch categories with item counts
$categories = [];
$res = $con->query("SELECT c.*, COUNT(i.id) as item_count 
                    FROM categories c 
                    LEFT JOIN items i ON c.id = i.category_id AND i.deleted = 0 
                    GROUP BY c.id 
                    ORDER BY c.id ASC");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $categories[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Categories | Biteora Admin</title>
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
        <li><a href="admin-menu.php" class="btn btn-secondary" style="width:100%; justify-content:flex-start;"><i class="fa-solid fa-bowl-food text-orange" style="width:20px;"></i> Food Menu</a></li>
        <li><a href="admin-categories.php" class="btn btn-primary" style="width:100%; justify-content:flex-start;"><i class="fa-solid fa-layer-group" style="width:20px;"></i> Categories</a></li>
        <li><a href="users.php" class="btn btn-secondary" style="width:100%; justify-content:flex-start;"><i class="fa-solid fa-users text-orange" style="width:20px;"></i> Students</a></li>
        <li style="margin-top:auto; padding-top:1.25rem; border-top:1px solid var(--border-subtle);"><a href="routers/logout.php" class="btn btn-danger" style="width:100%; justify-content:flex-start;"><i class="fa-solid fa-arrow-right-from-bracket" style="width:20px;"></i> Sign Out</a></li>
      </ul>
    </aside>

    <!-- Main Content Area -->
    <main style="flex-grow:1; padding:2rem 2.5rem; overflow-y:auto;">
      <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1.5rem; margin-bottom:2rem;">
        <div>
          <span style="color:var(--brand-primary); font-weight:700; font-size:0.85rem; text-transform:uppercase; letter-spacing:1px;">Menu Organization</span>
          <h1 style="font-family:'Outfit',sans-serif; font-size:2.2rem; font-weight:800; color:var(--text-primary);">Category Management</h1>
        </div>

        <button type="button" onclick="document.getElementById('cat-modal').classList.add('active')" class="btn btn-primary">
          <i class="fa-solid fa-plus"></i> Add Category
        </button>
      </div>

      <?php if ($msg === 'added'): ?>
        <div class="alert alert-success" style="margin-bottom:1.5rem;"><i class="fa-solid fa-circle-check"></i> Category created successfully!</div>
      <?php elseif ($msg === 'deleted'): ?>
        <div class="alert alert-success" style="margin-bottom:1.5rem;"><i class="fa-solid fa-circle-check"></i> Category deleted successfully!</div>
      <?php elseif ($error === 'linked_items'): ?>
        <div class="alert alert-danger" style="margin-bottom:1.5rem;"><i class="fa-solid fa-triangle-exclamation"></i> Cannot delete this category because active menu items are assigned to it.</div>
      <?php endif; ?>

      <div class="cb-table-wrapper">
        <table class="cb-table">
          <thead>
            <tr>
              <th>Icon</th>
              <th>Category Name</th>
              <th>Slug</th>
              <th>Description</th>
              <th>Linked Food Items</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($categories as $cat): ?>
              <tr>
                <td>
                  <div style="width:40px; height:40px; border-radius:10px; background:var(--brand-light); color:var(--brand-primary); display:flex; align-items:center; justify-content:center; font-size:1.15rem;">
                    <i class="fa-solid fa-<?php echo htmlspecialchars($cat['icon'] ?: 'utensils'); ?>"></i>
                  </div>
                </td>
                <td><strong style="color:var(--text-primary);"><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                <td><code style="color:var(--brand-primary); background:var(--bg-surface-2); padding:2px 6px; border-radius:4px; font-weight:600;"><?php echo htmlspecialchars($cat['slug']); ?></code></td>
                <td><span style="color:var(--text-secondary); font-size:0.85rem;"><?php echo htmlspecialchars($cat['description'] ?? ''); ?></span></td>
                <td><span class="badge badge-indigo"><?php echo $cat['item_count']; ?> items</span></td>
                <td>
                  <form method="POST" action="admin-categories.php" onsubmit="return confirm('Are you sure?');" style="margin:0;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                    <button type="submit" class="btn btn-danger btn-sm" <?php echo ($cat['item_count'] > 0) ? 'title="Cannot delete when items are linked"' : ''; ?>>
                      <i class="fa-solid fa-trash-can"></i>
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>

  <!-- Add Category Modal -->
  <div id="cat-modal" class="modal-overlay">
    <div class="modal-card">
      <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem;">
        <h3 style="font-family:'Outfit',sans-serif; font-size:1.35rem; font-weight:800; color:var(--text-primary);">Add New Category</h3>
        <button type="button" onclick="document.getElementById('cat-modal').classList.remove('active')" style="background:none; border:none; color:#94A3B8; font-size:1.2rem; cursor:pointer;">✕</button>
      </div>

      <form method="POST" action="admin-categories.php">
        <input type="hidden" name="action" value="add">

        <div class="form-group">
          <label class="form-label">Category Name</label>
          <input type="text" name="name" class="form-control" placeholder="e.g. Italian Platters" required>
        </div>

        <div class="form-group">
          <label class="form-label">FontAwesome Icon Class (e.g. utensils, pizza-slice, mug-hot, cookie)</label>
          <input type="text" name="icon" class="form-control" placeholder="pizza-slice" required value="utensils">
        </div>

        <div class="form-group" style="margin-bottom:1.5rem;">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="2" placeholder="Category highlights..."></textarea>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:0.75rem;">
          <button type="button" onclick="document.getElementById('cat-modal').classList.remove('active')" class="btn btn-secondary">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Category</button>
        </div>
      </form>
    </div>
  </div>

  <script src="js/campusbite.js"></script>
</body>
</html>
