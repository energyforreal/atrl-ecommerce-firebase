/**
 * Firebase Functions Entry Point
 * ATTRAL E-commerce Fulfillment Status Monitoring & Coupon Tracking
 */

const fulfillmentTrigger = require('./fulfillment-status-trigger');
const couponUsage = require('./rebuild-coupon-usage');
const couponTracker = require('./coupon-usage-tracker');
const razorpayWebhookFunc = require('./razorpay-webhook-function');
const affiliateFunctions = require('./affiliate-functions');

// Export the fulfillment status change trigger
exports.onFulfillmentStatusChange = fulfillmentTrigger.onFulfillmentStatusChange;

// Export the manual trigger function
exports.triggerFulfillmentEmail = fulfillmentTrigger.triggerFulfillmentEmail;

// Export coupon usage rebuild (scheduled + http)
exports.rebuildCouponUsage = couponUsage.rebuildCouponUsage;
exports.rebuildCouponUsageHttp = couponUsage.rebuildCouponUsageHttp;

// ==================== COUPON USAGE TRACKING ====================
// Export automatic coupon tracking on order creation
exports.onOrderCreated = couponTracker.onOrderCreated;

// Export manual coupon increment HTTP endpoint
exports.incrementCouponUsageHttp = couponTracker.incrementCouponUsageHttp;

// Export order coupon reprocessing HTTP endpoint
exports.reprocessOrderCouponsHttp = couponTracker.reprocessOrderCouponsHttp;

// ==================== RAZORPAY WEBHOOK ====================
// Export Razorpay webhook handler (backup to PHP webhook)
exports.razorpayWebhook = razorpayWebhookFunc.razorpayWebhook;

// ==================== AFFILIATE MANAGEMENT ====================
// Export affiliate management functions
exports.createAffiliateProfile = affiliateFunctions.createAffiliateProfile;
exports.getAffiliateOrders = affiliateFunctions.getAffiliateOrders;
exports.getAffiliateStats = affiliateFunctions.getAffiliateStats;
exports.getPaymentDetails = affiliateFunctions.getPaymentDetails;
exports.updatePaymentDetails = affiliateFunctions.updatePaymentDetails;
exports.getPayoutSettings = affiliateFunctions.getPayoutSettings;
exports.updatePayoutSettings = affiliateFunctions.updatePayoutSettings;