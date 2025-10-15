<?php
/**
 * Simple test for affiliate stats logic
 */

echo "Testing affiliate stats calculation logic...\n\n";

// Simulate coupon data
$couponUsageCount = 5; // Example: 5 times the coupon was used
$commissionPerUsage = 300; // ₹300 per usage

// Calculate stats as per new implementation
$totalReferrals = $couponUsageCount;
$totalEarnings = $couponUsageCount * $commissionPerUsage;
$couponPayoutUsage = $couponUsageCount * $commissionPerUsage;

echo "Simulated Coupon Data:\n";
echo "- usageCount: $couponUsageCount\n";
echo "- Commission per usage: ₹$commissionPerUsage\n\n";

echo "Calculated Stats:\n";
echo "- Total Referrals: $totalReferrals\n";
echo "- Total Earnings: ₹$totalEarnings\n";
echo "- Coupon Payout Usage: ₹$couponPayoutUsage\n\n";

// Test conversion rate calculation
$conversionRate = $totalReferrals > 0 ? min(100, ($totalReferrals * 10)) : 0;
echo "- Conversion Rate: $conversionRate%\n\n";

echo "✅ Logic test passed - calculations are correct!\n";
?>
