<?php
require 'db.php';
$result = $conn->query("SELECT * FROM orders ORDER BY id DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Kitchen Display System</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 20px; }
        .grid { display: flex; flex-wrap: wrap; gap: 20px; }
        .card { background: white; padding: 20px; border-radius: 8px; width: 300px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-top: 5px solid #06C167; }
        .badge { background: #e0f2fe; color: #0369a1; padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 12px; }
    </style>
</head>
<body>
    <h2>Kitchen Display System (Live Orders)</h2>
    <div class="grid">
        <?php while($row = $result->fetch_assoc()): ?>
            <div class="card">
                <span class="badge"><?php echo htmlspecialchars($row['source']); ?></span>
                <h3>#<?php echo htmlspecialchars($row['order_id']); ?></h3>
                <p><strong>Customer:</strong> <?php echo htmlspecialchars($row['customer_name']); ?></p>
                <p><strong>Items:</strong> <?php echo htmlspecialchars($row['items']); ?></p>
                <p><strong>Total:</strong> $<?php echo htmlspecialchars($row['total']); ?></p>
            </div>
        <?php endwhile; ?>
    </div>
</body>
</html>
