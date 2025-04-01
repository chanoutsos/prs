<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth.php';

// ✅ Database connection (PDO with port 3307)
function getDatabaseConnection() {
    $host = 'localhost';
    $port = 3307;
    $db = 'prs_database';
    $user = 'root';
    $pass = '';

    return new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
}

$db = getDatabaseConnection();
$method = $_SERVER['REQUEST_METHOD'];
$response = [];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['user_id'])) {
                $stmt = $db->prepare("SELECT * FROM vaccination_records WHERE user_id = ?");
                $stmt->execute([$_GET['user_id']]);
                $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $response = ['status' => RESPONSE_SUCCESS, 'data' => $records];

            } elseif (isset($_GET['stats'])) {
                $stats = [];

                $stmt = $db->query("SELECT COUNT(*) as total FROM vaccination_records");
                $stats['total_vaccinations'] = $stmt->fetchColumn();

                $stmt = $db->query("SELECT vaccine_type, COUNT(*) as count FROM vaccination_records GROUP BY vaccine_type");
                $stats['by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $stmt = $db->query("SELECT DATE(vaccination_date) as date, COUNT(*) as count FROM vaccination_records GROUP BY DATE(vaccination_date) ORDER BY date");
                $stats['over_time'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $response = ['status' => RESPONSE_SUCCESS, 'data' => $stats];

            } else {
                if (isAuthenticated() && $_SESSION['role_id'] == ROLE_ADMIN) {
                    $stmt = $db->query("SELECT * FROM vaccination_records");
                    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $response = ['status' => RESPONSE_SUCCESS, 'data' => $records];
                } else {
                    http_response_code(RESPONSE_UNAUTHORIZED);
                    $response = ['status' => RESPONSE_UNAUTHORIZED, 'message' => 'Unauthorized'];
                }
            }
            break;

        case 'POST':
            if (isAuthenticated() && in_array($_SESSION['role_id'], [ROLE_ADMIN, ROLE_HEALTH_WORKER])) {
                $data = json_decode(file_get_contents("php://input"), true);

                if (!empty($data['user_id']) && !empty($data['vaccine_type']) && !empty($data['dose_number']) && !empty($data['vaccination_date'])) {
                    $stmt = $db->prepare("INSERT INTO vaccination_records (user_id, vaccine_type, dose_number, vaccination_date, healthcare_provider, location, batch_number, next_due_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $data['user_id'],
                        $data['vaccine_type'],
                        $data['dose_number'],
                        $data['vaccination_date'],
                        $data['healthcare_provider'] ?? null,
                        $data['location'] ?? null,
                        $data['batch_number'] ?? null,
                        $data['next_due_date'] ?? null
                    ]);

                    $recordId = $db->lastInsertId();
                    if (isset($_SESSION['user_id'])) {
                        logAudit($_SESSION['user_id'], ACTION_CREATE, 'vaccination_records', $recordId);
                    }

                    http_response_code(RESPONSE_CREATED);
                    $response = ['status' => RESPONSE_CREATED, 'message' => 'Vaccination record created', 'record_id' => $recordId];

                } else {
                    http_response_code(RESPONSE_BAD_REQUEST);
                    $response = ['status' => RESPONSE_BAD_REQUEST, 'message' => 'Missing required fields'];
                }
            } else {
                http_response_code(RESPONSE_UNAUTHORIZED);
                $response = ['status' => RESPONSE_UNAUTHORIZED, 'message' => 'Unauthorized'];
            }
            break;

        case 'PUT':
            if (isAuthenticated() && in_array($_SESSION['role_id'], [ROLE_ADMIN, ROLE_HEALTH_WORKER])) {
                $recordId = $_GET['id'] ?? null;
                $data = json_decode(file_get_contents("php://input"), true);

                if ($recordId) {
                    $updateFields = [];
                    $params = [];

                    foreach (['vaccine_type', 'dose_number', 'vaccination_date', 'healthcare_provider', 'location', 'batch_number', 'next_due_date'] as $field) {
                        if (!empty($data[$field])) {
                            $updateFields[] = "$field = ?";
                            $params[] = $data[$field];
                        }
                    }

                    if (!empty($updateFields)) {
                        $params[] = $recordId;
                        $sql = "UPDATE vaccination_records SET " . implode(', ', $updateFields) . " WHERE record_id = ?";
                        $stmt = $db->prepare($sql);
                        $stmt->execute($params);

                        if (isset($_SESSION['user_id'])) {
                            logAudit($_SESSION['user_id'], ACTION_UPDATE, 'vaccination_records', $recordId);
                        }

                        $response = ['status' => RESPONSE_SUCCESS, 'message' => 'Vaccination record updated'];
                    } else {
                        http_response_code(RESPONSE_BAD_REQUEST);
                        $response = ['status' => RESPONSE_BAD_REQUEST, 'message' => 'No valid fields to update'];
                    }
                } else {
                    http_response_code(RESPONSE_BAD_REQUEST);
                    $response = ['status' => RESPONSE_BAD_REQUEST, 'message' => 'Missing record ID'];
                }
            } else {
                http_response_code(RESPONSE_UNAUTHORIZED);
                $response = ['status' => RESPONSE_UNAUTHORIZED, 'message' => 'Unauthorized'];
            }
            break;

        case 'DELETE':
            $recordId = $_GET['id'] ?? null;

            if ($recordId && isAuthenticated() && $_SESSION['role_id'] == ROLE_ADMIN) {
                $stmt = $db->prepare("DELETE FROM vaccination_records WHERE record_id = ?");
                $stmt->execute([$recordId]);

                if (isset($_SESSION['user_id'])) {
                    logAudit($_SESSION['user_id'], ACTION_DELETE, 'vaccination_records', $recordId);
                }

                $response = ['status' => RESPONSE_SUCCESS, 'message' => 'Vaccination record deleted'];
            } else {
                http_response_code(RESPONSE_UNAUTHORIZED);
                $response = ['status' => RESPONSE_UNAUTHORIZED, 'message' => 'Unauthorized or missing ID'];
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