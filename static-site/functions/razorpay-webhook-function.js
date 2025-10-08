/**
 * 🔥 PRIMARY Razorpay Webhook Cloud Function
 * Handles all Razorpay payment events and processes orders
 * 
 * This is the MAIN webhook handler for production use
 */

const functions = require('firebase-functions');
const admin = require('firebase-admin');
const crypto = require('crypto');
const axios = require('axios');

try { 
  if (!admin.apps.length) admin.initializeApp(); 
} catch(_) {}

// Configuration
const WEBHOOK_SECRET = 'Rakeshmurali@10';
const ORDER_MANAGER_URL = 'https://attral.in/api/firestore_order_manager.php/create';
const DOMAIN = 'attral.in';

exports.razorpayWebhook = functions
  .region('asia-south1')
  .https
  .onRequest(async (req, res) => {
    console.log('🔔 PRIMARY WEBHOOK: Received webhook request');
    
    // Set CORS headers
    res.set('Access-Control-Allow-Origin', '*');
    res.set('Access-Control-Allow-Methods', 'POST, OPTIONS');
    res.set('Access-Control-Allow-Headers', 'Content-Type, X-Razorpay-Signature');
    
    if (req.method === 'OPTIONS') {
      res.status(204).send('');
      return;
    }
    
    if (req.method !== 'POST') {
      console.warn('⚠️ Invalid method:', req.method);
      return res.status(405).json({ error: 'Method not allowed' });
    }
    
    try {
      // Get signature from headers
      const signature = req.headers['x-razorpay-signature'];
      
      if (!signature) {
        console.error('❌ Missing signature');
        return res.status(400).json({ error: 'Missing signature' });
      }
      
      // Verify webhook signature
      const rawBody = JSON.stringify(req.body);
      const expectedSignature = crypto
        .createHmac('sha256', WEBHOOK_SECRET)
        .update(rawBody)
        .digest('hex');
      
      if (signature !== expectedSignature) {
        console.error('❌ Invalid signature. Expected:', expectedSignature.substring(0, 10), 'Got:', signature.substring(0, 10));
        return res.status(401).json({ error: 'Invalid signature' });
      }
      
      console.log('✅ Signature verified');
      
      const event = req.body;
      console.log('📨 Event type:', event.event);
      
      // Handle different webhook events
      switch (event.event) {
        case 'payment.captured':
          await handlePaymentCaptured(event.payload.payment.entity, signature);
          break;
        
        case 'payment.failed':
          await handlePaymentFailed(event.payload.payment.entity);
          break;
        
        case 'order.paid':
          await handleOrderPaid(event.payload.order.entity);
          break;
        
        case 'payment.authorized':
          console.log('💳 Payment authorized:', event.payload.payment.entity.id);
          break;
        
        default:
          console.log('⚠️ Unhandled event type:', event.event);
      }
      
      console.log('✅ Webhook processed successfully');
      res.json({ 
        status: 'ok', 
        processed: true, 
        event: event.event,
        source: 'cloud-function-primary'
      });
      
    } catch (error) {
      console.error('❌ Webhook error:', error);
      res.status(500).json({ 
        error: error.message,
        source: 'cloud-function-primary'
      });
    }
  });

/**
 * Handle payment.captured event
 * This is the main event for successful payments
 */
async function handlePaymentCaptured(payment, signature) {
  const db = admin.firestore();
  
  try {
    const orderId = payment.order_id;
    const paymentId = payment.id;
    const amount = payment.amount; // Amount in paise
    const currency = payment.currency;
    const notes = payment.notes || {};
    
    console.log(`💰 Processing payment.captured: Order ${orderId}, Payment ${paymentId}, Amount ${amount/100} ${currency}`);
    
    // Extract customer data from notes
    const customerEmail = notes.email || 'customer@example.com';
    const customerFirstName = notes.firstName || 'Valued';
    const customerLastName = notes.lastName || 'Customer';
    const customerPhone = notes.phone || '';
    
    // Extract product data from notes (if available)
    const productData = notes.productData ? JSON.parse(notes.productData) : null;
    const couponsData = notes.coupons ? JSON.parse(notes.coupons) : [];
    
    console.log(`👤 Customer: ${customerFirstName} ${customerLastName} (${customerEmail})`);
    console.log(`📦 Products:`, productData ? 'Found' : 'Not in notes');
    console.log(`🎟️ Coupons:`, couponsData.length > 0 ? couponsData.length : 'None');
    
    // Build order data for PHP order manager
    const orderData = {
      order_id: orderId,
      payment_id: paymentId,
      signature: signature,
      customer: {
        firstName: customerFirstName,
        lastName: customerLastName,
        email: customerEmail,
        phone: customerPhone
      },
      product: productData || {
        id: 'webhook_order',
        title: 'ATTRAL 100W GaN Charger',
        price: amount / 100,
        items: [{
          id: 'webhook_item',
          title: 'ATTRAL 100W GaN Charger',
          price: amount / 100,
          quantity: 1
        }]
      },
      pricing: {
        subtotal: amount / 100,
        shipping: parseFloat(notes.shipping) || 0,
        discount: parseFloat(notes.discount) || 0,
        total: amount / 100,
        currency: currency
      },
      shipping: {
        address: notes.address || '',
        city: notes.city || '',
        state: notes.state || '',
        pincode: notes.pincode || '',
        country: notes.country || 'India'
      },
      payment: {
        method: 'razorpay',
        transaction_id: paymentId,
        signature: signature
      },
      coupons: couponsData,
      user_id: notes.userId || null,
      source: 'cloud-function-webhook'
    };
    
    console.log('📝 Order data prepared, calling PHP order manager...');
    
    // Call PHP order manager to handle all business logic
    // (This includes: order creation, affiliate tracking, coupon processing, emails, etc.)
    try {
      const response = await axios.post(ORDER_MANAGER_URL, orderData, {
        headers: {
          'Content-Type': 'application/json',
          'X-Webhook-Source': 'cloud-function',
          'X-Payment-Id': paymentId
        },
        timeout: 30000 // 30 second timeout
      });
      
      if (response.status === 200 && response.data.success) {
        console.log(`✅ Order created via PHP manager: ${response.data.orderNumber || 'unknown'}`);
        console.log(`📧 Emails and affiliate tracking handled by PHP manager`);
      } else {
        console.error('⚠️ PHP order manager returned error:', response.data.error || 'Unknown error');
      }
      
    } catch (phpError) {
      console.error('❌ Error calling PHP order manager:', phpError.message);
      
      // Fallback: Save order directly to Firestore
      console.log('🔄 Fallback: Saving order directly to Firestore...');
      await saveOrderToFirestore(orderData, payment);
    }
    
    // Also update order status in Firestore if order already exists
    const ordersQuery = await db.collection('orders')
      .where('razorpayOrderId', '==', orderId)
      .limit(1)
      .get();
    
    if (!ordersQuery.empty) {
      const orderDoc = ordersQuery.docs[0];
      await orderDoc.ref.update({
        paymentStatus: 'captured',
        razorpayPaymentId: paymentId,
        webhookProcessed: true,
        webhookProcessedAt: admin.firestore.FieldValue.serverTimestamp(),
        webhookSource: 'cloud-function-primary'
      });
      console.log('✅ Updated existing order in Firestore');
    }
    
  } catch (error) {
    console.error('❌ Error in handlePaymentCaptured:', error);
    throw error;
  }
}

/**
 * Fallback function to save order directly to Firestore
 * Used when PHP order manager is unavailable
 */
async function saveOrderToFirestore(orderData, payment) {
  const db = admin.firestore();
  
  try {
    const firestoreData = {
      orderId: orderData.order_id,
      razorpayOrderId: orderData.order_id,
      razorpayPaymentId: orderData.payment_id,
      status: 'confirmed',
      paymentStatus: 'captured',
      amount: orderData.pricing.total,
      currency: orderData.pricing.currency,
      customer: orderData.customer,
      product: orderData.product,
      pricing: orderData.pricing,
      shipping: orderData.shipping,
      payment: orderData.payment,
      coupons: orderData.coupons || [],
      createdAt: admin.firestore.FieldValue.serverTimestamp(),
      updatedAt: admin.firestore.FieldValue.serverTimestamp(),
      webhookProcessed: true,
      webhookProcessedAt: admin.firestore.FieldValue.serverTimestamp(),
      source: 'cloud-function-fallback'
    };
    
    const docRef = await db.collection('orders').add(firestoreData);
    console.log(`✅ Order saved to Firestore (fallback): ${docRef.id}`);
    
    return docRef.id;
    
  } catch (error) {
    console.error('❌ Error saving to Firestore:', error);
    throw error;
  }
}

/**
 * Handle payment.failed event
 */
async function handlePaymentFailed(payment) {
  const db = admin.firestore();
  
  try {
    console.log(`❌ Payment failed: ${payment.id}, Reason: ${payment.error_description || 'Unknown'}`);
    
    const ordersQuery = await db.collection('orders')
      .where('razorpayOrderId', '==', payment.order_id)
      .limit(1)
      .get();
    
    if (!ordersQuery.empty) {
      const orderDoc = ordersQuery.docs[0];
      await orderDoc.ref.update({
        paymentStatus: 'failed',
        paymentError: payment.error_description || 'Payment failed',
        webhookProcessed: true,
        webhookProcessedAt: admin.firestore.FieldValue.serverTimestamp(),
        webhookSource: 'cloud-function-primary'
      });
      console.log('✅ Marked order as failed in Firestore');
    }
    
  } catch (error) {
    console.error('❌ Error handling payment failed:', error);
    throw error;
  }
}

/**
 * Handle order.paid event
 */
async function handleOrderPaid(order) {
  console.log('✅ Order paid:', order.id);
  // This event is informational - actual processing happens in payment.captured
}

