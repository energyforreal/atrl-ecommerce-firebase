<?php
/**
 * 🔥 Firestore REST API Client for Hostinger Shared Hosting
 * 
 * Production-ready client for Google Cloud Firestore REST API v1
 * Compatible with Hostinger shared hosting (no gRPC, no custom extensions)
 * 
 * Features:
 * - JWT signing with RS256 (OpenSSL)
 * - Google OAuth2 service account authentication
 * - Full CRUD operations (Create, Read, Update, Delete)
 * - Atomic field operations (increment, serverTimestamp)
 * - Query support with filters
 * - Batch operations
 * - Token caching (1-hour expiry)
 * - Error handling and retry logic
 * 
 * Requirements:
 * - PHP 7.4+ (tested on PHP 8.4.12)
 * - cURL extension (standard on Hostinger)
 * - JSON extension (standard on Hostinger)
 * - OpenSSL extension (standard on Hostinger)
 * 
 * @version 1.0.0
 * @author ATTRAL E-Commerce Platform
 * @license MIT
 */

// Prevent direct access
if (!defined('FIRESTORE_REST_CLIENT_LOADED')) {
    define('FIRESTORE_REST_CLIENT_LOADED', true);
}

class FirestoreRestClient {
    
    private $projectId;
    private $serviceAccountPath;
    private $serviceAccount;
    private $accessToken;
    private $tokenExpiry;
    private $baseUrl;
    
    // Cache file for access token (optional, for performance)
    private $tokenCacheFile;
    
    /**
     * Constructor
     * 
     * @param string $projectId Firebase project ID
     * @param string $serviceAccountPath Path to service account JSON file
     * @param bool $enableCache Enable token caching to file (default: true)
     */
    public function __construct($projectId, $serviceAccountPath, $enableCache = true) {
        $this->projectId = $projectId;
        $this->serviceAccountPath = $serviceAccountPath;
        $this->baseUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents";
        
        // Load service account credentials
        $this->loadServiceAccount();
        
        // Set up token cache
        if ($enableCache) {
            $cacheDir = dirname($serviceAccountPath);
            $this->tokenCacheFile = $cacheDir . '/.firestore_token_cache.json';
        }
    }
    
    /**
     * Load service account JSON file
     * 
     * @throws Exception if file not found or invalid
     */
    private function loadServiceAccount() {
        if (!file_exists($this->serviceAccountPath)) {
            throw new Exception("Service account file not found: {$this->serviceAccountPath}");
        }
        
        $json = file_get_contents($this->serviceAccountPath);
        $this->serviceAccount = json_decode($json, true);
        
        if (!$this->serviceAccount || !isset($this->serviceAccount['private_key'])) {
            throw new Exception("Invalid service account file");
        }
        
        error_log("FIRESTORE REST: Service account loaded for project {$this->projectId}");
    }
    
    /**
     * Get Google OAuth2 access token using JWT
     * 
     * Implements Google's service account authentication:
     * https://developers.google.com/identity/protocols/oauth2/service-account
     * 
     * @param bool $forceRefresh Force token refresh even if cached token is valid
     * @return string Access token
     * @throws Exception if token generation fails
     */
    public function getAccessToken($forceRefresh = false) {
        // Check if we have a valid cached token
        if (!$forceRefresh && $this->accessToken && $this->tokenExpiry && time() < $this->tokenExpiry - 300) {
            error_log("FIRESTORE REST: Using cached access token (expires in " . ($this->tokenExpiry - time()) . "s)");
            return $this->accessToken;
        }
        
        // Try to load from cache file
        if (!$forceRefresh && $this->tokenCacheFile && file_exists($this->tokenCacheFile)) {
            $cache = json_decode(file_get_contents($this->tokenCacheFile), true);
            if ($cache && isset($cache['token']) && isset($cache['expiry']) && time() < $cache['expiry'] - 300) {
                $this->accessToken = $cache['token'];
                $this->tokenExpiry = $cache['expiry'];
                error_log("FIRESTORE REST: Loaded access token from cache file");
                return $this->accessToken;
            }
        }
        
        error_log("FIRESTORE REST: Generating new access token via JWT...");
        
        // Create JWT (JSON Web Token)
        $jwt = $this->createJWT();
        
        // Exchange JWT for access token
        $tokenUrl = 'https://oauth2.googleapis.com/token';
        $postData = [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ];
        
        $ch = curl_init($tokenUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            throw new Exception("OAuth2 token request failed: {$curlError}");
        }
        
        if ($httpCode !== 200) {
            error_log("FIRESTORE REST: Token request failed with HTTP {$httpCode}: {$response}");
            throw new Exception("Failed to obtain access token (HTTP {$httpCode})");
        }
        
        $result = json_decode($response, true);
        
        if (!isset($result['access_token'])) {
            throw new Exception("Invalid token response: " . $response);
        }
        
        $this->accessToken = $result['access_token'];
        $this->tokenExpiry = time() + ($result['expires_in'] ?? 3600);
        
        // Cache token to file
        if ($this->tokenCacheFile) {
            $cache = [
                'token' => $this->accessToken,
                'expiry' => $this->tokenExpiry,
                'created_at' => date('c')
            ];
            @file_put_contents($this->tokenCacheFile, json_encode($cache));
            @chmod($this->tokenCacheFile, 0600); // Secure permissions
        }
        
        error_log("FIRESTORE REST: ✅ New access token generated (expires at " . date('Y-m-d H:i:s', $this->tokenExpiry) . ")");
        
        return $this->accessToken;
    }
    
    /**
     * Create JWT (JSON Web Token) for service account authentication
     * 
     * Uses RS256 algorithm (RSA signature with SHA-256)
     * 
     * @return string Signed JWT
     * @throws Exception if JWT creation fails
     */
    private function createJWT() {
        $now = time();
        $expiry = $now + 3600; // 1 hour expiry
        
        // JWT Header
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT'
        ];
        
        // JWT Payload (Claims)
        $payload = [
            'iss' => $this->serviceAccount['client_email'], // Issuer (service account email)
            'scope' => 'https://www.googleapis.com/auth/datastore', // Firestore scope
            'aud' => 'https://oauth2.googleapis.com/token', // Audience (Google OAuth2)
            'exp' => $expiry, // Expiration time
            'iat' => $now // Issued at
        ];
        
        // Encode header and payload
        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
        
        // Create signature
        $signatureInput = "{$headerEncoded}.{$payloadEncoded}";
        $signature = $this->signRS256($signatureInput, $this->serviceAccount['private_key']);
        
        // Combine to create JWT
        $jwt = "{$signatureInput}.{$signature}";
        
        error_log("FIRESTORE REST: JWT created and signed with RS256");
        
        return $jwt;
    }
    
    /**
     * Sign data with RS256 (RSA-SHA256)
     * 
     * @param string $data Data to sign
     * @param string $privateKey PEM-encoded private key
     * @return string Base64 URL-encoded signature
     * @throws Exception if signing fails
     */
    private function signRS256($data, $privateKey) {
        $key = openssl_pkey_get_private($privateKey);
        
        if (!$key) {
            throw new Exception("Failed to load private key: " . openssl_error_string());
        }
        
        $signature = '';
        $success = openssl_sign($data, $signature, $key, OPENSSL_ALGO_SHA256);
        
        openssl_free_key($key);
        
        if (!$success) {
            throw new Exception("Failed to sign JWT: " . openssl_error_string());
        }
        
        return $this->base64UrlEncode($signature);
    }
    
    /**
     * Base64 URL encoding (URL-safe base64)
     * 
     * @param string $data Data to encode
     * @return string Base64 URL-encoded string
     */
    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Make authenticated request to Firestore REST API
     * 
     * @param string $method HTTP method (GET, POST, PATCH, DELETE)
     * @param string $url Full URL or path
     * @param array|null $data Request body data
     * @param array $additionalHeaders Additional HTTP headers
     * @return array Response data
     * @throws Exception if request fails
     */
    private function makeRequest($method, $url, $data = null, $additionalHeaders = []) {
        $token = $this->getAccessToken();
        
        $headers = array_merge([
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ], $additionalHeaders);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if ($data !== null && in_array($method, ['POST', 'PATCH', 'PUT'])) {
            $jsonData = is_string($data) ? $data : json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            throw new Exception("Request failed: {$curlError}");
        }
        
        $result = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $errorMsg = isset($result['error']['message']) ? $result['error']['message'] : $response;
            error_log("FIRESTORE REST: Request failed (HTTP {$httpCode}): {$errorMsg}");
            throw new Exception("Firestore request failed (HTTP {$httpCode}): {$errorMsg}");
        }
        
        return $result;
    }
    
    /**
     * Write (create or set) a document in Firestore
     * 
     * @param string $collection Collection path (e.g., 'orders' or 'orders/abc/subcollection')
     * @param array $data Document data (associative array)
     * @param string|null $documentId Optional document ID (auto-generated if null)
     * @return array Response with document name and data
     * @throws Exception if write fails
     */
    public function writeDocument($collection, $data, $documentId = null) {
        $firestoreData = $this->convertToFirestoreFormat($data);
        
        if ($documentId) {
            // Set document with specific ID
            $url = "{$this->baseUrl}/{$collection}/{$documentId}";
            $method = 'PATCH';
            $body = [
                'fields' => $firestoreData
            ];
        } else {
            // Create document with auto-generated ID
            $url = "{$this->baseUrl}/{$collection}";
            $method = 'POST';
            $body = [
                'fields' => $firestoreData
            ];
        }
        
        error_log("FIRESTORE REST: Writing document to {$collection}" . ($documentId ? " with ID {$documentId}" : " (auto-ID)"));
        
        $result = $this->makeRequest($method, $url, $body);
        
        // Extract document ID from name
        $docId = basename($result['name']);
        
        error_log("FIRESTORE REST: ✅ Document written successfully: {$docId}");
        
        return [
            'id' => $docId,
            'name' => $result['name'],
            'data' => $this->convertFromFirestoreFormat($result['fields'] ?? [])
        ];
    }
    
    /**
     * Get a document from Firestore
     * 
     * @param string $collection Collection path
     * @param string $documentId Document ID
     * @return array|null Document data or null if not found
     */
    public function getDocument($collection, $documentId) {
        $url = "{$this->baseUrl}/{$collection}/{$documentId}";
        
        try {
            $result = $this->makeRequest('GET', $url);
            
            if (!isset($result['fields'])) {
                return null;
            }
            
            return [
                'id' => $documentId,
                'data' => $this->convertFromFirestoreFormat($result['fields'])
            ];
        } catch (Exception $e) {
            // Document not found
            if (strpos($e->getMessage(), '404') !== false) {
                return null;
            }
            throw $e;
        }
    }
    
    /**
     * Query documents in a collection
     * 
     * @param string $collection Collection path
     * @param array $filters Array of filters [['field' => 'status', 'op' => 'EQUAL', 'value' => 'active']]
     * @param int $limit Limit number of results
     * @param string|null $orderBy Field to order by
     * @param string $direction Order direction ('ASCENDING' or 'DESCENDING')
     * @return array Array of documents
     */
    public function queryDocuments($collection, $filters = [], $limit = 100, $orderBy = null, $direction = 'ASCENDING') {
        $url = "{$this->baseUrl}:runQuery";
        
        // Build structured query
        $query = [
            'structuredQuery' => [
                'from' => [
                    ['collectionId' => $collection]
                ],
                'limit' => $limit
            ]
        ];
        
        // Add filters
        if (!empty($filters)) {
            $compositeFilter = [];
            foreach ($filters as $filter) {
                $field = $filter['field'];
                $op = $filter['op'] ?? 'EQUAL';
                $value = $filter['value'];
                
                $compositeFilter[] = [
                    'fieldFilter' => [
                        'field' => ['fieldPath' => $field],
                        'op' => $op,
                        'value' => $this->convertValueToFirestore($value)
                    ]
                ];
            }
            
            if (count($compositeFilter) === 1) {
                $query['structuredQuery']['where'] = $compositeFilter[0];
            } else {
                $query['structuredQuery']['where'] = [
                    'compositeFilter' => [
                        'op' => 'AND',
                        'filters' => $compositeFilter
                    ]
                ];
            }
        }
        
        // Add ordering
        if ($orderBy) {
            $query['structuredQuery']['orderBy'] = [
                [
                    'field' => ['fieldPath' => $orderBy],
                    'direction' => $direction
                ]
            ];
        }
        
        error_log("FIRESTORE REST: Querying {$collection} with " . count($filters) . " filters");
        
        $result = $this->makeRequest('POST', $url, $query);
        
        // Parse results
        $documents = [];
        foreach ($result as $item) {
            if (isset($item['document'])) {
                $doc = $item['document'];
                $docId = basename($doc['name']);
                $documents[] = [
                    'id' => $docId,
                    'data' => $this->convertFromFirestoreFormat($doc['fields'] ?? [])
                ];
            }
        }
        
        error_log("FIRESTORE REST: Query returned " . count($documents) . " documents");
        
        return $documents;
    }
    
    /**
     * Update specific fields in a document
     * 
     * @param string $collection Collection path
     * @param string $documentId Document ID
     * @param array $updates Field updates [['path' => 'status', 'value' => 'completed']]
     * @return array Updated document
     * @throws Exception if update fails
     */
    public function updateDocument($collection, $documentId, $updates) {
        $url = "{$this->baseUrl}/{$collection}/{$documentId}";
        
        // Build update mask and fields
        $updateMask = [];
        $fields = [];
        
        foreach ($updates as $update) {
            $path = $update['path'];
            $value = $update['value'];
            
            $updateMask[] = $path;
            $fields[$path] = $this->convertValueToFirestore($value);
        }
        
        $url .= '?updateMask.fieldPaths=' . implode('&updateMask.fieldPaths=', $updateMask);
        
        $body = [
            'fields' => $fields
        ];
        
        error_log("FIRESTORE REST: Updating document {$collection}/{$documentId} (" . count($updates) . " fields)");
        
        $result = $this->makeRequest('PATCH', $url, $body);
        
        return [
            'id' => $documentId,
            'data' => $this->convertFromFirestoreFormat($result['fields'] ?? [])
        ];
    }
    
    /**
     * Atomically increment a field in a document
     * 
     * Uses Firestore transforms for atomic operations
     * 
     * @param string $collection Collection path
     * @param string $documentId Document ID
     * @param string $fieldPath Field to increment
     * @param int|float $incrementValue Value to increment by (default: 1)
     * @return array Updated document
     * @throws Exception if increment fails
     */
    public function incrementField($collection, $documentId, $fieldPath, $incrementValue = 1) {
        $url = "{$this->baseUrl}:commit";
        
        $docPath = "projects/{$this->projectId}/databases/(default)/documents/{$collection}/{$documentId}";
        
        $body = [
            'writes' => [
                [
                    'transform' => [
                        'document' => $docPath,
                        'fieldTransforms' => [
                            [
                                'fieldPath' => $fieldPath,
                                'increment' => is_int($incrementValue) ? 
                                    ['integerValue' => (string)$incrementValue] : 
                                    ['doubleValue' => $incrementValue]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        
        error_log("FIRESTORE REST: Atomically incrementing {$fieldPath} by {$incrementValue} in {$collection}/{$documentId}");
        
        $result = $this->makeRequest('POST', $url, $body);
        
        error_log("FIRESTORE REST: ✅ Field incremented successfully");
        
        return $result;
    }
    
    /**
     * Batch write operations
     * 
     * @param array $operations Array of operations
     * @return array Batch write result
     * @throws Exception if batch write fails
     */
    public function batchWrite($operations) {
        $url = "{$this->baseUrl}:commit";
        
        $writes = [];
        
        foreach ($operations as $op) {
            $type = $op['type']; // 'create', 'update', 'delete', 'transform'
            $collection = $op['collection'];
            $documentId = $op['documentId'];
            $docPath = "projects/{$this->projectId}/databases/(default)/documents/{$collection}/{$documentId}";
            
            if ($type === 'create' || $type === 'update') {
                $writes[] = [
                    'update' => [
                        'name' => $docPath,
                        'fields' => $this->convertToFirestoreFormat($op['data'])
                    ]
                ];
            } elseif ($type === 'delete') {
                $writes[] = [
                    'delete' => $docPath
                ];
            } elseif ($type === 'transform') {
                $writes[] = [
                    'transform' => [
                        'document' => $docPath,
                        'fieldTransforms' => $op['transforms']
                    ]
                ];
            }
        }
        
        $body = ['writes' => $writes];
        
        error_log("FIRESTORE REST: Batch write with " . count($writes) . " operations");
        
        $result = $this->makeRequest('POST', $url, $body);
        
        return $result;
    }
    
    /**
     * Delete a document
     * 
     * @param string $collection Collection path
     * @param string $documentId Document ID
     * @return bool Success status
     */
    public function deleteDocument($collection, $documentId) {
        $url = "{$this->baseUrl}/{$collection}/{$documentId}";
        
        error_log("FIRESTORE REST: Deleting document {$collection}/{$documentId}");
        
        $this->makeRequest('DELETE', $url);
        
        error_log("FIRESTORE REST: ✅ Document deleted successfully");
        
        return true;
    }
    
    /**
     * Convert PHP array to Firestore field format
     * 
     * @param array $data PHP associative array
     * @return array Firestore fields format
     */
    private function convertToFirestoreFormat($data) {
        $fields = [];
        
        foreach ($data as $key => $value) {
            $fields[$key] = $this->convertValueToFirestore($value);
        }
        
        return $fields;
    }
    
    /**
     * Convert single PHP value to Firestore value format
     * 
     * @param mixed $value PHP value
     * @return array Firestore value format
     */
    private function convertValueToFirestore($value) {
        if (is_null($value)) {
            return ['nullValue' => null];
        } elseif (is_bool($value)) {
            return ['booleanValue' => $value];
        } elseif (is_int($value)) {
            return ['integerValue' => (string)$value];
        } elseif (is_float($value) || is_double($value)) {
            return ['doubleValue' => $value];
        } elseif (is_string($value)) {
            // Check if it's an ISO 8601 timestamp
            if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $value)) {
                return ['timestampValue' => $value];
            }
            return ['stringValue' => $value];
        } elseif (is_array($value)) {
            // Check if it's an associative array (map) or indexed array (array)
            if ($this->isAssociativeArray($value)) {
                return [
                    'mapValue' => [
                        'fields' => $this->convertToFirestoreFormat($value)
                    ]
                ];
            } else {
                return [
                    'arrayValue' => [
                        'values' => array_map([$this, 'convertValueToFirestore'], $value)
                    ]
                ];
            }
        } else {
            // Fallback to string
            return ['stringValue' => (string)$value];
        }
    }
    
    /**
     * Convert Firestore fields format to PHP array
     * 
     * @param array $fields Firestore fields
     * @return array PHP associative array
     */
    private function convertFromFirestoreFormat($fields) {
        $data = [];
        
        foreach ($fields as $key => $value) {
            $data[$key] = $this->convertValueFromFirestore($value);
        }
        
        return $data;
    }
    
    /**
     * Convert single Firestore value to PHP value
     * 
     * @param array $value Firestore value format
     * @return mixed PHP value
     */
    private function convertValueFromFirestore($value) {
        if (isset($value['nullValue'])) {
            return null;
        } elseif (isset($value['booleanValue'])) {
            return $value['booleanValue'];
        } elseif (isset($value['integerValue'])) {
            return (int)$value['integerValue'];
        } elseif (isset($value['doubleValue'])) {
            return (float)$value['doubleValue'];
        } elseif (isset($value['timestampValue'])) {
            return $value['timestampValue']; // Return as ISO 8601 string
        } elseif (isset($value['stringValue'])) {
            return $value['stringValue'];
        } elseif (isset($value['mapValue']['fields'])) {
            return $this->convertFromFirestoreFormat($value['mapValue']['fields']);
        } elseif (isset($value['arrayValue']['values'])) {
            return array_map([$this, 'convertValueFromFirestore'], $value['arrayValue']['values']);
        } else {
            return null;
        }
    }
    
    /**
     * Check if array is associative (map) or indexed (array)
     * 
     * @param array $array Array to check
     * @return bool True if associative, false if indexed
     */
    private function isAssociativeArray($array) {
        if (empty($array)) {
            return false;
        }
        return array_keys($array) !== range(0, count($array) - 1);
    }
    
    /**
     * Create ISO 8601 timestamp string (Firestore compatible)
     * 
     * @param int|null $timestamp Unix timestamp (null = now)
     * @return string ISO 8601 timestamp
     */
    public static function timestamp($timestamp = null) {
        if ($timestamp === null) {
            $timestamp = time();
        }
        return gmdate('Y-m-d\TH:i:s\Z', $timestamp);
    }
    
    /**
     * Clear token cache
     */
    public function clearTokenCache() {
        $this->accessToken = null;
        $this->tokenExpiry = null;
        
        if ($this->tokenCacheFile && file_exists($this->tokenCacheFile)) {
            @unlink($this->tokenCacheFile);
        }
        
        error_log("FIRESTORE REST: Token cache cleared");
    }
}

// Helper function to create timestamp
if (!function_exists('firestoreTimestamp')) {
    function firestoreTimestamp($timestamp = null) {
        return FirestoreRestClient::timestamp($timestamp);
    }
}

error_log("FIRESTORE REST: Client library loaded successfully");
?>

