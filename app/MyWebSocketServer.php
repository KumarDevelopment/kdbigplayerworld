<?php

namespace App;

use Exception;
use Predis\Client as RedisClient;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class WebSocketServer implements MessageComponentInterface
{
    private $redis;
    private $clients = [];

    public function __construct()
    {
        $this->redis = new RedisClient([
            'scheme' => 'tcp',
            'host'   => '127.0.0.1',
            'port'   => 6379,
        ]);
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients[$conn->resourceId] = $conn;
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        echo sprintf('Received message from %d: %s' . "\n", $from->resourceId, $msg);

        $this->redis->publish('websocket', $msg);
    }

    public function onClose(ConnectionInterface $conn)
    {
        unset($this->clients[$conn->resourceId]);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    public function run()
    {
        $server = IoServer::factory(
            new HttpServer(
                new WsServer($this)
            ),
            8080
        );

        $this->redis->subscribe(['websocket'], function ($message) {
            foreach ($this->clients as $client) {
                $client->send($message);
            }
        });

        $server->run();
    }
}
