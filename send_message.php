<?php
session_start();
include 'configure.php';

if (!isset($_SESSION['user_id'])) {
    exit("Unauthorized");
}

$sender_id = $_SESSION['user_id'];
$receiver_id = $_POST['receiver_id'];
$message = $_POST['message'];
$file_path = null;

if (!empty($_FILES['file']['name'])) {
    $target_dir = "uploads/";
    $file_path = $target_dir . basename($_FILES["file"]["name"]);
    move_uploaded_file($_FILES["file"]["tmp_name"], $file_path);
}

$stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, file_path) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiss", $sender_id, $receiver_id, $message, $file_path);
$stmt->execute();
$stmt->close();

echo "<div class='message sent'><p>" . htmlspecialchars($message) . "</p><small>Just now</small></div>";
if ($file_path) {
    echo "<a href='$file_path' target='_blank'>View Attachment</a>";
}
?>
