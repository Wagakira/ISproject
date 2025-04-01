<?php
session_start();

$host = "localhost";
$username = "hannah_b";
$password = "hannah1234$$";
$database = "catering_system";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    
    $sql = "SELECT email, password, role, created_at FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        
        if (password_verify($password, $row['password'])) {
            // Store user data in session
            $_SESSION['email'] = $row['email'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['created_at'] = $row['created_at'];
            
            if ($row['role'] == 'caterer') {
                header("Location: dashboard.php");
                exit();
            } elseif ($row['role'] == 'client') {
                header("Location: explore.php");
                exit();
            } else {
                $error = "Invalid role assigned to user";
            }
        } else {
            $error = "Invalid password";
        }
    } else {
        $error = "No user found with that email";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" type="text/css" href="login.css">
</head>
<body>
    <h2>Login</h2>
    
    <?php if (isset($error)) { ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php } ?>
    
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
        <div>
            <input type="submit" value="Login">
        </div>
    </form>
</body>
</html>

<?php
mysqli_close($conn);
?>