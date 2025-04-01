<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth.php';

function getDatabaseConnection() {
    $host = 'localhost';
    $port = 3307;
    $db   = 'prs_database';
    $user = 'root';
    $pass = '';

    try {
        return new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
        exit;
    }
}

$method = $_SERVER['REQUEST_METHOD'];
$response = [];

try {
    $db = getDatabaseConnection();

    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $stmt = $db->prepare("SELECT user_id, username, email, full_name, role_id FROM users WHERE user_id = ?");
                $stmt->execute([$_GET['id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    $response = ['status' => RESPONSE_SUCCESS, 'data' => $user];
                } else {
                    http_response_code(RESPONSE_NOT_FOUND);
                    $response = ['status' => RESPONSE_NOT_FOUND, 'message' => 'User not found'];
                }
            } else {
                $stmt = $db->query("SELECT user_id, username, email, full_name, role_id FROM users");
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $response = ['status' => RESPONSE_SUCCESS, 'data' => $users];
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents("php://input"), true);

            if (!empty($data['username']) && !empty($data['password']) && !empty($data['email']) && !empty($data['full_name']) && isset($data['role_id'])) {
                $stmt = $db->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$data['username'], $data['email']]);

                if ($stmt->fetch()) {
                    http_response_code(RESPONSE_BAD_REQUEST);
                    $response = ['status' => RESPONSE_BAD_REQUEST, 'message' => 'Username or email already exists'];
                } else {
                    $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
                    $stmt = $db->prepare("INSERT INTO users (username, password_hash, email, full_name, role_id) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$data['username'], $hashedPassword, $data['email'], $data['full_name'], $data['role_id']]);

                    $userId = $db->lastInsertId();
                    logAudit($_SESSION['user_id'] ?? null, ACTION_CREATE, 'users', $userId);

                    http_response_code(RESPONSE_CREATED);
                    $response = ['status' => RESPONSE_CREATED, 'message' => 'User created successfully', 'user_id' => $userId];
                }
            } else {
                http_response_code(RESPONSE_BAD_REQUEST);
                $response = ['status' => RESPONSE_BAD_REQUEST, 'message' => 'Missing required fields'];
            }
            break;

        case 'PUT':
            $data = json_decode(file_get_contents("php://input"), true);
            $userId = $_GET['id'] ?? null;

            if ($userId && isAuthenticated()) {
                $updateFields = [];
                $params = [];

                if (!empty($data['email'])) {
                    $updateFields[] = 'email = ?';
                    $params[] = $data['email'];
                }

                if (!empty($data['full_name'])) {
                    $updateFields[] = 'full_name = ?';
                    $params[] = $data['full_name'];
                }

                if (!empty($data['role_id']) && $_SESSION['role_id'] == ROLE_ADMIN) {
                    $updateFields[] = 'role_id = ?';
                    $params[] = $data['role_id'];
                }

                if (!empty($updateFields)) {
                    $params[] = $userId;
                    $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE user_id = ?";
                    $stmt = $db->prepare($sql);
                    $stmt->execute($params);

                    logAudit($_SESSION['user_id'], ACTION_UPDATE, 'users', $userId);
                    $response = ['status' => RESPONSE_SUCCESS, 'message' => 'User updated successfully'];
                } else {
                    http_response_code(RESPONSE_BAD_REQUEST);
                    $response = ['status' => RESPONSE_BAD_REQUEST, 'message' => 'No valid fields to update'];
                }
            } else {
                http_response_code(RESPONSE_UNAUTHORIZED);
                $response = ['status' => RESPONSE_UNAUTHORIZED, 'message' => 'Unauthorized'];
            }
            break;

        case 'DELETE':
            $userId = $_GET['id'] ?? null;

            if ($userId && isAuthenticated() && $_SESSION['role_id'] == ROLE_ADMIN) {
                $stmt = $db->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt->execute([$userId]);

                logAudit($_SESSION['user_id'], ACTION_DELETE, 'users', $userId);
                $response = ['status' => RESPONSE_SUCCESS, 'message' => 'User deleted successfully'];
            } else {
                http_response_code(RESPONSE_UNAUTHORIZED);
                $response = ['status' => RESPONSE_UNAUTHORIZED, 'message' => 'Unauthorized'];
            }
            break;

        default:
            http_response_code(RESPONSE_BAD_REQUEST);
            $response = ['status' => RESPONSE_BAD_REQUEST, 'message' => 'Invalid request method'];
    }
} catch (PDOException $e) {
    http_response_code(RESPONSE_SERVER_ERROR);
    $response = ['status' => RESPONSE_SERVER_ERROR, 'message' => 'Database error: ' . $e->getMessage()];
} catch (Exception $e) {
    http_response_code(RESPONSE_SERVER_ERROR);
    $response = ['status' => RESPONSE_SERVER_ERROR, 'message' => 'Error: ' . $e->getMessage()];
}

echo json_encode($response);
?>
