<?php
$conn = new mysqli('localhost', 'hannah_b', 'hannah1234$$', 'catering_system');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = '?'; 
$caterer_id = '?';

$sql = "SELECT * FROM messages 
        WHERE (sender_id = '$user_id' AND receiver_id = '$caterer_id') 
        OR (sender_id = '$caterer_id' AND receiver_id = '$user_id') 
        ORDER BY sent_at ASC";
$result = $conn->query($sql);
$messages = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages</title>
    <link rel="stylesheet" href="message.css">
</head>
<body>
    <div class="inbox-container">
        <div class="inbox-header">
            <h2>Messages</h2>
            <span class="status-dot online"></span>
        </div>

        <div class="messages-container" id="messages">
            <?php if (empty($messages)): ?>
                <div class="no-messages">No messages yet</div>
            <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                    <div class="message <?php echo $msg['sender_id'] == $user_id ? 'sent' : 'received'; ?>">
                        <div class="message-content">
                            <p><?php echo htmlspecialchars($msg['message']); ?></p>
                            <span class="timestamp">
                                <?php echo date('M j, g:i a', strtotime($msg['sent_at'])); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <form id="messageForm" class="message-form">
            <input type="hidden" name="sender_id" value="<?php echo $user_id; ?>">
            <input type="hidden" name="receiver_id" value="<?php echo $caterer_id; ?>">
            <textarea name="message" placeholder="Type your message..." required></textarea>
            <button type="submit">Send</button>
        </form>
    </div>

    <script>
        const userId = <?php echo $user_id; ?>;
        const conn = new WebSocket('ws://localhost:8080');
        const messagesContainer = document.getElementById('messages');
        const form = document.getElementById('messageForm');

        conn.onopen = () => console.log('Connected to WebSocket');
        
        conn.onmessage = (e) => {
            const data = JSON.parse(e.data);
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${data.sender_id == userId ? 'sent' : 'received'}`; 
            messageDiv.innerHTML = `
                <div class="message-content">
                    <p>${data.message}</p>
                    <span class="timestamp">${new Date(data.sent_at).toLocaleString()}</span>
                </div>
            `;
            messagesContainer.appendChild(messageDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
            
            if (data.sender_id != userId) {
                showNotification('New message from caterer');
            }
        };

        form.onsubmit = (e) => {
            e.preventDefault();
            const message = form.message.value;
            const data = {
                sender_id: userId, 
                receiver_id: <?php echo $caterer_id; ?>,
                message: message
            };
            conn.send(JSON.stringify(data));
            form.message.value = '';
        };

        function showNotification(message) {
            if (Notification.permission === 'granted') {
                new Notification(message);
            } else if (Notification.permission !== 'denied') {
                Notification.requestPermission().then(permission => {
                    if (permission === 'granted') {
                        new Notification(message);
                    }
                });
            }
        }
    </script>
</body>
</html>

<?php $conn->close(); ?>