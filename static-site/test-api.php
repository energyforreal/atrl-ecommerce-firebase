<?php 
header('Content-Type: application/json'); 
echo json_encode([ 
    'status' => 'ok', 
    'message' => 'ATTRAL API is working', 
    'timestamp' => date('Y-m-d H:i:s'), 
    'php_version' => PHP_VERSION 
]); 
?> 
