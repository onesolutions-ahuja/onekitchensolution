<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'db.php';

// Fetch Store Name
$setting = $conn->query("SELECT store_name, auto_refresh_sec FROM settings WHERE id = 1")->fetch_assoc();
$store_name = $setting['store_name'] ?? 'One Kitchen Hub';
$refresh_rate = ($setting['auto_refresh_sec'] ?? 3) * 1000;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($store_name) ?> - KDS</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f5f7; margin: 0; padding: 0; }
        .navbar { background: #1e293b; color: #fff; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .navbar h1 { margin: 0; font-size: 20px; }
        .nav-links a { color: #cbd5e1; text-decoration: none; margin-left: 15px; font-weight: bold; }
        .nav-links a:hover { color: #fff; }
        .container { padding: 20px; }
        .orders-grid { display: flex; flex-wrap: wrap; gap: 15px; }
        .card { background: #fff; border-radius: 8px; border-top: 5px solid #00b14f; box-shadow: 0 2px 5px rgba(0,0,0,0.1); width: 280px; padding: 15px; }
        .badge { background: #e0f2fe; color: #0369a1; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .order-id { font-size: 20px; font-weight: bold; margin: 10px 0; }
        .detail { margin: 5px 0; font-size: 14px; }
    </style>
</head>
<body>

    <div class="navbar">
        <h1><?= htmlspecialchars($store_name) ?> (Kitchen Display System)</h1>
        <div class="nav-links">
            <a href="settings.php">Settings</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <div id="orders-container" class="orders-grid">Loading orders...</div>
    </div>

    <script>
        function loadOrders() {
            fetch('fetch_orders.php')
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('orders-container');
                    if (data.length === 0) {
                        container.innerHTML = '<p>No live orders right now.</p>';
                        return;
                    }

                    let html = '';
                    data.forEach(order => {
                        html += `
                            <div class="card">
                                <span class="badge">${order.source || 'UBER_EATS'}</span>
                                <div class="order-id">#${order.order_id}</div>
                                <div class="detail"><strong>Customer:</strong> ${order.customer_name}</div>
                                <div class="detail"><strong>Items:</strong> ${order.items}</div>
                                <div class="detail"><strong>Total:</strong> $${parseFloat(order.total).toFixed(2)}</div>
                            </div>
                        `;
                    });
                    container.innerHTML = html;
                })
                .catch(err => console.error('Error fetching orders:', err));
        }

        loadOrders();
        setInterval(loadOrders, <?= $refresh_rate ?>);
    </script>
</body>
</html>
