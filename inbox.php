<?php
session_start();
include 'configure.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $caterer_id = $_POST['caterer_id'];
    $client_id = $_SESSION['user_id'];
    $message = $_POST['message'];

    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $client_id, $caterer_id, $message);

    if ($stmt->execute()) {
        echo "Message sent!";
    } else {
        echo "Message failed.";
    }
}
?>

<form method="POST">
    <input type="hidden" name="caterer_id" value="<?php echo $_GET['caterer_id']; ?>">
    <textarea name="message" required></textarea>
    <button type="submit">Send Message</button>
</form>
