<?php
$page_title = 'Menu Management';
include 'includes/header.php';

// Generate CSRF token for all forms on this page
$csrf_token = generate_csrf_token();

$errors = [];
$success = '';

// -- C.R.U.D. LOGIC --

// CREATE Menu Item
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_menu_item'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) { die('Invalid CSRF token.'); }
    $label = trim($_POST['label']);
    $link = trim($_POST['link']);
    $location = $_POST['location'];
    $sort_order = (int)$_POST['sort_order'];

    if (!empty($label) && !empty($link) && in_array($location, ['header', 'footer_company', 'footer_support'])) {
        $stmt = $conn->prepare("INSERT INTO menus (label, link, location, sort_order) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $label, $link, $location, $sort_order);
        if ($stmt->execute()) {
            $success = "Menu item created successfully.";
        } else {
            $errors[] = "Failed to create menu item.";
        }
        $stmt->close();
    } else {
        $errors[] = "Label, Link, and a valid Location are required.";
    }
}

// UPDATE Menu Item
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_menu_item'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) { die('Invalid CSRF token.'); }
    $menu_id = (int)$_POST['menu_id'];
    $label = trim($_POST['label']);
    $link = trim($_POST['link']);
    $location = $_POST['location'];
    $sort_order = (int)$_POST['sort_order'];

    if (!empty($label) && !empty($link) && in_array($location, ['header', 'footer_company', 'footer_support'])) {
        $stmt = $conn->prepare("UPDATE menus SET label=?, link=?, location=?, sort_order=? WHERE id=?");
        $stmt->bind_param("sssii", $label, $link, $location, $sort_order, $menu_id);
        if ($stmt->execute()) {
            $success = "Menu item updated successfully.";
        } else {
            $errors[] = "Failed to update menu item.";
        }
        $stmt->close();
    } else {
        $errors[] = "Label, Link, and a valid Location are required.";
    }
}

// DELETE Menu Item
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_menu_item'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) { die('Invalid CSRF token.'); }
    $menu_id = (int)$_POST['menu_id'];
    $stmt = $conn->prepare("DELETE FROM menus WHERE id = ?");
    $stmt->bind_param("i", $menu_id);
    if ($stmt->execute()) {
        $success = "Menu item deleted successfully.";
    } else {
        $errors[] = "Failed to delete menu item.";
    }
    $stmt->close();
}

// READ Menu Items
$header_menus = [];
$footer_company_menus = [];
$footer_support_menus = [];
$sql = "SELECT id, label, link, location, sort_order FROM menus ORDER BY location, sort_order ASC";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if ($row['location'] == 'header') {
            $header_menus[] = $row;
        } elseif ($row['location'] == 'footer_company') {
            $footer_company_menus[] = $row;
        } elseif ($row['location'] == 'footer_support') {
            $footer_support_menus[] = $row;
        }
    }
    $stmt->close();
}
?>

<div class="row mb-3">
    <div class="col">
        <h3 class="m-0">Menu Management</h3>
    </div>
    <div class="col text-end">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMenuItemModal">
            <i class="fas fa-plus"></i> Add New Menu Item
        </button>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $error): ?><p><?php echo $error; ?></p><?php endforeach; ?>
    </div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success">
        <p><?php echo $success; ?></p>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Header Menu -->
    <div class="col-md-12">
        <h4>Header Menu</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>Label</th>
                        <th>Link</th>
                        <th>Order</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($header_menus as $menu): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($menu['label']); ?></td>
                        <td><?php echo htmlspecialchars($menu['link']); ?></td>
                        <td><?php echo $menu['sort_order']; ?></td>
                        <td>
                            <div class="btn-group">
                                <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#editMenuItemModal<?php echo $menu['id']; ?>" title="Edit Menu Item"><i class="fas fa-edit"></i></button>
                                <form action="menus.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this menu item?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="menu_id" value="<?php echo $menu['id']; ?>">
                                    <button type="submit" name="delete_menu_item" class="btn btn-danger btn-sm" title="Delete Menu Item"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="row mt-4">
    <!-- Footer Company Menu -->
    <div class="col-md-6">
        <h4>Footer 'Company' Menu</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>Label</th>
                        <th>Link</th>
                        <th>Order</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($footer_company_menus as $menu): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($menu['label']); ?></td>
                        <td><?php echo htmlspecialchars($menu['link']); ?></td>
                        <td><?php echo $menu['sort_order']; ?></td>
                        <td>
                            <div class="btn-group">
                                <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#editMenuItemModal<?php echo $menu['id']; ?>" title="Edit Menu Item"><i class="fas fa-edit"></i></button>
                                <form action="menus.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this menu item?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="menu_id" value="<?php echo $menu['id']; ?>">
                                    <button type="submit" name="delete_menu_item" class="btn btn-danger btn-sm" title="Delete Menu Item"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Footer Support Menu -->
    <div class="col-md-6">
        <h4>Footer 'Support' Menu</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>Label</th>
                        <th>Link</th>
                        <th>Order</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($footer_support_menus as $menu): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($menu['label']); ?></td>
                        <td><?php echo htmlspecialchars($menu['link']); ?></td>
                        <td><?php echo $menu['sort_order']; ?></td>
                        <td>
                            <div class="btn-group">
                                <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#editMenuItemModal<?php echo $menu['id']; ?>" title="Edit Menu Item"><i class="fas fa-edit"></i></button>
                                <form action="menus.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this menu item?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="menu_id" value="<?php echo $menu['id']; ?>">
                                    <button type="submit" name="delete_menu_item" class="btn btn-danger btn-sm" title="Delete Menu Item"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<!-- Add Menu Item Modal -->
<div class="modal fade" id="addMenuItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="menus.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Menu Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="label" class="form-label">Label</label>
                        <input type="text" class="form-control" id="label" name="label" required>
                    </div>
                    <div class="mb-3">
                        <label for="link" class="form-label">Link</label>
                        <input type="text" class="form-control" id="link" name="link" required>
                        <small class="form-text text-muted">For internal pages, use relative paths like <code>page.php?slug=about-us</code>. For external links, use full URLs like <code>https://example.com</code>.</small>
                    </div>
                    <div class="mb-3">
                        <label for="location" class="form-label">Location</label>
                        <select class="form-select" id="location" name="location" required>
                            <option value="header" selected>Header (Main Nav)</option>
                            <option value="footer_company">Footer (Company Column)</option>
                            <option value="footer_support">Footer (Support Column)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="sort_order" class="form-label">Sort Order</label>
                        <input type="number" class="form-control" id="sort_order" name="sort_order" value="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="add_menu_item" class="btn btn-primary">Save Menu Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Menu Item Modals -->
<?php
$all_menus = array_merge($header_menus, $footer_company_menus, $footer_support_menus);
foreach ($all_menus as $menu):
?>
<div class="modal fade" id="editMenuItemModal<?php echo $menu['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="menus.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="menu_id" value="<?php echo $menu['id']; ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Menu Item: <?php echo htmlspecialchars($menu['label']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="label_<?php echo $menu['id']; ?>" class="form-label">Label</label>
                        <input type="text" class="form-control" id="label_<?php echo $menu['id']; ?>" name="label" value="<?php echo htmlspecialchars($menu['label']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="link_<?php echo $menu['id']; ?>" class="form-label">Link</label>
                        <input type="text" class="form-control" id="link_<?php echo $menu['id']; ?>" name="link" value="<?php echo htmlspecialchars($menu['link']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="location_<?php echo $menu['id']; ?>" class="form-label">Location</label>
                        <select class="form-select" id="location_<?php echo $menu['id']; ?>" name="location" required>
                            <option value="header" <?php if($menu['location'] == 'header') echo 'selected'; ?>>Header (Main Nav)</option>
                            <option value="footer_company" <?php if($menu['location'] == 'footer_company') echo 'selected'; ?>>Footer (Company Column)</option>
                            <option value="footer_support" <?php if($menu['location'] == 'footer_support') echo 'selected'; ?>>Footer (Support Column)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="sort_order_<?php echo $menu['id']; ?>" class="form-label">Sort Order</label>
                        <input type="number" class="form-control" id="sort_order_<?php echo $menu['id']; ?>" name="sort_order" value="<?php echo $menu['sort_order']; ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="edit_menu_item" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php include 'includes/footer.php'; ?>
