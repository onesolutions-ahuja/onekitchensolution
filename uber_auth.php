<?php
require_once 'db.php';

$setting_res = $conn->query("SELECT * FROM settings WHERE id = 1");
$setting = $setting_res ? $setting_res->fetch_assoc() : [];

$client_id = $setting['uber_client_id'] ?? '';
$client_secret = $setting['uber_client_secret'] ?? '';

// Direct server-to-server token request
$token_url = "https://auth.uber.com/oauth/v2/token";
$post_fields = [
    'client_id' => $client_id,
    'client_secret' => $client_secret,
    'grant_type' => 'client_credentials',
 ];

$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h3>Uber Token Response (HTTP Code: $http_code)</h3>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";
?>
