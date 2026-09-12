<?php
$client_id = 'hIQlHFPRSnXiekxqOfiAP7rz4aZTJrTI';
$redirect_uri = 'https://onekitchensolution.onrender.com/uber_callback.php';

// Notice the sandbox domain here:
$auth_url = "https://sandbox-login.uber.com/oauth/v2/authorize?" . http_build_query([
    'client_id' => $client_id,
    'response_type' => 'code',
    'redirect_uri' => $redirect_uri,
    'scope' => 'eats.order eats.store eats.pos_provisioning'
]);

echo "<h2>Kitchen Display System - Authorization</h2>";
echo "<p>Click the button below to sign in with Uber Sandbox and link your restaurant store:</p>";
echo "<a href='" . $auth_url . "' style='padding: 10px 20px; background: black; color: white; text-decoration: none; border-radius: 5px;'>Sign in with Uber Sandbox</a>";
?>
