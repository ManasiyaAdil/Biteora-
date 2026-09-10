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
  <title>Manage Categories | CampusBite Admin</title>
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2310B981'><path d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9v-2h2v2zm0-4H9V7h2v5z'/></svg>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/campusbite.css">
</head>
<body style="background:#070D1E;">

  <div class="admin-layout">
    <!-- Admin Sidebar -->
    <aside class="admin-sidebar">
      <div class="cb-logo" style="margin-bottom:1.5rem; padding:0 0.5rem;">
        <div class="cb-logo-icon">
          <i class="fa-solid fa-utensils"></i>
        </div>
        <span>Campus<span class="text-emerald">Bite</span></span>
      </div>

      <ul class="admin-nav">
        <li><a href="admin-page.php" class="admin-nav-link"><i class="fa-solid fa-chart-pie"></i> Dashboard & Analytics</a></li>
        <li><a href="all-orders.php" class="admin-nav-link"><i class="fa-solid fa-receipt"></i> All Orders Queue</a></li>
        <li><a href="admin-menu.php" class="admin-nav-link"><i class="fa-solid fa-bowl-food"></i> Food Menu CRUD</a></li>
        <li><a href="admin-categories.php" class="admin-nav-link active"><i class="fa-solid fa-layer-group"></i> Category Manager</a></li>
        <li><a href="users.php" class="admin-nav-link"><i class="fa-solid fa-users"></i> Student Accounts</a></li>
        <li><a href="all-tickets.php" class="admin-nav-link"><i class="fa-solid fa-headset"></i> Support & Tickets</a></li>
        <li style="margin-top:auto; padding-top:1.5rem; border-top:1px solid var(--border-subtle);"><a href="routers/logout.php" class="admin-nav-link" style="color:#F43F5E;"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sign Out</a></li>
      </ul>
    </aside>

    <!-- Main Content Area -->
    <main class="admin-main">
      <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1.5rem; margin-bottom:2rem;">
        <div>
          <span style="color:#10B981; font-weight:700; font-size:0.85rem; text-transform:uppercase; letter-spacing:1px;">Menu Organization</span>
          <h1 style="font-family:'Outfit',sans-serif; font-size:2.2rem; font-weight:800; color:#F8FAFC;">Category Management</h1>
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
                  <div style="width:40px; height:40px; border-radius:10px; background:rgba(16,185,129,0.12); color:#10B981; display:flex; align-items:center; justify-content:center; font-size:1.15rem;">
                    <i class="fa-solid fa-<?php echo htmlspecialchars($cat['icon'] ?: 'utensils'); ?>"></i>
                  </div>
                </td>
                <td><strong style="color:#F8FAFC;"><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                <td><code style="color:#34D399; background:rgba(255,255,255,0.04); padding:2px 6px; border-radius:4px;"><?php echo htmlspecialchars($cat['slug']); ?></code></td>
                <td><span style="color:#94A3B8; font-size:0.85rem;"><?php echo htmlspecialchars($cat['description'] ?? ''); ?></span></td>
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
        <h3 style="font-family:'Outfit',sans-serif; font-size:1.35rem; font-weight:800; color:#F8FAFC;">Add New Category</h3>
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
