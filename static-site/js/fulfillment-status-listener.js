/**
 * Fulfillment Status Listener
 * Monitors Firestore for fulfillment status changes and triggers email notifications
 */

class FulfillmentStatusListener {
    constructor() {
        this.db = null;
        this.isListening = false;
        this.lastProcessedStatus = new Map(); // Track last known status for each order
        this.webhookUrl = window.ATTRAL_PUBLIC?.API_BASE_URL + '/api/fulfillment_status_webhook.php' || 
                         'http://localhost:8000/api/fulfillment_status_webhook.php';
        
        this.init();
    }
    
    init() {
        // Wait for Firebase to be ready
        if (window.AttralFirebase && window.AttralFirebase.db) {
            this.db = window.AttralFirebase.db;
            this.startListening();
        } else {
            // Wait for Firebase to initialize
            window.addEventListener('attral-firebase-ready', () => {
                if (window.AttralFirebase && window.AttralFirebase.db) {
                    this.db = window.AttralFirebase.db;
                    this.startListening();
                }
            });
        }
    }
    
    startListening() {
        if (this.isListening || !this.db) {
            return;
        }
        
        console.log('🚚 Starting fulfillment status listener...');
        this.isListening = true;
        
        // Listen to orders collection for fulfillment status changes
        this.db.collection('orders')
            .onSnapshot((snapshot) => {
                snapshot.docChanges().forEach((change) => {
                    if (change.type === 'modified') {
                        this.handleOrderUpdate(change.doc);
                    }
                });
            }, (error) => {
                console.error('❌ Fulfillment listener error:', error);
                this.isListening = false;
                
                // Retry after 5 seconds
                setTimeout(() => {
                    console.log('🔄 Retrying fulfillment listener...');
                    this.startListening();
                }, 5000);
            });
    }
    
    handleOrderUpdate(doc) {
        const orderData = doc.data();
        const orderId = doc.id;
        const currentStatus = orderData.fulfillmentStatus;
        
        // Skip if no fulfillment status
        if (!currentStatus) {
            return;
        }
        
        // Check if status has changed
        const lastStatus = this.lastProcessedStatus.get(orderId);
        if (lastStatus === currentStatus) {
            return; // No change
        }
        
        // Skip initial load (when lastStatus is undefined)
        if (lastStatus === undefined) {
            this.lastProcessedStatus.set(orderId, currentStatus);
            return;
        }
        
        console.log(`📦 Order ${orderId} fulfillment status changed: ${lastStatus} → ${currentStatus}`);
        
        // Extract customer information
        const customerEmail = this.extractCustomerEmail(orderData);
        if (!customerEmail) {
            console.warn(`⚠️ No customer email found for order ${orderId}`);
            return;
        }
        
        // Prepare webhook data
        const webhookData = {
            orderId: orderId,
            customerEmail: customerEmail,
            customerName: this.extractCustomerName(orderData),
            fulfillmentStatus: currentStatus,
            productTitle: this.extractProductTitle(orderData),
            trackingNumber: orderData.trackingNumber || '',
            estimatedDelivery: orderData.estimatedDelivery || ''
        };
        
        // Call webhook
        this.callWebhook(webhookData);
        
        // Update last processed status
        this.lastProcessedStatus.set(orderId, currentStatus);
    }
    
    extractCustomerEmail(orderData) {
        // Try different possible locations for customer email
        if (orderData.email) return orderData.email;
        if (orderData.customer && orderData.customer.email) return orderData.customer.email;
        if (orderData.user && orderData.user.email) return orderData.user.email;
        if (orderData.shipping && orderData.shipping.email) return orderData.shipping.email;
        
        // Check notes field (as seen in your Firestore screenshot)
        if (orderData.notes && orderData.notes.email) return orderData.notes.email;
        
        return null;
    }
    
    extractCustomerName(orderData) {
        // Try different possible locations for customer name
        if (orderData.customerName) return orderData.customerName;
        if (orderData.customer && orderData.customer.firstName) {
            const firstName = orderData.customer.firstName || '';
            const lastName = orderData.customer.lastName || '';
            return `${firstName} ${lastName}`.trim();
        }
        if (orderData.user && orderData.user.displayName) return orderData.user.displayName;
        if (orderData.shipping && orderData.shipping.name) return orderData.shipping.name;
        
        // Check notes field
        if (orderData.notes && orderData.notes.firstName) {
            const firstName = orderData.notes.firstName || '';
            const lastName = orderData.notes.lastName || '';
            return `${firstName} ${lastName}`.trim();
        }
        
        return 'Customer';
    }
    
    extractProductTitle(orderData) {
        // Try different possible locations for product title
        if (orderData.productTitle) return orderData.productTitle;
        if (orderData.product && orderData.product.title) return orderData.product.title;
        if (orderData.items && orderData.items.length > 0) {
            return orderData.items[0].name || orderData.items[0].title || 'Product';
        }
        
        return 'Your Product';
    }
    
    async callWebhook(webhookData) {
        try {
            console.log('📧 Calling fulfillment webhook:', webhookData);
            
            const response = await fetch(this.webhookUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(webhookData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                console.log('✅ Fulfillment email sent successfully:', result);
            } else {
                console.error('❌ Fulfillment email failed:', result.error);
            }
            
        } catch (error) {
            console.error('❌ Webhook call failed:', error);
        }
    }
    
    stopListening() {
        if (this.isListening) {
            console.log('🛑 Stopping fulfillment status listener...');
            this.isListening = false;
        }
    }
    
    // Manual trigger for testing
    async testWebhook(orderId, fulfillmentStatus, customerEmail = 'attralsolar@gmail.com') {
        const testData = {
            orderId: orderId,
            customerEmail: customerEmail,
            customerName: 'Test User',
            fulfillmentStatus: fulfillmentStatus,
            productTitle: 'Test Product'
        };
        
        console.log('🧪 Testing webhook with data:', testData);
        await this.callWebhook(testData);
    }
}

// Initialize the listener when the script loads
window.FulfillmentStatusListener = new FulfillmentStatusListener();

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FulfillmentStatusListener;
}
