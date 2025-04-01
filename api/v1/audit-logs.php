<?php
require_once __DIR__ . '/../config/database.php'';
header('Content-Type: application/json');

// Dummy API response; replace with actual logic
echo json_encode([
    'status' => 200,
    'data' => [
        ['action' => 'Login', 'table_affected' => 'users', 'action_time' => '2025-04-01 10:00:00']
    ]
]);
?>
