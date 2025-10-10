<?php
/**
 * Test Suite for Firestore REST Client
 * 
 * Tests JWT signing, OAuth2 authentication, and CRUD operations
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../firestore_rest_client.php';

echo "🧪 FIRESTORE REST CLIENT TEST SUITE\n";
echo "=====================================\n\n";

// Initialize client
$projectId = 'e-commerce-1d40f';
$serviceAccountPath = __DIR__ . '/../firebase-service-account.json';

if (!file_exists($serviceAccountPath)) {
    die("❌ Service account file not found: $serviceAccountPath\n");
}

echo "✅ Service account file found\n";

try {
    $client = new FirestoreRestClient($projectId, $serviceAccountPath, true);
    echo "✅ FirestoreRestClient initialized\n\n";
} catch (Exception $e) {
    die("❌ Failed to initialize client: " . $e->getMessage() . "\n");
}

// Test 1: OAuth2 Token Generation
echo "TEST 1: OAuth2 Token Generation\n";
echo "--------------------------------\n";
try {
    $token = $client->getAccessToken();
    if (strlen($token) > 50) {
        echo "✅ Access token generated (length: " . strlen($token) . ")\n";
        echo "Token preview: " . substr($token, 0, 20) . "...\n";
    } else {
        echo "❌ Token too short or invalid\n";
    }
} catch (Exception $e) {
    echo "❌ Token generation failed: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 2: Token Caching
echo "TEST 2: Token Caching\n";
echo "---------------------\n";
try {
    $start = microtime(true);
    $token1 = $client->getAccessToken();
    $time1 = microtime(true) - $start;
    
    $start = microtime(true);
    $token2 = $client->getAccessToken();
    $time2 = microtime(true) - $start;
    
    if ($token1 === $token2) {
        echo "✅ Token caching works (cached call " . round($time2 / $time1 * 100) . "% faster)\n";
        echo "First call: " . round($time1 * 1000, 2) . "ms\n";
        echo "Cached call: " . round($time2 * 1000, 2) . "ms\n";
    } else {
        echo "❌ Tokens don't match\n";
    }
} catch (Exception $e) {
    echo "❌ Caching test failed: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 3: Write Document
echo "TEST 3: Write Test Document\n";
echo "---------------------------\n";
try {
    $testData = [
        'testField' => 'REST API Test',
        'timestamp' => firestoreTimestamp(),
        'number' => 12345,
        'decimal' => 99.99,
        'boolean' => true,
        'nested' => [
            'key1' => 'value1',
            'key2' => 'value2'
        ],
        'array' => ['item1', 'item2', 'item3']
    ];
    
    $result = $client->writeDocument('test_collection', $testData);
    
    if (isset($result['id'])) {
        echo "✅ Document created successfully\n";
        echo "Document ID: " . $result['id'] . "\n";
        echo "Data written: " . json_encode($testData, JSON_PRETTY_PRINT) . "\n";
        $testDocId = $result['id'];
    } else {
        echo "❌ Document creation failed\n";
        $testDocId = null;
    }
} catch (Exception $e) {
    echo "❌ Write test failed: " . $e->getMessage() . "\n";
    $testDocId = null;
}
echo "\n";

// Test 4: Read Document
echo "TEST 4: Read Test Document\n";
echo "--------------------------\n";
if ($testDocId) {
    try {
        $result = $client->getDocument('test_collection', $testDocId);
        
        if ($result && isset($result['data'])) {
            echo "✅ Document read successfully\n";
            echo "Data read: " . json_encode($result['data'], JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "❌ Document not found\n";
        }
    } catch (Exception $e) {
        echo "❌ Read test failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠️ Skipping (no test document created)\n";
}
echo "\n";

// Test 5: Update Document
echo "TEST 5: Update Test Document\n";
echo "----------------------------\n";
if ($testDocId) {
    try {
        $updates = [
            ['path' => 'testField', 'value' => 'Updated via REST API'],
            ['path' => 'updateCount', 'value' => 1],
            ['path' => 'lastUpdated', 'value' => firestoreTimestamp()]
        ];
        
        $result = $client->updateDocument('test_collection', $testDocId, $updates);
        
        if ($result) {
            echo "✅ Document updated successfully\n";
            echo "Updated data: " . json_encode($result['data'], JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "❌ Document update failed\n";
        }
    } catch (Exception $e) {
        echo "❌ Update test failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠️ Skipping (no test document created)\n";
}
echo "\n";

// Test 6: Atomic Increment
echo "TEST 6: Atomic Field Increment\n";
echo "-------------------------------\n";
if ($testDocId) {
    try {
        // Increment updateCount by 5
        $client->incrementField('test_collection', $testDocId, 'updateCount', 5);
        
        // Read the updated value
        $result = $client->getDocument('test_collection', $testDocId);
        $updateCount = $result['data']['updateCount'] ?? 0;
        
        if ($updateCount === 6) { // 1 + 5
            echo "✅ Atomic increment works correctly\n";
            echo "Value after increment: $updateCount (expected: 6)\n";
        } else {
            echo "❌ Unexpected value: $updateCount (expected: 6)\n";
        }
    } catch (Exception $e) {
        echo "❌ Increment test failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠️ Skipping (no test document created)\n";
}
echo "\n";

// Test 7: Query Documents
echo "TEST 7: Query Documents\n";
echo "-----------------------\n";
try {
    $results = $client->queryDocuments(
        'test_collection',
        [
            ['field' => 'testField', 'op' => 'EQUAL', 'value' => 'Updated via REST API']
        ],
        10
    );
    
    echo "✅ Query executed successfully\n";
    echo "Found " . count($results) . " matching document(s)\n";
    
    if (!empty($results)) {
        echo "First result ID: " . $results[0]['id'] . "\n";
    }
} catch (Exception $e) {
    echo "❌ Query test failed: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 8: Delete Document
echo "TEST 8: Delete Test Document\n";
echo "----------------------------\n";
if ($testDocId) {
    try {
        $result = $client->deleteDocument('test_collection', $testDocId);
        
        if ($result) {
            echo "✅ Document deleted successfully\n";
            
            // Verify deletion
            $deleted = $client->getDocument('test_collection', $testDocId);
            if (!$deleted) {
                echo "✅ Deletion verified (document no longer exists)\n";
            } else {
                echo "⚠️ Document still exists after deletion\n";
            }
        } else {
            echo "❌ Document deletion failed\n";
        }
    } catch (Exception $e) {
        echo "❌ Delete test failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠️ Skipping (no test document created)\n";
}
echo "\n";

// Test 9: Performance Test
echo "TEST 9: Performance Test\n";
echo "------------------------\n";
try {
    $iterations = 5;
    $times = [];
    
    echo "Running $iterations write operations...\n";
    
    for ($i = 1; $i <= $iterations; $i++) {
        $start = microtime(true);
        
        $testData = [
            'perfTest' => true,
            'iteration' => $i,
            'timestamp' => firestoreTimestamp()
        ];
        
        $result = $client->writeDocument('test_perf', $testData);
        $time = microtime(true) - $start;
        $times[] = $time;
        
        echo "  Iteration $i: " . round($time * 1000, 2) . "ms (ID: {$result['id']})\n";
        
        // Clean up
        $client->deleteDocument('test_perf', $result['id']);
    }
    
    $avgTime = array_sum($times) / count($times);
    echo "\n✅ Average time per operation: " . round($avgTime * 1000, 2) . "ms\n";
    
    if ($avgTime < 2) {
        echo "✅ Performance is excellent (< 2s per operation)\n";
    } elseif ($avgTime < 5) {
        echo "⚠️ Performance is acceptable (< 5s per operation)\n";
    } else {
        echo "❌ Performance may need optimization (> 5s per operation)\n";
    }
} catch (Exception $e) {
    echo "❌ Performance test failed: " . $e->getMessage() . "\n";
}
echo "\n";

// Test Summary
echo "=====================================\n";
echo "TEST SUITE COMPLETE\n";
echo "=====================================\n";
echo "\n";
echo "✅ All tests completed!\n";
echo "Check logs above for any failures.\n";
echo "\n";
echo "Next Steps:\n";
echo "1. Review any failed tests\n";
echo "2. Test order creation flow\n";
echo "3. Test coupon tracking\n";
echo "4. Run integration tests\n";
?>

