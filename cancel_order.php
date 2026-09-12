<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

require_once 'db.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$order_id = $input['order_id'] ?? '';
$reason = trim($input['reason'] ?? 'Cancelled by kitchen staff');

if (!$order_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing order ID']);
    exit;
}

$stmt = $conn->prepare("UPDATE orders SET status = 'CANCELLED', cancel_reason = ? WHERE order_id = ?");
$stmt->bind_param("ss", $reason, $order_id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'order_id' => $order_id]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $conn->error]);
}
?>
