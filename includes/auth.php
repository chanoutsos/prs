<?php
require_once __DIR__ . '/../config/database.php';

session_start();

function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

function authenticate($username, $password) {
    $db = getDatabaseConnection();
    
    $stmt = $db->prepare("SELECT user_id, username, password_hash, role_id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role_id'] = $user['role_id'];
        
        logAudit($user['user_id'], ACTION_LOGIN, 'users', $user['user_id']);
        return true;
    }
    
    return false;
}

function logout() {
    if (isAuthenticated()) {
        logAudit($_SESSION['user_id'], ACTION_LOGOUT, 'users', $_SESSION['user_id']);
    }
    
    session_unset();
    session_destroy();
}

function requireAuth() {
    if (!isAuthenticated()) {
        http_response_code(RESPONSE_UNAUTHORIZED);
        echo json_encode(['status' => RESPONSE_UNAUTHORIZED, 'message' => 'Authentication required']);
        exit;
    }
}

function requireRole($requiredRole) {
    requireAuth();
    
    if ($_SESSION['role_id'] != $requiredRole) {
        http_response_code(RESPONSE_FORBIDDEN);
        echo json_encode(['status' => RESPONSE_FORBIDDEN, 'message' => 'Insufficient privileges']);
        exit;
    }
}

function getCurrentUser() {
    if (isAuthenticated()) {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("SELECT user_id, username, email, full_name, role_id FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }
    return null;
}

function logAudit($userId, $action, $table, $recordId) {
    $db = getDatabaseConnection();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    $stmt = $db->prepare("INSERT INTO audit_logs (user_id, action, table_affected, record_id, ip_address) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $action, $table, $recordId, $ip]);
}
?>