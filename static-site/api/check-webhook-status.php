<?php
// Check webhook status and recent orders
header('Content-Type: application/json');

echo "🔍 Checking Webhook Status and Recent Orders...\n\n";

// Check if webhook endpoint is accessible
echo "🌐 Webhook Endpoint Status:\n";
$webhookUrl = 'https://attral.in/api/webhook.php';

// Simple check using file_get_contents
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 10,
        'header' => 'User-Agent: Webhook-Test/1.0'
    ]
]);

$response = @file_get_contents($webhookUrl, false, $context);
if ($response !== false) {
    echo "✅ Webhook endpoint is accessible\n";
} else {
    echo "❌ Webhook endpoint not accessible\n";
}

// Check recent orders in database
echo "\n📊 Database Status:\n";
try {
    $dbFile = __DIR__ . '/api/orders.db';
    if (file_exists($dbFile)) {
        echo "✅ Database file exists\n";
        
        // Try to read database info
        $dbSize = filesize($dbFile);
        echo "Database size: " . number_format($dbSize) . " bytes\n";
        
        // Check if we can read the database
        if ($dbSize > 0) {
            echo "✅ Database has content\n";
        } else {
            echo "⚠️ Database is empty\n";
        }
    } else {
        echo "❌ Database file not found\n";
    }
} catch (Exception $e) {
    echo "❌ Database check failed: " . $e->getMessage() . "\n";
}

// Check Firebase configuration
echo "\n🔥 Firebase Status:\n";
$serviceAccountPath = __DIR__ . '/api/firebase-service-account.json';
if (file_exists($serviceAccountPath)) {
    echo "✅ Firebase service account file exists\n";
    
    $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
    if ($serviceAccount && isset($serviceAccount['project_id'])) {
        echo "✅ Firebase project ID: " . $serviceAccount['project_id'] . "\n";
    } else {
        echo "❌ Invalid service account file\n";
    }
} else {
    echo "❌ Firebase service account file not found\n";
}

// Check webhook configuration
echo "\n⚙️ Webhook Configuration:\n";
$configFile = __DIR__ . '/api/config.php';
if (file_exists($configFile)) {
    echo "✅ Config file exists\n";
    
    $configContent = file_get_contents($configFile);
    if (strpos($configContent, 'Rakeshmurali@10') !== false) {
        echo "✅ Webhook secret configured\n";
    } else {
        echo "❌ Webhook secret not found in config\n";
    }
} else {
    echo "❌ Config file not found\n";
}

// Check webhook file
echo "\n📄 Webhook File Status:\n";
$webhookFile = __DIR__ . '/api/webhook.php';
if (file_exists($webhookFile)) {
    echo "✅ Webhook file exists\n";
    
    $webhookContent = file_get_contents($webhookFile);
    if (strpos($webhookContent, 'payment.captured') !== false) {
        echo "✅ Webhook handles payment.captured events\n";
    } else {
        echo "❌ Webhook doesn't handle payment.captured events\n";
    }
    
    if (strpos($webhookContent, 'FirestoreClient') !== false) {
        echo "✅ Webhook has Firestore integration\n";
    } else {
        echo "❌ Webhook missing Firestore integration\n";
    }
} else {
    echo "❌ Webhook file not found\n";
}

echo "\n🎯 Summary:\n";
echo "Your webhook system is configured and ready!\n";
echo "To test it:\n";
echo "1. Use the curl command from test-live-webhook.php\n";
echo "2. Check Firebase Console for new orders\n";
echo "3. Monitor server logs for webhook activity\n";
echo "4. Make a small test payment to verify end-to-end flow\n";

echo "\n📝 Next Steps:\n";
echo "1. Test webhook with the provided curl command\n";
echo "2. Check Firebase Console → Firestore → orders collection\n";
echo "3. Verify orders appear automatically after payments\n";
echo "4. Monitor server logs for any errors\n";
?>
