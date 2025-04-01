<?php
include "configure.php";

$sql = "SELECT caterer_id, password FROM caterers";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $caterer_id = $row['caterer_id'];
    $plain_password = $row['password'];
    
    // Hash the password
    $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);
    
    // Update the caterers table with the hashed password
    $update_sql = "UPDATE caterers SET password = ? WHERE caterer_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $hashed_password, $caterer_id);
    $stmt->execute();
    $stmt->close();
}

$conn->close();
echo "Passwords hashed successfully.";
?>