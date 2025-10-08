// Dashboard Manager - Core functionality for dashboard operations

// Helper functions for order status
function hasRazorpayPayment(order) {
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
}

function getActualOrderStatus(order) {
  const baseStatus = order.status || 'pending';
  const hasPayment = hasRazorpayPayment(order);
  
  // If no payment acknowledgment, status is incomplete regardless of order status
  if (!hasPayment) {
    return 'incomplete';
  }
  
  // If payment exists, use the order status
  return baseStatus;
}

// Dashboard Data Management with Firestore Integration
class DashboardManager {
  constructor() {
    this.data = {
      orders: [],
      messages: [],
      products: [],
      affiliates: [],
      fulfillmentOrders: []
    };
    this.isInitialized = false;
    this.retryCount = 0;
    this.maxRetries = 3;
    this.init();
  }

  async init() {
    console.log('🚀 Initializing Dashboard with Firestore...');
    try {
      await this.waitForFirebase();
      await this.loadAllData();
      this.startAutoRefresh();
      this.setupRealtimeListeners();
      this.isInitialized = true;
      console.log('✅ Dashboard initialized successfully');
    } catch (error) {
      console.error('❌ Dashboard initialization failed:', error);
      await this.handleInitializationError(error);
    }
  }

  async handleInitializationError(error) {
    this.retryCount++;
    console.log(`🔄 Retry attempt ${this.retryCount}/${this.maxRetries}`);
    
    if (this.retryCount < this.maxRetries) {
      // Wait 2 seconds before retry
      await new Promise(resolve => setTimeout(resolve, 2000));
      await this.init();
    } else {
      console.error('❌ Max retries reached. Loading demo data...');
      this.loadDemoData();
      this.showErrorNotification('Failed to connect to Firebase. Showing demo data.');
    }
  }

  loadDemoData() {
    console.log('📊 Loading demo data...');
    this.data.orders = [
      {
        id: 'demo-1',
        orderId: 'ORD-2024-001',
        customerName: 'John Doe',
        customerEmail: 'john@example.com',
        totalAmount: 2999,
        status: 'completed',
        paymentStatus: 'paid',
        fulfillmentStatus: 'delivered',
        createdAt: new Date(),
        items: [{ name: 'Demo Product', quantity: 1, price: 2999 }]
      }
    ];
    this.data.messages = [
      {
        id: 'msg-1',
        name: 'Jane Smith',
        email: 'jane@example.com',
        message: 'Demo message',
        status: 'new',
        timestamp: new Date()
      }
    ];
    this.data.products = [
      {
        id: 'prod-1',
        name: 'Demo Product',
        price: 2999,
        status: 'active',
        createdAt: new Date()
      }
    ];
    this.data.affiliates = [];
    this.updateUI();
  }

  showErrorNotification(message) {
    // Create error notification
    const notification = document.createElement('div');
    notification.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      background: #ef4444;
      color: white;
      padding: 1rem 1.5rem;
      border-radius: 0.5rem;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      z-index: 10000;
      max-width: 400px;
    `;
    notification.innerHTML = `
      <div style="display: flex; align-items: center; gap: 0.5rem;">
        <span>⚠️</span>
        <span>${message}</span>
      </div>
    `;
    document.body.appendChild(notification);
    
    // Remove after 5 seconds
    setTimeout(() => {
      notification.remove();
    }, 5000);
  }

  async waitForFirebase() {
    let attempts = 0;
    const maxAttempts = 100; // Increased attempts
    
    while (attempts < maxAttempts) {
      if (window.AttralFirebase && window.AttralFirebase.db && window.AttralFirebase.auth) {
        console.log('✅ Firebase is ready');
        
        // Test Firestore connection
        try {
          await window.AttralFirebase.db.collection('orders').limit(1).get();
          console.log('✅ Firestore connection test successful');
          return;
        } catch (error) {
          console.warn('⚠️ Firestore connection test failed:', error.message);
          if (attempts > 50) { // After 50 attempts, still try to proceed
            console.log('🔄 Proceeding despite connection test failure...');
            return;
          }
        }
      }
      attempts++;
      console.log(`⏳ Waiting for Firebase... attempt ${attempts}/${maxAttempts}`);
      await new Promise(resolve => setTimeout(resolve, 200)); // Increased delay
    }
    
    throw new Error('Firebase failed to load after maximum attempts');
  }

  async loadAllData() {
    try {
      console.log('📊 Loading all dashboard data...');
      
      // Load data with individual error handling
      const loadPromises = [
        this.loadOrders().catch(err => {
          console.error('❌ Failed to load orders:', err);
          this.data.orders = [];
        }),
        this.loadMessages().catch(err => {
          console.error('❌ Failed to load messages:', err);
          this.data.messages = [];
        }),
        this.loadProducts().catch(err => {
          console.error('❌ Failed to load products:', err);
          this.data.products = [];
        }),
        this.loadFulfillment().catch(err => {
          console.error('❌ Failed to load fulfillment:', err);
          this.data.fulfillmentOrders = [];
        }),
        this.loadAffiliates().catch(err => {
          console.error('❌ Failed to load affiliates:', err);
          this.data.affiliates = [];
        })
      ];

      await Promise.allSettled(loadPromises);
      
      // Check if we have any data loaded
      const hasData = this.data.orders.length > 0 || 
                     this.data.messages.length > 0 || 
                     this.data.products.length > 0 ||
                     this.data.affiliates.length > 0;
      
      if (hasData) {
        console.log('✅ Dashboard data loaded successfully');
        this.updateUI();
      } else {
        console.warn('⚠️ No data loaded, showing empty state');
        this.updateUI();
      }
      
    } catch (error) {
      console.error('❌ Critical error loading dashboard data:', error);
      this.showErrorNotification('Failed to load dashboard data. Please refresh the page.');
    }
  }

  async loadOrders() {
    try {
      console.log('📦 Loading orders from Firestore...');
      
      if (!window.AttralFirebase || !window.AttralFirebase.db) {
        throw new Error('Firebase not initialized');
      }

      // Fetch all orders with better error handling
      const ordersSnapshot = await window.AttralFirebase.db
        .collection('orders')
        .orderBy('createdAt', 'desc')
        .limit(500)
        .get();

      const allOrders = [];
      ordersSnapshot.forEach(doc => {
        const orderData = doc.data();
        allOrders.push({
          id: doc.id,
          ...orderData,
          createdAt: orderData.createdAt ? orderData.createdAt.toDate() : new Date()
        });
      });

      this.data.orders = allOrders;
      
      // Helper to get total amount from varying schemas
      const getOrderTotal = (order) => {
        if (typeof order.totalAmount === 'number') return order.totalAmount;
        if (typeof order.amount === 'number') return order.amount;
        if (order.pricing && typeof order.pricing.total === 'number') return order.pricing.total;
        return 0;
      };
      
      // Calculate stats
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      const tomorrow = new Date(today);
      tomorrow.setDate(tomorrow.getDate() + 1);
      
      const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
      const monthEnd = new Date(today.getFullYear(), today.getMonth() + 1, 1);
      
      const todayOrders = allOrders.filter(order => 
        order.createdAt >= today && order.createdAt < tomorrow
      );
      
      const monthOrders = allOrders.filter(order => 
        order.createdAt >= monthStart && order.createdAt < monthEnd
      );
      
      const pendingToday = todayOrders.filter(order => {
        const actualStatus = getActualOrderStatus(order);
        return (actualStatus === 'pending' || actualStatus === 'processing');
      }).length;
      
      const shippedThisMonth = monthOrders.filter(order => {
        const actualStatus = getActualOrderStatus(order);
        return (actualStatus === 'shipped' || actualStatus === 'delivered');
      }).length;
      
      const revenueToday = todayOrders.reduce((sum, order) => {
        const actualStatus = getActualOrderStatus(order);
        // Only count revenue for orders with payment acknowledgment
        return sum + (actualStatus !== 'incomplete' ? (parseFloat(getOrderTotal(order)) || 0) : 0);
      }, 0);
      
      const revenueThisMonth = monthOrders.reduce((sum, order) => {
        const actualStatus = getActualOrderStatus(order);
        // Only count revenue for orders with payment acknowledgment
        return sum + (actualStatus !== 'incomplete' ? (parseFloat(getOrderTotal(order)) || 0) : 0);
      }, 0);

      // Update stats
      document.getElementById('pending-orders-today').textContent = pendingToday;
      document.getElementById('shipped-orders-month').textContent = shippedThisMonth;
      document.getElementById('revenue-today').textContent = `₹${revenueToday.toLocaleString()}`;
      document.getElementById('revenue-month').textContent = `₹${revenueThisMonth.toLocaleString()}`;

      console.log('✅ Orders loaded:', allOrders.length);
      console.log('📊 Stats - Pending today:', pendingToday, 'Shipped this month:', shippedThisMonth);
      console.log('💰 Revenue - Today:', revenueToday, 'This month:', revenueThisMonth);

    } catch (error) {
      console.error('❌ Error loading orders:', error);
      // Set default values
      document.getElementById('pending-orders-today').textContent = '0';
      document.getElementById('shipped-orders-month').textContent = '0';
      document.getElementById('revenue-today').textContent = '₹0';
      document.getElementById('revenue-month').textContent = '₹0';
    }
  }

  async loadMessages() {
    try {
      console.log('💬 Loading messages from Firestore...');
      
      // Use contact_messages which our server writes for admin access
      const messagesSnapshot = await window.AttralFirebase.db
        .collection('contact_messages')
        .orderBy('createdAt', 'desc')
        .limit(5)
        .get();

      this.data.messages = [];
      messagesSnapshot.forEach(doc => {
        const messageData = doc.data();
        this.data.messages.push({
          id: doc.id,
          ...messageData,
          createdAt: messageData.createdAt?.toDate ? messageData.createdAt.toDate() : new Date()
        });
      });

      console.log('✅ Messages loaded:', this.data.messages.length);
    } catch (error) {
      console.error('❌ Error loading messages:', error);
      this.data.messages = [];
    }
  }

  async loadProducts() {
    try {
      console.log('🛍️ Loading products from Firestore...');
      
      const productsSnapshot = await window.AttralFirebase.db
        .collection('products')
        .orderBy('createdAt', 'desc')
        .limit(5)
        .get();

      this.data.products = [];
      productsSnapshot.forEach(doc => {
        const productData = doc.data();
        this.data.products.push({
          id: doc.id,
          title: productData.title || productData.name || 'Unnamed Product',
          price: productData.price || 0,
          image: productData.image || '../assets/product_images/default.jpg',
          category: productData.category || 'Electronics',
          description: productData.description || '',
          featured: productData.featured || false,
          createdAt: productData.createdAt ? productData.createdAt.toDate() : new Date()
        });
      });

      console.log('✅ Products loaded from Firestore:', this.data.products.length);
      console.log('📊 Product data:', this.data.products);
      
      // If no products from Firestore, try JSON fallback
      if (this.data.products.length === 0) {
        console.log('📄 No products in Firestore, trying JSON fallback...');
        await this.loadProductsFromJSON();
      }
    } catch (error) {
      console.error('❌ Error loading products from Firestore:', error);
      console.log('📄 Falling back to JSON...');
      await this.loadProductsFromJSON();
    }
  }

  async loadProductsFromJSON() {
    try {
      console.log('📄 Loading products from JSON fallback...');
      const response = await fetch('data/products.json');
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      const products = await response.json();
      
      this.data.products = products.map(p => ({
        id: p.id,
        title: p.title,
        price: p.price,
        image: p.image,
        category: p.category,
        description: p.description,
        featured: p.featured,
        createdAt: new Date()
      }));
      
      console.log('✅ Products loaded from JSON:', this.data.products.length);
      console.log('📊 JSON Product data:', this.data.products);
    } catch (error) {
      console.error('❌ Error loading products from JSON:', error);
      this.data.products = [];
    }
  }

  async loadFulfillment() {
    try {
      console.log('📦 Loading fulfillment orders from Firestore...');
      
      // Fetch recent orders and filter client-side for those needing fulfillment
      const ordersSnapshot = await window.AttralFirebase.db
        .collection('orders')
        .orderBy('createdAt', 'desc')
        .limit(200)
        .get();

      const all = [];
      ordersSnapshot.forEach(doc => {
        const d = doc.data();
        all.push({
          id: doc.id,
          ...d,
          createdAt: d.createdAt ? d.createdAt.toDate() : new Date()
        });
      });

      const isPaid = (o) => hasRazorpayPayment(o);
      const needsFulfillment = (o) => {
        const actualStatus = getActualOrderStatus(o);
        const fulfillmentStatus = o.fulfillmentStatus || 'yet-to-dispatch';
        
        // Only include paid orders (exclude incomplete/unpaid orders)
        if (!isPaid(o)) {
          return false;
        }
        
        // Include orders that are paid and have valid fulfillment status
        return ['yet-to-dispatch', 'ready-to-dispatch', 'shipped', 'delivered', 'cancelled'].includes(fulfillmentStatus);
      };

      this.data.fulfillmentOrders = all.filter(o => needsFulfillment(o));

      // Debug information
      console.log('📊 Fulfillment Debug Info:');
      console.log('- Total orders fetched:', all.length);
      console.log('- Paid orders:', all.filter(o => isPaid(o)).length);
      console.log('- Orders with fulfillment status:', all.filter(o => o.fulfillmentStatus).length);
      console.log('- Fulfillment orders after filtering:', this.data.fulfillmentOrders.length);
      
      // Log details of each order for debugging
      all.forEach(order => {
        const paid = isPaid(order);
        const fulfillmentStatus = order.fulfillmentStatus || 'yet-to-dispatch';
        const actualStatus = getActualOrderStatus(order);
        console.log(`Order ${order.id}: paid=${paid}, fulfillmentStatus=${fulfillmentStatus}, actualStatus=${actualStatus}`);
      });

      console.log('✅ Fulfillment orders loaded:', this.data.fulfillmentOrders.length);
    } catch (error) {
      console.error('❌ Error loading fulfillment orders:', error);
      this.data.fulfillmentOrders = [];
    }
  }

  async loadAffiliates() {
    try {
      console.log('🤝 Loading affiliates from Firestore...');
      const db = window.AttralFirebase.db;
      // Load up to 200 recent affiliates
      const snap = await db.collection('affiliates')
        .orderBy('createdAt', 'desc')
        .limit(200)
        .get();

      const affiliates = [];
      snap.forEach(doc => {
        const data = doc.data();
        affiliates.push({
          id: doc.id,
          uid: data.uid || doc.id,
          name: data.displayName || data.name || data.email || 'Unknown',
          email: data.email || null,
          code: data.code || null,
          link: data.affiliateLink || null,
          status: data.status || 'active',
          totalEarnings: Number(data.totalEarnings || 0),
          totalReferrals: Number(data.totalReferrals || 0),
          createdAt: data.createdAt?.toDate ? data.createdAt.toDate() : null
        });
      });

      this.data.affiliates = affiliates;
      document.getElementById('total-affiliates').textContent = affiliates.length.toString();
      console.log('✅ Affiliates loaded:', affiliates.length);
    } catch (error) {
      console.error('❌ Error loading affiliates:', error);
      this.data.affiliates = [];
      document.getElementById('total-affiliates').textContent = '0';
    }
  }

  updateUI() {
    this.updateOrders();
    this.updateProducts();
    this.updateMessages();
    this.updateFulfillment();
    this.updateAffiliates();
  }

  startAutoRefresh() {
    // Refresh data every 30 seconds
    setInterval(() => {
      if (this.isInitialized) {
        console.log('🔄 Auto-refreshing dashboard data...');
        this.loadAllData();
      }
    }, 30000);
  }

  setupRealtimeListeners() {
    try {
      if (!window.AttralFirebase || !window.AttralFirebase.db) {
        console.warn('⚠️ Firebase not available for real-time listeners');
        return;
      }

      console.log('🔄 Setting up real-time listeners...');

      // Listen for new orders
      window.AttralFirebase.db.collection('orders')
        .orderBy('createdAt', 'desc')
        .limit(10)
        .onSnapshot((snapshot) => {
          console.log('📦 Real-time update: orders changed');
          this.loadOrders().then(() => this.updateUI());
        }, (error) => {
          console.error('❌ Orders listener error:', error);
        });

      // Listen for new messages
      window.AttralFirebase.db.collection('contact_messages')
        .orderBy('createdAt', 'desc')
        .limit(5)
        .onSnapshot((snapshot) => {
          console.log('💬 Real-time update: messages changed');
          this.loadMessages().then(() => this.updateUI());
        }, (error) => {
          console.error('❌ Messages listener error:', error);
        });

      // Listen for new affiliates
      window.AttralFirebase.db.collection('affiliates')
        .orderBy('createdAt', 'desc')
        .limit(10)
        .onSnapshot((snapshot) => {
          console.log('🤝 Real-time update: affiliates changed');
          this.loadAffiliates().then(() => this.updateUI());
        }, (error) => {
          console.error('❌ Affiliates listener error:', error);
        });

      console.log('✅ Real-time listeners set up successfully');
    } catch (error) {
      console.error('❌ Failed to set up real-time listeners:', error);
    }
  }

  showError(message) {
    console.error('❌ Dashboard Error:', message);
  }
}
