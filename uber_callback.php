<?php
// Check if Uber returned an authorization code
if (!isset($_GET['code'])) {
    die("Authorization failed or code missing.");
}

$authorization_code = $_GET['code'];
$client_id = 'hIQlHFPRSnXiekxqOfiAP7rz4aZTJrTI';
$client_secret = 'IpA44FdRXYOiGRlExoQ_ENTZGQkad2llO3bfxFC1';
$redirect_uri = 'https://onekitchensolution.onrender.com/uber_callback.php';

// Exchange authorization code for access token
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

if ($access_token) {
    echo "<h2>Success! Access Token Acquired:</h2>";
    echo "<p><code>" . htmlspecialchars($access_token) . "</code></p>";
    // TODO: Save this $access_token securely into your TiDB database!
} else {
    echo "Failed to parse access token from response: <pre>" . htmlspecialchars($response) . "</pre>";
}
?>
