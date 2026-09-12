<?php
// Allow cross-origin POST requests from webhooks / Postman
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'db.php';

// Retrieve raw POST body
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON payload received']);
    exit;
}

// Extract payload fields
$order_id = $data['order_id'] ?? $data['id'] ?? ('UBER-' . rand(1000, 9999));
$customer_name = $data['customer']['name'] ?? $data['eater']['first_name'] ?? 'Uber Eats Customer';

// Parse item list
$items_list = [];
if (!empty($data['cart']['items'])) {
    foreach ($data['cart']['items'] as $item) {
        $qty = $item['quantity'] ?? 1;
        $title = $item['title'] ?? $item['name'] ?? 'Item';
        $items_list[] = "{$qty}x {$title}";
    }
    $items_formatted = implode(', ', $items_list);
} else {
    $items_formatted = $data['items_description'] ?? '1x Standard Meal';
}

// Parse price total
$total = isset($data['payment']['total']) ? ($data['payment']['total'] / 100) : ($data['total'] ?? 0.00);

// Save order to database
$stmt = $conn->prepare("INSERT INTO orders (order_id, customer_name, items, total, status, source) VALUES (?, ?, ?, ?, 'PENDING', 'UBER_EATS')");
$stmt->bind_param("sssd", $order_id, $customer_name, $items_formatted, $total);

if ($stmt->execute()) {
    http_response_code(200);
    echo json_encode(['status' => 'success', 'order_id' => $order_id]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $conn->error]);
}
?>
