<?php
$host = getenv('DB_HOST');
$port = getenv('DB_PORT') ?: 4000;
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$name = getenv('DB_NAME');

// Initialize MySQLi object
$conn = mysqli_init();

if (!$conn) {
    die("mysqli_init failed");
}

// Enable SSL encryption (required by TiDB Cloud)
$conn->ssl_set(NULL, NULL, NULL, NULL, NULL);

// Establish secure connection
$success = $conn->real_connect($host, $user, $pass, $name, $port, NULL, MYSQLI_CLIENT_SSL);

if (!$success) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
