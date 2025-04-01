<?php
// Include database configuration
include "configure.php";

// Fetch all caterers
$sql = "SELECT * FROM caterers";
$result = $connect->query($sql);

if ($result->num_rows > 0) {
    while ($caterer = $result->fetch_assoc()) {
        $caterer_id = $caterer['caterer_id'];
        $email = $caterer['email'];
        $plain_password = $caterer['password'];

        // Hash the password
        $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);
        $role = 'caterer';

        // Check if the email already exists in the users table
        $check_sql = "SELECT user_id FROM users WHERE email = ?";
        $stmt = $connect->prepare($check_sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $check_result = $stmt->get_result();

        if ($check_result->num_rows > 0) {
            // Email already exists, update the role if necessary
            $user = $check_result->fetch_assoc();
            $user_id = $user['user_id'];
            if ($user_id != $caterer_id) {
                echo "Warning: Email $email exists with user_id $user_id, but caterer_id is $caterer_id. IDs do not match. Skipping...\n";
                continue;
            }
            $update_sql = "UPDATE users SET role = ? WHERE user_id = ?";
            $update_stmt = $connect->prepare($update_sql);
            $update_stmt->bind_param("si", $role, $user_id);
            if ($update_stmt->execute()) {
                echo "Updated role for $email (user_id: $user_id) to 'caterer'.\n";
            } else {
                echo "Error updating role for $email: " . $update_stmt->error . "\n";
            }
            $update_stmt->close();
        } else {
            // Insert into users table with user_id matching caterer_id
            $insert_sql = "INSERT INTO users (user_id, email, password, role) VALUES (?, ?, ?, ?)";
            $stmt = $connect->prepare($insert_sql);
            $stmt->bind_param("isss", $caterer_id, $email, $hashed_password, $role);
            if ($stmt->execute()) {
                echo "Successfully added $email (user_id: $caterer_id) to users table.\n";
            } else {
                echo "Error adding $email to users table: " . $stmt->error . "\n";
            }
        }
        $stmt->close();
    }
} else {
    echo "No caterers found in the caterers table.\n";
}

$connect->close();
?>