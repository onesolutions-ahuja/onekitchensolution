<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'db.php';

// Fetch store settings
$setting_res = $conn->query("SELECT store_name, auto_refresh_sec, uber_require_otp FROM settings WHERE id = 1");
$setting = $setting_res ? $setting_res->fetch_assoc() : [];
$store_name = $setting['store_name'] ?? 'One Kitchen Solution';
$refresh_sec = $setting['auto_refresh_sec'] ?? 3;
$uber_require_otp = $setting['uber_require_otp'] ?? 0;

// Handle Status Updates (Completed, Pending, etc.)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    $stmt->bind_param("ss", $new_status, $order_id);
    $stmt->execute();
    
    header("Location: index.php");
    exit;
}

// Fetch Active Pending Orders
$orders_res = $conn->query("SELECT * FROM orders WHERE status = 'PENDING' ORDER BY created_at ASC");
$orders = $orders_res ? $orders_res->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($store_name) ?> - KDS Dashboard</title>
    <!-- Auto-refresh page periodically -->
    <meta http-equiv="refresh" content="<?= (int)$refresh_sec ?>">
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background-color: #0f172a; color: #f8fafc; margin: 0; padding: 20px; }
        
        /* Header & Nav */
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #334155; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; color: #38bdf8; }
        .nav-links { display: flex; gap: 10px; align-items: center; }
        .nav-btn { background: #334155; color: white; padding: 8px 14px; text-decoration: none; border-radius: 6px; font-size: 14px; font-weight: bold; }
        .nav-btn:hover { background: #475569; }
        .logout-btn { background: #ef4444; }
        .logout-btn:hover { background: #dc2626; }

        /* KDS Grid */
        .kds-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        
        /* Order Cards */
        .card { background: #1e293b; border-radius: 8px; border-top: 5px solid #38bdf8; padding: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); display: flex; flex-direction: column; justify-content: space-between; }
        .card.card-UBER_EATS { border-top-color: #000000; }
        .card.card-JUST_EAT { border-top-color: #ff8000; }
        .card.card-DELIVEROO { border-top-color: #00ccbc; }

        .card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
        .card-header h3 { margin: 0; font-size: 18px; color: #f1f5f9; }
        
        /* Vendor Badges */
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; color: white; display: inline-block; }
        .badge-UBER_EATS { background: #000000; border: 1px solid #334155; }
        .badge-JUST_EAT { background: #ff8000; }
        .badge-DELIVEROO { background: #00ccbc; }

        .btn-cancel-sm { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; cursor: pointer; margin-left: 6px; }
        .btn-cancel-sm:hover { background: #fca5a5; }

        .customer-name { font-weight: bold; color: #94a3b8; font-size: 14px; margin-bottom: 10px; }
        .otp-notice { background: #f59e0b; color: #000; font-size: 11px; font-weight: bold; padding: 3px 6px; border-radius: 4px; margin-bottom: 10px; display: inline-block; }
        
        .items-box { background: #0f172a; padding: 10px; border-radius: 6px; margin-bottom: 15px; flex-grow: 1; font-size: 15px; line-height: 1.4; color: #cbd5e1; white-space: pre-line; }
        .order-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 10px; border-top: 1px solid #334155; padding-top: 10px; }
        .order-total { font-size: 18px; font-weight: bold; color: #22c55e; }

        .btn-complete { background: #22c55e; color: white; border: none; padding: 10px 15px; border-radius: 6px; font-size: 14px; font-weight: bold; cursor: pointer; width: 100%; margin-top: 10px; }
        .btn-complete:hover { background: #16a34a; }

        .empty-state { text-align: center; color: #64748b; grid-column: 1 / -1; padding: 50px 0; font-size: 18px; }

        /* Modal Overlay */
        .modal { display: none; position: fixed; z-index: 100; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); align-items: center; justify-content: center; }
        .modal-content { background: #1e293b; color: #f8fafc; padding: 25px; border-radius: 8px; width: 380px; max-width: 90%; border: 1px solid #334155; }
        .modal-content h3 { margin-top: 0; color: #ef4444; }
        .modal-content select { width: 100%; padding: 10px; margin-top: 10px; margin-bottom: 15px; background: #0f172a; color: white; border: 1px solid #334155; border-radius: 6px; }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <div class="header">
        <h1><?= htmlspecialchars($store_name) ?> — Kitchen Display</h1>
        <div class="nav-links">
            <a href="history.php" class="nav-btn">Order History & Analytics</a>
            <a href="settings.php" class="nav-btn">Settings</a>
            <a href="logout.php" class="nav-btn logout-btn">Logout</a>
        </div>
    </div>

    <!-- Active Orders Grid -->
    <div class="kds-grid">
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <h2>No active orders right now</h2>
                <p>New orders from Uber Eats, Just Eat, and Deliveroo will appear here automatically.</p>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $o): ?>
                <div class="card card-<?= $o['source'] ?>">
                    <div>
                        <div class="card-header">
                            <h3>#<?= htmlspecialchars($o['order_id']) ?></h3>
                            <div>
                                <span class="badge badge-<?= $o['source'] ?>"><?= htmlspecialchars($o['source']) ?></span>
                                <button type="button" onclick="openCancelModal('<?= $o['order_id'] ?>')" class="btn-cancel-sm">✕ Cancel</button>
                            </div>
                        </div>

                        <div class="customer-name">👤 <?= htmlspecialchars($o['customer_name']) ?></div>

                        <?php if ($o['source'] === 'UBER_EATS' && $uber_require_otp): ?>
                            <div class="otp-notice">🔑 REQUIRE DELIVERY OTP PIN</div>
                        <?php endif; ?>

                        <div class="items-box"><?= htmlspecialchars($o['items']) ?></div>
                    </div>

                    <div>
                        <div class="order-footer">
                            <span class="order-total">£<?= number_format($o['total'], 2) ?></span>
                            <small style="color: #64748b;"><?= date('H:i', strtotime($o['created_at'])) ?></small>
                        </div>

                        <form method="POST">
                            <input type="hidden" name="order_id" value="<?= htmlspecialchars($o['order_id']) ?>">
                            <input type="hidden" name="status" value="COMPLETED">
                            <button type="submit" name="action_status" class="btn-complete">✓ Complete Order</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Cancellation Modal Popup -->
    <div id="cancelModal" class="modal">
        <div class="modal-content">
            <h3>Cancel Order <span id="cancelOrderId"></span></h3>
            <p>Select a reason for cancelling this order:</p>
            <select id="cancelReasonSelect">
                <option value="Out of stock / missing item">Out of stock / missing item</option>
                <option value="Kitchen too busy">Kitchen overburdened</option>
                <option value="Customer requested cancellation">Customer requested cancellation</option>
                <option value="Store closing soon">Store closing soon</option>
            </select>
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 15px;">
                <button type="button" onclick="closeCancelModal()" style="background:#64748b; color:white; border:none; padding:8px 14px; border-radius:6px; cursor:pointer;">Close</button>
                <button type="button" onclick="submitCancellation()" style="background:#dc2626; color:white; border:none; padding:8px 14px; border-radius:6px; cursor:pointer; font-weight:bold;">Confirm Cancel</button>
            </div>
        </div>
    </div>

    <script>
    let activeCancelId = null;

    function openCancelModal(orderId) {
        activeCancelId = orderId;
        document.getElementById('cancelOrderId').innerText = '#' + orderId;
        document.getElementById('cancelModal').style.display = 'flex';
    }

    function closeCancelModal() {
        document.getElementById('cancelModal').style.display = 'none';
        activeCancelId = null;
    }

    function submitCancellation() {
        if (!activeCancelId) return;
        const reason = document.getElementById('cancelReasonSelect').value;

        fetch('cancel_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order_id: activeCancelId, reason: reason })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                closeCancelModal();
                location.reload();
            } else {
                alert('Failed to cancel order: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(err => {
            alert('Error processing request.');
        });
    }
    </script>
</body>
</html>
