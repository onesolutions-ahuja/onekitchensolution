<?php
// Paste your newly acquired access token here for testing
$access_token = 'IA.AQAAAAhY0t-CGzR-8IQZ0lSJP3Bhopw3Zff6Y-Mm1GtIeNYXYtGYKS5Td9_rjLACmQK6SzXO2mQ8kBJvxgGywIC3szPSXnVe3ynd20jaRCw3qOXmxBQAYvxcPkA0aakEfjkCb79Yr4yPRnx3PrxJlxzAHFbjsF-RBRvi6HyOMiSAuA';

$ch = curl_init("https://test-api.uber.com/v1/eats/stores");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $access_token,
    "Accept: application/json"
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h2>Store Listing Response (HTTP {$http_code}):</h2>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";
?>
