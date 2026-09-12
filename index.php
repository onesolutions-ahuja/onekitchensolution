<?php
// dashboard.php - Alliance KDS Dashboard with Table View, OTP, Cancel, and Print
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Alliance KDS - Active Orders Dashboard</title>
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; background: #121212; color: #e0e0e0; margin: 0; padding: 20px; }
        h1 { color: #ffb703; text-align: center; }
        .container { max-width: 1200px; margin: 0 auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #1e1e1e; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.5); }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #333; }
        th { background: #2a2a2a; color: #ffb703; }
        tr:hover { background: #252525; }
        .badge { background: #fb8500; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        
        /* Action Buttons */
        .btn { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: bold; display: inline-flex; align-items: center; gap: 5px; text-decoration: none; }
        .btn-print { background: #219ebc; color: white; }
        .btn-print:hover { background: #023047; }
        .btn-cancel { background: #d90429; color: white; }
        .btn-cancel:hover { background: #8d0801; }
        
        /* OTP Input */
        .otp-input { width: 70px; padding: 5px; background: #121212; border: 1px solid #555; color: white; border-radius: 4px; text-align: center; margin-right: 5px; }

        /* Print styles: formatting a clean ticket output when print is clicked */
        @media print {
            body * { visibility: hidden; }
            .printable-section, .printable-section * { visibility: visible; }
            .printable-section { position: absolute; left: 0; top: 0; width: 100%; background: white; color: black; padding: 20px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="container">
        <h1>🪄 The Three Broomsticks - Kitchen Display System</h1>
        <p style="text-align: center; color: #888;">Live Order Monitoring, Verification, and Ticket Printing</p>

        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Status</th>
                    <th>OTP / PIN Verification</th>
                    <th class="no-print">Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Sample Active Order Row -->
                <tr id="row-9432">
                    <td><strong>#9432</strong></td>
                    <td>Harry P.</td>
                    <td>
                        2x Hot Butterbeer<br>
                        1x Cauldron Cake
                    </td>
                    <td><span class="badge">PENDING</span></td>
                    <td>
                        <input type="text" id="otp-9432" class="otp-input" placeholder="Enter PIN">
                    </td>
                    <td class="no-print" style="display: flex; gap: 8px; align-items: center;">
                        <!-- Print Button -->
                        <button class="btn btn-print" onclick="printOrderTicket('9432', 'Harry P.', '2x Hot Butterbeer, 1x Cauldron Cake')">
                            <i class="fa-solid fa-print"></i> Print
                        </button>
                        <!-- Cancel Button with OTP check -->
                        <button class="btn btn-cancel" onclick="cancelOrder('9432')">
                            <i class="fa-solid fa-ban"></i> Cancel
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Hidden Printable Ticket Template -->
    <div id="print-ticket-modal" class="printable-section" style="display: none;">
        <h2 style="border-bottom: 2px solid black; padding-bottom: 5px;">THE THREE BROOMSTICKS - KITCHEN TICKET</h2>
        <p><strong>Order ID:</strong> <span id="p-order-id"></span></p>
        <p><strong>Customer:</strong> <span id="p-customer"></span></p>
        <h3>Items:</h3>
        <p id="p-items" style="font-size: 16px; font-weight: bold;"></p>
        <hr style="border: 1px dashed black; margin-top: 20px;">
        <p style="font-size: 12px; text-align: center;">Ready for kitchen preparation</p>
    </div>

    <script>
        function printOrderTicket(orderId, customer, items) {
            // Populate hidden print template fields
            document.getElementById('p-order-id').innerText = orderId;
            document.getElementById('p-customer').innerText = customer;
            document.getElementById('p-items').innerText = items;

            // Make template visible for printing context
            const ticketModal = document.getElementById('print-ticket-modal');
            ticketModal.style.display = 'block';

            // Trigger print
            window.print();

            // Hide template again after print dialog closes
            ticketModal.style.display = 'none';
        }

        function cancelOrder(orderId) {
            const otpValue = document.getElementById('otp-' + orderId).value;
            if (!otpValue) {
                alert("Please enter the verification PIN/OTP before canceling order #" + orderId + ".");
                return;
            }
            if (confirm("Are you sure you want to cancel order #" + orderId + " using PIN: " + otpValue + "?")) {
                alert("Cancellation request submitted for order #" + orderId + " with PIN verification.");
                // Hook up backend cancellation call here
            }
        }
    </script>
</body>
</html>
