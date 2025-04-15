<?php
header('HTTP/1.1 404 Not Found');
header('Content-Type: application/json');
echo json_encode([
    'success' => false,
    'message' => 'API endpoint not found'
]);
