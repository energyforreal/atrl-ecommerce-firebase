<?php
/**
 * Admin Firestore Bypass API
 * This API provides admin access to Firestore data without permission restrictions
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Admin authentication check
function verifyAdminAccess() {
    // Check for admin session in localStorage (passed via request)
    $adminAuth = $_POST['adminAuth'] ?? $_GET['adminAuth'] ?? '';
    $adminUser = $_POST['adminUser'] ?? $_GET['adminUser'] ?? '';
    
    if ($adminAuth === 'true' && !empty($adminUser)) {
        $userData = json_decode($adminUser, true);
        if ($userData && $userData['email'] === 'attralsolar@gmail.com') {
            return true;
        }
    }
    
    return false;
}

// Firebase Admin SDK setup
require_once __DIR__ . '/../vendor/autoload.php';

use Google\Cloud\Firestore\FirestoreClient;

function getFirestoreClient() {
    $keyFile = __DIR__ . '/firebase-service-account.json';
    
    if (!file_exists($keyFile)) {
        throw new Exception('Firebase service account key file not found');
    }
    
    $firestore = new FirestoreClient([
        'keyFile' => $keyFile,
        'projectId' => 'e-commerce-1d40f'
    ]);
    
    return $firestore;
}

try {
    // Verify admin access
    if (!verifyAdminAccess()) {
        http_response_code(403);
        echo json_encode(['error' => 'Admin access required']);
        exit();
    }
    
    $firestore = getFirestoreClient();
    $method = $_SERVER['REQUEST_METHOD'];
    $path = $_GET['path'] ?? '';
    
    switch ($method) {
        case 'GET':
            if (empty($path)) {
                // Get all collections data
                $collections = ['orders', 'users', 'messages', 'coupons', 'products', 'affiliates'];
                $data = [];
                
                foreach ($collections as $collection) {
                    try {
                        $collectionRef = $firestore->collection($collection);
                        $documents = $collectionRef->documents();
                        
                        $collectionData = [];
                        foreach ($documents as $document) {
                            $docData = $document->data();
                            $docData['id'] = $document->id();
                            $collectionData[] = $docData;
                        }
                        
                        $data[$collection] = $collectionData;
                    } catch (Exception $e) {
                        $data[$collection] = [];
                        error_log("Error fetching $collection: " . $e->getMessage());
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'data' => $data,
                    'timestamp' => date('c')
                ]);
            } else {
                // Get specific collection
                $collectionRef = $firestore->collection($path);
                $documents = $collectionRef->documents();
                
                $data = [];
                foreach ($documents as $document) {
                    $docData = $document->data();
                    $docData['id'] = $document->id();
                    $data[] = $docData;
                }
                
                echo json_encode([
                    'success' => true,
                    'data' => $data,
                    'collection' => $path,
                    'count' => count($data),
                    'timestamp' => date('c')
                ]);
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            $collection = $input['collection'] ?? $path;
            $documentData = $input['data'] ?? [];
            
            if (empty($collection) || empty($documentData)) {
                http_response_code(400);
                echo json_encode(['error' => 'Collection and data required']);
                exit();
            }
            
            $docRef = $firestore->collection($collection)->add($documentData);
            
            echo json_encode([
                'success' => true,
                'documentId' => $docRef->id(),
                'message' => 'Document created successfully'
            ]);
            break;
            
        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true);
            $collection = $input['collection'] ?? $path;
            $documentId = $input['documentId'] ?? '';
            $documentData = $input['data'] ?? [];
            
            if (empty($collection) || empty($documentId) || empty($documentData)) {
                http_response_code(400);
                echo json_encode(['error' => 'Collection, documentId and data required']);
                exit();
            }
            
            $docRef = $firestore->collection($collection)->document($documentId);
            $docRef->set($documentData);
            
            echo json_encode([
                'success' => true,
                'message' => 'Document updated successfully'
            ]);
            break;
            
        case 'DELETE':
            $input = json_decode(file_get_contents('php://input'), true);
            $collection = $input['collection'] ?? $path;
            $documentId = $input['documentId'] ?? '';
            
            if (empty($collection) || empty($documentId)) {
                http_response_code(400);
                echo json_encode(['error' => 'Collection and documentId required']);
                exit();
            }
            
            $docRef = $firestore->collection($collection)->document($documentId);
            $docRef->delete();
            
            echo json_encode([
                'success' => true,
                'message' => 'Document deleted successfully'
            ]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
?>













