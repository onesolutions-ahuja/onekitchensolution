<?php
session_start();
require_once 'db.php';

$setting_res = $conn->query("SELECT * FROM settings WHERE id = 1");
$setting = $setting_res ? $setting_res->fetch_assoc() : [];

$client_id = $setting['uber_client_id'] ?? '';
$redirect_uri = "https://onekitchensolution.onrender.com/uber_callback.php";

// Correct authorization endpoint and eats marketplace user scopes
$uber_auth_url = "https://auth.uber.com/oauth/v2/authorize?client_id=" . urlencode($client_id) . 
                 "&response_type=code&redirect_uri=" . urlencode($redirect_uri) . 
                 "&scope=eats.store.orders%20eats.order";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Connect Uber Eats</title>
    <style>
        body { font-family: Arial, sans-serif; background: #0f172a; color: #f8fafc; padding: 40px; text-align: center; }
        .box { background: #1e293b; padding: 30px; border-radius: 8px; display: inline-block; border: 1px solid #334155; max-width: 400px; }
        .btn { background: #000; color: #fff; padding: 12px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block; margin-top: 15px; border: 1px solid #334155; }
        .btn:hover { background: #1e293b; }
    </style>
</head>
<body>
    <div class="box">
        <h2>🔗 Connect Uber Eats</h2>
        <p>Link your Uber restaurant account to allow automatic order completion and PIN verification.</p>
        <?php if (empty($client_id)): ?>
            <p style="color: #ef4444;">Please save your Uber Client ID in the Settings page first!</p>
            <a href="settings.php" class="btn" style="background: #334155;">Go to Settings</a>
        <?php else: ?>
            <a href="<?= $uber_auth_url ?>" class="btn">Authorize with Uber Eats</a>
        <?php endif; ?>
    </div>
</body>
</html>
