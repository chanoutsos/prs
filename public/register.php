<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(["error" => "Invalid request method"], 405);
}

// Sanitize input
$data = sanitizeInput($_POST);

// Required fields
$required = ['full_name', 'email', 'password', 'phone', 'national_id', 'prs_id', 'role_id'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        jsonResponse(["error" => "Missing field: $field"], 400);
    }
}

// Validate email
if (!validateEmail($data['email'])) {
    jsonResponse(["error" => "Invalid email format"], 400);
}

// Check if user already exists (email or national ID)
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR national_id = ?");
$stmt->bind_param("ss", $data['email'], $data['national_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    jsonResponse(["error" => "User with this email or national ID already exists"], 409);
}

// Insert new user
$passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash, phone, national_id, prs_id, role_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param(
    "ssssssi",
    $data['full_name'],
    $data['email'],
    $passwordHash,
    $data['phone'],
    $data['national_id'],
    $data['prs_id'],
    $data['role_id']
);

if ($stmt->execute()) {
    jsonResponse(["message" => "Registration successful"], 201);
} else {
    jsonResponse(["error" => "Registration failed: " . $stmt->error], 500);
}
?>
