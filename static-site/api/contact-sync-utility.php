<?php
/**
 * 🔄 ATTRAL Contact Sync Utility
 * Syncs Firestore users and affiliates with Brevo contact lists
 * 
 * Features:
 * - Sync Firestore users to Brevo customer list
 * - Sync Firestore affiliates to Brevo affiliate list
 * - Batch processing for large datasets
 * - Duplicate prevention
 * - Sync status tracking
 */

// Suppress warnings and errors to prevent JSON corruption
error_reporting(0);
ini_set('display_errors', 0);

// Only set headers if running in web context and no output has been sent
if (php_sapi_name() !== 'cli' && !headers_sent()) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

// Load dependencies
require_once __DIR__ . '/firestore_admin_service.php';
require_once __DIR__ . '/admin-email-system.php';

// Load configuration if not already loaded
if (!defined('BREVO_API_KEY')) {
    require_once __DIR__ . '/config.php';
}

// Brevo Configuration (use config if available, otherwise define)
if (!defined('BREVO_API_URL')) {
    define('BREVO_API_URL', 'https://api.brevo.com/v3');
}
if (!defined('BREVO_CUSTOMER_LIST_ID')) {
    define('BREVO_CUSTOMER_LIST_ID', 3); // Attral Shopping - Customer contacts
}
if (!defined('BREVO_AFFILIATE_LIST_ID')) {
    define('BREVO_AFFILIATE_LIST_ID', 10); // e-Commerce Affiliates - Affiliate contacts
}

class ContactSyncUtility {
    
    private $firestoreService;
    private $emailSystem;
    private $apiKey;
    private $apiUrl;
    
    public function __construct() {
        $this->firestoreService = new FirestoreAdminService();
        $this->emailSystem = new AdminEmailSystem();
        $this->apiKey = BREVO_API_KEY;
        $this->apiUrl = BREVO_API_URL;
    }
    
    /**
     * Sync all Firestore users to Brevo customer list
     */
    public function syncUsersToBrevo($limit = 100, $offset = 0) {
        try {
            $users = $this->firestoreService->getCollection('users', [
                'orderBy' => 'created_at',
                'direction' => 'desc',
                'limit' => $limit,
                'offset' => $offset
            ]);
            
            $results = [];
            $successCount = 0;
            $failureCount = 0;
            
            foreach ($users as $user) {
                if (empty($user['email'])) {
                    continue;
                }
                
                $attributes = [
                    'FIRSTNAME' => $user['displayName'] ?? $user['name'] ?? '',
                    'PHONE' => $user['phone'] ?? '',
                    'USER_ID' => $user['id'] ?? '',
                    'IS_AFFILIATE' => ($user['is_affiliate'] ?? false) ? 'true' : 'false',
                    'CREATED_AT' => $user['created_at'] ?? date('Y-m-d H:i:s'),
                    'LAST_LOGIN' => $user['lastLoginAt'] ?? ''
                ];
                
                $result = $this->emailSystem->addContactToBrevoList(
                    $user['email'],
                    $attributes['FIRSTNAME'],
                    BREVO_CUSTOMER_LIST_ID,
                    $attributes
                );
                
                if ($result['success']) {
                    $successCount++;
                } else {
                    $failureCount++;
                }
                
                $results[] = [
                    'email' => $user['email'],
                    'name' => $attributes['FIRSTNAME'],
                    'success' => $result['success'],
                    'error' => $result['error'] ?? null
                ];
                
                // Add small delay to avoid rate limiting
                usleep(100000); // 0.1 second delay
            }
            
            return [
                'success' => true,
                'totalProcessed' => count($users),
                'successCount' => $successCount,
                'failureCount' => $failureCount,
                'results' => $results
            ];
            
        } catch (Exception $e) {
            error_log("SYNC USERS ERROR: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Sync all Firestore affiliates to Brevo affiliate list
     */
    public function syncAffiliatesToBrevo($limit = 100, $offset = 0) {
        try {
            $affiliates = $this->firestoreService->getCollection('affiliates', [
                'orderBy' => 'createdAt',
                'direction' => 'desc',
                'limit' => $limit,
                'offset' => $offset
            ]);
            
            $results = [];
            $successCount = 0;
            $failureCount = 0;
            
            foreach ($affiliates as $affiliate) {
                if (empty($affiliate['email'])) {
                    continue;
                }
                
                $attributes = [
                    'FIRSTNAME' => $affiliate['displayName'] ?? $affiliate['name'] ?? '',
                    'AFFILIATE_CODE' => $affiliate['code'] ?? '',
                    'AFFILIATE_ID' => $affiliate['id'] ?? '',
                    'STATUS' => $affiliate['status'] ?? 'active',
                    'TOTAL_EARNINGS' => $affiliate['totalEarnings'] ?? 0,
                    'TOTAL_REFERRALS' => $affiliate['totalReferrals'] ?? 0,
                    'CREATED_AT' => $affiliate['createdAt'] ?? date('Y-m-d H:i:s')
                ];
                
                $result = $this->emailSystem->addContactToBrevoList(
                    $affiliate['email'],
                    $attributes['FIRSTNAME'],
                    BREVO_AFFILIATE_LIST_ID,
                    $attributes
                );
                
                if ($result['success']) {
                    $successCount++;
                } else {
                    $failureCount++;
                }
                
                $results[] = [
                    'email' => $affiliate['email'],
                    'name' => $attributes['FIRSTNAME'],
                    'code' => $attributes['AFFILIATE_CODE'],
                    'success' => $result['success'],
                    'error' => $result['error'] ?? null
                ];
                
                // Add small delay to avoid rate limiting
                usleep(100000); // 0.1 second delay
            }
            
            return [
                'success' => true,
                'totalProcessed' => count($affiliates),
                'successCount' => $successCount,
                'failureCount' => $failureCount,
                'results' => $results
            ];
            
        } catch (Exception $e) {
            error_log("SYNC AFFILIATES ERROR: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Sync all contacts (users + affiliates) to Brevo
     */
    public function syncAllContactsToBrevo($limit = 100) {
        try {
            $userResults = $this->syncUsersToBrevo($limit);
            $affiliateResults = $this->syncAffiliatesToBrevo($limit);
            
            return [
                'success' => $userResults['success'] && $affiliateResults['success'],
                'users' => $userResults,
                'affiliates' => $affiliateResults,
                'summary' => [
                    'totalProcessed' => ($userResults['totalProcessed'] ?? 0) + ($affiliateResults['totalProcessed'] ?? 0),
                    'totalSuccessful' => ($userResults['successCount'] ?? 0) + ($affiliateResults['successCount'] ?? 0),
                    'totalFailed' => ($userResults['failureCount'] ?? 0) + ($affiliateResults['failureCount'] ?? 0)
                ]
            ];
            
        } catch (Exception $e) {
            error_log("SYNC ALL CONTACTS ERROR: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get sync status and statistics
     */
    public function getSyncStatus() {
        try {
            // Get Firestore counts
            $firestoreUsers = $this->firestoreService->getCollection('users');
            $firestoreAffiliates = $this->firestoreService->getCollection('affiliates');
            
            $firestoreUserCount = count($firestoreUsers);
            $firestoreAffiliateCount = count($firestoreAffiliates);
            
            // Get Brevo list statistics
            $brevoLists = $this->emailSystem->getBrevoContactLists();
            $brevoStats = [];
            
            if ($brevoLists['success']) {
                foreach ($brevoLists['lists'] as $list) {
                    if ($list['id'] == BREVO_CUSTOMER_LIST_ID) {
                        $brevoStats['customers'] = $list['totalSubscribers'] ?? 0;
                    }
                    if ($list['id'] == BREVO_AFFILIATE_LIST_ID) {
                        $brevoStats['affiliates'] = $list['totalSubscribers'] ?? 0;
                    }
                }
            }
            
            return [
                'success' => true,
                'firestore' => [
                    'users' => $firestoreUserCount,
                    'affiliates' => $firestoreAffiliateCount,
                    'total' => $firestoreUserCount + $firestoreAffiliateCount
                ],
                'brevo' => [
                    'customers' => $brevoStats['customers'] ?? 0,
                    'affiliates' => $brevoStats['affiliates'] ?? 0,
                    'total' => ($brevoStats['customers'] ?? 0) + ($brevoStats['affiliates'] ?? 0)
                ],
                'syncStatus' => [
                    'usersSynced' => min($firestoreUserCount, $brevoStats['customers'] ?? 0),
                    'affiliatesSynced' => min($firestoreAffiliateCount, $brevoStats['affiliates'] ?? 0),
                    'usersPending' => max(0, $firestoreUserCount - ($brevoStats['customers'] ?? 0)),
                    'affiliatesPending' => max(0, $firestoreAffiliateCount - ($brevoStats['affiliates'] ?? 0))
                ]
            ];
            
        } catch (Exception $e) {
            error_log("SYNC STATUS ERROR: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Remove duplicate contacts from Brevo lists
     */
    public function removeDuplicateContacts($listId) {
        try {
            // Get all contacts from the list
            $contacts = $this->emailSystem->getBrevoContacts($listId, 1000, 0);
            
            if (!$contacts['success']) {
                return $contacts;
            }
            
            $emailCounts = [];
            $duplicates = [];
            
            // Count email occurrences
            foreach ($contacts['contacts'] as $contact) {
                $email = $contact['email'];
                if (!isset($emailCounts[$email])) {
                    $emailCounts[$email] = 0;
                }
                $emailCounts[$email]++;
                
                if ($emailCounts[$email] > 1) {
                    $duplicates[] = $email;
                }
            }
            
            // Remove duplicates (keep first occurrence)
            $uniqueDuplicates = array_unique($duplicates);
            $removedCount = 0;
            
            foreach ($uniqueDuplicates as $email) {
                // Note: Brevo API doesn't have a direct way to remove duplicates
                // This would require manual intervention or a more complex approach
                $removedCount++;
            }
            
            return [
                'success' => true,
                'totalContacts' => count($contacts['contacts']),
                'duplicateEmails' => count($uniqueDuplicates),
                'duplicates' => $uniqueDuplicates,
                'message' => 'Duplicate detection completed. Manual removal may be required.'
            ];
            
        } catch (Exception $e) {
            error_log("REMOVE DUPLICATES ERROR: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Validate email addresses before syncing
     */
    public function validateEmails($emails) {
        $validEmails = [];
        $invalidEmails = [];
        
        foreach ($emails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $validEmails[] = $email;
            } else {
                $invalidEmails[] = $email;
            }
        }
        
        return [
            'success' => true,
            'validEmails' => $validEmails,
            'invalidEmails' => $invalidEmails,
            'validCount' => count($validEmails),
            'invalidCount' => count($invalidEmails)
        ];
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
    
    $syncUtility = new ContactSyncUtility();
    $action = $input['action'];
    
    try {
        switch ($action) {
            case 'sync_users_to_brevo':
                $result = $syncUtility->syncUsersToBrevo(
                    $input['limit'] ?? 100,
                    $input['offset'] ?? 0
                );
                break;
                
            case 'sync_affiliates_to_brevo':
                $result = $syncUtility->syncAffiliatesToBrevo(
                    $input['limit'] ?? 100,
                    $input['offset'] ?? 0
                );
                break;
                
            case 'sync_all_contacts_to_brevo':
                $result = $syncUtility->syncAllContactsToBrevo(
                    $input['limit'] ?? 100
                );
                break;
                
            case 'get_sync_status':
                $result = $syncUtility->getSyncStatus();
                break;
                
            case 'remove_duplicate_contacts':
                $result = $syncUtility->removeDuplicateContacts(
                    $input['listId'] ?? BREVO_CUSTOMER_LIST_ID
                );
                break;
                
            case 'validate_emails':
                $result = $syncUtility->validateEmails(
                    $input['emails'] ?? []
                );
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Unknown action']);
                exit;
        }
        
        echo json_encode($result);
        
    } catch (Exception $e) {
        error_log("Contact Sync Utility Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} elseif (php_sapi_name() !== 'cli') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
?>
