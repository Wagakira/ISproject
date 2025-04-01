<?php
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

class Chat implements MessageComponentInterface {
    protected $clients;
    private $conn;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->conn = new mysqli('localhost', 'hannah_b', 'hannah1234$$', 'catering_system');
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        $sender_id = $this->conn->real_escape_string($data['sender_id']);
        $receiver_id = $this->conn->real_escape_string($data['receiver_id']);
        $message = $this->conn->real_escape_string($data['message']);
        $sent_at = date('Y-m-d H:i:s');

        // Save to database
        $sql = "INSERT INTO messages (sender_id, receiver_id, message, sent_at) 
                VALUES ('$sender_id', '$receiver_id', '$message', '$sent_at')";
        $this->conn->query($sql);

        $messageData = [
            'sender_id' => $sender_id,
            'message' => $message,
            'sent_at' => $sent_at
        ];

        foreach ($this->clients as $client) {
            $client->send(json_encode($messageData));
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Connection closed! ({$conn->resourceId})\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

$server = \Ratchet\Server\IoServer::factory(
    new \Ratchet\Http\HttpServer(
        new \Ratchet\WebSocket\WsServer(
            new Chat()
        )
    ),
    8080
);

$server->run();