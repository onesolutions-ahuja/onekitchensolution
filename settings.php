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

    // Vendor Toggles
    $uber_enabled = isset($_POST['uber_enabled']) ? 1 : 0;
    $uber_client_id = trim($_POST['uber_client_id']);
    $uber_client_secret = trim($_POST['uber_client_secret']);

    $doordash_enabled = isset($_POST['doordash_enabled']) ? 1 : 0;
    $doordash_developer_id = trim($_POST['doordash_developer_id']);
    $doordash_key_id = trim($_POST['doordash_key_id']);
    $doordash_signing_secret = trim($_POST['doordash_signing_secret']);

    $deliveroo_enabled = isset($_POST['deliveroo_enabled']) ? 1 : 0;
    $deliveroo_brand_id = trim($_POST['deliveroo_brand_id']);
    $deliveroo_api_key = trim($_POST['deliveroo_api_key']);

    $stmt = $conn->prepare("UPDATE settings SET 
        store_name = ?, 
        auto_refresh_sec = ?, 
        uber_enabled = ?, uber_client_id = ?, uber_client_secret = ?,
        doordash_enabled = ?, doordash_developer_id = ?, doordash_key_id = ?, doordash_signing_secret = ?,
        deliveroo_enabled = ?, deliveroo_brand_id = ?, deliveroo_api_key = ?
        WHERE id = 1");

    $stmt->bind_param("siiisisissis", 
        $store_name, $refresh_sec,
        $uber_enabled, $uber_client_id, $uber_client_secret,
        $doordash_enabled, $doordash_developer_id, $doordash_key_id, $doordash_signing_secret,
        $deliveroo_enabled, $deliveroo_brand_id, $deliveroo_api_key
    );

    if ($stmt->execute()) {
        $msg = "Multi-vendor settings updated successfully!";
    } else {
        $msg = "Error updating settings: " . $conn->error;
    }
}

$setting = $conn->query("SELECT * FROM settings WHERE id = 1")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>KDS Settings - Multi-Vendor Credentials</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f5f7; padding: 20px; }
        .container { max-width: 650px; margin: 0 auto; }
        .card { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 20px; }
        h2 { margin-top: 0; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; color: #1e293b; }
        h3 { color: #0f172a; margin-top: 20px; font-size: 16px; display: flex; align-items: center; justify-content: space-between; }
        label { font-size: 13px; font-weight: bold; color: #475569; display: block; margin-top: 10px; }
        input[type="text"], input[type="number"], input[type="password"] { 
            width: 100%; padding: 10px; margin-top: 4px; border: 1px solid #cbd5e1; border-radius: 5px; box-sizing: border-box; 
        }
        .vendor-section { background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 6px; margin-top: 15px; }
        .toggle-label { display: inline-flex; align-items: center; gap: 8px; font-size: 14px; font-weight: bold; cursor: pointer; }
        button { background: #00b14f; color: white; border: none; padding: 12px 20px; border-radius: 6px; font-size: 15px; font-weight: bold; cursor: pointer; margin-top: 15px; }
        button:hover { background: #008f40; }
        .back-link { text-decoration: none; color: #64748b; font-weight: bold; margin-left: 15px; }
        .success { color: #15803d; background: #dcfce7; padding: 10px; border-radius: 6px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h2>KDS & Multi-Vendor Settings</h2>
            <?php if ($msg): ?><div class="success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

            <form method="POST">
                <label>Store Name:</label>
                <input type="text" name="store_name" value="<?= htmlspecialchars($setting['store_name'] ?? 'One Kitchen Hub') ?>" required>

                <label>Auto Refresh Rate (Seconds):</label>
                <input type="number" name="auto_refresh_sec" value="<?= $setting['auto_refresh_sec'] ?? 3 ?>" min="1" max="60" required>

                <!-- UBER EATS -->
                <div class="vendor-section">
                    <h3>
                        <span>Uber Eats Integrations</span>
                        <label class="toggle-label">
                            <input type="checkbox" name="uber_enabled" value="1" <?= ($setting['uber_enabled'] ?? 1) ? 'checked' : '' ?>> Enable
                        </label>
                    </h3>
                    <label>Uber Client ID:</label>
                    <input type="text" name="uber_client_id" value="<?= htmlspecialchars($setting['uber_client_id'] ?? '') ?>">
                    
                    <label>Uber Client Secret:</label>
                    <input type="password" name="uber_client_secret" value="<?= htmlspecialchars($setting['uber_client_secret'] ?? '') ?>">
                </div>

                <!-- DOORDASH -->
                <div class="vendor-section">
                    <h3>
                        <span>DoorDash Integrations</span>
                        <label class="toggle-label">
                            <input type="checkbox" name="doordash_enabled" value="1" <?= ($setting['doordash_enabled'] ?? 1) ? 'checked' : '' ?>> Enable
                        </label>
                    </h3>
                    <label>DoorDash Developer ID:</label>
                    <input type="text" name="doordash_developer_id" value="<?= htmlspecialchars($setting['doordash_developer_id'] ?? '') ?>">

                    <label>DoorDash Key ID:</label>
                    <input type="text" name="doordash_key_id" value="<?= htmlspecialchars($setting['doordash_key_id'] ?? '') ?>">

                    <label>DoorDash Signing Secret:</label>
                    <input type="password" name="doordash_signing_secret" value="<?= htmlspecialchars($setting['doordash_signing_secret'] ?? '') ?>">
                </div>

                <!-- DELIVEROO -->
                <div class="vendor-section">
                    <h3>
                        <span>Deliveroo Integrations</span>
                        <label class="toggle-label">
                            <input type="checkbox" name="deliveroo_enabled" value="1" <?= ($setting['deliveroo_enabled'] ?? 1) ? 'checked' : '' ?>> Enable
                        </label>
                    </h3>
                    <label>Deliveroo Brand / Store ID:</label>
                    <input type="text" name="deliveroo_brand_id" value="<?= htmlspecialchars($setting['deliveroo_brand_id'] ?? '') ?>">

                    <label>Deliveroo API Key:</label>
                    <input type="password" name="deliveroo_api_key" value="<?= htmlspecialchars($setting['deliveroo_api_key'] ?? '') ?>">
                </div>

                <button type="submit">Save All Settings</button>
                <a href="index.php" class="back-link">Back to Dashboard</a>
            </form>
        </div>
    </div>
</body>
</html>
