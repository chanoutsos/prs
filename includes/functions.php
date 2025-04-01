<?php
require_once __DIR__ . '/../config/database.php';

function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function uploadFile($file, $allowedTypes = ALLOWED_FILE_TYPES, $maxSize = MAX_UPLOAD_SIZE) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("File upload error: " . $file['error']);
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        throw new Exception("File size exceeds maximum allowed size");
    }
    
    // Get file extension
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Check allowed types
    if (!in_array($fileExt, $allowedTypes)) {
        throw new Exception("File type not allowed");
    }
    
    // Generate unique filename
    $fileName = uniqid('', true) . '.' . $fileExt;
    $destination = UPLOAD_DIR . $fileName;
    
    // Create upload directory if it doesn't exist
    if (!file_exists(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception("Failed to move uploaded file");
    }
    
    return [
        'name' => $file['name'],
        'path' => '/uploads/' . $fileName,
        'type' => $fileExt,
        'size' => $file['size']
    ];
}
?>