/**
 * Cloud Function: Coupon Usage Tracker
 * Automatically increments coupon usageCount when new orders are created
 * Triggers on onCreate event in the orders collection
 */

const functions = require('firebase-functions');
const admin = require('firebase-admin');

// Admin is already initialized by other modules; guard init for local tests
try { 
  if (!admin.apps.length) admin.initializeApp(); 
} catch(_) {}

/**
 * Extract coupon codes from order data
 * Handles various data structures for backward compatibility
 */
function extractCouponsFromOrder(orderData) {
  const coupons = [];
  
  // Check if coupons array exists
  if (Array.isArray(orderData.coupons)) {
    orderData.coupons.forEach(coupon => {
      if (coupon && coupon.code) {
        coupons.push({
          code: coupon.code.toString().trim().toUpperCase(),
          name: coupon.name || '',
          type: coupon.type || '',
          value: coupon.value || 0,
          isAffiliateCoupon: coupon.isAffiliateCoupon || false,
          affiliateCode: coupon.affiliateCode || null
        });
      }
    });
  }
  
  return coupons;
}

/**
 * Increment usage count for a coupon in Firestore
 */
async function incrementCouponUsage(couponCode) {
  try {
    const db = admin.firestore();
    
    // Find the coupon document by code
    const couponsQuery = await db.collection('coupons')
      .where('code', '==', couponCode)
      .limit(1)
      .get();
    
    if (couponsQuery.empty) {
      console.warn(`⚠️ Coupon not found in database: ${couponCode}`);
      return { success: false, error: 'Coupon not found' };
    }
    
    const couponDoc = couponsQuery.docs[0];
    const couponRef = couponDoc.ref;
    const couponData = couponDoc.data();
    
    // Increment usage count
    const currentUsageCount = couponData.usageCount || 0;
    const newUsageCount = currentUsageCount + 1;
    
    await couponRef.update({
      usageCount: newUsageCount,
      updatedAt: admin.firestore.FieldValue.serverTimestamp()
    });
    
    console.log(`✅ Incremented usage for coupon ${couponCode}: ${currentUsageCount} → ${newUsageCount}`);
    
    return { 
      success: true, 
      couponCode: couponCode,
      previousCount: currentUsageCount,
      newCount: newUsageCount 
    };
    
  } catch (error) {
    console.error(`❌ Error incrementing coupon ${couponCode}:`, error);
    return { success: false, error: error.message };
  }
}

/**
 * Cloud Function: Triggered when a new order is created
 * Automatically increments coupon usage for all coupons used in the order
 */
exports.onOrderCreated = functions
  .region('asia-south1')
  .firestore
  .document('orders/{orderId}')
  .onCreate(async (snapshot, context) => {
    const orderId = context.params.orderId;
    const orderData = snapshot.data();
    
    console.log(`📦 New order created: ${orderId}`);
    
    try {
      // Extract coupons from the order
      const coupons = extractCouponsFromOrder(orderData);
      
      if (coupons.length === 0) {
        console.log(`ℹ️ No coupons used in order ${orderId}`);
        return { success: true, message: 'No coupons to process' };
      }
      
      console.log(`🎫 Processing ${coupons.length} coupon(s) for order ${orderId}:`, coupons.map(c => c.code).join(', '));
      
      // Increment usage for each coupon
      const results = [];
      for (const coupon of coupons) {
        const result = await incrementCouponUsage(coupon.code);
        results.push(result);
      }
      
      // Log summary
      const successful = results.filter(r => r.success).length;
      const failed = results.filter(r => !r.success).length;
      
      console.log(`📊 Coupon processing complete for order ${orderId}: ${successful} successful, ${failed} failed`);
      
      // Update the order document with processing timestamp
      await snapshot.ref.update({
        couponUsageProcessed: true,
        couponUsageProcessedAt: admin.firestore.FieldValue.serverTimestamp(),
        couponUsageResults: results
      });
      
      return { 
        success: true, 
        orderId: orderId,
        couponsProcessed: coupons.length,
        successful: successful,
        failed: failed,
        results: results
      };
      
    } catch (error) {
      console.error(`❌ Error processing coupons for order ${orderId}:`, error);
      
      // Log error to Firestore for monitoring
      await admin.firestore().collection('coupon_processing_errors').add({
        orderId: orderId,
        error: error.message,
        timestamp: admin.firestore.FieldValue.serverTimestamp()
      });
      
      throw error;
    }
  });

/**
 * Cloud Function: Manual coupon usage increment (HTTP endpoint)
 * Useful for testing or manual corrections
 */
exports.incrementCouponUsageHttp = functions
  .region('asia-south1')
  .https
  .onRequest(async (req, res) => {
    // Set CORS headers
    res.set('Access-Control-Allow-Origin', '*');
    res.set('Access-Control-Allow-Methods', 'POST, OPTIONS');
    res.set('Access-Control-Allow-Headers', 'Content-Type');
    
    if (req.method === 'OPTIONS') {
      res.status(204).send('');
      return;
    }
    
    if (req.method !== 'POST') {
      return res.status(405).json({ success: false, error: 'Method not allowed' });
    }
    
    try {
      const { couponCode, orderId } = req.body;
      
      if (!couponCode) {
        return res.status(400).json({ success: false, error: 'couponCode is required' });
      }
      
      console.log(`🔧 Manual increment request for coupon: ${couponCode}${orderId ? ` (Order: ${orderId})` : ''}`);
      
      const result = await incrementCouponUsage(couponCode.toUpperCase());
      
      res.json({
        success: result.success,
        message: result.success ? 'Coupon usage incremented successfully' : 'Failed to increment coupon usage',
        ...result
      });
      
    } catch (error) {
      console.error('❌ Manual increment error:', error);
      res.status(500).json({
        success: false,
        error: error.message
      });
    }
  });

/**
 * Cloud Function: Reprocess coupons for existing order
 * Useful for orders that were created before this function was deployed
 */
exports.reprocessOrderCouponsHttp = functions
  .region('asia-south1')
  .https
  .onRequest(async (req, res) => {
    // Set CORS headers
    res.set('Access-Control-Allow-Origin', '*');
    res.set('Access-Control-Allow-Methods', 'POST, OPTIONS');
    res.set('Access-Control-Allow-Headers', 'Content-Type');
    
    if (req.method === 'OPTIONS') {
      res.status(204).send('');
      return;
    }
    
    if (req.method !== 'POST') {
      return res.status(405).json({ success: false, error: 'Method not allowed' });
    }
    
    try {
      const { orderId } = req.body;
      
      if (!orderId) {
        return res.status(400).json({ success: false, error: 'orderId is required' });
      }
      
      console.log(`🔄 Reprocessing coupons for order: ${orderId}`);
      
      // Get order data
      const orderDoc = await admin.firestore().collection('orders').doc(orderId).get();
      
      if (!orderDoc.exists) {
        return res.status(404).json({ success: false, error: 'Order not found' });
      }
      
      const orderData = orderDoc.data();
      const coupons = extractCouponsFromOrder(orderData);
      
      if (coupons.length === 0) {
        return res.json({ success: true, message: 'No coupons to process' });
      }
      
      // Increment usage for each coupon
      const results = [];
      for (const coupon of coupons) {
        const result = await incrementCouponUsage(coupon.code);
        results.push(result);
      }
      
      const successful = results.filter(r => r.success).length;
      const failed = results.filter(r => !r.success).length;
      
      res.json({
        success: true,
        message: 'Coupons reprocessed successfully',
        orderId: orderId,
        couponsProcessed: coupons.length,
        successful: successful,
        failed: failed,
        results: results
      });
      
    } catch (error) {
      console.error('❌ Reprocess error:', error);
      res.status(500).json({
        success: false,
        error: error.message
      });
    }
  });

