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

    // Uber Eats
    $uber_enabled = isset($_POST['uber_enabled']) ? 1 : 0;
    $uber_require_otp = isset($_POST['uber_require_otp']) ? 1 : 0;
    $uber_client_id = trim($_POST['uber_client_id']);
    $uber_client_secret = trim($_POST['uber_client_secret']);

    // Just Eat
    $justeat_enabled = isset($_POST['justeat_enabled']) ? 1 : 0;
    $justeat_restaurant_id = trim($_POST['justeat_restaurant_id']);
    $justeat_api_key = trim($_POST['justeat_api_key']);

    // Deliveroo
    $deliveroo_enabled = isset($_POST['deliveroo_enabled']) ? 1 : 0;
    $deliveroo_brand_id = trim($_POST['deliveroo_brand_id']);
    $deliveroo_api_key = trim($_POST['deliveroo_api_key']);

    // Prepared statement matching exactly 12 variables (s iii s s i s s i s s s -> 12 total)
    $stmt = $conn->prepare("UPDATE settings SET 
        store_name = ?, 
        auto_refresh_sec = ?, 
        uber_enabled = ?, 
        uber_require_otp = ?, 
        uber_client_id = ?, 
        uber_client_secret = ?,
        justeat_enabled = ?, 
        justeat_restaurant_id = ?, 
        justeat_api_key = ?,
        deliveroo_enabled = ?, 
        deliveroo_brand_id = ?, 
        deliveroo_api_key = ?
        WHERE id = 1");

    // Types string: s(1) i(2) i(3) i(4) s(5) s(6) i(7) s(8) s(9) i(10) s(11) s(12) = "siiiisississ"
    $stmt->bind_param("siiissississ", 
        $store_name,          // s (1)
        $refresh_sec,         // i (2)
        $uber_enabled,        // i (3)
        $uber_require_otp,    // i (4)
        $uber_client_id,      // s (5)
        $uber_client_secret,  // s (6)
        $justeat_enabled,     // i (7)
        $justeat_restaurant_id, // s (8)
        $justeat_api_key,     // s (9)
        $deliveroo_enabled,   // i (10)
        $deliveroo_brand_id,  // s (11)
        $deliveroo_api_key    // s (12)
    );

    if ($stmt->execute()) {
        $msg = "Settings updated successfully!";
    } else {
        $msg = "Error updating settings: " . $conn->error;
    }
}

$setting_res = $conn->query("SELECT * FROM settings WHERE id = 1");
$setting = $setting_res ? $setting_res->fetch_assoc() : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>KDS Settings - Delivery & Verification</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f5f7; padding: 20px; }
        .container { max-width: 650px; margin: 0 auto; }
        .card { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h2 { margin-top: 0; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; color: #1e293b; }
        h3 { color: #0f172a; margin-top: 20px; font-size: 16px; display: flex; align-items: center; justify-content: space-between; }
        label { font-size: 13px; font-weight: bold; color: #475569; display: block; margin-top: 10px; }
        input[type="text"], input[type="number"], input[type="password"] { 
            width: 100%; padding: 10px; margin-top: 4px; border: 1px solid #cbd5e1; border-radius: 5px; box-sizing: border-box; 
        }
        .vendor-section { background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 6px; margin-top: 15px; }
        .toggle-label { display: inline-flex; align-items: center; gap: 8px; font-size: 14px; font-weight: bold; cursor: pointer; }
        .sub-setting { margin-top: 12px; padding-top: 10px; border-top: 1px dashed #cbd5e1; }
        button { background: #00b14f; color: white; border: none; padding: 12px 20px; border-radius: 6px; font-size: 15px; font-weight: bold; cursor: pointer; margin-top: 15px; }
        .back-link { text-decoration: none; color: #64748b; font-weight: bold; margin-left: 15px; }
        .success { color: #15803d; background: #dcfce7; padding: 10px; border-radius: 6px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h2>KDS Settings</h2>
            <?php if ($msg): ?><div class="success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

            <form method="POST">
                <label>Store Name:</label>
                <input type="text" name="store_name" value="<?= htmlspecialchars($setting['store_name'] ?? 'One Kitchen Hub') ?>" required>

                <label>Auto Refresh Rate (Seconds):</label>
                <input type="number" name="auto_refresh_sec" value="<?= htmlspecialchars($setting['auto_refresh_sec'] ?? 3) ?>" min="1" max="60" required>

                <!-- UBER EATS -->
                <div class="vendor-section">
                    <h3>
                        <span>Uber Eats Integration</span>
                        <label class="toggle-label">
                            <input type="checkbox" name="uber_enabled" value="1" <?= (!isset($setting['uber_enabled']) || $setting['uber_enabled'] == 1) ? 'checked' : '' ?>> Enable
                        </label>
                    </h3>
                    <label>Uber Client ID:</label>
                    <input type="text" name="uber_client_id" value="<?= htmlspecialchars($setting['uber_client_id'] ?? '') ?>">
                    
                    <label>Uber Client Secret:</label>
                    <input type="password" name="uber_client_secret" value="<?= htmlspecialchars($setting['uber_client_secret'] ?? '') ?>">

                    <div class="sub-setting">
                        <label class="toggle-label">
                            <input type="checkbox" name="uber_require_otp" value="1" <?= (!empty($setting['uber_require_otp'])) ? 'checked' : '' ?>>
                            Require OTP / PIN verification on delivery pickup
                        </label>
                    </div>
                </div>

                <!-- JUST EAT -->
                <div class="vendor-section">
                    <h3>
                        <span>Just Eat Integration</span>
                        <label class="toggle-label">
                            <input type="checkbox" name="justeat_enabled" value="1" <?= (!isset($setting['justeat_enabled']) || $setting['justeat_enabled'] == 1) ? 'checked' : '' ?>> Enable
                        </label>
                    </h3>
                    <label>Just Eat Restaurant ID:</label>
                    <input type="text" name="justeat_restaurant_id" value="<?= htmlspecialchars($setting['justeat_restaurant_id'] ?? '') ?>">

                    <label>Just Eat API Key:</label>
                    <input type="password" name="justeat_api_key" value="<?= htmlspecialchars($setting['justeat_api_key'] ?? '') ?>">
                </div>

                <!-- DELIVEROO -->
                <div class="vendor-section">
                    <h3>
                        <span>Deliveroo Integration</span>
                        <label class="toggle-label">
                            <input type="checkbox" name="deliveroo_enabled" value="1" <?= (!isset($setting['deliveroo_enabled']) || $setting['deliveroo_enabled'] == 1) ? 'checked' : '' ?>> Enable
                        </label>
                    </h3>
                    <label>Deliveroo Brand / Store ID:</label>
                    <input type="text" name="deliveroo_brand_id" value="<?= htmlspecialchars($setting['deliveroo_brand_id'] ?? '') ?>">

                    <label>Deliveroo API Key:</label>
                    <input type="password" name="deliveroo_api_key" value="<?= htmlspecialchars($setting['deliveroo_api_key'] ?? '') ?>">
                </div>

                <button type="submit">Save Settings</button>
                <a href="index.php" class="back-link">Back to Dashboard</a>
            </form>
        </div>
    </div>
</body>
</html>
