<?php
$host = getenv('DB_HOST') ?: 'gateway01.eu-central-1.prod.aws.tidbcloud.com';
$user = getenv('DB_USER') ?: '23PFnNCAJo2UvRB.root';
$pass = getenv('DB_PASS') ?: 'OzzHz3Y1GObSN6F7';
$dbname = getenv('DB_NAME') ?: 'test';
$port = (int)(getenv('DB_PORT') ?: 4000);

$conn = mysqli_init();

if (!$conn) {
    die("mysqli_init failed");
}

// Enable SSL/TLS encryption for TiDB Cloud
$conn->ssl_set(NULL, NULL, NULL, NULL, NULL);

$success = $conn->real_connect($host, $user, $pass, $dbname, $port, NULL, MYSQLI_CLIENT_SSL);

if (!$success) {
    die(json_encode(["status" => "error", "message" => "Database Connection Failed: " . mysqli_connect_error()]));
}
?>
