<?php
// dashboard.php - Kitchen Display System (KDS) for the Alliance
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Alliance KDS - Kitchen Display</title>
    <!-- FontAwesome for the Print Icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; background: #121212; color: #e0e0e0; margin: 0; padding: 20px; }
        h1 { color: #ffb703; text-align: center; }
        .orders-container { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; margin-top: 30px; }
        .order-card { background: #1e1e1e; border: 2px solid #ffb703; border-radius: 8px; width: 300px; padding: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.5); position: relative; }
        .order-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #444; padding-bottom: 10px; margin-bottom: 15px; }
        .order-title { font-size: 18px; font-weight: bold; color: #fff; }
        .print-btn { background: #ffb703; border: none; color: #121212; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-size: 16px; transition: 0.2s; }
        .print-btn:hover { background: #fb8500; }
        .item-list { list-style-type: none; padding: 0; margin: 0 0 15px 0; }
        .item-list li { padding: 5px 0; border-bottom: 1px dashed #333; }
        .badge { background: #fb8500; color: white; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }

        /* Print styles: ensures only the receipt card prints cleanly */
        @media print {
            body * { visibility: hidden; }
            .printable-ticket, .printable-ticket * { visibility: visible; }
            .printable-ticket { position: absolute; left: 0; top: 0; width: 100%; background: white; color: black; border: none; box-shadow: none; }
            .print-btn { display: none; }
        }
    </style>
</head>
<body>

    <h1>🪄 The Three Broomsticks - Kitchen Display System</h1>
    <p style="text-align: center; color: #888;">Pending orders waiting for preparation...</p>

    <div class="orders-container">
        <!-- Mock Pending Order Card (Will dynamically load via API later) -->
        <div class="order-card printable-ticket" id="order-9432">
            <div class="order-header">
                <div>
                    <span class="badge">PENDING</span>
                    <div class="order-title" style="margin-top: 5px;">Order #9432</div>
                </div>
                <!-- Print Icon Button -->
                <button class="print-btn" onclick="printTicket('order-9432')" title="Print Order Ticket">
                    <i class="fa-solid fa-print"></i>
                </button>
            </div>
            <p><strong>Customer:</strong> Harry P.</p>
            <ul class="item-list">
                <li>2x Hot Butterbeer</li>
                <li>1x Cauldron Cake</li>
                <li>1x Shepherd's Pie</li>
            </ul>
            <p style="font-size: 12px; color: #aaa; margin: 0;">Time: Just now</p>
        </div>
    </div>

    <script>
        function printTicket(orderId) {
            // Triggers browser print restricted to the ticket card CSS rules
            window.print();
        }
    </script>
</body>
</html>
