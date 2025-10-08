<?php
// Simple endpoint that returns the API configuration for JavaScript
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Load configuration
require_once __DIR__ . '/config.php';

// Brevo API configuration
$config = [
    'apiKey' => defined('BREVO_API_KEY') ? BREVO_API_KEY : getenv('BREVO_API_KEY'),
    'apiUrl' => 'https://api.brevo.com/v3/contacts',
    'listId' => 3, // Attral Shopping list ID
    'method' => 'POST'
];

echo json_encode($config);
?>
