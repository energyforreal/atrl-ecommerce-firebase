const functions = require('firebase-functions');
const admin = require('firebase-admin');

// Admin is already initialized by other modules; guard init for local tests
try { if (!admin.apps.length) admin.initializeApp(); } catch(_) {}

// Normalize a coupon entry from an order object
function extractCouponsFromOrder(order){
  const list = [];
  if (Array.isArray(order.coupons)){
    order.coupons.forEach(c => {
      if (c && c.code){
        list.push({ code: (c.code||'').toString().trim(), affiliateCode: c.affiliateCode || null });
      }
    });
  }
  return list;
}

async function rebuildCore(){
  const db = admin.firestore();
  const counts = new Map();

  // Iterate through orders collection in pages
  let q = db.collection('orders').orderBy('createdAt', 'desc');
  const pageSize = 500;
  let last = null;
  let processed = 0;

  while (true){
    let query = q.limit(pageSize);
    if (last) query = query.startAfter(last);
    const snap = await query.get();
    if (snap.empty) break;
    snap.forEach(doc => {
      const data = doc.data() || {};
      const coupons = extractCouponsFromOrder(data);
      coupons.forEach(c => {
        const key = (c.code||'').toUpperCase();
        counts.set(key, (counts.get(key)||0) + 1);
      });
      processed++;
    });
    last = snap.docs[snap.docs.length - 1];
    if (snap.size < pageSize) break;
  }

  // Apply updates to coupons collection
  const batch = db.batch();
  const now = admin.firestore.FieldValue.serverTimestamp();
  for (const [code, usage] of counts.entries()){
    const q = await db.collection('coupons').where('code','==', code).limit(1).get();
    if (q.empty){
      continue; // skip missing codes
    }
    const ref = q.docs[0].ref;
    batch.set(ref, { usageCount: usage, updatedAt: now }, { merge: true });
  }
  await batch.commit();

  return { processed, couponsUpdated: counts.size };
}

exports.rebuildCouponUsage = functions.region('us-central1').pubsub.schedule('every 24 hours').onRun(async () => {
  const result = await rebuildCore();
  console.log('Coupon usage rebuild complete', result);
  return result;
});

exports.rebuildCouponUsageHttp = functions.region('us-central1').https.onRequest(async (req, res) => {
  if (req.method !== 'POST') return res.status(405).json({ success:false, error:'Method not allowed' });
  try {
    const result = await rebuildCore();
    res.json({ success:true, ...result });
  } catch (e){
    console.error('rebuild error', e);
    res.status(500).json({ success:false, error: e.message });
  }
});


