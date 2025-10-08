/**
 * Affiliate Management Cloud Functions
 * Note: Primary affiliate functionality is handled by PHP APIs
 * These are backup/monitoring functions
 */

const functions = require('firebase-functions');
const admin = require('firebase-admin');

try { 
  if (!admin.apps.length) admin.initializeApp(); 
} catch(_) {}

/**
 * Create Affiliate Profile
 */
exports.createAffiliateProfile = functions
  .region('asia-south1')
  .https
  .onRequest(async (req, res) => {
    res.set('Access-Control-Allow-Origin', '*');
    res.set('Access-Control-Allow-Methods', 'POST, OPTIONS');
    res.set('Access-Control-Allow-Headers', 'Content-Type');
    
    if (req.method === 'OPTIONS') {
      res.status(204).send('');
      return;
    }
    
    if (req.method !== 'POST') {
      return res.status(405).json({ error: 'Method not allowed' });
    }
    
    try {
      const { uid, email, name, phone } = req.body;
      
      if (!uid || !email) {
        return res.status(400).json({ error: 'uid and email are required' });
      }
      
      const db = admin.firestore();
      const affiliateData = {
        uid: uid,
        email: email,
        name: name || '',
        phone: phone || '',
        affiliateCode: generateAffiliateCode(),
        status: 'pending',
        commissionRate: 0.10, // 10% default
        totalEarnings: 0,
        totalOrders: 0,
        createdAt: admin.firestore.FieldValue.serverTimestamp(),
        updatedAt: admin.firestore.FieldValue.serverTimestamp()
      };
      
      const docRef = await db.collection('affiliates').add(affiliateData);
      
      res.json({
        success: true,
        affiliateId: docRef.id,
        affiliateCode: affiliateData.affiliateCode
      });
      
    } catch (error) {
      console.error('Error creating affiliate profile:', error);
      res.status(500).json({ error: error.message });
    }
  });

/**
 * Get Affiliate Orders
 */
exports.getAffiliateOrders = functions
  .region('asia-south1')
  .https
  .onRequest(async (req, res) => {
    res.set('Access-Control-Allow-Origin', '*');
    res.set('Access-Control-Allow-Methods', 'GET, OPTIONS');
    res.set('Access-Control-Allow-Headers', 'Content-Type');
    
    if (req.method === 'OPTIONS') {
      res.status(204).send('');
      return;
    }
    
    if (req.method !== 'GET') {
      return res.status(405).json({ error: 'Method not allowed' });
    }
    
    try {
      const affiliateCode = req.query.affiliateCode;
      
      if (!affiliateCode) {
        return res.status(400).json({ error: 'affiliateCode is required' });
      }
      
      const db = admin.firestore();
      const ordersSnapshot = await db.collection('orders')
        .where('affiliate.code', '==', affiliateCode)
        .orderBy('createdAt', 'desc')
        .limit(100)
        .get();
      
      const orders = [];
      ordersSnapshot.forEach(doc => {
        orders.push({
          id: doc.id,
          ...doc.data()
        });
      });
      
      res.json({
        success: true,
        orders: orders,
        count: orders.length
      });
      
    } catch (error) {
      console.error('Error fetching affiliate orders:', error);
      res.status(500).json({ error: error.message });
    }
  });

/**
 * Get Affiliate Stats
 */
exports.getAffiliateStats = functions
  .region('asia-south1')
  .https
  .onRequest(async (req, res) => {
    res.set('Access-Control-Allow-Origin', '*');
    res.set('Access-Control-Allow-Methods', 'GET, OPTIONS');
    res.set('Access-Control-Allow-Headers', 'Content-Type');
    
    if (req.method === 'OPTIONS') {
      res.status(204).send('');
      return;
    }
    
    if (req.method !== 'GET') {
      return res.status(405).json({ error: 'Method not allowed' });
    }
    
    try {
      const affiliateCode = req.query.affiliateCode;
      
      if (!affiliateCode) {
        return res.status(400).json({ error: 'affiliateCode is required' });
      }
      
      const db = admin.firestore();
      
      // Get affiliate profile
      const affiliatesSnapshot = await db.collection('affiliates')
        .where('affiliateCode', '==', affiliateCode)
        .limit(1)
        .get();
      
      if (affiliatesSnapshot.empty) {
        return res.status(404).json({ error: 'Affiliate not found' });
      }
      
      const affiliate = affiliatesSnapshot.docs[0].data();
      
      // Get order stats
      const ordersSnapshot = await db.collection('orders')
        .where('affiliate.code', '==', affiliateCode)
        .get();
      
      let totalOrders = 0;
      let totalRevenue = 0;
      let totalCommission = 0;
      
      ordersSnapshot.forEach(doc => {
        const order = doc.data();
        totalOrders++;
        totalRevenue += order.amount || 0;
        totalCommission += (order.amount || 0) * (affiliate.commissionRate || 0.10);
      });
      
      res.json({
        success: true,
        stats: {
          affiliateCode: affiliateCode,
          totalOrders: totalOrders,
          totalRevenue: totalRevenue,
          totalCommission: totalCommission,
          commissionRate: affiliate.commissionRate || 0.10,
          status: affiliate.status || 'active'
        }
      });
      
    } catch (error) {
      console.error('Error fetching affiliate stats:', error);
      res.status(500).json({ error: error.message });
    }
  });

/**
 * Get Payment Details
 */
exports.getPaymentDetails = functions
  .region('asia-south1')
  .https
  .onRequest(async (req, res) => {
    res.set('Access-Control-Allow-Origin', '*');
    res.set('Access-Control-Allow-Methods', 'GET, OPTIONS');
    res.set('Access-Control-Allow-Headers', 'Content-Type');
    
    if (req.method === 'OPTIONS') {
      res.status(204).send('');
      return;
    }
    
    try {
      const orderId = req.query.orderId;
      
      if (!orderId) {
        return res.status(400).json({ error: 'orderId is required' });
      }
      
      const db = admin.firestore();
      const orderDoc = await db.collection('orders').doc(orderId).get();
      
      if (!orderDoc.exists) {
        return res.status(404).json({ error: 'Order not found' });
      }
      
      const order = orderDoc.data();
      
      res.json({
        success: true,
        payment: {
          orderId: orderId,
          razorpayOrderId: order.razorpayOrderId || '',
          razorpayPaymentId: order.razorpayPaymentId || '',
          amount: order.amount || 0,
          currency: order.currency || 'INR',
          status: order.status || 'pending',
          paymentMethod: order.payment?.method || 'unknown',
          createdAt: order.createdAt
        }
      });
      
    } catch (error) {
      console.error('Error fetching payment details:', error);
      res.status(500).json({ error: error.message });
    }
  });

/**
 * Update Payment Details
 */
exports.updatePaymentDetails = functions
  .region('asia-south1')
  .https
  .onRequest(async (req, res) => {
    res.set('Access-Control-Allow-Origin', '*');
    res.set('Access-Control-Allow-Methods', 'POST, OPTIONS');
    res.set('Access-Control-Allow-Headers', 'Content-Type');
    
    if (req.method === 'OPTIONS') {
      res.status(204).send('');
      return;
    }
    
    if (req.method !== 'POST') {
      return res.status(405).json({ error: 'Method not allowed' });
    }
    
    try {
      const { orderId, paymentId, status } = req.body;
      
      if (!orderId) {
        return res.status(400).json({ error: 'orderId is required' });
      }
      
      const db = admin.firestore();
      const updateData = {
        updatedAt: admin.firestore.FieldValue.serverTimestamp()
      };
      
      if (paymentId) updateData.razorpayPaymentId = paymentId;
      if (status) updateData.paymentStatus = status;
      
      await db.collection('orders').doc(orderId).update(updateData);
      
      res.json({
        success: true,
        message: 'Payment details updated'
      });
      
    } catch (error) {
      console.error('Error updating payment details:', error);
      res.status(500).json({ error: error.message });
    }
  });

/**
 * Get Payout Settings
 */
exports.getPayoutSettings = functions
  .region('asia-south1')
  .https
  .onRequest(async (req, res) => {
    res.set('Access-Control-Allow-Origin', '*');
    res.set('Access-Control-Allow-Methods', 'GET, OPTIONS');
    res.set('Access-Control-Allow-Headers', 'Content-Type');
    
    if (req.method === 'OPTIONS') {
      res.status(204).send('');
      return;
    }
    
    try {
      const affiliateId = req.query.affiliateId;
      
      if (!affiliateId) {
        return res.status(400).json({ error: 'affiliateId is required' });
      }
      
      const db = admin.firestore();
      const affiliateDoc = await db.collection('affiliates').doc(affiliateId).get();
      
      if (!affiliateDoc.exists) {
        return res.status(404).json({ error: 'Affiliate not found' });
      }
      
      const affiliate = affiliateDoc.data();
      
      res.json({
        success: true,
        payoutSettings: affiliate.payoutSettings || {
          method: 'bank_transfer',
          minimumPayout: 1000,
          currency: 'INR'
        }
      });
      
    } catch (error) {
      console.error('Error fetching payout settings:', error);
      res.status(500).json({ error: error.message });
    }
  });

/**
 * Update Payout Settings
 */
exports.updatePayoutSettings = functions
  .region('asia-south1')
  .https
  .onRequest(async (req, res) => {
    res.set('Access-Control-Allow-Origin', '*');
    res.set('Access-Control-Allow-Methods', 'POST, OPTIONS');
    res.set('Access-Control-Allow-Headers', 'Content-Type');
    
    if (req.method === 'OPTIONS') {
      res.status(204).send('');
      return;
    }
    
    if (req.method !== 'POST') {
      return res.status(405).json({ error: 'Method not allowed' });
    }
    
    try {
      const { affiliateId, payoutSettings } = req.body;
      
      if (!affiliateId || !payoutSettings) {
        return res.status(400).json({ error: 'affiliateId and payoutSettings are required' });
      }
      
      const db = admin.firestore();
      await db.collection('affiliates').doc(affiliateId).update({
        payoutSettings: payoutSettings,
        updatedAt: admin.firestore.FieldValue.serverTimestamp()
      });
      
      res.json({
        success: true,
        message: 'Payout settings updated'
      });
      
    } catch (error) {
      console.error('Error updating payout settings:', error);
      res.status(500).json({ error: error.message });
    }
  });

// Helper function to generate affiliate code
function generateAffiliateCode() {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
  let code = 'AFF-';
  for (let i = 0; i < 8; i++) {
    code += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  return code;
}

