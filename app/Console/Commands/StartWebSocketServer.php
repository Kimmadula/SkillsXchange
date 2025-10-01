<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WebSocketSignalingService;

class StartWebSocketServer extends Command
{
    protected $signature = 'websocket:start {--port=8080}';
    protected $description = 'Start the WebSocket signaling server for video calls';

    public function handle()
    {
        $port = $this->option('port');
        
        $this->info("Starting WebSocket signaling server on port {$port}...");
        
        try {
            WebSocketSignalingService::startServer($port);
        } catch (\Exception $e) {
            $this->error("Failed to start WebSocket server: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
