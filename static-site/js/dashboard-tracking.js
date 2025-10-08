// Dashboard Tracking Details and Modal Functions

// View tracking details function
async function viewTrackingDetails(orderId) {
  try {
    console.log('📋 Viewing tracking details for order:', orderId);
    
    // Get order details from Firestore
    const orderDoc = await window.AttralFirebase.db.collection('orders').doc(orderId).get();
    
    if (!orderDoc.exists) {
      alert('❌ Order not found');
      return;
    }
    
    const orderData = orderDoc.data();
    
    // Create tracking details modal content
    const trackingDetails = `
      <div style="padding: 20px;">
        <h3 style="margin-bottom: 20px; color: var(--dark);">📋 Tracking Details</h3>
        
        <div style="display: grid; gap: 15px;">
          <div style="background: var(--light); padding: 15px; border-radius: 8px;">
            <strong>Order ID:</strong> ${orderId}
          </div>
          
          <div style="background: var(--light); padding: 15px; border-radius: 8px;">
            <strong>Tracking ID:</strong> ${orderData.trackingId || 'Not provided'}
          </div>
          
          <div style="background: var(--light); padding: 15px; border-radius: 8px;">
            <strong>Courier Service:</strong> ${orderData.courierName || 'Not specified'}
          </div>
          
          <div style="background: var(--light); padding: 15px; border-radius: 8px;">
            <strong>Shipped Date:</strong> ${orderData.shippedAt ? new Date(orderData.shippedAt.toDate ? orderData.shippedAt.toDate() : orderData.shippedAt).toLocaleString() : 'Not available'}
          </div>
          
          ${orderData.shippingNotes ? `
            <div style="background: var(--light); padding: 15px; border-radius: 8px;">
              <strong>Shipping Notes:</strong><br>
              ${orderData.shippingNotes}
            </div>
          ` : ''}
        </div>
        
        <div style="margin-top: 20px; text-align: center;">
          <button class="btn-secondary" onclick="closeTrackingDetailsModal()">Close</button>
        </div>
      </div>
    `;
    
    // Show tracking details in a modal
    showTrackingDetailsModal(trackingDetails);
    
  } catch (error) {
    console.error('❌ Error fetching tracking details:', error);
    alert('❌ Error fetching tracking details. Please try again.');
  }
}

// Show tracking details modal
function showTrackingDetailsModal(content) {
  const modal = document.createElement('div');
  modal.className = 'modal';
  modal.style.display = 'flex';
  modal.innerHTML = `
    <div class="modal-content" style="max-width: 500px;">
      <div class="modal-header">
        <h2>📋 Tracking Details</h2>
        <span class="close" onclick="closeTrackingDetailsModal()">&times;</span>
      </div>
      <div class="modal-body">
        ${content}
      </div>
    </div>
  `;
  
  document.body.appendChild(modal);
  
  // Close modal when clicking outside
  modal.onclick = function(event) {
    if (event.target === modal) {
      closeTrackingDetailsModal();
    }
  };
}

// Close tracking details modal
function closeTrackingDetailsModal() {
  const modal = document.querySelector('.modal');
  if (modal) {
    document.body.removeChild(modal);
  }
}

// View cancellation details function
async function viewCancellationDetails(orderId) {
  try {
    console.log('📋 Viewing cancellation details for order:', orderId);
    
    // Get order details from Firestore
    const orderDoc = await window.AttralFirebase.db.collection('orders').doc(orderId).get();
    
    if (!orderDoc.exists) {
      alert('❌ Order not found');
      return;
    }
    
    const orderData = orderDoc.data();
    
    // Create cancellation details modal content
    const cancellationDetails = `
      <div style="padding: 32px; background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);">
        <div style="text-align: center; margin-bottom: 32px;">
          <div style="font-size: 3rem; margin-bottom: 16px;">📋</div>
          <h3 style="margin: 0; color: #1f2937; font-size: 1.5rem; font-weight: 700;">Cancellation Details</h3>
          <p style="margin: 8px 0 0 0; color: #6b7280; font-size: 0.9rem;">Complete information about this order cancellation</p>
        </div>
        
        <div style="display: grid; gap: 20px;">
          <div style="background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); padding: 20px; border-radius: 12px; border-left: 4px solid #6b7280;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
              <span style="font-size: 1.2rem;">🆔</span>
              <strong style="color: #374151; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;">Order ID</strong>
            </div>
            <div style="font-family: monospace; font-weight: 600; color: #1f2937; font-size: 1.1rem;">${orderId}</div>
          </div>
          
          <div style="background: linear-gradient(135deg, #fef2f2 0%, #fecaca 100%); padding: 20px; border-radius: 12px; border-left: 4px solid #ef4444;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
              <span style="font-size: 1.2rem;">❌</span>
              <strong style="color: #991b1b; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;">Cancellation Reason</strong>
            </div>
            <div style="font-weight: 600; color: #7f1d1d; font-size: 1rem;">${orderData.cancellationReason || 'Not specified'}</div>
          </div>
          
          <div style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); padding: 20px; border-radius: 12px; border-left: 4px solid #0ea5e9;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
              <span style="font-size: 1.2rem;">📅</span>
              <strong style="color: #0c4a6e; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;">Cancelled Date</strong>
            </div>
            <div style="font-weight: 600; color: #0c4a6e; font-size: 1rem;">${orderData.cancelledAt ? new Date(orderData.cancelledAt.toDate ? orderData.cancelledAt.toDate() : orderData.cancelledAt).toLocaleString() : 'Not available'}</div>
          </div>
          
          <div style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); padding: 20px; border-radius: 12px; border-left: 4px solid #22c55e;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
              <span style="font-size: 1.2rem;">💰</span>
              <strong style="color: #166534; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;">Refund Amount</strong>
            </div>
            <div style="font-weight: 700; color: #166534; font-size: 1.2rem;">₹${(orderData.refundAmount || orderData.amount || 0).toLocaleString()}</div>
          </div>
          
          <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); padding: 20px; border-radius: 12px; border-left: 4px solid #f59e0b;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
              <span style="font-size: 1.2rem;">⏳</span>
              <strong style="color: #92400e; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;">Refund Status</strong>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
              <span class="status-badge ${orderData.refundStatus === 'processed' ? 'status-completed' : 'status-pending'}" style="font-size: 0.8rem; padding: 6px 12px;">
                ${(orderData.refundStatus || 'pending').toUpperCase()}
              </span>
            </div>
          </div>
          
          ${orderData.cancellationNotes ? `
            <div style="background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); padding: 20px; border-radius: 12px; border-left: 4px solid #6b7280;">
              <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                <span style="font-size: 1.2rem;">📝</span>
                <strong style="color: #374151; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;">Cancellation Notes</strong>
              </div>
              <div style="color: #1f2937; font-size: 1rem; line-height: 1.5; background: white; padding: 12px; border-radius: 8px; border: 1px solid #d1d5db;">${orderData.cancellationNotes}</div>
            </div>
          ` : ''}
          
          ${orderData.refundProcessedAt ? `
            <div style="background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); padding: 20px; border-radius: 12px; border-left: 4px solid #10b981;">
              <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                <span style="font-size: 1.2rem;">✅</span>
                <strong style="color: #065f46; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;">Refund Processed Date</strong>
              </div>
              <div style="font-weight: 600; color: #065f46; font-size: 1rem;">${new Date(orderData.refundProcessedAt.toDate ? orderData.refundProcessedAt.toDate() : orderData.refundProcessedAt).toLocaleString()}</div>
            </div>
          ` : ''}
        </div>
        
        <div style="margin-top: 32px; text-align: center;">
          <button onclick="closeCancellationDetailsModal()" style="padding: 12px 32px; border-radius: 8px; font-weight: 600; background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%); border: none; color: white; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 12px rgba(107, 114, 128, 0.3);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(107, 114, 128, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(107, 114, 128, 0.3)'">
            Close Details
          </button>
        </div>
      </div>
    `;
    
    // Show cancellation details in a modal
    showCancellationDetailsModal(cancellationDetails);
    
  } catch (error) {
    console.error('❌ Error fetching cancellation details:', error);
    alert('❌ Error fetching cancellation details. Please try again.');
  }
}

// Show cancellation details modal
function showCancellationDetailsModal(content) {
  const modal = document.createElement('div');
  modal.className = 'modal';
  modal.style.display = 'flex';
  modal.innerHTML = `
    <div class="modal-content" style="max-width: 700px; background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden;">
      <div class="modal-header" style="background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%); color: white; padding: 24px; border-bottom: none;">
        <h2 style="margin: 0; font-size: 1.5rem; font-weight: 700; display: flex; align-items: center; gap: 12px;">
          <span style="font-size: 1.8rem;">📋</span>
          Cancellation Details
        </h2>
        <span class="close" onclick="closeCancellationDetailsModal()" style="color: white; font-size: 2rem; font-weight: 300; opacity: 0.8; transition: opacity 0.2s;">&times;</span>
      </div>
      <div class="modal-body" style="padding: 0; max-height: 80vh; overflow-y: auto;">
        ${content}
      </div>
    </div>
  `;
  
  document.body.appendChild(modal);
  
  // Close modal when clicking outside
  modal.onclick = function(event) {
    if (event.target === modal) {
      closeCancellationDetailsModal();
    }
  };
}

// Close cancellation details modal
function closeCancellationDetailsModal() {
  const modal = document.querySelector('.modal');
  if (modal) {
    document.body.removeChild(modal);
  }
}

// View delivery details function
async function viewDeliveryDetails(orderId) {
  try {
    console.log('📋 Viewing delivery details for order:', orderId);
    
    // Get order details from Firestore
    const orderDoc = await window.AttralFirebase.db.collection('orders').doc(orderId).get();
    
    if (!orderDoc.exists) {
      alert('❌ Order not found');
      return;
    }
    
    const orderData = orderDoc.data();
    
    // Create delivery details modal content
    const deliveryDetails = `
      <div style="padding: 32px; background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);">
        <div style="text-align: center; margin-bottom: 32px;">
          <div style="font-size: 3rem; margin-bottom: 16px;">✅</div>
          <h3 style="margin: 0; color: #1f2937; font-size: 1.5rem; font-weight: 700;">Delivery Details</h3>
          <p style="margin: 8px 0 0 0; color: #6b7280; font-size: 0.9rem;">Complete delivery information for this order</p>
        </div>
        
        <div style="display: grid; gap: 20px;">
          <div style="background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); padding: 20px; border-radius: 12px; border-left: 4px solid #6b7280;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
              <span style="font-size: 1.2rem;">🆔</span>
              <strong style="color: #374151; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;">Order ID</strong>
            </div>
            <div style="font-family: monospace; font-weight: 600; color: #1f2937; font-size: 1.1rem;">${orderId}</div>
          </div>
          
          <div style="background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); padding: 20px; border-radius: 12px; border-left: 4px solid #10b981;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
              <span style="font-size: 1.2rem;">✅</span>
              <strong style="color: #065f46; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;">Delivery Status</strong>
            </div>
            <div style="font-weight: 700; color: #065f46; font-size: 1.2rem;">DELIVERED</div>
          </div>
          
          <div style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); padding: 20px; border-radius: 12px; border-left: 4px solid #0ea5e9;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
              <span style="font-size: 1.2rem;">📅</span>
              <strong style="color: #0c4a6e; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;">Delivered Date</strong>
            </div>
            <div style="font-weight: 600; color: #0c4a6e; font-size: 1rem;">${orderData.deliveredAt ? new Date(orderData.deliveredAt.toDate ? orderData.deliveredAt.toDate() : orderData.deliveredAt).toLocaleString() : 'Not available'}</div>
          </div>
          
          <div style="background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); padding: 20px; border-radius: 12px; border-left: 4px solid #6b7280;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
              <span style="font-size: 1.2rem;">📦</span>
              <strong style="color: #374151; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;">Tracking ID</strong>
            </div>
            <div style="font-family: monospace; font-weight: 600; color: #1f2937; font-size: 1rem;">${orderData.trackingId || 'Not provided'}</div>
          </div>
          
          <div style="background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); padding: 20px; border-radius: 12px; border-left: 4px solid #6b7280;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
              <span style="font-size: 1.2rem;">🚚</span>
              <strong style="color: #374151; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;">Courier Service</strong>
            </div>
            <div style="font-weight: 600; color: #1f2937; font-size: 1rem;">${orderData.courierName || 'Not specified'}</div>
          </div>
          
          <div style="background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); padding: 20px; border-radius: 12px; border-left: 4px solid #6b7280;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
              <span style="font-size: 1.2rem;">📍</span>
              <strong style="color: #374151; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;">Delivery Address</strong>
            </div>
            <div style="color: #1f2937; font-size: 1rem; line-height: 1.5; background: white; padding: 12px; border-radius: 8px; border: 1px solid #d1d5db;">
              ${orderData.shippingAddress ? 
                `${orderData.shippingAddress.name || ''}<br>
                 ${orderData.shippingAddress.line1 || ''}<br>
                 ${orderData.shippingAddress.line2 || ''}<br>
                 ${orderData.shippingAddress.city || ''}, ${orderData.shippingAddress.state || ''}<br>
                 ${orderData.shippingAddress.postalCode || ''}<br>
                 ${orderData.shippingAddress.country || ''}` : 
                'Address not available'
              }
            </div>
          </div>
          
          ${orderData.deliveryNotes ? `
            <div style="background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); padding: 20px; border-radius: 12px; border-left: 4px solid #6b7280;">
              <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                <span style="font-size: 1.2rem;">📝</span>
                <strong style="color: #374151; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;">Delivery Notes</strong>
              </div>
              <div style="color: #1f2937; font-size: 1rem; line-height: 1.5; background: white; padding: 12px; border-radius: 8px; border: 1px solid #d1d5db;">${orderData.deliveryNotes}</div>
            </div>
          ` : ''}
          
          ${orderData.shippingNotes ? `
            <div style="background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); padding: 20px; border-radius: 12px; border-left: 4px solid #6b7280;">
              <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                <span style="font-size: 1.2rem;">📝</span>
                <strong style="color: #374151; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;">Shipping Notes</strong>
              </div>
              <div style="color: #1f2937; font-size: 1rem; line-height: 1.5; background: white; padding: 12px; border-radius: 8px; border: 1px solid #d1d5db;">${orderData.shippingNotes}</div>
            </div>
          ` : ''}
        </div>
        
        <div style="margin-top: 32px; text-align: center;">
          <button onclick="closeDeliveryDetailsModal()" style="padding: 12px 32px; border-radius: 8px; font-weight: 600; background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%); border: none; color: white; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 12px rgba(107, 114, 128, 0.3);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(107, 114, 128, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(107, 114, 128, 0.3)'">
            Close Details
          </button>
        </div>
      </div>
    `;
    
    // Show delivery details in a modal
    showDeliveryDetailsModal(deliveryDetails);
    
  } catch (error) {
    console.error('❌ Error fetching delivery details:', error);
    alert('❌ Error fetching delivery details. Please try again.');
  }
}

// Show delivery details modal
function showDeliveryDetailsModal(content) {
  const modal = document.createElement('div');
  modal.className = 'modal';
  modal.style.display = 'flex';
  modal.innerHTML = `
    <div class="modal-content" style="max-width: 700px; background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden;">
      <div class="modal-header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 24px; border-bottom: none;">
        <h2 style="margin: 0; font-size: 1.5rem; font-weight: 700; display: flex; align-items: center; gap: 12px;">
          <span style="font-size: 1.8rem;">✅</span>
          Delivery Details
        </h2>
        <span class="close" onclick="closeDeliveryDetailsModal()" style="color: white; font-size: 2rem; font-weight: 300; opacity: 0.8; transition: opacity 0.2s;">&times;</span>
      </div>
      <div class="modal-body" style="padding: 0; max-height: 80vh; overflow-y: auto;">
        ${content}
      </div>
    </div>
  `;
  
  document.body.appendChild(modal);
  
  // Close modal when clicking outside
  modal.onclick = function(event) {
    if (event.target === modal) {
      closeDeliveryDetailsModal();
    }
  };
}

// Close delivery details modal
function closeDeliveryDetailsModal() {
  const modal = document.querySelector('.modal');
  if (modal) {
    document.body.removeChild(modal);
  }
}
