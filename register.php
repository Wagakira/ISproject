<?php
// Include database configuration
include "configure.php";

// Start session
session_start();

// Initialize variables for error/success messages
$success = false;
$error = "";

// Check if the database connection is established
if (!isset($conn) || $conn->connect_error) {
    $error = "Database connection failed. Please check your configuration.";
} else {
    // Check if the form is submitted
    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $business_name = trim($_POST['business_name']);
        $phone_number = trim($_POST['phone_number']);
        $specialty = trim($_POST['specialty']);
        $location = trim($_POST['location']);
        $description = trim($_POST['description']);

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        }
        // Validate password length (at least 8 characters)
        elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
        }
        // Validate required fields
        elseif (empty($business_name) || empty($phone_number) || empty($specialty) || empty($location) || empty($description)) {
            $error = "All fields are required.";
        }
        else {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Email already exists. Please use a different email.";
            } else {
                // Hash the password
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $role = 'caterer';

                // Insert into users table
                $stmt = $conn->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $email, $hash, $role);
                
                if ($stmt->execute()) {
                    // Get the newly inserted user ID
                    $user_id = $stmt->insert_id;

                    // Insert into caterers table
                    $stmt = $conn->prepare("INSERT INTO caterers (caterer_id, business_name, phone_number, email, specialty, location, description, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param("issssss", $user_id, $business_name, $phone_number, $email, $specialty, $location, $description);
                    
                    if ($stmt->execute()) {
                        $success = true;
                        $message = "Sign up successful! Redirecting to login...";
                        // Redirect to login.php after 3 seconds
                        header("refresh:3;url=logiin.php");
                    } else {
                        $error = "Error inserting into caterers table: " . $stmt->error;
                        // Rollback: Delete the user from users table if caterers insertion fails
                        $connect->query("DELETE FROM users WHERE user_id = $user_id");
                    }
                } else {
                    $error = "Error signing up: " . $stmt->error;
                }
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caterer Sign Up</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="signup.js" defer></script>
</head>
<body>
    <div class="container">
        <header>
            <h1>Caterer Sign Up</h1>
            <p>Create an account to manage your catering business</p>
            <?php if ($success): ?>
                <div class="success-message"><?php echo $message; ?></div>
            <?php elseif (!empty($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
        </header>
        <div class="signup-form">
            <form method="POST" id="signupForm">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" placeholder="Enter email" required>
                    <span class="error-text" id="email-error"></span>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" placeholder="Enter password" required>
                    <span class="error-text" id="password-error"></span>
                </div>

                <div class="form-group">
                    <label for="business_name">Business Name</label>
                    <input type="text" name="business_name" id="business_name" placeholder="Enter business name" required>
                    <span class="error-text" id="business-name-error"></span>
                </div>

                <div class="form-group">
                    <label for="phone_number">Phone Number</label>
                    <input type="text" name="phone_number" id="phone_number" placeholder="Enter phone number" required>
                    <span class="error-text" id="phone-number-error"></span>
                </div>

                <div class="form-group">
                    <label for="specialty">Specialty</label>
                    <input type="text" name="specialty" id="specialty" placeholder="Enter specialty (e.g., Italian Foods)" required>
                    <span class="error-text" id="specialty-error"></span>
                </div>

                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" name="location" id="location" placeholder="Enter location (e.g., Parklands, Nairobi)" required>
                    <span class="error-text" id="location-error"></span>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" placeholder="Describe your catering business" required></textarea>
                    <span class="error-text" id="description-error"></span>
                </div>

                <button type="submit" name="signup">Sign Up</button>
                <div class="form-text">Already have an account? <a href="logiin.php">Login</a></div>
            </form>
        </div>
    </div>
</body>
</html>