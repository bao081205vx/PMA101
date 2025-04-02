<?php
function sendResponse($status = 200, $success = true, $message = '', $data = null) {
    http_response_code($status);
    
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}
