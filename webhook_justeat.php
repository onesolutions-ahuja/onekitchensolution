<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'db.php';

// Verify Just Eat is enabled
$setting = $conn->query("SELECT justeat_enabled FROM settings WHERE id = 1")->fetch_assoc();
if (!($setting['justeat_enabled'] ?? 1)) {
    http_response_code(403);
    echo json_encode(['status' => 'disabled', 'message' => 'Just Eat integrations disabled']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

// Just Eat payload format
$order_id = $data['OrderId'] ?? $data['id'] ?? ('JE-' . rand(1000, 9999));
$customer_name = $data['Customer']['Name'] ?? $data['customer_name'] ?? 'Just Eat Customer';

$items_list = [];
if (!empty($data['Lines'])) {
    foreach ($data['Lines'] as $line) {
        $qty = $line['Quantity'] ?? 1;
        $title = $line['Name'] ?? 'Item';
        $items_list[] = "{$qty}x {$title}";
    }
    $items_formatted = implode(', ', $items_list);
} else {
    $items_formatted = '1x Just Eat Order';
}

$total = $data['TotalAmount'] ?? $data['total'] ?? 0.00;

$stmt = $conn->prepare("INSERT INTO orders (order_id, customer_name, items, total, status, source) VALUES (?, ?, ?, ?, 'PENDING', 'JUST_EAT')");
$stmt->bind_param("sssd", $order_id, $customer_name, $items_formatted, $total);

if ($stmt->execute()) {
    http_response_code(200);
    echo json_encode(['status' => 'success', 'order_id' => $order_id]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $conn->error]);
}
?>
