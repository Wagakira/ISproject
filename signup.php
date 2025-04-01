<?php

$conn = new mysqli("localhost", "hannah_b", "hannah1234$$", "catering_system");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); 
    $role = $conn->real_escape_string($_POST['role']);
    
    $valid_roles = ['client', 'caterer',]; 
    if (!in_array($role, $valid_roles)) {
        $role = 'user'; 
    }
    
    $sql = "INSERT INTO users (email, password, role, created_at) 
            VALUES ('$email', '$password', '$role', NOW())";
            
    if ($conn->query($sql) === TRUE) {
        echo "Registration successful!";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" type="text/css" href="signup.css">
</head>
<body>
    <form method="POST" action="">
        <div>
            <label>Email:</label>
            <input type="email" name="email" required>
        </div>
        <div>
            <label>Password:</label>
            <input type="password" name="password" required>
        </div>
        <div>
            <label>Role:</label>
            <select name="role" required>
                <option value="client">Client</option>
                <option value="caterer">Caterer</option>
            </select>
        </div>
        <button type="submit">Register</button>
        <div class="formtext">Already have an account? <a href="login.php">Login</a></div>

    </form>
</body>
</html>

<?php
$conn->close();
?>