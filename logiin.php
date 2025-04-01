<?php
session_start();
include "configure.php";

// Redirect if already logged in
//if (isset($_SESSION['user_id'])) {
    //header("Location: dashboard.php");
  //  exit();
//}

//$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    // Check in caterers table
    $sql = "SELECT * FROM caterers WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $caterer = $result->fetch_assoc();

    if ($caterer && password_verify($password, $caterer['password'])) {
        $_SESSION['user_id'] = $caterer['caterer_id'];
        $_SESSION['role'] = 'caterer';
        header("Location: dashboard.php");
        exit();
    }

    // Check in users table (assuming caterers might also be registered as users)
    $sql = "SELECT * FROM users WHERE email = ? AND role = 'caterer'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = 'caterer';
        header("Location: dashboard.php");
        exit();
    }

    $error = "Invalid email or password.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caterer Login</title>
    <link rel="stylesheet" href="login.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <h2>Caterer Login</h2>
        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
    </div>
</body>
</html>