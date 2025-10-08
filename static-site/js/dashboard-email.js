// Dashboard Email Notification Functions

// Email Notification Functions
async function sendFulfillmentEmailNotification(orderData, newStatus) {
  try {
    console.log('📧 Sending fulfillment email notification for status:', newStatus);
    
    // Get customer email
    const customerEmail = orderData.customer?.email || orderData.customer_email || orderData.email;
    if (!customerEmail) {
      console.warn('⚠️ No customer email found for order:', orderData.id || orderData.orderId);
      return;
    }

    // Get customer name
    const customerName = orderData.customer?.firstName || 
                       orderData.customer?.name || 
                       orderData.customer_name || 
                       orderData.customerName || 
                       'Valued Customer';

    // Prepare email data based on status
    const emailData = {
      to: customerEmail,
      subject: getFulfillmentSubject(newStatus),
      message: getFulfillmentMessage(orderData, newStatus, customerName),
      orderId: orderData.id || orderData.orderId,
      customerName: customerName,
      status: newStatus
    };

    // Send email via API
    await sendEmailNotification(emailData);
    
  } catch (error) {
    console.error('❌ Error sending fulfillment email:', error);
    // Don't throw error - email failure shouldn't break the fulfillment update
  }
}

async function sendShippingEmailNotification(orderData, trackingData) {
  try {
    console.log('📧 Sending shipping email notification');
    
    // Get customer email
    const customerEmail = orderData.customer?.email || orderData.customer_email || orderData.email;
    if (!customerEmail) {
      console.warn('⚠️ No customer email found for order:', orderData.id || orderData.orderId);
      return;
    }

    // Get customer name
    const customerName = orderData.customer?.firstName || 
                       orderData.customer?.name || 
                       orderData.customer_name || 
                       orderData.customerName || 
                       'Valued Customer';

    // Prepare shipping email data
    const emailData = {
      to: customerEmail,
      subject: `🚚 Your Order #${trackingData.orderId} Has Been Shipped!`,
      message: getShippingMessage(orderData, trackingData, customerName),
      orderId: trackingData.orderId,
      customerName: customerName,
      trackingId: trackingData.trackingId,
      courierName: trackingData.courierName,
      status: 'shipped'
    };

    // Send email via API
    await sendEmailNotification(emailData);
    
  } catch (error) {
    console.error('❌ Error sending shipping email:', error);
    // Don't throw error - email failure shouldn't break the shipping update
  }
}

async function sendEmailNotification(emailData) {
  try {
    console.log('📧 Sending email notification:', emailData.subject);
    
    const apiBaseUrl = window.ATTRAL_PUBLIC?.API_BASE_URL || '';
    const response = await fetch(`${apiBaseUrl}/api/send_email.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(emailData)
    });

    if (response.ok) {
      console.log('✅ Email notification sent successfully');
    } else {
      console.error('❌ Email API error:', response.status, response.statusText);
    }
    
  } catch (error) {
    console.error('❌ Error sending email notification:', error);
  }
}

function getFulfillmentSubject(status) {
  switch(status) {
    case 'ready-to-dispatch':
      return '📦 Your Order is Ready to Dispatch!';
    case 'shipped':
      return '🚚 Your Order Has Been Shipped!';
    case 'delivered':
      return '✅ Your Order Has Been Delivered!';
    default:
      return '📦 Order Status Update';
  }
}

function getFulfillmentMessage(orderData, status, customerName) {
  const orderId = orderData.id || orderData.orderId;
  const orderAmount = orderData.amount || orderData.pricing?.total || 0;
  
  let statusMessage = '';
  let actionText = '';
  
  switch(status) {
    case 'ready-to-dispatch':
      statusMessage = 'Your order is now ready to dispatch and will be shipped soon!';
      actionText = 'We\'re preparing your package for shipment. You\'ll receive tracking information once it\'s dispatched.';
      break;
    case 'shipped':
      statusMessage = 'Great news! Your order has been shipped and is on its way to you.';
      actionText = 'Your package is now in transit. You can track your order using the tracking information provided.';
      break;
    case 'delivered':
      statusMessage = 'Your order has been successfully delivered!';
      actionText = 'Thank you for choosing ATTRAL. We hope you enjoy your purchase. If you have any questions, please don\'t hesitate to contact us.';
      break;
    default:
      statusMessage = 'Your order status has been updated.';
      actionText = 'Thank you for your patience. We\'ll keep you updated on any further changes.';
  }

  return `
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
      <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center;">
        <h1 style="margin: 0; font-size: 24px; font-weight: 700;">ATTRAL Store</h1>
        <p style="margin: 10px 0 0 0; opacity: 0.9;">Smart Power. Smarter Living.</p>
      </div>
      
      <div style="padding: 30px;">
        <h2 style="color: #333; margin: 0 0 20px 0; font-size: 20px;">Hello ${customerName}!</h2>
        
        <p style="color: #555; line-height: 1.6; margin: 0 0 20px 0;">${statusMessage}</p>
        
        <div style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; margin: 20px 0;">
          <h3 style="color: #333; margin: 0 0 15px 0; font-size: 16px;">Order Details</h3>
          <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
            <span style="color: #666;">Order ID:</span>
            <span style="font-weight: 600; color: #333;">#${orderId}</span>
          </div>
          <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
            <span style="color: #666;">Order Amount:</span>
            <span style="font-weight: 600; color: #333;">₹${orderAmount.toLocaleString()}</span>
          </div>
          <div style="display: flex; justify-content: space-between;">
            <span style="color: #666;">Status:</span>
            <span style="font-weight: 600; color: #28a745; text-transform: capitalize;">${status.replace('-', ' ')}</span>
          </div>
        </div>
        
        <p style="color: #555; line-height: 1.6; margin: 20px 0;">${actionText}</p>
        
        <div style="background: #e3f2fd; border: 1px solid #bbdefb; border-radius: 8px; padding: 20px; margin: 20px 0;">
          <p style="margin: 0; color: #1976d2;">
            <strong>Need Help?</strong><br>
            If you have any questions about your order, please contact our support team at <a href="mailto:info@attral.in" style="color: #1976d2;">info@attral.in</a> or call us at <a href="tel:+918903479870" style="color: #1976d2;">+91 8903479870</a>.
          </p>
        </div>
      </div>
      
      <div style="background: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #e9ecef;">
        <p style="margin: 0; color: #666; font-size: 14px;">
          © ${new Date().getFullYear()} ATTRAL. All rights reserved.<br>
          <a href="https://attral.in" style="color: #667eea;">Visit our website</a> | 
          <a href="mailto:info@attral.in" style="color: #667eea;">Contact Support</a>
        </p>
      </div>
    </div>
  `;
}

function getShippingMessage(orderData, trackingData, customerName) {
  const orderId = trackingData.orderId;
  const orderAmount = orderData.amount || orderData.pricing?.total || 0;
  
  return `
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
      <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center;">
        <h1 style="margin: 0; font-size: 24px; font-weight: 700;">ATTRAL Store</h1>
        <p style="margin: 10px 0 0 0; opacity: 0.9;">Smart Power. Smarter Living.</p>
      </div>
      
      <div style="padding: 30px;">
        <h2 style="color: #333; margin: 0 0 20px 0; font-size: 20px;">Hello ${customerName}!</h2>
        
        <p style="color: #555; line-height: 1.6; margin: 0 0 20px 0;">Great news! Your order has been shipped and is on its way to you. You can now track your package using the details below.</p>
        
        <div style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; margin: 20px 0;">
          <h3 style="color: #333; margin: 0 0 15px 0; font-size: 16px;">Order & Shipping Details</h3>
          <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
            <span style="color: #666;">Order ID:</span>
            <span style="font-weight: 600; color: #333;">#${orderId}</span>
          </div>
          <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
            <span style="color: #666;">Order Amount:</span>
            <span style="font-weight: 600; color: #333;">₹${orderAmount.toLocaleString()}</span>
          </div>
          <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
            <span style="color: #666;">Tracking ID:</span>
            <span style="font-weight: 600; color: #333; font-family: monospace;">${trackingData.trackingId}</span>
          </div>
          <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
            <span style="color: #666;">Courier Service:</span>
            <span style="font-weight: 600; color: #333;">${trackingData.courierName}</span>
          </div>
          <div style="display: flex; justify-content: space-between;">
            <span style="color: #666;">Shipped Date:</span>
            <span style="font-weight: 600; color: #333;">${trackingData.shippedAt.toLocaleDateString()}</span>
          </div>
        </div>
        
        <div style="background: #e8f5e8; border: 1px solid #c8e6c9; border-radius: 8px; padding: 20px; margin: 20px 0;">
          <h3 style="color: #2e7d32; margin: 0 0 15px 0; font-size: 16px;">🚚 Track Your Package</h3>
          <p style="color: #2e7d32; margin: 0 0 15px 0; line-height: 1.6;">
            You can track your package using the tracking ID above on the ${trackingData.courierName} website or by contacting their customer service.
          </p>
          <p style="color: #2e7d32; margin: 0; font-size: 14px;">
            <strong>Expected Delivery:</strong> 2-5 business days (depending on location)
          </p>
        </div>
        
        ${trackingData.shippingNotes ? `
          <div style="background: #fff3e0; border: 1px solid #ffcc02; border-radius: 8px; padding: 20px; margin: 20px 0;">
            <h3 style="color: #f57c00; margin: 0 0 15px 0; font-size: 16px;">📝 Shipping Notes</h3>
            <p style="color: #f57c00; margin: 0; line-height: 1.6;">${trackingData.shippingNotes}</p>
          </div>
        ` : ''}
        
        <div style="background: #e3f2fd; border: 1px solid #bbdefb; border-radius: 8px; padding: 20px; margin: 20px 0;">
          <p style="margin: 0; color: #1976d2;">
            <strong>Need Help?</strong><br>
            If you have any questions about your shipment, please contact our support team at <a href="mailto:info@attral.in" style="color: #1976d2;">info@attral.in</a> or call us at <a href="tel:+918903479870" style="color: #1976d2;">+91 8903479870</a>.
          </p>
        </div>
      </div>
      
      <div style="background: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #e9ecef;">
        <p style="margin: 0; color: #666; font-size: 14px;">
          © ${new Date().getFullYear()} ATTRAL. All rights reserved.<br>
          <a href="https://attral.in" style="color: #667eea;">Visit our website</a> | 
          <a href="mailto:info@attral.in" style="color: #667eea;">Contact Support</a>
        </p>
      </div>
    </div>
  `;
}
