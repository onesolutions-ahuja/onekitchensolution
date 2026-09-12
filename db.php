<?php
$host   = getenv('DB_HOST') ?: 'gateway01.eu-central-1.prod.aws.tidbcloud.com';
$user   = getenv('DB_USER') ?: '23PFnNCAJo2UvRB.root';
$pass   = getenv('DB_PASS') ?: 'OzzHz3Y1GObSN6F7';
$dbname = getenv('DB_NAME') ?: 'test';
$port   = getenv('DB_PORT') ?: 4000;

$conn = new mysqli($host, $user, $pass, $dbname, (int)$port);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Database Connection Failed: " . $conn->connect_error]));
}
?>
