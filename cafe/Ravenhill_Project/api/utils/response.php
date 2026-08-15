<?php
function sendResponse($success, $message, $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');

    $response = [
        'success' => $success,
        'message' => $message,
    ];

    if ($data !== null) {
        $response['data'] = $data;
    }

    echo json_encode($response);
    exit;
}

function getRequestBody() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return $data ?? [];
}

function requireMethod($method) {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        http_response_code(204);
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
        sendResponse(false, 'Method not allowed. Expected: ' . $method, null, 405);
    }
}
