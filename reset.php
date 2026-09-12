<?php
require_once 'db.php';

$new_password = 'admin123';
$new_hash = password_hash($new_password, PASSWORD_BCRYPT);

$stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
$stmt->bind_param("s", $new_hash);

if ($stmt->execute()) {
    echo "<h1>Password updated successfully!</h1>";
    echo "<p>Your password for <strong>admin</strong> is now: <strong>admin123</strong></p>";
    echo "<a href='login.php'>Go to Login</a>";
} else {
    echo "Error updating password: " . $conn->error;
}
?>
