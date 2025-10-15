<?php
/**
 * Simple test script for affiliate API
 * Visit: https://attral.in/api/test_affiliate_api.php?code=attral-71hlzssgan
 */

header('Content-Type: text/plain; charset=utf-8');

echo "=================================================================\n";
echo "AFFILIATE API TEST SCRIPT\n";
echo "=================================================================\n\n";

$testCode = $_GET['code'] ?? 'attral-71hlzssgan';
echo "Testing with affiliate code: $testCode\n\n";

// Test 1: getAffiliateStats
echo "--- Test 1: getAffiliateStats ---\n";
$statsUrl = "affiliate_functions.php?action=getAffiliateStats&code=$testCode";
echo "URL: $statsUrl\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/" . $statsUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
$statsResponse = curl_exec($ch);
$statsHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $statsHttpCode\n";
echo "Response: $statsResponse\n\n";

// Test 2: getAffiliateOrders
echo "--- Test 2: getAffiliateOrders ---\n";
$ordersUrl = "affiliate_functions.php?action=getAffiliateOrders&code=$testCode&pageSize=5";
echo "URL: $ordersUrl\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/" . $ordersUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
$ordersResponse = curl_exec($ch);
$ordersHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $ordersHttpCode\n";
echo "Response: $ordersResponse\n\n";

// Test 3: getAffiliateByCode
echo "--- Test 3: getAffiliateByCode ---\n";
$affiliateUrl = "affiliate_functions.php?action=getAffiliateByCode&code=$testCode";
echo "URL: $affiliateUrl\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/" . $affiliateUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
$affiliateResponse = curl_exec($ch);
$affiliateHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $affiliateHttpCode\n";
echo "Response: $affiliateResponse\n\n";

echo "=================================================================\n";
echo "TEST COMPLETE\n";
echo "=================================================================\n";
?>
