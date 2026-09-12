<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'db.php';

// Fetch store settings for name
$setting_res = $conn->query("SELECT store_name FROM settings WHERE id = 1");
$setting = $setting_res ? $setting_res->fetch_assoc() : [];
$store_name = $setting['store_name'] ?? 'One Kitchen Solution';

$success_msg = '';
$error_msg = '';

// Handle form submission for adding/updating menu items
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_item') {
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? 'General');
        $price = floatval($_POST['price'] ?? 0);
        $ubereats = trim($_POST['ubereats_uuid'] ?? '');
        $justeat = trim($_POST['justeat_uuid'] ?? '');
        $deliveroo = trim($_POST['deliveroo_uuid'] ?? '');
        $item_id = intval($_POST['item_id'] ?? 0);

        if ($name === '') {
            $error_msg = "Item name cannot be left blank!";
        } else {
            if ($item_id > 0) {
                // Update existing item
                $stmt = $conn->prepare("UPDATE menu_items SET name = ?, category = ?, price = ?, ubereats_uuid = ?, justeat_uuid = ?, deliveroo_uuid = ? WHERE id = ?");
                $stmt->bind_param("ssdissi", $name, $category, $price, $ubereats, $justeat, $deliveroo, $item_id);
                if ($stmt->execute()) {
                    $success_msg = "Menu item updated successfully!";
                } else {
                    $error_msg = "Failed to update item.";
                }
            } else {
                // Insert new item with a unique UUID
                $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000,
                    mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
                
                $stmt = $conn->prepare("INSERT INTO menu_items (uuid, name, category, price, ubereats_uuid, justeat_uuid, deliveroo_uuid) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssdsss", $uuid, $name, $category, $price, $ubereats, $justeat, $deliveroo);
                if ($stmt->execute()) {
                    $success_msg = "New menu item added to the vault!";
                } else {
                    $error_msg = "Failed to add menu item.";
                }
            }
        }
    } elseif ($action === 'delete_item') {
        $item_id = intval($_POST['item_id'] ?? 0);
        if ($item_id > 0) {
            $stmt = $conn->prepare("DELETE FROM menu_items WHERE id = ?");
            $stmt->bind_param("i", $item_id);
            $stmt->execute();
            $success_msg = "Menu item removed.";
        }
    }
}

// Fetch all menu items ordered by category then name
$menu_res = $conn->query("SELECT * FROM menu_items ORDER BY category ASC, name ASC");
$menu_items = $menu_res ? $menu_res->fetch_all(MYSQLI_ASSOC) : [];

// Fetch distinct categories for suggestions
$cat_res = $conn->query("SELECT DISTINCT category FROM menu_items ORDER BY category ASC");
$categories = [];
if ($cat_res) {
    while ($row = $cat_res->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}
if (empty($categories)) {
    $categories = ['Drinks', 'Mains', 'Sides', 'Desserts'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($store_name) ?> - Menu & Aggregator Mapping</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #334155; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #38bdf8; font-size: 24px; }
        .back-btn { background: #334155; color: white; padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 14px; }
        .back-btn:hover { background: #475569; }

        .alert-success { background: #dcfce7; color: #166534; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; }
        .alert-error { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; }

        .card { background: #1e293b; padding: 20px; border-radius: 8px; margin-bottom: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); border: 1px solid #334155; }
        .card h3 { margin-top: 0; color: #38bdf8; }

        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-size: 13px; color: #94a3b8; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { padding: 8px 12px; background: #0f172a; color: #f8fafc; border: 1px solid #334155; border-radius: 6px; }
        
        .btn-primary { background: #22c55e; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 15px; }
        .btn-primary:hover { background: #16a34a; }

        table { width: 100%; border-collapse: collapse; background: #1e293b; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.3); border: 1px solid #334155; margin-top: 15px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #334155; font-size: 14px; }
        th { background: #0f172a; color: #38bdf8; font-weight: bold; }
        tr:hover { background: #253347; }

        .category-badge { background: #334155; color: #38bdf8; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; display: inline-block; }
        .uuid-code { font-family: monospace; font-size: 12px; color: #cbd5e1; background: #0f172a; padding: 3px 6px; border-radius: 4px; border: 1px solid #334155; }
        .actions-cell { display: flex; gap: 8px; }
        .btn-edit { background: #0284c7; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .btn-delete { background: #ef4444; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .btn-edit:hover { background: #0369a1; }
        .btn-delete:hover { background: #dc2626; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?= htmlspecialchars($store_name) ?> — Menu & Aggregator Mappings</h1>
            <a href="index.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <?php if (!empty($success_msg)): ?>
            <div class="alert-success"><?= htmlspecialchars($success_msg) ?></div>
        <?php endif; ?>
        <?php if (!empty($error_msg)): ?>
            <div class="alert-error"><?= htmlspecialchars($error_msg) ?></div>
        <?php endif; ?>

        <!-- Add/Edit Form Card -->
        <div class="card">
            <h3 id="formTitle"><i class="fa-solid fa-plus-circle"></i> Add New Menu Item</h3>
            <form method="POST" id="menuForm">
                <input type="hidden" name="action" value="save_item">
                <input type="hidden" name="item_id" id="itemId" value="">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Parent Category *</label>
                        <input type="text" name="category" id="itemCategory" list="catList" required placeholder="e.g. Drinks, Mains">
                        <datalist id="catList">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="form-group">
                        <label>Item Name *</label>
                        <input type="text" name="name" id="itemName" required placeholder="e.g. Butterbeer">
                    </div>
                    <div class="form-group">
                        <label>Price (£) *</label>
                        <input type="number" step="0.01" name="price" id="itemPrice" required placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label>Uber Eats UUID</label>
                        <input type="text" name="ubereats_uuid" id="itemUber" placeholder="Mapping ID">
                    </div>
                    <div class="form-group">
                        <label>Just Eat UUID</label>
                        <input type="text" name="justeat_uuid" id="itemJust" placeholder="Mapping ID">
                    </div>
                    <div class="form-group">
                        <label>Deliveroo UUID</label>
                        <input type="text" name="deliveroo_uuid" id="itemDeliveroo" placeholder="Mapping ID">
                    </div>
                </div>
                <button type="submit" class="btn-primary" id="submitBtn"><i class="fa-solid fa-save"></i> Save Item</button>
                <button type="button" onclick="resetForm()" id="cancelBtn" style="display:none; background: #64748b; color: white; border: none; padding: 10px 15px; border-radius: 6px; cursor: pointer; margin-left: 10px;">Cancel Edit</button>
            </form>
        </div>

        <!-- Menu Table -->
        <div class="card">
            <h3><i class="fa-solid fa-utensils"></i> Active Alliance Menu Inventory</h3>
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Item Name</th>
                        <th>Price</th>
                        <th>Internal UUID</th>
                        <th>Uber Eats UUID</th>
                        <th>Just Eat UUID</th>
                        <th>Deliveroo UUID</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($menu_items)): ?>
                        <tr><td colspan="8" style="text-align: center; color: #94a3b8; padding: 20px;">No menu items logged yet. Add your items above!</td></tr>
                    <?php else: ?>
                        <?php foreach ($menu_items as $item): ?>
                            <tr>
                                <td><span class="category-badge"><?= htmlspecialchars($item['category']) ?></span></td>
                                <td><strong><?= htmlspecialchars($item['name']) ?></strong></td>
                                <td style="color: #22c55e; font-weight: bold;">£<?= number_format($item['price'], 2) ?></td>
                                <td><span class="uuid-code"><?= htmlspecialchars($item['uuid']) ?></span></td>
                                <td><span class="uuid-code"><?= htmlspecialchars($item['ubereats_uuid'] ?: '—') ?></span></td>
                                <td><span class="uuid-code"><?= htmlspecialchars($item['justeat_uuid'] ?: '—') ?></span></td>
                                <td><span class="uuid-code"><?= htmlspecialchars($item['deliveroo_uuid'] ?: '—') ?></span></td>
                                <td>
                                    <div class="actions-cell">
                                        <button type="button" class="btn-edit" onclick='editItem(<?= json_encode($item) ?>)'><i class="fa-solid fa-pen"></i></button>
                                        <form method="POST" onsubmit="return confirm('Are you sure you wish to banish this menu item?');" style="margin:0;">
                                            <input type="hidden" name="action" value="delete_item">
                                            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                            <button type="submit" class="btn-delete"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    function editItem(item) {
        document.getElementById('itemId').value = item.id;
        document.getElementById('itemCategory').value = item.category || 'General';
        document.getElementById('itemName').value = item.name;
        document.getElementById('itemPrice').value = item.price;
        document.getElementById('itemUber').value = item.ubereats_uuid || '';
        document.getElementById('itemJust').value = item.justeat_uuid || '';
        document.getElementById('itemDeliveroo').value = item.deliveroo_uuid || '';
        
        document.getElementById('formTitle').innerHTML = '<i class="fa-solid fa-pen-to-square"></i> Edit Menu Item: ' + item.name;
        document.getElementById('submitBtn').innerHTML = '<i class="fa-solid fa-rotate"></i> Update Item';
        document.getElementById('cancelBtn').style.display = 'inline-block';
        window.scrollTo({top: 0, behavior: 'smooth'});
    }

    function resetForm() {
        document.getElementById('menuForm').reset();
        document.getElementById('itemId').value = '';
        document.getElementById('formTitle').innerHTML = '<i class="fa-solid fa-plus-circle"></i> Add New Menu Item';
        document.getElementById('submitBtn').innerHTML = '<i class="fa-solid fa-save"></i> Save Item';
        document.getElementById('cancelBtn').style.display = 'none';
    }
    </script>
</body>
</html>
