<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'db.php';

// Fetch store settings & credentials
$setting_res = $conn->query("SELECT * FROM settings WHERE id = 1");
$setting = $setting_res ? $setting_res->fetch_assoc() : [];
$store_name = $setting['store_name'] ?? 'One Kitchen Solution';
$refresh_sec = max(2, (int)($setting['auto_refresh_sec'] ?? 3));
$uber_require_otp = $setting['uber_require_otp'] ?? 0;

$error_msg = '';

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    $entered_otp = trim($_POST['entered_otp'] ?? '');

    // Check if it's an Uber Eats order and OTP is enabled
    if ($new_status === 'COMPLETED') {
        $chk_stmt = $conn->prepare("SELECT source FROM orders WHERE order_id = ?");
        $chk_stmt->bind_param("s", $order_id);
        $chk_stmt->execute();
        $order_data = $chk_stmt->get_result()->fetch_assoc();

        if ($order_data && $order_data['source'] === 'UBER_EATS' && $uber_require_otp) {
            
            // Send the entered PIN directly to Uber's API for validation
            $uber_api_url = "https://api.uber.com/v1/eats/stores/" . urlencode($setting['uber_store_id'] ?? '') . "/orders/" . urlencode($order_id) . "/complete";
            
            $payload = json_encode([
                "pin" => $entered_otp
            ]);

            $ch = curl_init($uber_api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/json",
                "Authorization: Bearer " . ($setting['uber_client_secret'] ?? '')
            ]);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // If Uber rejects the PIN, show an error and keep the order active
            if ($http_code !== 200 && $http_code !== 204) {
                $error_msg = "Uber API Error: Invalid PIN entered. Please check with the driver.";
            }
        }
    }

    // If no errors occurred, update local database status
    if (empty($error_msg)) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $stmt->bind_param("ss", $new_status, $order_id);
        $stmt->execute();
        
        header("Location: index.php");
        exit;
    }
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
    <!-- FontAwesome Icons for Print and Cancel -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background-color: #0f172a; color: #f8fafc; margin: 0; padding: 20px; }
        
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #334155; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; color: #38bdf8; }
        .nav-links { display: flex; gap: 10px; align-items: center; }
        .nav-btn { background: #334155; color: white; padding: 8px 14px; text-decoration: none; border-radius: 6px; font-size: 14px; font-weight: bold; }
        .nav-btn:hover { background: #475569; }
        .logout-btn { background: #ef4444; }
        .logout-btn:hover { background: #dc2626; }

        .kds-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        
        .card { background: #1e293b; border-radius: 8px; border-top: 5px solid #38bdf8; padding: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); display: flex; flex-direction: column; justify-content: space-between; }
        .card.card-UBER_EATS { border-top-color: #000000; }
        .card.card-JUST_EAT { border-top-color: #ff8000; }
        .card.card-DELIVEROO { border-top-color: #00ccbc; }

        .card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
        .card-header h3 { margin: 0; font-size: 18px; color: #f1f5f9; }
        
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; color: white; display: inline-block; }
        .badge-UBER_EATS { background: #000000; border: 1px solid #334155; }
        .badge-JUST_EAT { background: #ff8000; }
        .badge-DELIVEROO { background: #00ccbc; }

        .btn-cancel-sm { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; cursor: pointer; margin-left: 4px; }
        .btn-cancel-sm:hover { background: #fca5a5; }

        /* Print Button Icon Styling */
        .btn-print-sm { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; cursor: pointer; margin-left: 6px; }
        .btn-print-sm:hover { background: #bae6fd; }

        .customer-name { font-weight: bold; color: #94a3b8; font-size: 14px; margin-bottom: 10px; }
        .otp-notice { background: #f59e0b; color: #000; font-size: 11px; font-weight: bold; padding: 3px 6px; border-radius: 4px; margin-bottom: 10px; display: inline-block; }
        
        .items-box { background: #0f172a; padding: 10px; border-radius: 6px; margin-bottom: 15px; flex-grow: 1; font-size: 15px; line-height: 1.4; color: #cbd5e1; white-space: pre-line; }
        .order-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 10px; border-top: 1px solid #334155; padding-top: 10px; }
        .order-total { font-size: 18px; font-weight: bold; color: #22c55e; }

        .btn-complete { background: #22c55e; color: white; border: none; padding: 10px 15px; border-radius: 6px; font-size: 14px; font-weight: bold; cursor: pointer; width: 100%; margin-top: 10px; }
        .btn-complete:hover { background: #16a34a; }

        .empty-state { text-align: center; color: #64748b; grid-column: 1 / -1; padding: 50px 0; font-size: 18px; }

        /* Modals */
        .modal { display: none; position: fixed; z-index: 100; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); align-items: center; justify-content: center; }
        .modal-content { background: #1e293b; color: #f8fafc; padding: 25px; border-radius: 8px; width: 380px; max-width: 90%; border: 1px solid #334155; }
        .modal-content h3 { margin-top: 0; color: #f8fafc; }
        .modal-content select, .modal-content input[type="text"] { width: 100%; padding: 10px; margin-top: 10px; margin-bottom: 15px; background: #0f172a; color: white; border: 1px solid #334155; border-radius: 6px; }

        /* Print Media Query: Isolates a clean physical ticket layout */
        @media print {
            body * { visibility: hidden; }
            .ticket-print-target, .ticket-print-target * { visibility: visible; }
            .ticket-print-target { position: absolute; left: 0; top: 0; width: 100%; background: white !important; color: black !important; border: none !important; box-shadow: none !important; padding: 15px; }
            .header, .nav-links, .btn-complete, .btn-cancel-sm, .btn-print-sm, .otp-notice { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="header">
        <h1><?= htmlspecialchars($store_name) ?> — Kitchen Display</h1>
        <div class="nav-links">
            <a href="history.php" class="nav-btn">Order History & Analytics</a>
            <a href="settings.php" class="nav-btn">Settings</a>
            <a href="logout.php" class="nav-btn logout-btn">Logout</a>
        </div>
    </div>

    <!-- Error Banner for Wrong PIN -->
    <?php if (!empty($error_msg)): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 12px 20px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; border: 1px solid #fca5a5;">
            ⚠️ <?= htmlspecialchars($error_msg) ?>
        </div>
    <?php endif; ?>

    <!-- Active Orders Grid Container -->
    <div id="kdsGridContainer" class="kds-grid">
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <h2>No active orders right now</h2>
                <p>New orders from Uber Eats, Just Eat, and Deliveroo will appear here automatically.</p>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $o): ?>
                <div class="card card-<?= $o['source'] ?> ticket-print-target" id="card-ticket-<?= $o['order_id'] ?>">
                    <div>
                        <div class="card-header">
                            <h3>#<?= htmlspecialchars($o['order_id']) ?></h3>
                            <div>
                                <span class="badge badge-<?= $o['source'] ?>"><?= htmlspecialchars($o['source']) ?></span>
                                <!-- Print Icon Button -->
                                <button type="button" onclick="printSingleTicket('card-ticket-<?= $o['order_id'] ?>')" class="btn-print-sm" title="Print Ticket"><i class="fa-solid fa-print"></i> Print</button>
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

                        <?php if ($o['source'] === 'UBER_EATS' && $uber_require_otp): ?>
                            <button type="button" onclick="openOtpModal('<?= $o['order_id'] ?>')" class="btn-complete">✓ Complete Order</button>
                        <?php else: ?>
                            <form method="POST">
                                <input type="hidden" name="order_id" value="<?= htmlspecialchars($o['order_id']) ?>">
                                <input type="hidden" name="status" value="COMPLETED">
                                <button type="submit" name="action_status" class="btn-complete">✓ Complete Order</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- OTP Verification Modal -->
    <div id="otpModal" class="modal">
        <div class="modal-content">
            <h3 style="color: #f59e0b;">🔑 Verify Uber Eats OTP</h3>
            <p>This order requires a pickup PIN/OTP from the driver before completion.</p>
            <form method="POST">
                <input type="hidden" name="order_id" id="otpOrderId">
                <input type="hidden" name="status" value="COMPLETED">
                <label style="font-size: 13px; color: #94a3b8;">Enter Driver OTP / PIN:</label>
                <input type="text" name="entered_otp" placeholder="e.g. 4-digit PIN" required autocomplete="off">
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 15px;">
                    <button type="button" onclick="closeOtpModal()" style="background:#64748b; color:white; border:none; padding:8px 14px; border-radius:6px; cursor:pointer;">Cancel</button>
                    <button type="submit" name="action_status" style="background:#22c55e; color:white; border:none; padding:8px 14px; border-radius:6px; cursor:pointer; font-weight:bold;">Verify & Complete</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Cancellation Modal Popup -->
    <div id="cancelModal" class="modal">
        <div class="modal-content">
            <h3 style="color: #ef4444;">Cancel Order <span id="cancelOrderId"></span></h3>
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
    const pollIntervalSec = <?= (int)$refresh_sec ?> * 1000;

    function printSingleTicket(cardId) {
        // Temporarily isolate the target card for printing
        const cards = document.querySelectorAll('.ticket-print-target');
        cards.forEach(card => {
            if (card.id === cardId) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });

        window.print();

        // Restore all cards after print window closes
        cards.forEach(card => {
            card.style.display = '';
        });
    }

    function openOtpModal(orderId) {
        document.getElementById('otpOrderId').value = orderId;
        document.getElementById('otpModal').style.display = 'flex';
    }

    function closeOtpModal() {
        document.getElementById('otpModal').style.display = 'none';
    }

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
        });
    }

    // Auto-poll for new orders in the background ONLY if no modals are currently open
    setInterval(() => {
        const otpOpen = document.getElementById('otpModal').style.display === 'flex';
        const cancelOpen = document.getElementById('cancelModal').style.display === 'flex';

        if (otpOpen || cancelOpen) return;

        fetch('index.php')
            .then(res => res.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newGrid = doc.getElementById('kdsGridContainer');
                if (newGrid) {
                    document.getElementById('kdsGridContainer').innerHTML = newGrid.innerHTML;
                }
            })
            .catch(err => console.log('Background poll error:', err));
    }, pollIntervalSec);
    </script>
</body>
</html>
