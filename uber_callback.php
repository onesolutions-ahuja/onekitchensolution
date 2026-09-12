<?php
session_start();
require_once 'db.php';

// Check if Uber returned an error
if (isset($_GET['error'])) {
    die("Authorization failed: " . htmlspecialchars($_GET['error_description'] ?? $_GET['error']));
}

// Check if authorization code is present
if (!isset($_GET['code'])) {
    die("Authorization failed: No code provided by Uber.");
}

$code = $_GET['code'];

// Fetch settings from database
$setting_res = $conn->query("SELECT * FROM settings WHERE id = 1");
$setting = $setting_res ? $setting_res->fetch_assoc() : [];

$client_id = $setting['uber_client_id'] ?? '';
$client_secret = $setting['uber_client_secret'] ?? '';
$redirect_uri = "https://onekitchensolution.onrender.com/uber_callback.php";

// Exchange code for token
$token_url = "https://auth.uber.com/oauth/v2/token";
$post_fields = [
    'client_id' => $client_id,
    'client_secret' => $client_secret,
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => $redirect_uri
];

$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

if ($http_code === 200 && isset($data['access_token'])) {
    $access_token = $data['access_token'];
    $refresh_token = $data['refresh_token'] ?? '';
    
    // Save tokens to database
    $stmt = $conn->prepare("UPDATE settings SET uber_access_token = ?, uber_refresh_token = ? WHERE id = 1");
    $stmt->bind_param("ss", $access_token, $refresh_token);
    $stmt->execute();
    $stmt->close();
    
    echo "<h2>Success! Uber account connected successfully.</h2>";
    echo "<p>Tokens have been saved securely to TiDB Cloud.</p>";
    echo "<a href='settings.php'>Back to Settings</a>";
} else {
    echo "<h2>Token Exchange Failed (HTTP $http_code)</h2>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}
?>
