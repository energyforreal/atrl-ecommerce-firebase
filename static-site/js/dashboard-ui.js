// Dashboard UI Update Functions

// Extend DashboardManager with UI update methods
DashboardManager.prototype.updateOrders = function() {
  const orders = this.data.orders.slice(0, 5);
  const container = document.getElementById('recent-orders');
  
  if (orders.length === 0) {
    container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">📦</div><p>No orders found</p></div>';
    return;
  }

  const getOrderTotal = (order) => {
    if (typeof order.totalAmount === 'number') return order.totalAmount;
    if (typeof order.amount === 'number') return order.amount;
    if (order.pricing && typeof order.pricing.total === 'number') return order.pricing.total;
    return 0;
  };

  const hasRazorpayPayment = (order) => {
    try {
      return !!(
        order.razorpayPaymentId ||
        order.paymentId ||
        order.razorpay_payment_id ||
        order.payment_id ||
        (order.payment && (order.payment.transaction_id || order.payment.paymentId))
      );
    } catch (_) {
      return false;
    }
  };

  const getActualOrderStatus = (order) => {
    const baseStatus = order.status || 'pending';
    const hasPayment = hasRazorpayPayment(order);
    
    // If no payment acknowledgment, status is incomplete regardless of order status
    if (!hasPayment) {
      return 'incomplete';
    }
    
    // If payment exists, use the order status
    return baseStatus;
  };

  const table = `
    <table class="table">
      <thead>
        <tr>
          <th>Order ID</th>
          <th>Customer</th>
          <th>Amount</th>
          <th>Status</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        ${orders.map(order => {
          const actualStatus = getActualOrderStatus(order);
          return `
          <tr>
            <td>${order.id || 'N/A'}</td>
            <td>${order.customerName || order.customer?.firstName || order.customer_name || 'N/A'}</td>
            <td>₹${(parseFloat(getOrderTotal(order)) || 0).toLocaleString()}</td>
            <td><span class="status-badge status-${actualStatus}">${actualStatus.toUpperCase()}</span></td>
            <td>${order.createdAt.toLocaleDateString()}</td>
          </tr>
        `;
        }).join('')}
      </tbody>
    </table>
  `;
  
  container.innerHTML = table;
};

DashboardManager.prototype.updateProducts = function() {
  const products = this.data.products.slice(0, 5);
  const container = document.getElementById('top-products');
  
  if (products.length === 0) {
    container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">🛍️</div><p>No products found</p></div>';
    return;
  }

  console.log('🛍️ Updating products display:', products);

  const list = `
    <div style="display: flex; flex-direction: column; gap: 1rem;">
      ${products.map(product => {
        const price = parseFloat(product.price) || 0;
        const title = product.title || product.name || 'Unnamed Product';
        const category = product.category || 'Electronics';
        
        console.log(`📦 Product: ${title}, Price: ₹${price}, Category: ${category}`);
        
        return `
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 1.25rem; background: var(--light); border-radius: 12px; border: 1px solid var(--border); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='var(--shadow-md)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
          <div style="flex: 1;">
            <div style="font-weight: 700; color: var(--text-primary); font-size: 1rem; margin-bottom: 0.25rem;">${title}</div>
            <div style="font-size: 0.875rem; color: var(--text-secondary); font-weight: 500;">${category}</div>
          </div>
          <div style="text-align: right; margin-left: 1rem;">
            <div style="font-weight: 800; color: var(--primary); font-size: 1.125rem;">₹${price.toLocaleString()}</div>
            <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Price</div>
          </div>
        </div>
      `;
      }).join('')}
    </div>
  `;
  
  container.innerHTML = list;
};

DashboardManager.prototype.updateMessages = function() {
  const messages = this.data.messages.slice(0, 5);
  const container = document.getElementById('recent-messages');
  
  if (messages.length === 0) {
    container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">💬</div><p>No messages found</p></div>';
    return;
  }

  const list = `
    <div style="display: flex; flex-direction: column; gap: 1rem;">
      ${messages.map(message => `
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: var(--light); border-radius: 8px; border-left: 4px solid ${message.status === 'new' ? 'var(--primary)' : 'var(--success)'};">
          <div>
            <div style="font-weight: 600; color: var(--dark);">${message.name || 'Unknown'}</div>
            <div style="font-size: 0.875rem; color: #6b7280;">${message.subject || message.message?.substring(0, 50) + '...' || 'No subject'}</div>
          </div>
          <div style="text-align: right;">
            <div style="font-size: 0.75rem; color: #6b7280;">${message.createdAt.toLocaleDateString()}</div>
            <div style="font-size: 0.75rem; color: ${message.status === 'new' ? 'var(--primary)' : 'var(--success)'}; font-weight: 600;">
              ${message.status === 'new' ? 'NEW' : 'READ'}
            </div>
          </div>
        </div>
      `).join('')}
    </div>
  `;
  
  container.innerHTML = list;
};

DashboardManager.prototype.updateFulfillment = function() {
  const orders = this.data.fulfillmentOrders || [];
  const container = document.getElementById('fulfillment-content');
  
  if (orders.length === 0) {
    container.innerHTML = `
      <div style="text-align: center; padding: 60px 20px; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-radius: 16px; border: 2px dashed #cbd5e1;">
        <div style="font-size: 4rem; margin-bottom: 24px; opacity: 0.6;">📦</div>
        <h3 style="color: #475569; font-size: 1.5rem; font-weight: 600; margin-bottom: 12px;">No Orders Pending Fulfillment</h3>
        <p style="color: #64748b; font-size: 1rem; margin-bottom: 24px; max-width: 400px; margin-left: auto; margin-right: auto; line-height: 1.6;">
          All orders are either completed or there are no orders requiring fulfillment at this time.
        </p>
        <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
          <div style="background: white; padding: 12px 20px; border-radius: 8px; border: 1px solid #e2e8f0; color: #475569; font-size: 0.9rem; font-weight: 500;">
            📊 Check your orders dashboard
          </div>
          <div style="background: white; padding: 12px 20px; border-radius: 8px; border: 1px solid #e2e8f0; color: #475569; font-size: 0.9rem; font-weight: 500;">
            🔄 Refresh to see updates
          </div>
        </div>
      </div>
    `;
    return;
  }

  const table = `
    <div class="fulfillment-wrap">
      <table class="table fulfillment-table">
        <thead>
          <tr>
            <th style="padding: 16px 12px; text-align: left;">Order ID</th>
            <th style="padding: 16px 12px; text-align: left;">Customer</th>
            <th style="padding: 16px 12px; text-align: left;">Amount</th>
            <th style="padding: 16px 12px; text-align: left;">Payment</th>
            <th style="padding: 16px 12px; text-align: left;">Fulfillment Status</th>
            <th style="padding: 16px 12px; text-align: left;">Refund Status</th>
            <th style="padding: 16px 12px; text-align: left;">Date</th>
            <th style="padding: 16px 12px; text-align: center;">Actions</th>
          </tr>
        </thead>
        <tbody>
          ${orders.map(order => {
            const actualStatus = getActualOrderStatus(order);
            const isPaid = hasRazorpayPayment(order);
            const fulfillmentStatus = order.fulfillmentStatus || 'yet-to-dispatch';
            
            // Get fulfillment status display
            const getFulfillmentStatusDisplay = (status) => {
              switch(status) {
                case 'yet-to-dispatch': return { text: 'Yet to Dispatch', class: 'status-pending', icon: '⏳' };
                case 'ready-to-dispatch': return { text: 'Ready to Dispatch', class: 'status-processing', icon: '📦' };
                case 'shipped': return { text: 'Shipped', class: 'status-processing', icon: '🚚' };
                case 'delivered': return { text: 'Delivered', class: 'status-completed', icon: '✅' };
                case 'cancelled': return { text: 'Cancelled', class: 'status-cancelled', icon: '❌' };
                default: return { text: 'Yet to Dispatch', class: 'status-pending', icon: '⏳' };
              }
            };
            
            const fulfillment = getFulfillmentStatusDisplay(fulfillmentStatus);
            
            // Get customer name with better extraction logic
            const getCustomerName = (order) => {
              return order.customerName || 
                     order.customer?.firstName || 
                     order.customer?.name ||
                     order.customer_name ||
                     order.billingAddress?.name ||
                     order.shippingAddress?.name ||
                     'N/A';
            };
            
            const customerName = getCustomerName(order);
            
            // Get action button based on status
            const getActionButton = (status, orderId, customerName) => {
              const buttonStyle = `
                padding: 8px 16px; 
                border-radius: 8px; 
                font-size: 0.8rem; 
                font-weight: 600; 
                border: none; 
                cursor: pointer; 
                transition: all 0.2s ease; 
                display: inline-flex; 
                align-items: center; 
                gap: 6px;
                text-decoration: none;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
              `;
              
              const warningButton = `background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; ${buttonStyle}`;
              const primaryButton = `background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; ${buttonStyle}`;
              const secondaryButton = `background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%); color: white; ${buttonStyle}`;
              const dangerButton = `background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; ${buttonStyle}`;
              const successButton = `background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; ${buttonStyle}`;
              
              switch(status) {
                case 'yet-to-dispatch':
                  return `<div style="display: flex; gap: 8px; flex-wrap: wrap; justify-content: center;">
                            <button style="${warningButton}" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(245, 158, 11, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)'" onclick="updateFulfillmentStatus('${orderId}', 'ready-to-dispatch')">
                              📦 Mark Ready to Dispatch
                            </button>
                            <button style="${dangerButton}" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(239, 68, 68, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)'" onclick="openCancelOrderModal('${orderId}', '${customerName}')">
                              ❌ Cancel Order
                            </button>
                          </div>`;
                case 'ready-to-dispatch':
                  return `<div style="display: flex; gap: 8px; flex-wrap: wrap; justify-content: center;">
                            <button style="${primaryButton}" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(59, 130, 246, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)'" onclick="openTrackingModal('${orderId}', '${customerName}')">
                              🚚 Ship Order
                            </button>
                            <button style="${dangerButton}" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(239, 68, 68, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)'" onclick="openCancelOrderModal('${orderId}', '${customerName}')">
                              ❌ Cancel Order
                            </button>
                          </div>`;
                case 'shipped':
                  return `<div style="display: flex; gap: 8px; flex-wrap: wrap; justify-content: center;">
                            <button style="${successButton}" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(16, 185, 129, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)'" onclick="updateFulfillmentStatus('${orderId}', 'delivered')">
                              ✅ Mark Delivered
                            </button>
                            <button style="${secondaryButton}" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(107, 114, 128, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)'" onclick="viewTrackingDetails('${orderId}')">
                              📋 View Tracking
                            </button>
                            <button style="${dangerButton}" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(239, 68, 68, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)'" onclick="openCancelOrderModal('${orderId}', '${customerName}')">
                              ❌ Cancel Order
                            </button>
                          </div>`;
                case 'delivered':
                  return `<div style="display: flex; gap: 8px; flex-wrap: wrap; justify-content: center;">
                            <button style="${secondaryButton}" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(107, 114, 128, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)'" onclick="viewTrackingDetails('${orderId}')">
                              📋 View Tracking
                            </button>
                            <button style="${primaryButton}" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(59, 130, 246, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)'" onclick="viewDeliveryDetails('${orderId}')">
                              📋 View Delivery Details
                            </button>
                          </div>`;
                case 'cancelled':
                  return `<div style="display: flex; gap: 8px; flex-wrap: wrap; justify-content: center;">
                            <button style="${secondaryButton}" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(107, 114, 128, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)'" onclick="viewCancellationDetails('${orderId}')">
                              📋 View Cancellation
                            </button>
                            <button style="${successButton}" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(16, 185, 129, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)'" onclick="processRefund('${orderId}')">
                              💰 Process Refund
                            </button>
                          </div>`;
                default:
                  return `<div style="display: flex; gap: 8px; flex-wrap: wrap; justify-content: center;">
                            <button style="${warningButton}" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(245, 158, 11, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)'" onclick="updateFulfillmentStatus('${orderId}', 'ready-to-dispatch')">
                              📦 Mark Ready to Dispatch
                            </button>
                            <button style="${dangerButton}" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(239, 68, 68, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)'" onclick="openCancelOrderModal('${orderId}', '${customerName}')">
                              ❌ Cancel Order
                            </button>
                          </div>`;
              }
            };
            
            // Get refund status display
            const getRefundStatusDisplay = (order) => {
              if (order.fulfillmentStatus !== 'cancelled') {
                return { text: 'N/A', class: 'status-incomplete', icon: '—' };
              }
              
              const refundStatus = order.refundStatus || 'pending';
              switch(refundStatus) {
                case 'pending': return { text: 'Pending', class: 'status-pending', icon: '⏳' };
                case 'processed': return { text: 'Processed', class: 'status-completed', icon: '✅' };
                default: return { text: 'Pending', class: 'status-pending', icon: '⏳' };
              }
            };
            
            const refundStatus = getRefundStatusDisplay(order);
            
            return `
            <tr>
              <td style="padding: 16px 12px; font-family: monospace; font-weight: 600; color: #1f2937; background: #f9fafb;">
                <strong>${order.orderCode || order.orderId || order.id || 'N/A'}</strong>
              </td>
              <td style="padding: 16px 12px; font-weight: 500; color: #374151;">
                ${customerName}
              </td>
              <td class="amount-cell" style="padding: 16px 12px;">
                ₹${(parseFloat((typeof order.amount === 'number' ? order.amount : (order.pricing?.total || 0))) || 0).toLocaleString()}
              </td>
              <td style="padding: 16px 12px; text-align: center;">
                <span class="status-badge status-${isPaid ? 'completed' : 'incomplete'} paid-badge">
                  ${isPaid ? '✅ PAID' : '❌ UNPAID'}
                </span>
              </td>
              <td style="padding: 16px 12px; text-align: center;">
                <span class="status-badge ${fulfillment.class} fulfillment-chip">
                  ${fulfillment.icon} ${fulfillment.text}
                </span>
              </td>
              <td style="padding: 16px 12px; text-align: center;">
                <span class="status-badge ${refundStatus.class} fulfillment-chip">
                  ${refundStatus.icon} ${refundStatus.text}
                </span>
              </td>
              <td style="padding: 16px 12px; color: #6b7280; font-size: 0.9rem;">
                ${order.createdAt.toLocaleDateString()}
              </td>
              <td style="padding: 16px 12px; text-align: center;">
                ${getActionButton(fulfillmentStatus, order.id, customerName)}
              </td>
            </tr>
          `;
          }).join('')}
        </tbody>
      </table>
    </div>
  `;
  
  container.innerHTML = table;
};

DashboardManager.prototype.updateAffiliates = function() {
  const container = document.getElementById('affiliates-content');
  const affiliates = this.data.affiliates || [];

  if (!container) return;
  if (affiliates.length === 0) {
    container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">🤝</div><p>No affiliates found</p></div>';
    return;
  }

  const table = `
    <div style="overflow-x:auto;">
      <table class="table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Code</th>
            <th>Status</th>
            <th>Joined</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          ${affiliates.map(a => `
            <tr>
              <td>${a.name}</td>
              <td>${a.code || '—'}</td>
              <td><span class="status-badge ${a.status === 'active' ? 'status-completed' : 'status-incomplete'}">${(a.status||'active').toUpperCase()}</span></td>
              <td>${a.createdAt ? a.createdAt.toLocaleDateString() : '—'}</td>
              <td>
                <button class="card-action" onclick="openAffiliateModal('${a.id}')">View</button>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    </div>
  `;

  container.innerHTML = table;
};
