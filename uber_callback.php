<?php
if (!isset($_GET['code'])) {
    die("Authorization failed or code missing.");
}

$authorization_code = $_GET['code'];
$client_id = 'hIQlHFPRSnXiekxqOfiAP7rz4aZTJrTI';
$client_secret = 'IpA44FdRXYOiGRlExoQ_ENTZGQkad2llO3bfxFC1';
$redirect_uri = 'https://onekitchensolution.onrender.com/uber_callback.php';

// 1. Exchange code for access token
$ch = curl_init('https://sandbox-login.uber.com/oauth/v2/token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'client_id' => $client_id,
    'client_secret' => $client_secret,
    'grant_type' => 'authorization_code',
    'redirect_uri' => $redirect_uri,
    'code' => $authorization_code
]));

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200) {
    die("Token Exchange Failed (HTTP {$http_code}): <pre>" . htmlspecialchars($response) . "</pre>");
}

$data = json_decode($response, true);
$access_token = $data['access_token'] ?? null;

if (!$access_token) {
    die("Failed to parse access token.");
}

echo "<h2>Access Token Acquired Successfully!</h2>";

// 2. Automatically try to create a sandbox store using the token
$ch = curl_init("https://test-api.uber.com/v1/eats/stores");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $access_token,
    "Content-Type: application/json",
    "Accept: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    "name" => "The Three Broomsticks Kitchen",
    "phone_number" => "+15555555555",
    "location" => [
        "address_1" => "123 Diagon Alley",
        "city" => "San Francisco",
        "state" => "CA",
        "zip" => "94107",
        "country" => "US"
    ]
]));

$store_response = curl_exec($ch);
$store_http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h3>Store Provisioning Response (HTTP {$store_http}):</h3>";
echo "<pre>" . htmlspecialchars($store_response) . "</pre>";
?>
