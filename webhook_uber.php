<?php
header('Content-Type: application/json');
require 'db.php';

$rawPayload = file_get_contents('php://input');
$data = json_decode($rawPayload, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input']);
    exit();
}

$external_order_id = $data['meta']['order_id'] ?? $data['order_id'] ?? ('UBER-' . rand(1000, 9999));
$customer_name     = $data['meta']['customer_name'] ?? 'Uber Customer';
$items_summary     = $data['meta']['items'] ?? 'Assorted Items';
$total_amount      = $data['meta']['total'] ?? 0.00;

$stmt = $conn->prepare("INSERT INTO orders (order_id, customer_name, items, total, status, source) VALUES (?, ?, ?, ?, 'PENDING', 'UBER_EATS')");
$stmt->bind_param("sssd", $external_order_id, $customer_name, $items_summary, $total_amount);

if ($stmt->execute()) {
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'ORDER CREATED SUCCESSFULLY!', 'order_id' => $external_order_id]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $stmt->error]);
}
?>
