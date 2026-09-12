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
            
            // --- CALL UBER EATS API TO VERIFY PIN ---
            // Note: Replace with the actual Uber Eats API endpoint for order completion / PIN verification
            $uber_api_url = "https://api.uber.com/v1/eats/stores/" . urlencode($setting['justeat_restaurant_id'] ?? '') . "/orders/" . urlencode($order_id) . "/complete"; // (or your specific Uber endpoint)
            
            $payload = json_encode([
                "pin" => $entered_otp
            ]);

            $ch = curl_init($uber_api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/json",
                "Authorization: Bearer " . ($setting['uber_client_secret'] ?? '') // Adjust based on your Uber Auth token method
            ]);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // If Uber returns an error code (e.g., 400 Bad Request or 401 Unauthorized for wrong PIN)
            if ($http_code !== 200 && $http_code !== 204) {
                $error_msg = "Uber API Error: Invalid PIN / OTP entered. Please try again.";
            }
        }
    }

    // If no errors occurred, update database status
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
