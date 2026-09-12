<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'db.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $store_name = trim($_POST['store_name']);
    $refresh_sec = (int)$_POST['auto_refresh_sec'];

    $stmt = $conn->prepare("UPDATE settings SET store_name = ?, auto_refresh_sec = ? WHERE id = 1");
    $stmt->bind_param("si", $store_name, $refresh_sec);
    if ($stmt->execute()) {
        $msg = "Settings updated successfully!";
    }
}

$setting = $conn->query("SELECT * FROM settings WHERE id = 1")->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Kitchen System - Settings</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f5f7; padding: 20px; }
        .card { background: #fff; padding: 20px; border-radius: 8px; max-width: 400px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        input { width: 100%; padding: 8px; margin: 8px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { padding: 10px 15px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        a { text-decoration: none; color: #555; margin-left: 10px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Store Settings</h2>
        <?php if ($msg): ?><p style="color:green;"><?= $msg ?></p><?php endif; ?>
        <form method="POST">
            <label>Store Name:</label>
            <input type="text" name="store_name" value="<?= htmlspecialchars($setting['store_name'] ?? 'One Kitchen Hub') ?>" required>
            
            <label>Auto Refresh Rate (seconds):</label>
            <input type="number" name="auto_refresh_sec" value="<?= $setting['auto_refresh_sec'] ?? 3 ?>" min="1" max="60" required>
            
            <button type="submit">Save Settings</button>
            <a href="index.php">Back to Dashboard</a>
        </form>
    </div>
</body>
</html>
