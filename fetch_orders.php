<?php
header('Content-Type: application/json');
require_once 'db.php';

// Fetch all pending orders ordered by newest first
$result = $conn->query("SELECT * FROM orders WHERE status = 'PENDING' ORDER BY created_at DESC");

$orders = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

echo json_encode($orders);
?>
