<?php
session_start();
require_once 'db.php';

if (!isset($_GET['code'])) {
    die("Authorization failed: No code provided by Uber.");
}

$code = $_GET['code'];
$setting_res = $conn->query("SELECT * FROM settings WHERE id = 1");
$setting = $setting_res ? $setting_res->fetch_assoc() : [];

$client_id = $setting['uber_client_id'] ?? '';
$client_secret = $setting['uber_client_secret'] ?? '';
$redirect_uri = "https://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/uber_callback.php";

// Step 4: Exchange authorization code for access token via cURL POST
$token_url = "https://login.uber.com/oauth/v2/token";
$post_fields = [
    'client_id' => $client_id,
    'client_secret' => $client_secret,
    'grant_type' => 'authorization_code',
    'redirect_uri' => $redirect_uri,
    'code' => $code
];

$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    $data = json_decode($response, true);
    $access_token = $data['access_token'] ?? '';
    $refresh_token = $data['refresh_token'] ?? '';
    $expires_in = time() + ($data['expires_in'] ?? 2592000);

    // Save tokens securely in your settings database table
    $stmt = $conn->prepare("UPDATE settings SET uber_access_token = ?, uber_refresh_token = ?, uber_token_expires = ? WHERE id = 1");
    $stmt->bind_param("ssi", $access_token, $refresh_token, $expires_in);
    $stmt->execute();

    header("Location: settings.php?success=uber_connected");
    exit;
} else {
    echo "<h3>Token Exchange Failed (HTTP $http_code)</h3>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}
?>
