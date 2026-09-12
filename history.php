<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'db.php';

// Fetch summary metrics
$total_revenue_res = $conn->query("SELECT SUM(total) as revenue, COUNT(*) as total_orders FROM orders WHERE status != 'CANCELLED'");
$metrics = $total_revenue_res->fetch_assoc();

$vendor_stats_res = $conn->query("SELECT source, COUNT(*) as count, SUM(total) as revenue FROM orders WHERE status != 'CANCELLED' GROUP BY source");

// Search & Filter parameters
$search = trim($_GET['search'] ?? '');
$vendor_filter = trim($_GET['vendor'] ?? '');
$status_filter = trim($_GET['status'] ?? '');

// Build dynamic SQL
$sql = "SELECT * FROM orders WHERE 1=1";
$params = [];
$types = "";

if ($search !== '') {
    $sql .= " AND (order_id LIKE ? OR customer_name LIKE ? OR items LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

if ($vendor_filter !== '') {
    $sql .= " AND source = ?";
    $params[] = $vendor_filter;
    $types .= "s";
}

if ($status_filter !== '') {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$sql .= " ORDER BY created_at DESC LIMIT 100";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order History & Analytics - KDS</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f5f7; margin: 0; padding: 20px; }
        .container { max-width: 1100px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #1e293b; }
        .back-btn { background: #64748b; color: white; padding: 10px 16px; border-radius: 6px; text-decoration: none; font-weight: bold; }
        
        /* Analytics Grid */
        .analytics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .stat-card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.06); }
        .stat-card h3 { margin: 0 0 10px 0; font-size: 13px; color: #64748b; text-transform: uppercase; }
        .stat-card .val { font-size: 24px; font-weight: bold; color: #0f172a; }

        /* Vendor Breakdown */
        .vendor-breakdown { background: #fff; padding: 15px; border-radius: 8px; margin-bottom: 25px; box-shadow: 0 2px 6px rgba(0,0,0,0.06); }
        .vendor-list { display: flex; gap: 20px; flex-wrap: wrap; margin-top: 10px; }
        .vendor-item { font-size: 14px; color: #334155; }
        .vendor-badge { padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; color: white; display: inline-block; }
        .badge-UBER_EATS { background: #000; }
        .badge-JUST_EAT { background: #ff8000; }
        .badge-DELIVEROO { background: #00ccbc; }

        /* Filter Form */
        .filter-card { background: #fff; padding: 15px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 6px rgba(0,0,0,0.06); }
        .filter-form { display: flex; gap: 10px; flex-wrap: wrap; }
        .filter-form input, .filter-form select { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; }
        .filter-form input[type="text"] { flex-grow: 1; }
        .filter-form button { background: #00b14f; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: bold; cursor: pointer; }

        /* Orders Table */
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,0.06); }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        th { background: #f8fafc; color: #475569; font-weight: bold; }
        tr:hover { background: #f1f5f9; }
        .status-tag { padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; }
        .status-PENDING { background: #fef3c7; color: #92400e; }
        .status-COMPLETED { background: #dcfce7; color: #166534; }
        .status-CANCELLED { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Order History & Analytics</h1>
            <a href="index.php" class="back-btn">Back to Dashboard</a>
        </div>

        <!-- Analytics Cards -->
        <div class="analytics-grid">
            <div class="stat-card">
                <h3>Total Revenue</h3>
                <div class="val">£<?= number_format($metrics['revenue'] ?? 0, 2) ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Orders</h3>
                <div class="val"><?= number_format($metrics['total_orders'] ?? 0) ?></div>
            </div>
            <div class="stat-card">
                <h3>Avg Order Value</h3>
                <div class="val">
                    £<?= $metrics['total_orders'] > 0 ? number_format($metrics['revenue'] / $metrics['total_orders'], 2) : '0.00' ?>
                </div>
            </div>
        </div>

        <!-- Vendor Breakdown -->
        <div class="vendor-breakdown">
            <strong>Revenue Breakdown by Vendor:</strong>
            <div class="vendor-list">
                <?php foreach ($vendor_stats_res as $v): ?>
                    <div class="vendor-item">
                        <span class="vendor-badge badge-<?= $v['source'] ?>"><?= htmlspecialchars($v['source']) ?></span>
                        <?= $v['count'] ?> orders — <strong>£<?= number_format($v['revenue'], 2) ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Search & Filter -->
        <div class="filter-card">
            <form method="GET" class="filter-form">
                <input type="text" name="search" placeholder="Search Order ID, Customer, or Items..." value="<?= htmlspecialchars($search) ?>">
                <select name="vendor">
                    <option value="">All Vendors</option>
                    <option value="UBER_EATS" <?= $vendor_filter === 'UBER_EATS' ? 'selected' : '' ?>>Uber Eats</option>
                    <option value="JUST_EAT" <?= $vendor_filter === 'JUST_EAT' ? 'selected' : '' ?>>Just Eat</option>
                    <option value="DELIVEROO" <?= $vendor_filter === 'DELIVEROO' ? 'selected' : '' ?>>Deliveroo</option>
                </select>
                <select name="status">
                    <option value="">All Statuses</option>
                    <option value="PENDING" <?= $status_filter === 'PENDING' ? 'selected' : '' ?>>Pending</option>
                    <option value="COMPLETED" <?= $status_filter === 'COMPLETED' ? 'selected' : '' ?>>Completed</option>
                    <option value="CANCELLED" <?= $status_filter === 'CANCELLED' ? 'selected' : '' ?>>Cancelled</option>
                </select>
                <button type="submit">Filter</button>
            </form>
        </div>

        <!-- Table -->
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Vendor</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Date / Time</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="7" style="text-align:center; padding: 20px; color: #94a3b8;">No orders found matching criteria.</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><strong>#<?= htmlspecialchars($o['order_id']) ?></strong></td>
                            <td><span class="vendor-badge badge-<?= $o['source'] ?>"><?= htmlspecialchars($o['source']) ?></span></td>
                            <td><?= htmlspecialchars($o['customer_name']) ?></td>
                            <td><?= htmlspecialchars($o['items']) ?></td>
                            <td>£<?= number_format($o['total'], 2) ?></td>
                            <td><span class="status-tag status-<?= $o['status'] ?>"><?= htmlspecialchars($o['status']) ?></span></td>
                            <td><?= date('Y-m-d H:i', strtotime($o['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
