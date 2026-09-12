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

// Fetch summary metrics
$total_revenue_res = $conn->query("SELECT SUM(total) as revenue, COUNT(*) as total_orders FROM orders WHERE status != 'CANCELLED'");
$metrics = $total_revenue_res ? $total_revenue_res->fetch_assoc() : ['revenue' => 0, 'total_orders' => 0];

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($store_name) ?> - Order History & Analytics</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #334155; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #38bdf8; font-size: 24px; }
        .back-btn { background: #334155; color: white; padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 14px; }
        .back-btn:hover { background: #475569; }
        
        /* Analytics Grid */
        .analytics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .stat-card { background: #1e293b; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); border: 1px solid #334155; }
        .stat-card h3 { margin: 0 0 10px 0; font-size: 13px; color: #94a3b8; text-transform: uppercase; }
        .stat-card .val { font-size: 24px; font-weight: bold; color: #f8fafc; }

        /* Vendor Breakdown */
        .vendor-breakdown { background: #1e293b; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); border: 1px solid #334155; color: #f8fafc; }
        .vendor-list { display: flex; gap: 20px; flex-wrap: wrap; margin-top: 10px; }
        .vendor-item { font-size: 14px; color: #cbd5e1; display: flex; align-items: center; gap: 8px; }
        
        .vendor-badge { padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; color: white; display: inline-block; }
        .badge-UBER_EATS { background: #000; border: 1px solid #475569; }
        .badge-JUST_EAT { background: #ff8000; }
        .badge-DELIVEROO { background: #00ccbc; }
        .badge-DOORDASH { background: #ff3008; }

        /* Filter Form */
        .filter-card { background: #1e293b; padding: 15px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); border: 1px solid #334155; }
        .filter-form { display: flex; gap: 10px; flex-wrap: wrap; }
        .filter-form input, .filter-form select { padding: 8px 12px; background: #0f172a; color: #f8fafc; border: 1px solid #334155; border-radius: 6px; }
        .filter-form input[type="text"] { flex-grow: 1; }
        .filter-form button { background: #22c55e; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .filter-form button:hover { background: #16a34a; }

        /* Orders Table */
        table { width: 100%; border-collapse: collapse; background: #1e293b; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.3); border: 1px solid #334155; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #334155; font-size: 14px; }
        th { background: #0f172a; color: #38bdf8; font-weight: bold; }
        tr:hover { background: #253347; }
        
        .status-tag { padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; display: inline-block; }
        .status-PENDING { background: #fef3c7; color: #92400e; }
        .status-COMPLETED { background: #dcfce7; color: #166534; }
        .status-CANCELLED { background: #fee2e2; color: #991b1b; }

        .order-id-link { color: #38bdf8; text-decoration: none; font-weight: bold; cursor: pointer; }
        .order-id-link:hover { text-decoration: underline; }

        .btn-print-sm { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; }
        .btn-print-sm:hover { background: #bae6fd; }

        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 100; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); align-items: center; justify-content: center; }
        .modal-content { background: #1e293b; color: #f8fafc; padding: 25px; border-radius: 8px; width: 450px; max-width: 90%; border: 1px solid #334155; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; padding-bottom: 10px; margin-bottom: 15px; }
        .modal-header h3 { margin: 0; color: #38bdf8; }
        .close-btn { background: none; border: none; color: #94a3b8; font-size: 20px; cursor: pointer; }
        .close-btn:hover { color: #fff; }
        .detail-row { margin-bottom: 12px; font-size: 14px; }
        .detail-row strong { color: #94a3b8; display: inline-block; width: 110px; }

        /* Print Media Query */
        @media print {
            body * { visibility: hidden; }
            .printable-ticket, .printable-ticket * { visibility: visible; }
            .printable-ticket { display: block !important; position: absolute; left: 0; top: 0; width: 100%; background: white !important; color: black !important; padding: 20px; box-shadow: none !important; border: none !important; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?= htmlspecialchars($store_name) ?> — Order History & Analytics</h1>
            <a href="index.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
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
                <?php if ($vendor_stats_res): ?>
                    <?php while ($v = $vendor_stats_res->fetch_assoc()): ?>
                        <div class="vendor-item">
                            <span class="vendor-badge badge-<?= htmlspecialchars($v['source']) ?>"><?= htmlspecialchars($v['source']) ?></span>
                            <?= (int)$v['count'] ?> orders — <strong>£<?= number_format($v['revenue'], 2) ?></strong>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
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
                    <option value="DOORDASH" <?= $vendor_filter === 'DOORDASH' ? 'selected' : '' ?>>DoorDash</option>
                </select>
                <select name="status">
                    <option value="">All Statuses</option>
                    <option value="PENDING" <?= $status_filter === 'PENDING' ? 'selected' : '' ?>>Pending</option>
                    <option value="COMPLETED" <?= $status_filter === 'COMPLETED' ? 'selected' : '' ?>>Completed</option>
                    <option value="CANCELLED" <?= $status_filter === 'CANCELLED' ? 'selected' : '' ?>>Cancelled</option>
                </select>
                <button type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
            </form>
        </div>

        <!-- Table -->
        <table>
            <thead>
                <tr>
                    <th style="width: 70px; text-align: center;">Print</th>
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
                    <tr><td colspan="8" style="text-align:center; padding: 25px; color: #94a3b8;">No orders found matching criteria.</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): 
                        $orderJson = htmlspecialchars(json_encode($o), ENT_QUOTES, 'UTF-8');
                    ?>
                        <tr>
                            <td style="text-align: center;">
                                <button type="button" onclick="printOrder(<?= $orderJson ?>)" class="btn-print-sm" title="Print Ticket">
                                    <i class="fa-solid fa-print"></i>
                                </button>
                            </td>
                            <td>
                                <a class="order-id-link" onclick="openDetailsModal(<?= $orderJson ?>)">
                                    #<?= htmlspecialchars($o['order_id']) ?>
                                </a>
                            </td>
                            <td><span class="vendor-badge badge-<?= htmlspecialchars($o['source']) ?>"><?= htmlspecialchars($o['source']) ?></span></td>
                            <td><?= htmlspecialchars($o['customer_name']) ?></td>
                            <td><?= nl2br(htmlspecialchars($o['items'])) ?></td>
                            <td>£<?= number_format($o['total'], 2) ?></td>
                            <td><span class="status-tag status-<?= htmlspecialchars($o['status']) ?>"><?= htmlspecialchars($o['status']) ?></span></td>
                            <td><?= date('Y-m-d H:i', strtotime($o['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Order Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Order Details: <span id="modalOrderId"></span></h3>
                <button type="button" class="close-btn" onclick="closeDetailsModal()">&times;</button>
            </div>
            <div class="detail-row"><strong>Vendor:</strong> <span id="modalVendor"></span></div>
            <div class="detail-row"><strong>Customer:</strong> <span id="modalCustomer"></span></div>
            <div class="detail-row"><strong>Status:</strong> <span id="modalStatus"></span></div>
            <div class="detail-row"><strong>Time:</strong> <span id="modalTime"></span></div>
            <div class="detail-row" style="margin-top: 15px;"><strong>Items Ordered:</strong></div>
            <div id="modalItems" style="background: #0f172a; padding: 12px; border-radius: 6px; white-space: pre-line; color: #cbd5e1; margin-top: 5px;"></div>
            <div class="detail-row" style="margin-top: 15px; font-size: 16px;"><strong>Total Amount:</strong> <span id="modalTotal" style="color: #22c55e; font-weight: bold;"></span></div>
            
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" onclick="printModalContent()" style="background: #0284c7; color: white; border: none; padding: 8px 14px; border-radius: 6px; font-weight: bold; cursor: pointer;"><i class="fa-solid fa-print"></i> Print Receipt</button>
                <button type="button" onclick="closeDetailsModal()" style="background: #64748b; color: white; border: none; padding: 8px 14px; border-radius: 6px; cursor: pointer;">Close</button>
            </div>
        </div>
    </div>

    <!-- Hidden Printable Ticket Template Area -->
    <div id="printableArea" class="printable-ticket" style="display: none;">
        <h2 style="border-bottom: 2px solid black; padding-bottom: 5px; margin-top: 0;"><?= htmlspecialchars($store_name) ?></h2>
        <p><strong>Order ID:</strong> <span id="printOrderId"></span></p>
        <p><strong>Vendor:</strong> <span id="printVendor"></span></p>
        <p><strong>Customer:</strong> <span id="printCustomer"></span></p>
        <p><strong>Date / Time:</strong> <span id="printTime"></span></p>
        <hr style="border: 1px dashed black;">
        <h3>Items:</h3>
        <p id="printItems" style="font-size: 15px; white-space: pre-line; line-height: 1.4;"></p>
        <hr style="border: 1px dashed black;">
        <p style="font-size: 16px;"><strong>Total:</strong> <span id="printTotal"></span></p>
        <p style="font-size: 12px; text-align: center; margin-top: 30px;">Status: <span id="printStatus"></span></p>
    </div>

    <script>
    function openDetailsModal(order) {
        document.getElementById('modalOrderId').innerText = '#' + order.order_id;
        document.getElementById('modalVendor').innerText = order.source;
        document.getElementById('modalCustomer').innerText = order.customer_name;
        document.getElementById('modalStatus').innerText = order.status;
        document.getElementById('modalTime').innerText = order.created_at;
        document.getElementById('modalItems').innerText = order.items;
        document.getElementById('modalTotal').innerText = '£' + parseFloat(order.total).toFixed(2);
        
        document.getElementById('detailsModal').style.display = 'flex';
    }

    function closeDetailsModal() {
        document.getElementById('detailsModal').style.display = 'none';
    }

    function printOrder(order) {
        document.getElementById('printOrderId').innerText = '#' + order.order_id;
        document.getElementById('printVendor').innerText = order.source;
        document.getElementById('printCustomer').innerText = order.customer_name;
        document.getElementById('printTime').innerText = order.created_at;
        document.getElementById('printItems').innerText = order.items;
        document.getElementById('printTotal').innerText = '£' + parseFloat(order.total).toFixed(2);
        document.getElementById('printStatus').innerText = order.status;

        window.print();
    }

    function printModalContent() {
        document.getElementById('printOrderId').innerText = document.getElementById('modalOrderId').innerText;
        document.getElementById('printVendor').innerText = document.getElementById('modalVendor').innerText;
        document.getElementById('printCustomer').innerText = document.getElementById('modalCustomer').innerText;
        document.getElementById('printTime').innerText = document.getElementById('modalTime').innerText;
        document.getElementById('printItems').innerText = document.getElementById('modalItems').innerText;
        document.getElementById('printTotal').innerText = document.getElementById('modalTotal').innerText;
        document.getElementById('printStatus').innerText = document.getElementById('modalStatus').innerText;

        window.print();
    }
    </script>
</body>
</html>
