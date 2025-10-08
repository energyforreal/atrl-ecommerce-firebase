<?php
/**
 * 🔄 ATTRAL Affiliate Sync to Brevo
 * Syncs affiliate data from Firestore to Brevo email list #10
 * 
 * Features:
 * - Fetches all affiliates from Firestore
 * - Syncs to Brevo affiliate list (#10)
 * - Handles batch processing for large datasets
 * - Comprehensive error handling and logging
 * - Duplicate prevention
 */

// Only set headers if running in web context
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

require_once 'config.php';
require_once 'brevo_email_service.php';

// Composer autoload for Firebase Admin SDK
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

use Google\Cloud\Firestore\FirestoreClient;

class AffiliateSyncService {
    private $firestore;
    private $brevoService;
    private $logFile;
    
    public function __construct() {
        $this->initFirestore();
        $this->brevoService = new BrevoEmailService();
        $this->logFile = __DIR__ . '/logs/affiliate_sync_' . date('Y-m-d') . '.log';
        $this->ensureLogDirectory();
    }
    
    private function initFirestore() {
        try {
            $serviceAccountPath = __DIR__ . '/firebase-service-account.json';
            if (!file_exists($serviceAccountPath)) {
                throw new Exception('Firebase service account file not found');
            }
            
            $this->firestore = new FirestoreClient([
                'projectId' => 'e-commerce-1d40f',
                'keyFilePath' => $serviceAccountPath
            ]);
            
            $this->log('Firestore initialized successfully');
        } catch (Exception $e) {
            $this->log('Firestore initialization failed: ' . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    private function ensureLogDirectory() {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    private function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Also output to console for real-time monitoring
        echo $logEntry;
    }
    
    /**
     * Fetch all affiliates from Firestore
     */
    public function fetchAffiliatesFromFirestore() {
        try {
            $this->log('Fetching affiliates from Firestore...');
            
            $affiliatesRef = $this->firestore->collection('affiliates');
            $snapshot = $affiliatesRef->orderBy('createdAt', 'desc')->get();
            
            $affiliates = [];
            foreach ($snapshot as $doc) {
                $data = $doc->data();
                $affiliates[] = [
                    'id' => $doc->id(),
                    'uid' => $data['uid'] ?? $doc->id(),
                    'email' => $data['email'] ?? null,
                    'name' => $data['displayName'] ?? $data['name'] ?? $data['email'] ?? 'Unknown',
                    'code' => $data['code'] ?? null,
                    'status' => $data['status'] ?? 'active',
                    'totalEarnings' => $data['totalEarnings'] ?? 0,
                    'totalReferrals' => $data['totalReferrals'] ?? 0,
                    'createdAt' => $data['createdAt'] ?? null,
                    'lastSync' => $data['lastSync'] ?? null
                ];
            }
            
            $this->log('Fetched ' . count($affiliates) . ' affiliates from Firestore');
            return $affiliates;
            
        } catch (Exception $e) {
            $this->log('Error fetching affiliates from Firestore: ' . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Sync single affiliate to Brevo
     */
    public function syncAffiliateToBrevo($affiliate) {
        try {
            if (empty($affiliate['email'])) {
                $this->log("Skipping affiliate {$affiliate['id']} - no email address", 'WARNING');
                return ['success' => false, 'error' => 'No email address'];
            }
            
            // Prepare attributes for Brevo
            $attributes = [
                'FIRSTNAME' => $affiliate['name'],
                'AFFILIATE_CODE' => $affiliate['code'] ?? '',
                'AFFILIATE_ID' => $affiliate['id'],
                'AFFILIATE_UID' => $affiliate['uid'],
                'STATUS' => $affiliate['status'],
                'TOTAL_EARNINGS' => $affiliate['totalEarnings'],
                'TOTAL_REFERRALS' => $affiliate['totalReferrals'],
                'SIGNUP_DATE' => $affiliate['createdAt'] ? $affiliate['createdAt']->format('Y-m-d') : date('Y-m-d'),
                'LAST_SYNC' => date('Y-m-d H:i:s')
            ];
            
            // Add to Brevo affiliate list
            $result = $this->brevoService->addToAffiliateList(
                $affiliate['email'],
                $affiliate['name'],
                $attributes
            );
            
            if ($result['success']) {
                $this->log("Successfully synced affiliate {$affiliate['id']} ({$affiliate['email']}) to Brevo");
                
                // Update lastSync timestamp in Firestore
                $this->updateLastSyncInFirestore($affiliate['id']);
                
                return ['success' => true, 'data' => $result['data']];
            } else {
                $this->log("Failed to sync affiliate {$affiliate['id']} to Brevo: " . ($result['error'] ?? 'Unknown error'), 'ERROR');
                return ['success' => false, 'error' => $result['error'] ?? 'Unknown error'];
            }
            
        } catch (Exception $e) {
            $this->log("Exception syncing affiliate {$affiliate['id']}: " . $e->getMessage(), 'ERROR');
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Update lastSync timestamp in Firestore
     */
    private function updateLastSyncInFirestore($affiliateId) {
        try {
            $docRef = $this->firestore->collection('affiliates')->document($affiliateId);
            $docRef->update([
                ['path' => 'lastSync', 'value' => new DateTime()]
            ]);
        } catch (Exception $e) {
            $this->log("Failed to update lastSync for affiliate $affiliateId: " . $e->getMessage(), 'WARNING');
        }
    }
    
    /**
     * Sync all affiliates to Brevo with batch processing
     */
    public function syncAllAffiliatesToBrevo($batchSize = 10, $delayBetweenBatches = 2) {
        try {
            $this->log('Starting affiliate sync to Brevo...');
            
            $affiliates = $this->fetchAffiliatesFromFirestore();
            $totalAffiliates = count($affiliates);
            $successCount = 0;
            $errorCount = 0;
            $skippedCount = 0;
            
            $this->log("Processing $totalAffiliates affiliates in batches of $batchSize");
            
            // Process in batches
            $batches = array_chunk($affiliates, $batchSize);
            
            foreach ($batches as $batchIndex => $batch) {
                $this->log("Processing batch " . ($batchIndex + 1) . "/" . count($batches) . " (" . count($batch) . " affiliates)");
                
                foreach ($batch as $affiliate) {
                    $result = $this->syncAffiliateToBrevo($affiliate);
                    
                    if ($result['success']) {
                        $successCount++;
                    } elseif (strpos($result['error'], 'No email address') !== false) {
                        $skippedCount++;
                    } else {
                        $errorCount++;
                    }
                }
                
                // Delay between batches to avoid rate limiting
                if ($batchIndex < count($batches) - 1) {
                    $this->log("Waiting $delayBetweenBatches seconds before next batch...");
                    sleep($delayBetweenBatches);
                }
            }
            
            $summary = [
                'total' => $totalAffiliates,
                'success' => $successCount,
                'errors' => $errorCount,
                'skipped' => $skippedCount,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            $this->log("Sync completed! Total: $totalAffiliates, Success: $successCount, Errors: $errorCount, Skipped: $skippedCount");
            
            return [
                'success' => true,
                'summary' => $summary,
                'message' => "Successfully synced $successCount out of $totalAffiliates affiliates to Brevo"
            ];
            
        } catch (Exception $e) {
            $this->log('Error during affiliate sync: ' . $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get sync status and statistics
     */
    public function getSyncStatus() {
        try {
            $affiliates = $this->fetchAffiliatesFromFirestore();
            
            $totalAffiliates = count($affiliates);
            $withEmail = count(array_filter($affiliates, function($a) { return !empty($a['email']); }));
            $lastSynced = count(array_filter($affiliates, function($a) { return !empty($a['lastSync']); }));
            $neverSynced = $totalAffiliates - $lastSynced;
            
            return [
                'success' => true,
                'stats' => [
                    'total_affiliates' => $totalAffiliates,
                    'with_email' => $withEmail,
                    'last_synced' => $lastSynced,
                    'never_synced' => $neverSynced,
                    'sync_percentage' => $totalAffiliates > 0 ? round(($lastSynced / $totalAffiliates) * 100, 2) : 0
                ]
            ];
            
        } catch (Exception $e) {
            $this->log('Error getting sync status: ' . $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Sync specific affiliate by ID
     */
    public function syncAffiliateById($affiliateId) {
        try {
            $this->log("Syncing specific affiliate: $affiliateId");
            
            $docRef = $this->firestore->collection('affiliates')->document($affiliateId);
            $doc = $docRef->get();
            
            if (!$doc->exists()) {
                return ['success' => false, 'error' => 'Affiliate not found'];
            }
            
            $data = $doc->data();
            $affiliate = [
                'id' => $doc->id(),
                'uid' => $data['uid'] ?? $doc->id(),
                'email' => $data['email'] ?? null,
                'name' => $data['displayName'] ?? $data['name'] ?? $data['email'] ?? 'Unknown',
                'code' => $data['code'] ?? null,
                'status' => $data['status'] ?? 'active',
                'totalEarnings' => $data['totalEarnings'] ?? 0,
                'totalReferrals' => $data['totalReferrals'] ?? 0,
                'createdAt' => $data['createdAt'] ?? null
            ];
            
            return $this->syncAffiliateToBrevo($affiliate);
            
        } catch (Exception $e) {
            $this->log("Error syncing affiliate $affiliateId: " . $e->getMessage(), 'ERROR');
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

// ==================== API ENDPOINTS ====================

if (php_sapi_name() !== 'cli' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['action'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid request']);
        exit;
    }
    
    $syncService = new AffiliateSyncService();
    $action = $input['action'];
    
    try {
        switch ($action) {
            case 'sync_all':
                $batchSize = $input['batch_size'] ?? 10;
                $delay = $input['delay_between_batches'] ?? 2;
                $result = $syncService->syncAllAffiliatesToBrevo($batchSize, $delay);
                break;
                
            case 'sync_specific':
                if (empty($input['affiliate_id'])) {
                    throw new Exception('Affiliate ID is required');
                }
                $result = $syncService->syncAffiliateById($input['affiliate_id']);
                break;
                
            case 'get_status':
                $result = $syncService->getSyncStatus();
                break;
                
            case 'fetch_affiliates':
                $result = [
                    'success' => true,
                    'affiliates' => $syncService->fetchAffiliatesFromFirestore()
                ];
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Unknown action']);
                exit;
        }
        
        echo json_encode($result);
        
    } catch (Exception $e) {
        error_log("Affiliate Sync Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} elseif (php_sapi_name() !== 'cli') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
?>
