<?php
$host = 'localhost';
$dbname = 'catering_system';
$username = 'hannah_b';
$password = 'hannah1234$$';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = '';
$success = '';
$users = [];

$user_id = 13;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $email = $conn->real_escape_string($_POST['email']);
        $bio = $conn->real_escape_string($_POST['bio']);

        $sql = "UPDATE users SET 
                email = '$email',
                bio = '$bio'
                WHERE user_id = $user_id";
        
        if ($conn->query($sql)) {
            $success = "Profile updated successfully!";
        } else {
            $error = "Error updating profile: " . $conn->error;
        }
    } elseif (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        $password_query = "SELECT password FROM users WHERE user_id = $user_id";
        $password_result = $conn->query($password_query);
        $current_hashed_password = $password_result->fetch_assoc()['password'];

        if (!password_verify($current_password, $current_hashed_password)) {
            $error = "Current password is incorrect.";
        } elseif ($new_password !== $confirm_password) {
            $error = "New password and confirmation do not match.";
        } elseif (strlen($new_password) < 8) {
            $error = "New password must be at least 8 characters long.";
        } else {
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password = '$new_hashed_password' WHERE user_id = $user_id";
            if ($conn->query($sql)) {
                $success = "Password updated successfully!";
            } else {
                $error = "Error updating password: " . $conn->error;
            }
        }
    } elseif (isset($_POST['delete_picture'])) {

        $sql = "SELECT profile_picture FROM users WHERE user_id = $user_id";
        $result = $conn->query($sql);
        $user_data = $result->fetch_assoc();
        $current_picture = $user_data['profile_picture'];

        if ($current_picture && file_exists("uploads/profiles/$current_picture")) {
            unlink("uploads/profiles/$current_picture");
        }

        $sql = "UPDATE users SET profile_picture = NULL WHERE user_id = $user_id";
        if ($conn->query($sql)) {
            $success = "Profile picture deleted successfully!";
        } else {
            $error = "Error deleting profile picture: " . $conn->error;
        }
    } elseif (isset($_FILES['profile_picture'])) {
        // Handle profile picture upload
        $target_dir = "uploads/profiles/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $target_file = $target_dir . basename($_FILES["profile_picture"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $new_filename = "user_" . $user_id . "_" . time() . "." . $imageFileType;
        $target_file = $target_dir . $new_filename;
        
        $check = getimagesize($_FILES["profile_picture"]["tmp_name"]);
        if ($check === false) {
            $error = "File is not an image.";
        } elseif ($_FILES["profile_picture"]["size"] > 500000) {
            $error = "Sorry, your file is too large (max 500KB).";
        } elseif (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            $error = "Only JPG, JPEG, PNG & GIF files are allowed.";
        } elseif (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
            $sql = "UPDATE users SET profile_picture = '$new_filename' WHERE user_id = $user_id";
            if ($conn->query($sql)) {
                $success = "Profile picture updated successfully!";
            } else {
                $error = "Error updating profile picture: " . $conn->error;
            }
        } else {
            $error = "Sorry, there was an error uploading your file.";
        }
    }
}

$sql = "SELECT * FROM users WHERE user_id = $user_id";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $users = $result->fetch_assoc();
} else {
    $error = "User not found! Please ensure user ID 13 exists in the database.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Catering System</title>
    <link rel="stylesheet" type="text/css" href="userprofile.css">
    <script src="userprofile.js"></script>
</head>
<body>
    <div class="container">
        <div class="profile-header">
            <h1>My Profile</h1>
            <p>Manage your personal information and profile picture</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <div class="profile-content">
            <div class="profile-sidebar">
                <?php 
                $profile_pic = isset($users['profile_picture']) && !empty($users['profile_picture']) ? 
                    'images/aura.jpeg' . $users['profile_picture'] : 
                    'images/aura.jpeg';
                ?>
                <img src="<?php echo $profile_pic; ?>" alt="Profile Picture" class="profile-picture" id="profile-preview">
                
                <div class="profile-upload">
                    <form action="" method="post" enctype="multipart/form-data">
                        <input type="file" name="profile_picture" id="file-upload" accept="image/*"><br>
                        <label for="file-upload" class="upload-btn">
                            <i class="fas fa-camera"></i> Change Photo</label>
                        <!--<p class="small">JPG, PNG or GIF (Max 500KB)</p>-->
                    </form>
                    <?php if (isset($users['profile_picture']) && !empty($users['profile_picture'])): ?>
                        <form action="" method="post">
                            <button type="submit" name="delete_picture" class="delete-btn">
                                <i class="fas fa-trash"></i> Delete Photo
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="profile-details">
                <form action="" method="post">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" name="email" id="email" 
                               value="<?php echo htmlspecialchars($users['email'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="bio">Bio</label>
                        <textarea name="bio" id="bio"><?php echo htmlspecialchars($users['bio'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </form>

                <div class="password-section">
                    <h3>Change Password</h3>
                    <form action="" method="post">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" name="current_password" id="current_password" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" name="new_password" id="new_password" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" required>
                        </div>
                        <button type="submit" name="update_password" class="btn">
                            <i class="fas fa-lock"></i> Update Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>