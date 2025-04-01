<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];
$response = [];

try {
    $db = getDatabaseConnection();
    
    switch ($method) {
        case 'GET':
            // Get documents
            if (isset($_GET['id'])) {
                // Get single document
                $stmt = $db->prepare("SELECT * FROM documents WHERE doc_id = ?");
                $stmt->execute([$_GET['id']]);
                $document = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($document) {
                    // Verify user has access (owner or admin)
                    if (isAuthenticated() && ($_SESSION['user_id'] == $document['user_id'] || $_SESSION['role_id'] == ROLE_ADMIN)) {
                        $response = [
                            'status' => RESPONSE_SUCCESS,
                            'data' => $document
                        ];
                    } else {
                        http_response_code(RESPONSE_UNAUTHORIZED);
                        $response = ['status' => RESPONSE_UNAUTHORIZED, 'message' => 'Unauthorized access'];
                    }
                } else {
                    http_response_code(RESPONSE_NOT_FOUND);
                    $response = ['status' => RESPONSE_NOT_FOUND, 'message' => 'Document not found'];
                }
            } else {
                // Get documents list (user-specific or all for admin)
                if (isAuthenticated()) {
                    if ($_SESSION['role_id'] == ROLE_ADMIN) {
                        $stmt = $db->query("SELECT * FROM documents");
                    } else {
                        $stmt = $db->prepare("SELECT * FROM documents WHERE user_id = ?");
                        $stmt->execute([$_SESSION['user_id']]);
                    }
                    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $response = [
                        'status' => RESPONSE_SUCCESS,
                        'data' => $documents
                    ];
                } else {
                    http_response_code(RESPONSE_UNAUTHORIZED);
                    $response = ['status' => RESPONSE_UNAUTHORIZED, 'message' => 'Authentication required'];
                }
            }
            break;
            
        case 'POST':
            // Upload new document
            if (isAuthenticated()) {
                if (!empty($_FILES['document'])) {
                    try {
                        $uploadedFile = uploadFile($_FILES['document']);
                        
                        $stmt = $db->prepare("INSERT INTO documents (user_id, doc_name, doc_type, doc_path) VALUES (?, ?, ?, ?)");
                        $stmt->execute([
                            $_SESSION['user_id'],
                            basename($_FILES['document']['name']),
                            pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION),
                            $uploadedFile['path']
                        ]);
                        
                        $docId = $db->lastInsertId();
                        logAudit($_SESSION['user_id'], ACTION_CREATE, 'documents', $docId);
                        
                        http_response_code(RESPONSE_CREATED);
                        $response = [
                            'status' => RESPONSE_CREATED,
                            'message' => 'Document uploaded successfully',
                            'doc_id' => $docId,
                            'path' => $uploadedFile['path']
                        ];
                    } catch (Exception $e) {
                        http_response_code(RESPONSE_BAD_REQUEST);
                        $response = ['status' => RESPONSE_BAD_REQUEST, 'message' => $e->getMessage()];
                    }
                } else {
                    http_response_code(RESPONSE_BAD_REQUEST);
                    $response = ['status' => RESPONSE_BAD_REQUEST, 'message' => 'No document uploaded'];
                }
            } else {
                http_response_code(RESPONSE_UNAUTHORIZED);
                $response = ['status' => RESPONSE_UNAUTHORIZED, 'message' => 'Authentication required'];
            }
            break;
            
        case 'DELETE':
            // Delete document
            if (isAuthenticated()) {
                $docId = $_GET['id'] ?? null;
                
                if ($docId) {
                    // First verify ownership
                    $stmt = $db->prepare("SELECT user_id FROM documents WHERE doc_id = ?");
                    $stmt->execute([$docId]);
                    $document = $stmt->fetch();
                    
                    if ($document && ($_SESSION['user_id'] == $document['user_id'] || $_SESSION['role_id'] == ROLE_ADMIN)) {
                        // Get path before deletion
                        $stmt = $db->prepare("SELECT doc_path FROM documents WHERE doc_id = ?");
                        $stmt->execute([$docId]);
                        $docPath = $stmt->fetchColumn();
                        
                        // Delete from database
                        $stmt = $db->prepare("DELETE FROM documents WHERE doc_id = ?");
                        $stmt->execute([$docId]);
                        
                        // Delete physical file
                        if ($docPath && file_exists($_SERVER['DOCUMENT_ROOT'] . $docPath)) {
                            unlink($_SERVER['DOCUMENT_ROOT'] . $docPath);
                        }
                        
                        logAudit($_SESSION['user_id'], ACTION_DELETE, 'documents', $docId);
                        
                        $response = [
                            'status' => RESPONSE_SUCCESS,
                            'message' => 'Document deleted successfully'
                        ];
                    } else {
                        http_response_code(RESPONSE_UNAUTHORIZED);
                        $response = ['status' => RESPONSE_UNAUTHORIZED, 'message' => 'Unauthorized access'];
                    }
                } else {
                    http_response_code(RESPONSE_BAD_REQUEST);
                    $response = ['status' => RESPONSE_BAD_REQUEST, 'message' => 'Missing document ID'];
                }
            } else {
                http_response_code(RESPONSE_UNAUTHORIZED);
                $response = ['status' => RESPONSE_UNAUTHORIZED, 'message' => 'Authentication required'];
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
