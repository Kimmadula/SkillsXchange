<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Loop;
use React\Socket\SocketServer;

class WebSocketSignalingService implements MessageComponentInterface
{
    protected $clients;
    protected $rooms;
    protected $userConnections;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->rooms = [];
        $this->userConnections = [];
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        // Store a unique identifier for this connection
        /** @var object $conn */
        $conn->resourceId = uniqid('conn_', true);
        Log::info("New WebSocket connection: {$conn->resourceId}");
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        try {
            $data = json_decode($msg, true);
            
            if (!$data || !isset($data['type'])) {
                $this->sendError($from, 'Invalid message format');
                return;
            }

            switch ($data['type']) {
                case 'join':
                    $this->handleJoin($from, $data);
                    break;
                case 'offer':
                    $this->handleOffer($from, $data);
                    break;
                case 'answer':
                    $this->handleAnswer($from, $data);
                    break;
                case 'ice-candidate':
                    $this->handleIceCandidate($from, $data);
                    break;
                case 'end-call':
                    $this->handleEndCall($from, $data);
                    break;
                default:
                    $this->sendError($from, 'Unknown message type');
            }
        } catch (\Exception $e) {
            Log::error("WebSocket message error: " . $e->getMessage());
            $this->sendError($from, 'Message processing error');
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        
        // Clean up user connections
        foreach ($this->userConnections as $userId => $connection) {
            if ($connection === $conn) {
                unset($this->userConnections[$userId]);
                break;
            }
        }
        
        // Clean up rooms
        foreach ($this->rooms as $roomId => $room) {
            $resourceId = $conn->resourceId ?? null;
            if ($resourceId && isset($room['connections'][$resourceId])) {
                unset($this->rooms[$roomId]['connections'][$resourceId]);
                if (empty($this->rooms[$roomId]['connections'])) {
                    unset($this->rooms[$roomId]);
                }
            }
        }
        
        Log::info("WebSocket connection closed: " . ($conn->resourceId ?? 'unknown'));
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        Log::error("WebSocket error: " . $e->getMessage());
        $conn->close();
    }

    protected function handleJoin(ConnectionInterface $conn, $data)
    {
        if (!isset($data['userId']) || !isset($data['tradeId'])) {
            $this->sendError($conn, 'Missing userId or tradeId');
            return;
        }

        $userId = $data['userId'];
        $tradeId = $data['tradeId'];
        $roomId = "trade_{$tradeId}";

        // Store user connection
        $this->userConnections[$userId] = $conn;
        
        // Add to room
        if (!isset($this->rooms[$roomId])) {
            $this->rooms[$roomId] = [
                'connections' => [],
                'users' => []
            ];
        }
        
        $resourceId = $conn->resourceId ?? uniqid('conn_', true);
        $this->rooms[$roomId]['connections'][$resourceId] = $conn;
        $this->rooms[$roomId]['users'][$userId] = $conn;
        
        // Store room info in connection
        /** @var object $conn */
        $conn->roomId = $roomId;
        $conn->userId = $userId;
        
        Log::info("User {$userId} joined room {$roomId}");
        
        $this->sendToConnection($conn, [
            'type' => 'joined',
            'roomId' => $roomId,
            'userId' => $userId
        ]);
    }

    protected function handleOffer(ConnectionInterface $from, $data)
    {
        if (!isset($data['toUserId']) || !isset($data['offer']) || !isset($data['callId'])) {
            $this->sendError($from, 'Missing required fields for offer');
            return;
        }

        $toUserId = $data['toUserId'];
        $targetConn = $this->userConnections[$toUserId] ?? null;
        
        if (!$targetConn) {
            $this->sendError($from, 'Target user not connected');
            return;
        }

        $this->sendToConnection($targetConn, [
            'type' => 'offer',
            'fromUserId' => $from->userId ?? null,
            'offer' => $data['offer'],
            'callId' => $data['callId']
        ]);

        Log::info("Offer sent from " . ($from->userId ?? 'unknown') . " to {$toUserId}");
    }

    protected function handleAnswer(ConnectionInterface $from, $data)
    {
        if (!isset($data['toUserId']) || !isset($data['answer']) || !isset($data['callId'])) {
            $this->sendError($from, 'Missing required fields for answer');
            return;
        }

        $toUserId = $data['toUserId'];
        $targetConn = $this->userConnections[$toUserId] ?? null;
        
        if (!$targetConn) {
            $this->sendError($from, 'Target user not connected');
            return;
        }

        $this->sendToConnection($targetConn, [
            'type' => 'answer',
            'fromUserId' => $from->userId ?? null,
            'answer' => $data['answer'],
            'callId' => $data['callId']
        ]);

        Log::info("Answer sent from " . ($from->userId ?? 'unknown') . " to {$toUserId}");
    }

    protected function handleIceCandidate(ConnectionInterface $from, $data)
    {
        if (!isset($data['toUserId']) || !isset($data['candidate']) || !isset($data['callId'])) {
            $this->sendError($from, 'Missing required fields for ICE candidate');
            return;
        }

        $toUserId = $data['toUserId'];
        $targetConn = $this->userConnections[$toUserId] ?? null;
        
        if (!$targetConn) {
            $this->sendError($from, 'Target user not connected');
            return;
        }

        $this->sendToConnection($targetConn, [
            'type' => 'ice-candidate',
            'fromUserId' => $from->userId ?? null,
            'candidate' => $data['candidate'],
            'callId' => $data['callId']
        ]);

        Log::info("ICE candidate sent from " . ($from->userId ?? 'unknown') . " to {$toUserId}");
    }

    protected function handleEndCall(ConnectionInterface $from, $data)
    {
        if (!isset($data['toUserId']) || !isset($data['callId'])) {
            $this->sendError($from, 'Missing required fields for end call');
            return;
        }

        $toUserId = $data['toUserId'];
        $targetConn = $this->userConnections[$toUserId] ?? null;
        
        if ($targetConn) {
            $this->sendToConnection($targetConn, [
                'type' => 'end-call',
                'fromUserId' => $from->userId ?? null,
                'callId' => $data['callId']
            ]);
        }

        Log::info("End call sent from " . ($from->userId ?? 'unknown') . " to {$toUserId}");
    }

    protected function sendToConnection(ConnectionInterface $conn, $data)
    {
        try {
            $conn->send(json_encode($data));
        } catch (\Exception $e) {
            Log::error("Error sending to connection: " . $e->getMessage());
        }
    }

    protected function sendError(ConnectionInterface $conn, $message)
    {
        $this->sendToConnection($conn, [
            'type' => 'error',
            'message' => $message
        ]);
    }

    public static function startServer($port = 8080)
    {
        $loop = Loop::get();
        $socket = new SocketServer("0.0.0.0:{$port}", [], $loop);
        $server = new IoServer(
            new HttpServer(
                new WsServer(
                    new self()
                )
            ),
            $socket,
            $loop
        );

        Log::info("WebSocket signaling server started on port {$port}");
        $server->run();
    }
}
