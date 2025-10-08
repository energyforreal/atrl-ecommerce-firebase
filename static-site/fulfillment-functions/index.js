/**
 * ATTRAL Cloud Functions
 * - Fulfillment Status Monitoring
 * - Coupon Usage Tracking
 */

const functions = require('firebase-functions');
const admin = require('firebase-admin');

// Initialize Firebase Admin SDK
admin.initializeApp();

// Import coupon usage tracker
const couponUsageTracker = require('./coupon-usage-tracker');

exports.onFulfillmentStatusChange = functions
  .region('us-central1')
  .firestore
  .document('orders/{orderId}')
  .onUpdate(async (change, context) => {
    const before = change.before.data();
    const after = change.after.data();
    const orderId = context.params.orderId;

    // Check if fulfillment status has changed
    const beforeStatus = before.fulfillmentStatus;
    const afterStatus = after.fulfillmentStatus;

    if (!afterStatus || beforeStatus === afterStatus) {
      console.log(`No fulfillment status change for order ${orderId}`);
      return null;
    }

    console.log(`Order ${orderId} fulfillment status changed: ${beforeStatus} → ${afterStatus}`);

    try {
      // Extract customer information
      const customerEmail = extractCustomerEmail(after);
      if (!customerEmail) {
        console.warn(`No customer email found for order ${orderId}`);
        return null;
      }

      // Prepare email data
      const emailData = {
        orderId: orderId,
        customerEmail: customerEmail,
        customerName: extractCustomerName(after),
        fulfillmentStatus: afterStatus,
        productTitle: extractProductTitle(after),
        trackingNumber: after.trackingNumber || '',
        estimatedDelivery: after.estimatedDelivery || ''
      };

      // Call the fulfillment email API
      const emailResult = await sendFulfillmentEmail(emailData);

      console.log(`Fulfillment email sent for order ${orderId}:`, emailResult);

      // Optionally update the order with email sent timestamp
      await change.after.ref.update({
        lastEmailSent: admin.firestore.FieldValue.serverTimestamp(),
        emailSentForStatus: afterStatus
      });

      return emailResult;

    } catch (error) {
      console.error(`Error processing fulfillment status change for order ${orderId}:`, error);
      
      // Log the error to Firestore for monitoring
      await admin.firestore().collection('email_errors').add({
        orderId: orderId,
        error: error.message,
        timestamp: admin.firestore.FieldValue.serverTimestamp(),
        status: afterStatus
      });

      throw error;
    }
  });

/**
 * Extract customer email from order data
 */
function extractCustomerEmail(orderData) {
  // Try different possible locations for customer email
  if (orderData.email) return orderData.email;
  if (orderData.customer && orderData.customer.email) return orderData.customer.email;
  if (orderData.user && orderData.user.email) return orderData.user.email;
  if (orderData.shipping && orderData.shipping.email) return orderData.shipping.email;
  if (orderData.notes && orderData.notes.email) return orderData.notes.email;
  
  return null;
}

/**
 * Extract customer name from order data
 */
function extractCustomerName(orderData) {
  if (orderData.customerName) return orderData.customerName;
  
  if (orderData.customer && orderData.customer.firstName) {
    const firstName = orderData.customer.firstName || '';
    const lastName = orderData.customer.lastName || '';
    return `${firstName} ${lastName}`.trim();
  }
  
  if (orderData.user && orderData.user.displayName) return orderData.user.displayName;
  if (orderData.shipping && orderData.shipping.name) return orderData.shipping.name;
  
  if (orderData.notes && orderData.notes.firstName) {
    const firstName = orderData.notes.firstName || '';
    const lastName = orderData.notes.lastName || '';
    return `${firstName} ${lastName}`.trim();
  }
  
  return 'Customer';
}

/**
 * Extract product title from order data
 */
function extractProductTitle(orderData) {
  if (orderData.productTitle) return orderData.productTitle;
  if (orderData.product && orderData.product.title) return orderData.product.title;
  if (orderData.items && orderData.items.length > 0) {
    return orderData.items[0].name || orderData.items[0].title || 'Product';
  }
  
  return 'Your Product';
}

/**
 * Send fulfillment email via HTTP API
 */
async function sendFulfillmentEmail(emailData) {
  const https = require('https');
  const http = require('http');
  
  return new Promise((resolve, reject) => {
    const postData = JSON.stringify(emailData);
    
    const options = {
      hostname: 'attral.in', // Your production domain
      port: 443,
      path: '/api/send_fulfillment_email.php',
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Content-Length': Buffer.byteLength(postData)
      }
    };

    const req = https.request(options, (res) => {
      let data = '';
      
      res.on('data', (chunk) => {
        data += chunk;
      });
      
      res.on('end', () => {
        try {
          const result = JSON.parse(data);
          if (result.success) {
            resolve(result);
          } else {
            reject(new Error(result.error || 'Email sending failed'));
          }
        } catch (error) {
          reject(new Error(`Invalid response: ${data}`));
        }
      });
    });

    req.on('error', (error) => {
      reject(error);
    });

    req.write(postData);
    req.end();
  });
}

/**
 * HTTP function for manual triggering (optional)
 */
exports.triggerFulfillmentEmail = functions
  .region('us-central1')
  .https
  .onRequest(async (req, res) => {
  if (req.method !== 'POST') {
    return res.status(405).json({ error: 'Method not allowed' });
  }

  try {
    const { orderId, fulfillmentStatus } = req.body;
    
    if (!orderId || !fulfillmentStatus) {
      return res.status(400).json({ error: 'orderId and fulfillmentStatus are required' });
    }

    // Get order data from Firestore
    const orderDoc = await admin.firestore().collection('orders').doc(orderId).get();
    
    if (!orderDoc.exists) {
      return res.status(404).json({ error: 'Order not found' });
    }

    const orderData = orderDoc.data();
    const customerEmail = extractCustomerEmail(orderData);
    
    if (!customerEmail) {
      return res.status(400).json({ error: 'Customer email not found' });
    }

    // Send email
    const emailData = {
      orderId: orderId,
      customerEmail: customerEmail,
      customerName: extractCustomerName(orderData),
      fulfillmentStatus: fulfillmentStatus,
      productTitle: extractProductTitle(orderData),
      trackingNumber: orderData.trackingNumber || '',
      estimatedDelivery: orderData.estimatedDelivery || ''
    };

    const result = await sendFulfillmentEmail(emailData);
    
    res.json({
      success: true,
      message: 'Fulfillment email sent successfully',
      result: result
    });

  } catch (error) {
    console.error('Manual trigger error:', error);
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

// ==================== COUPON USAGE TRACKING ====================
// Export coupon usage tracking functions
exports.onOrderCreated = couponUsageTracker.onOrderCreated;
exports.incrementCouponUsageHttp = couponUsageTracker.incrementCouponUsageHttp;
exports.reprocessOrderCouponsHttp = couponUsageTracker.reprocessOrderCouponsHttp;
