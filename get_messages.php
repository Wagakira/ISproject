<?php
session_start();
$conn = new mysqli('localhost', 'hannah_b', 'hannah1234$$', 'catering_system');

$customer_id = $_SESSION['user_id'];
$caterer_id = $_GET['caterer_id'];

$sql = "SELECT message_id, sender_id, receiver_id, message, sent_at FROM messages 
        WHERE (sender_id = '$customer_id' AND receiver_id = '$caterer_id') 
        OR (sender_id = '$caterer_id' AND receiver_id = '$customer_id') 
        ORDER BY sent_at ASC";
$result = $conn->query($sql);

$messages = [];
while($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

header('Content-Type: application/json');
echo json_encode($messages);
$conn->close();
?>