<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['id'])) {
    $id = (int)$data['id'];
    $stmt = $conn->prepare("UPDATE orders SET status = 'COMPLETED' WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Missing order ID']);
}
?>
