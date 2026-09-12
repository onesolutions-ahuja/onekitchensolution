<?php
// 1. Fetch token using client_credentials
$ch = curl_init('https://sandbox-login.uber.com/oauth/v2/token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'client_id' => 'hIQlHFPRSnXiekxqOfiAP7rz4aZTJrTI',
    'client_secret' => 'IpA44FdRXYOiGRlExoQ_ENTZGQkad2llO3bfxFC1',
    'grant_type' => 'client_credentials',
    'scope' => 'eats.order eats.store'
]));
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
$access_token = $data['access_token'] ?? null;

if (!$access_token) {
    die("Token acquisition failed: " . $response);
}

// 2. Use the token to test fetching store/orders from Sandbox
$store_id = 'YOUR_SANDBOX_STORE_ID'; // Replace with your test store ID if available
$ch = curl_init("https://api.uber.com/v1/eats/stores/{$store_id}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $access_token,
    "Accept: application/json"
]);
$store_response = curl_exec($ch);
curl_close($ch);

echo "API Connection Successful! Store Response: <pre>" . htmlspecialchars($store_response) . "</pre>";
?>
