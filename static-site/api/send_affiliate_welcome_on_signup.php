<?php
/**
 * 🎉 Send Welcome Email on Affiliate Signup
 * Automatically sends welcome email when affiliate profile is created in Firestore
 */

require_once __DIR__ . '/affiliate_email_sender.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Required fields: affiliateId (Firestore document ID)
    if (!isset($input['affiliateId'])) {
        throw new Exception('affiliateId is required');
    }
    
    $affiliateId = $input['affiliateId'];
    
    // Get affiliate data from Firestore
    $affiliateData = getAffiliateFromFirestore($affiliateId);
    
    if (!$affiliateData) {
        throw new Exception('Affiliate not found in Firestore');
    }
    
    // Send welcome email using Firestore data
    $result = sendAffiliateWelcomeEmail(null, [
        'email' => $affiliateData['email'],
        'name' => $affiliateData['displayName'],
        'affiliateCode' => $affiliateData['code']
    ]);
    
    if ($result['success']) {
        error_log("AFFILIATE WELCOME: Email sent to {$affiliateData['email']} for new affiliate {$affiliateData['code']}");
        
        echo json_encode([
            'success' => true,
            'message' => 'Welcome email sent successfully',
            'affiliateId' => $affiliateId,
            'email' => $affiliateData['email'],
            'name' => $affiliateData['displayName'],
            'code' => $affiliateData['code'],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        throw new Exception($result['error'] ?? 'Failed to send welcome email');
    }
    
} catch (Exception $e) {
    error_log("AFFILIATE WELCOME ERROR: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Get affiliate data from Firestore by document ID
 */
function getAffiliateFromFirestore($affiliateId) {
    try {
        // Initialize Firebase
        $serviceAccountPath = __DIR__ . '/firebase-service-account.json';
        if (!file_exists($serviceAccountPath)) {
            error_log("AFFILIATE WELCOME: Firebase service account file not found");
            return null;
        }
        
        $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
        $firebase = (new \Kreait\Firebase\Factory())
            ->withServiceAccount($serviceAccount)
            ->create();
        
        $firestore = $firebase->firestore();
        
        // Get affiliate document by ID
        $docRef = $firestore->collection('affiliates')->document($affiliateId);
        $doc = $docRef->snapshot();
        
        if ($doc->exists()) {
            $data = $doc->data();
            return [
                'id' => $doc->id(),
                'email' => $data['email'] ?? '',
                'displayName' => $data['displayName'] ?? $data['name'] ?? 'Affiliate',
                'code' => $data['code'] ?? '',
                'status' => $data['status'] ?? 'active',
                'createdAt' => $data['createdAt'] ?? null
            ];
        }
        
        return null;
        
    } catch (Exception $e) {
        error_log("AFFILIATE WELCOME FIRESTORE ERROR: " . $e->getMessage());
        return null;
    }
}
?>
