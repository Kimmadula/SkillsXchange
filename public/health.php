<?php
/**
 * Health Check Endpoint for WebSocket Service
 * This endpoint is used by Render to check if the WebSocket service is running
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Simple health check response
$response = [
    'status' => 'healthy',
    'service' => 'websocket-signaling',
    'timestamp' => date('Y-m-d H:i:s'),
    'uptime' => time() - $_SERVER['REQUEST_TIME_FLOAT']
];

http_response_code(200);
echo json_encode($response);
?>