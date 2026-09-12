<?php
header('Content-Type: application/json');
require_once 'db.php';

// Retrieve raw POST body
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Fallback / standard logging for debugging
if (!$data) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

/*
 * Uber Eats Webhook Event Structure:
 * event_type: "orders.notification"
 * payload contains: order details, customer info, and items list
 */

// Parse order parameters from Uber's payload format
$order_id = $data['order_id'] ?? $data['id'] ?? ('UBER-' . rand(1000, 9999));
$customer_name = $data['customer']['name'] ?? $data['eater']['first_name'] ?? 'Uber Eats Customer';

// Parse items array into a clean text string
$items_list = [];
if (!empty($data['cart']['items'])) {
    foreach ($data['cart']['items'] as $item) {
        $qty = $item['quantity'] ?? 1;
        $title = $item['title'] ?? $item['name'] ?? 'Item';
        $items_list[] = "{$qty}x {$title}";
    }
    $items_formatted = implode(', ', $items_list);
} else {
    $items_formatted = $data['items_description'] ?? '1x Standard Meal Deal';
}

// Calculate total price
$total = isset($data['payment']['total']) ? ($data['payment']['total'] / 100) : ($data['total'] ?? 0.00);

// Insert into orders table
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
