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
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        $sender_id = $data['sender_id'];
        $receiver_id = $data['receiver_id'];
        $message = $this->conn->real_escape_string($data['message']);
        $sent_at = date('Y-m-d H:i:s');

        $sql = "INSERT INTO messages (sender_id, receiver_id, message, sent_at) 
                VALUES ('$sender_id', '$receiver_id', '$message', '$sent_at')";
        $this->conn->query($sql);

        $message_id = $this->conn->insert_id;

        foreach ($this->clients as $client) {
            $client->send(json_encode([
                'message_id' => $message_id,
                'sender_id' => $sender_id,
                'receiver_id' => $receiver_id,
                'message' => $message,
                'sent_at' => $sent_at
            ]));
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
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