#!/usr/bin/env php
<?php
/**
 * 🖥️ ATTRAL Affiliate Sync CLI Tool
 * Command-line interface for syncing affiliates from Firestore to Brevo
 * 
 * Usage:
 * php sync_affiliates_cli.php [command] [options]
 * 
 * Commands:
 * - sync-all: Sync all affiliates to Brevo
 * - sync-specific <id>: Sync specific affiliate by ID
 * - status: Show sync status and statistics
 * - fetch: Fetch and display affiliates from Firestore
 * - test: Test connection to both Firestore and Brevo
 */

require_once __DIR__ . '/sync_affiliates_to_brevo.php';

class AffiliateSyncCLI {
    private $syncService;
    
    public function __construct() {
        $this->syncService = new AffiliateSyncService();
    }
    
    public function run($args) {
        $command = $args[1] ?? 'help';
        
        switch ($command) {
            case 'sync-all':
                $this->syncAll($args);
                break;
                
            case 'sync-specific':
                $this->syncSpecific($args);
                break;
                
            case 'status':
                $this->showStatus();
                break;
                
            case 'fetch':
                $this->fetchAffiliates();
                break;
                
            case 'test':
                $this->testConnections();
                break;
                
            case 'help':
            default:
                $this->showHelp();
                break;
        }
    }
    
    private function syncAll($args) {
        echo "🔄 Starting affiliate sync to Brevo...\n\n";
        
        $batchSize = 10;
        $delay = 2;
        
        // Parse command line options
        for ($i = 2; $i < count($args); $i++) {
            if ($args[$i] === '--batch-size' && isset($args[$i + 1])) {
                $batchSize = (int)$args[$i + 1];
                $i++;
            } elseif ($args[$i] === '--delay' && isset($args[$i + 1])) {
                $delay = (int)$args[$i + 1];
                $i++;
            }
        }
        
        echo "📊 Configuration:\n";
        echo "   Batch size: $batchSize\n";
        echo "   Delay between batches: {$delay}s\n\n";
        
        $result = $this->syncService->syncAllAffiliatesToBrevo($batchSize, $delay);
        
        if ($result['success']) {
            $summary = $result['summary'];
            echo "✅ Sync completed successfully!\n\n";
            echo "📈 Summary:\n";
            echo "   Total affiliates: {$summary['total']}\n";
            echo "   Successfully synced: {$summary['success']}\n";
            echo "   Errors: {$summary['errors']}\n";
            echo "   Skipped (no email): {$summary['skipped']}\n";
            echo "   Completion time: {$summary['timestamp']}\n";
        } else {
            echo "❌ Sync failed: " . ($result['error'] ?? 'Unknown error') . "\n";
            exit(1);
        }
    }
    
    private function syncSpecific($args) {
        $affiliateId = $args[2] ?? null;
        
        if (!$affiliateId) {
            echo "❌ Error: Affiliate ID is required\n";
            echo "Usage: php sync_affiliates_cli.php sync-specific <affiliate_id>\n";
            exit(1);
        }
        
        echo "🔄 Syncing affiliate: $affiliateId\n\n";
        
        $result = $this->syncService->syncAffiliateById($affiliateId);
        
        if ($result['success']) {
            echo "✅ Affiliate synced successfully!\n";
        } else {
            echo "❌ Failed to sync affiliate: " . ($result['error'] ?? 'Unknown error') . "\n";
            exit(1);
        }
    }
    
    private function showStatus() {
        echo "📊 Affiliate Sync Status\n";
        echo "======================\n\n";
        
        $result = $this->syncService->getSyncStatus();
        
        if ($result['success']) {
            $stats = $result['stats'];
            echo "Total affiliates: {$stats['total_affiliates']}\n";
            echo "With email addresses: {$stats['with_email']}\n";
            echo "Last synced: {$stats['last_synced']}\n";
            echo "Never synced: {$stats['never_synced']}\n";
            echo "Sync percentage: {$stats['sync_percentage']}%\n";
        } else {
            echo "❌ Failed to get status: " . ($result['error'] ?? 'Unknown error') . "\n";
            exit(1);
        }
    }
    
    private function fetchAffiliates() {
        echo "📋 Fetching affiliates from Firestore...\n\n";
        
        try {
            $affiliates = $this->syncService->fetchAffiliatesFromFirestore();
            
            echo "Found " . count($affiliates) . " affiliates:\n\n";
            
            foreach ($affiliates as $affiliate) {
                echo "ID: {$affiliate['id']}\n";
                echo "Name: {$affiliate['name']}\n";
                echo "Email: " . ($affiliate['email'] ?: 'No email') . "\n";
                echo "Code: " . ($affiliate['code'] ?: 'No code') . "\n";
                echo "Status: {$affiliate['status']}\n";
                echo "Earnings: ₹{$affiliate['totalEarnings']}\n";
                echo "Referrals: {$affiliate['totalReferrals']}\n";
                echo "Last sync: " . ($affiliate['lastSync'] ?: 'Never') . "\n";
                echo "---\n";
            }
        } catch (Exception $e) {
            echo "❌ Error fetching affiliates: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    private function testConnections() {
        echo "🧪 Testing connections...\n\n";
        
        // Test Firestore connection
        echo "Testing Firestore connection... ";
        try {
            $affiliates = $this->syncService->fetchAffiliatesFromFirestore();
            echo "✅ Connected (found " . count($affiliates) . " affiliates)\n";
        } catch (Exception $e) {
            echo "❌ Failed: " . $e->getMessage() . "\n";
        }
        
        // Test Brevo connection
        echo "Testing Brevo connection... ";
        try {
            $brevoService = new BrevoEmailService();
            // Try to get contact info for a test email
            $result = $brevoService->getContact('test@example.com');
            if ($result['success'] || (isset($result['httpCode']) && $result['httpCode'] === 404)) {
                echo "✅ Connected\n";
            } else {
                echo "❌ Failed: " . ($result['error'] ?? 'Unknown error') . "\n";
            }
        } catch (Exception $e) {
            echo "❌ Failed: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    private function showHelp() {
        echo "🔄 ATTRAL Affiliate Sync CLI Tool\n";
        echo "================================\n\n";
        echo "Usage: php sync_affiliates_cli.php [command] [options]\n\n";
        echo "Commands:\n";
        echo "  sync-all              Sync all affiliates to Brevo\n";
        echo "    --batch-size N      Set batch size (default: 10)\n";
        echo "    --delay N           Set delay between batches in seconds (default: 2)\n\n";
        echo "  sync-specific <id>    Sync specific affiliate by ID\n\n";
        echo "  status                Show sync status and statistics\n\n";
        echo "  fetch                 Fetch and display affiliates from Firestore\n\n";
        echo "  test                  Test connections to Firestore and Brevo\n\n";
        echo "  help                  Show this help message\n\n";
        echo "Examples:\n";
        echo "  php sync_affiliates_cli.php sync-all\n";
        echo "  php sync_affiliates_cli.php sync-all --batch-size 5 --delay 3\n";
        echo "  php sync_affiliates_cli.php sync-specific abc123\n";
        echo "  php sync_affiliates_cli.php status\n";
        echo "  php sync_affiliates_cli.php test\n";
    }
}

// Run the CLI
if (php_sapi_name() === 'cli') {
    $cli = new AffiliateSyncCLI();
    $cli->run($argv);
} else {
    echo "This script can only be run from the command line.\n";
    exit(1);
}
?>
