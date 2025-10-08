// Dashboard Modal and Form Handling Functions

// Product Management Functions
function openProductManager() {
  document.getElementById('productModal').style.display = 'flex';
}

function closeProductManager() {
  document.getElementById('productModal').style.display = 'none';
  document.getElementById('productForm').reset();
}

// Handle product form submission
document.addEventListener('DOMContentLoaded', function() {
  const productForm = document.getElementById('productForm');
  if (productForm) {
    productForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      
      const formData = new FormData(e.target);
      const productData = {
        title: formData.get('title'),
        price: parseFloat(formData.get('price')),
        description: formData.get('description'),
        category: formData.get('category'),
        image: formData.get('image') || '../assets/product_images/default.jpg',
        featured: false,
        createdAt: new Date()
      };

      try {
        console.log('💾 Saving product to Firestore...');
        
        // Save to Firestore
        await window.AttralFirebase.db.collection('products').add(productData);
        
        // Also save to products.json for backward compatibility
        const response = await fetch('/api/save_product.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(productData)
        });

        if (response.ok) {
          alert('✅ Product added successfully!');
          closeProductManager();
          
          // Refresh products list
          if (window.dashboardManager) {
            await window.dashboardManager.loadProducts();
            window.dashboardManager.updateProducts();
          }
        } else {
          throw new Error('Failed to save product');
        }
        
      } catch (error) {
        console.error('❌ Error saving product:', error);
        alert('❌ Error saving product. Please try again.');
      }
    });
  }
});

// Global functions for button clicks
function refreshDashboard() {
  console.log('🔄 Manual refresh triggered');
  if (window.dashboardManager) {
    window.dashboardManager.loadAllData();
  }
}

function refreshOrders() {
  console.log('🔄 Refreshing orders...');
  if (window.dashboardManager) {
    window.dashboardManager.loadOrders().then(() => {
      window.dashboardManager.updateOrders();
    });
  }
}

function refreshProducts() {
  console.log('🔄 Refreshing products...');
  if (window.dashboardManager) {
    window.dashboardManager.loadProducts().then(() => {
      window.dashboardManager.updateProducts();
    });
  }
}

function refreshMessages() {
  console.log('🔄 Refreshing messages...');
  if (window.dashboardManager) {
    window.dashboardManager.loadMessages().then(() => {
      window.dashboardManager.updateMessages();
    });
  }
}

function refreshFulfillment() {
  console.log('🔄 Refreshing fulfillment...');
  if (window.dashboardManager) {
    window.dashboardManager.loadFulfillment().then(() => {
      window.dashboardManager.updateFulfillment();
    });
  }
}

function refreshAffiliates() {
  console.log('🔄 Refreshing affiliates...');
  if (window.dashboardManager) {
    window.dashboardManager.loadAffiliates().then(() => {
      window.dashboardManager.updateAffiliates();
    });
  }
}

function refreshChart() {
  console.log('🔄 Refreshing chart...');
  // Chart refresh logic would go here
}

// Tracking Modal Functions
function openTrackingModal(orderId, customerName) {
  document.getElementById('orderId').value = orderId;
  document.getElementById('customerName').value = customerName;
  document.getElementById('trackingModal').style.display = 'flex';
}

function closeTrackingModal() {
  document.getElementById('trackingModal').style.display = 'none';
  document.getElementById('trackingForm').reset();
}

// Handle tracking form submission
document.addEventListener('DOMContentLoaded', function() {
  const trackingForm = document.getElementById('trackingForm');
  if (trackingForm) {
    trackingForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      
      const formData = new FormData(e.target);
      const trackingData = {
        orderId: formData.get('orderId'),
        trackingId: formData.get('trackingId'),
        courierName: formData.get('courierName'),
        shippingNotes: formData.get('shippingNotes'),
        shippedAt: new Date(),
        status: 'shipped'
      };

      try {
        console.log('📦 Updating order with tracking info...');
        
        // Get current order data first
        const orderDoc = await window.AttralFirebase.db.collection('orders').doc(trackingData.orderId).get();
        if (!orderDoc.exists) {
          throw new Error('Order not found');
        }
        const orderData = orderDoc.data();
        
        // Update order in Firestore with fulfillment status
        await window.AttralFirebase.db.collection('orders').doc(trackingData.orderId).update({
          status: 'shipped',
          fulfillmentStatus: 'shipped',
          shipping: {
            tracking: {
              id: trackingData.trackingId,
              courierName: trackingData.courierName,
              shippedAt: trackingData.shippedAt,
              notes: trackingData.shippingNotes || null
            },
            // Preserve existing address fields if any; this shallow set may overwrite.
          },
          // Keep existing top-level fields for backward compatibility
          trackingId: trackingData.trackingId,
          courierName: trackingData.courierName,
          shippingNotes: trackingData.shippingNotes,
          shippedAt: trackingData.shippedAt,
          updatedAt: new Date()
        });

        // Send shipping notification email to customer
        await sendShippingEmailNotification(orderData, trackingData);

        alert('✅ Order marked as shipped with tracking ID: ' + trackingData.trackingId + ' & customer notified');
        closeTrackingModal();
        
        // Refresh fulfillment list
        if (window.dashboardManager) {
          await window.dashboardManager.loadFulfillment();
          window.dashboardManager.updateFulfillment();
        }
        
      } catch (error) {
        console.error('❌ Error updating order:', error);
        alert('❌ Error updating order. Please try again.');
      }
    });
  }
});

// Update fulfillment status function
async function updateFulfillmentStatus(orderId, newStatus) {
  try {
    console.log('📦 Updating fulfillment status:', orderId, 'to', newStatus);
    
    // Get current order data first
    const orderDoc = await window.AttralFirebase.db.collection('orders').doc(orderId).get();
    if (!orderDoc.exists) {
      throw new Error('Order not found');
    }
    
    const orderData = orderDoc.data();
    
    // Update order in Firestore
    const updateData = {
      fulfillmentStatus: newStatus,
      updatedAt: new Date()
    };
    
    // If marking as delivered, also set the deliveredAt timestamp
    if (newStatus === 'delivered') {
      updateData.deliveredAt = new Date();
    }
    
    await window.AttralFirebase.db.collection('orders').doc(orderId).update(updateData);

    // Send email notification to customer
    await sendFulfillmentEmailNotification(orderData, newStatus);

    // Show success message based on status
    let message = '';
    switch(newStatus) {
      case 'ready-to-dispatch':
        message = '✅ Order marked as Ready to Dispatch & customer notified';
        break;
      case 'shipped':
        message = '✅ Order marked as Shipped & customer notified';
        break;
      case 'delivered':
        message = '✅ Order marked as Delivered & customer notified';
        break;
      default:
        message = '✅ Fulfillment status updated & customer notified';
    }
    
    alert(message);
    
    // Refresh fulfillment list
    if (window.dashboardManager) {
      await window.dashboardManager.loadFulfillment();
      window.dashboardManager.updateFulfillment();
    }
    
  } catch (error) {
    console.error('❌ Error updating fulfillment status:', error);
    alert('❌ Error updating fulfillment status. Please try again.');
  }
}

// Order Cancellation Functions
async function openCancelOrderModal(orderId, customerName) {
  document.getElementById('cancelOrderId').value = orderId;
  
  // Try to get the actual customer name from Firestore if not provided
  let actualCustomerName = customerName;
  if (!customerName || customerName === 'N/A') {
    try {
      const orderDoc = await window.AttralFirebase.db.collection('orders').doc(orderId).get();
      if (orderDoc.exists) {
        const orderData = orderDoc.data();
        actualCustomerName = orderData.customerName || 
                            orderData.customer?.firstName || 
                            orderData.customer?.name ||
                            orderData.customer_name ||
                            orderData.billingAddress?.name ||
                            orderData.shippingAddress?.name ||
                            'Customer Name Not Available';
      }
    } catch (error) {
      console.error('Error fetching customer name:', error);
      actualCustomerName = 'Customer Name Not Available';
    }
  }
  
  document.getElementById('cancelCustomerName').value = actualCustomerName;
  document.getElementById('cancelOrderModal').style.display = 'flex';
}

function closeCancelOrderModal() {
  document.getElementById('cancelOrderModal').style.display = 'none';
  document.getElementById('cancelOrderForm').reset();
}

// Handle order cancellation form submission
document.addEventListener('DOMContentLoaded', function() {
  const cancelOrderForm = document.getElementById('cancelOrderForm');
  if (cancelOrderForm) {
    cancelOrderForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      
      const formData = new FormData(e.target);
      const cancellationData = {
        orderId: formData.get('orderId'),
        reason: formData.get('reason'),
        notes: formData.get('notes'),
        refundAmount: parseFloat(formData.get('refundAmount')) || 0,
        notifyCustomer: formData.get('notifyCustomer') === 'on',
        cancelledAt: new Date(),
        cancelledBy: 'admin' // You can get this from auth context
      };

      try {
        console.log('❌ Cancelling order:', cancellationData.orderId);
        
        // Get current order data to calculate refund amount if not specified
        const orderDoc = await window.AttralFirebase.db.collection('orders').doc(cancellationData.orderId).get();
        const orderData = orderDoc.data();
        
        if (!orderData) {
          throw new Error('Order not found');
        }

        // Calculate refund amount if not specified
        if (!cancellationData.refundAmount) {
          cancellationData.refundAmount = orderData.amount || orderData.pricing?.total || 0;
        }

        // Update order in Firestore with cancellation details
        await window.AttralFirebase.db.collection('orders').doc(cancellationData.orderId).update({
          status: 'cancelled',
          fulfillmentStatus: 'cancelled',
          cancellationReason: cancellationData.reason,
          cancellationNotes: cancellationData.notes,
          refundAmount: cancellationData.refundAmount,
          refundStatus: 'pending',
          cancelledAt: cancellationData.cancelledAt,
          cancelledBy: cancellationData.cancelledBy,
          notifyCustomer: cancellationData.notifyCustomer,
          updatedAt: new Date()
        });

        alert(`✅ Order ${cancellationData.orderId} has been cancelled successfully.\nRefund Amount: ₹${cancellationData.refundAmount.toLocaleString()}`);
        closeCancelOrderModal();
        
        // Refresh fulfillment list
        if (window.dashboardManager) {
          await window.dashboardManager.loadFulfillment();
          window.dashboardManager.updateFulfillment();
        }
        
      } catch (error) {
        console.error('❌ Error cancelling order:', error);
        alert('❌ Error cancelling order: ' + error.message);
      }
    });
  }
});

// Process refund function
async function processRefund(orderId) {
  try {
    const confirmed = confirm(`Are you sure you want to process the refund for order ${orderId}?`);
    if (!confirmed) return;

    console.log('💰 Processing refund for order:', orderId);
    
    // Get order data
    const orderDoc = await window.AttralFirebase.db.collection('orders').doc(orderId).get();
    const orderData = orderDoc.data();
    
    if (!orderData) {
      throw new Error('Order not found');
    }

    // Update refund status
    await window.AttralFirebase.db.collection('orders').doc(orderId).update({
      refundStatus: 'processed',
      refundProcessedAt: new Date(),
      refundProcessedBy: 'admin',
      updatedAt: new Date()
    });

    alert(`✅ Refund processed successfully for order ${orderId}.\nAmount: ₹${(orderData.refundAmount || orderData.amount || 0).toLocaleString()}`);
    
    // Refresh fulfillment list
    if (window.dashboardManager) {
      await window.dashboardManager.loadFulfillment();
      window.dashboardManager.updateFulfillment();
    }
    
  } catch (error) {
    console.error('❌ Error processing refund:', error);
    alert('❌ Error processing refund: ' + error.message);
  }
}

// Close modal when clicking outside
window.onclick = function(event) {
  const productModal = document.getElementById('productModal');
  const trackingModal = document.getElementById('trackingModal');
  const cancelOrderModal = document.getElementById('cancelOrderModal');
  const affiliateModal = document.getElementById('affiliateModal');
  
  if (event.target === productModal) {
    closeProductManager();
  }
  if (event.target === trackingModal) {
    closeTrackingModal();
  }
  if (event.target === cancelOrderModal) {
    closeCancelOrderModal();
  }
  if (event.target === affiliateModal) {
    closeAffiliateModal();
  }
};

// Affiliate detail modal logic
async function openAffiliateModal(affiliateId) {
  const modal = document.getElementById('affiliateModal');
  const body = document.getElementById('affiliate-modal-body');
  modal.style.display = 'flex';
  body.innerHTML = '<div class="loading"><div class="loading-spinner"></div><span>Loading affiliate details...</span></div>';

  try {
    const dm = window.dashboardManager;
    const affiliate = (dm?.data?.affiliates || []).find(a => a.id === affiliateId);
    if (!affiliate) throw new Error('Affiliate not found');

    // Compute orders count via two queries (supports both schemas: affiliate.code and ref)
    const db = window.AttralFirebase.db;
    let totalOrders = 0;
    let totalAmount = 0;

    const [byEmbedSnap, byFlatSnap, affiliateDoc] = await Promise.all([
      affiliate.code ? db.collection('orders').where('affiliate.code', '==', affiliate.code).get() : Promise.resolve({ empty: true, forEach: () => {} }),
      affiliate.code ? db.collection('orders').where('ref', '==', affiliate.code).get() : Promise.resolve({ empty: true, forEach: () => {} }),
      db.collection('affiliates').doc(affiliateId).get()
    ]);

    const accumulate = (snap) => {
      if (!snap || snap.empty) return;
      snap.forEach(doc => {
        const o = doc.data();
        const amount = typeof o.amount === 'number' ? o.amount : (o.pricing?.total || 0);
        totalOrders += 1;
        totalAmount += Number(amount || 0);
      });
    };
    accumulate(byEmbedSnap);
    accumulate(byFlatSnap);

    // Get payment details from affiliate document
    const affiliateData = affiliateDoc.exists ? affiliateDoc.data() : {};
    const paymentDetails = affiliateData.paymentDetails || {};
    const payoutSettings = {
      payoutThreshold: affiliateData.payoutThreshold || 1000,
      payoutFrequency: affiliateData.payoutFrequency || 'monthly',
      currency: affiliateData.currency || 'INR',
      commissionRate: affiliateData.commissionRate || 0.1
    };

    const html = `
      <div style="display:flex; flex-direction:column; gap:16px;">
        <div>
          <div style="font-weight:800; font-size:1.1rem; color: var(--dark);">${affiliate.name}</div>
          <div style="color:#6b7280; font-size:0.9rem;">${affiliate.email || ''}</div>
        </div>
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px,1fr)); gap:12px;">
          <div style="background:var(--light); padding:12px; border-radius:8px; border:1px solid var(--border);">
            <div style="font-size:12px; color:#6b7280; text-transform:uppercase; font-weight:600;">Affiliate Code</div>
            <div style="font-weight:700;">${affiliate.code || '—'}</div>
          </div>
          <div style="background:var(--light); padding:12px; border-radius:8px; border:1px solid var(--border);">
            <div style="font-size:12px; color:#6b7280; text-transform:uppercase; font-weight:600;">Orders from Link</div>
            <div style="font-weight:800; color: var(--primary);">${totalOrders}</div>
          </div>
          <div style="background:var(--light); padding:12px; border-radius:8px; border:1px solid var(--border);">
            <div style="font-size:12px; color:#6b7280; text-transform:uppercase; font-weight:600;">Total Order Value</div>
            <div style="font-weight:800; color: var(--primary);">₹${totalAmount.toLocaleString()}</div>
          </div>
        </div>
        <div>
          <div style="font-size:12px; color:#6b7280; text-transform:uppercase; font-weight:600; margin-bottom:6px;">Affiliate Link</div>
          <div style="display:flex; gap:8px; align-items:center;">
            <input type="text" value="${affiliate.link || ''}" readonly style="flex:1; padding:8px; border:2px solid var(--border); border-radius:8px;" />
            <button class="btn-primary" onclick="navigator.clipboard.writeText('${(affiliate.link||'').replace(/'/g, "\\'")}')">Copy</button>
          </div>
        </div>
        
        <!-- Payment Details Section -->
        <div style="border-top: 2px solid var(--border); padding-top: 16px;">
          <div style="font-size:14px; font-weight:700; color: var(--dark); margin-bottom:12px; display:flex; align-items:center; gap:8px;">
            💳 Payment Details
            ${paymentDetails.bankAccountName ? '<span style="background: var(--success); color: white; padding: 2px 8px; border-radius: 12px; font-size: 10px; font-weight: 600;">CONFIGURED</span>' : '<span style="background: var(--warning); color: white; padding: 2px 8px; border-radius: 12px; font-size: 10px; font-weight: 600;">NOT SET</span>'}
          </div>
          
          ${paymentDetails.bankAccountName ? `
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px,1fr)); gap:12px;">
              <div style="background:var(--light); padding:12px; border-radius:8px; border:1px solid var(--border);">
                <div style="font-size:11px; color:#6b7280; text-transform:uppercase; font-weight:600; margin-bottom:4px;">Bank Account</div>
                <div style="font-weight:600; color: var(--dark);">${paymentDetails.bankAccountName || '—'}</div>
                <div style="font-size:12px; color:#6b7280; margin-top:2px;">${paymentDetails.bankAccountNumber || '—'}</div>
              </div>
              <div style="background:var(--light); padding:12px; border-radius:8px; border:1px solid var(--border);">
                <div style="font-size:11px; color:#6b7280; text-transform:uppercase; font-weight:600; margin-bottom:4px;">IFSC Code</div>
                <div style="font-weight:600; color: var(--dark); font-family: monospace;">${paymentDetails.ifsc || '—'}</div>
              </div>
              <div style="background:var(--light); padding:12px; border-radius:8px; border:1px solid var(--border);">
                <div style="font-size:11px; color:#6b7280; text-transform:uppercase; font-weight:600; margin-bottom:4px;">UPI ID</div>
                <div style="font-weight:600; color: var(--dark);">${paymentDetails.upiId || '—'}</div>
                <div style="font-size:12px; color:#6b7280; margin-top:2px;">${paymentDetails.upiMobile || '—'}</div>
              </div>
            </div>
            <div style="margin-top:8px; font-size:11px; color:#6b7280;">
              Last updated: ${paymentDetails.updatedAt ? new Date(paymentDetails.updatedAt.toDate ? paymentDetails.updatedAt.toDate() : paymentDetails.updatedAt).toLocaleDateString() : 'Unknown'}
            </div>
          ` : `
            <div style="background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 12px; text-align: center;">
              <div style="color: #92400e; font-weight: 600; margin-bottom: 4px;">⚠️ Payment Details Not Configured</div>
              <div style="color: #92400e; font-size: 12px;">This affiliate hasn't set up their payment details yet.</div>
            </div>
          `}
        </div>
        
        <!-- Payout Settings Section -->
        <div style="border-top: 2px solid var(--border); padding-top: 16px;">
          <div style="font-size:14px; font-weight:700; color: var(--dark); margin-bottom:12px; display:flex; align-items:center; gap:8px;">
            💰 Payout Settings
          </div>
          
          <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px,1fr)); gap:12px;">
            <div style="background:var(--light); padding:12px; border-radius:8px; border:1px solid var(--border);">
              <div style="font-size:11px; color:#6b7280; text-transform:uppercase; font-weight:600; margin-bottom:4px;">Commission Rate</div>
              <div style="font-weight:700; color: var(--primary);">${(payoutSettings.commissionRate * 100).toFixed(1)}%</div>
            </div>
            <div style="background:var(--light); padding:12px; border-radius:8px; border:1px solid var(--border);">
              <div style="font-size:11px; color:#6b7280; text-transform:uppercase; font-weight:600; margin-bottom:4px;">Payout Threshold</div>
              <div style="font-weight:700; color: var(--dark);">₹${payoutSettings.payoutThreshold.toLocaleString()}</div>
            </div>
            <div style="background:var(--light); padding:12px; border-radius:8px; border:1px solid var(--border);">
              <div style="font-size:11px; color:#6b7280; text-transform:uppercase; font-weight:600; margin-bottom:4px;">Payout Frequency</div>
              <div style="font-weight:700; color: var(--dark); text-transform:capitalize;">${payoutSettings.payoutFrequency}</div>
            </div>
            <div style="background:var(--light); padding:12px; border-radius:8px; border:1px solid var(--border);">
              <div style="font-size:11px; color:#6b7280; text-transform:uppercase; font-weight:600; margin-bottom:4px;">Currency</div>
              <div style="font-weight:700; color: var(--dark);">${payoutSettings.currency}</div>
            </div>
          </div>
          
          <div style="margin-top:12px; padding:12px; background: #e0f2fe; border: 1px solid #0288d1; border-radius: 8px;">
            <div style="font-size:12px; color: #01579b; font-weight: 600; margin-bottom:4px;">💡 Commission Calculation</div>
            <div style="font-size:11px; color: #01579b;">
              Total Order Value: ₹${totalAmount.toLocaleString()} × ${(payoutSettings.commissionRate * 100).toFixed(1)}% = 
              <strong>₹${(totalAmount * payoutSettings.commissionRate).toLocaleString()} commission</strong>
            </div>
          </div>
        </div>
        
        <div style="display:flex; justify-content:flex-end; gap:8px;">
          <a class="card-action" href="affiliate-dashboard.html" target="_blank">Open Affiliate Dashboard</a>
          <button class="btn-secondary" onclick="closeAffiliateModal()">Close</button>
        </div>
      </div>
    `;

    body.innerHTML = html;
  } catch (e) {
    console.error(e);
    body.innerHTML = '<div class="empty-state"><div class="empty-state-icon">⚠️</div><p>Failed to load affiliate details</p></div>';
  }
}

function closeAffiliateModal() {
  document.getElementById('affiliateModal').style.display = 'none';
}
