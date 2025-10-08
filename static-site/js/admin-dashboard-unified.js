/**
 * 🎛️ ATTRAL Admin Dashboard - Unified Modern Interface
 * Real-time Firestore integration with modern UI/UX
 */

class AdminDashboardUnified {
    constructor() {
this.currentSection = 'dashboard';
this.adminData = {
    orders: [],
    users: [],
    messages: [],
    coupons: [],
    products: [],
    affiliates: [],
    analytics: {}
};
this.charts = {};
this.init();
    }

    async init() {
console.log('🚀 Initializing Unified Admin Dashboard...');

try {
            // Wait for Firebase with enhanced error handling
    await this.waitForFirebase();
    
    // Setup navigation
    this.setupNavigation();
    
    // Setup authentication modal
    this.setupAuthModal();
    
    // Check authentication status
    if (this.isAuthenticated()) {
        // Load real data if authenticated
        await this.loadDashboardData();
        this.setupRealtimeListeners();
                this.showNotification('Admin dashboard loaded successfully', 'success');
    } else {
        // Show authentication modal
        this.showAuthModal();
        this.showNotification('Please sign in to access admin features', 'info');
    }
    
    // Update UI
    this.updateLastUpdated();
    setInterval(() => this.updateLastUpdated(), 60000); // Update every minute
    
    console.log('✅ Unified Admin Dashboard initialized');
} catch (error) {
    console.error('❌ Failed to initialize admin dashboard:', error);
            
            // Enhanced error handling with specific error types
            await this.handleInitializationError(error);
        }
    }

    async handleInitializationError(error) {
        console.log('🔄 Handling initialization error:', error.message);
        
        // Determine error type and show appropriate message
        let errorMessage = 'Dashboard initialization failed';
        let errorType = 'error';
        
        if (error.message.includes('Firebase')) {
            errorMessage = 'Firebase connection failed. Loading demo data.';
            errorType = 'warning';
        } else if (error.message.includes('network') || error.message.includes('fetch')) {
            errorMessage = 'Network connection issue. Please check your internet connection.';
            errorType = 'warning';
        } else if (error.message.includes('permission')) {
            errorMessage = 'Permission denied. Please check your admin access.';
            errorType = 'error';
        }
    
    // Load demo data as fallback
    console.log('🔄 Loading demo data as fallback...');
    this.loadDemoData();
    this.calculateAnalytics();
    this.updateDashboard();
    
    // Setup navigation and auth modal even with demo data
    this.setupNavigation();
    this.setupAuthModal();
    
        this.showNotification(errorMessage, errorType);
        
        // Store error for debugging
        this.lastError = {
            message: error.message,
            timestamp: new Date(),
            type: 'initialization'
        };
    }

    isAuthenticated() {
        // Standardized authentication check - prioritize Firebase Auth
if (window.AttralFirebase && window.AttralFirebase.auth && window.AttralFirebase.auth.currentUser) {
            const currentUser = window.AttralFirebase.auth.currentUser;
            
            // Check if current user is admin
            if (currentUser && this.isAdminUser(currentUser)) {
    return true;
            }
}

        // Fallback to local storage for development/admin bypass
const adminAuth = localStorage.getItem('adminAuthenticated');
const adminUser = localStorage.getItem('adminUser');

if (adminAuth === 'true' && adminUser) {
    try {
        const userData = JSON.parse(adminUser);
        // Check if it's the correct admin email
                return userData.email === 'attralsolar@gmail.com' || userData.email === 'admin@attral.in';
    } catch (error) {
        console.error('❌ Error parsing admin user data:', error);
        return false;
    }
}

return false;
    }

    isAdminUser(user) {
        // Standardized admin user check
        const adminEmails = ['attralsolar@gmail.com', 'admin@attral.in'];
        const adminUsernames = ['attral', 'admin'];
        
        return (
            adminEmails.includes(user.email) ||
            adminUsernames.includes(user.displayName) ||
            (user.customClaims && user.customClaims.admin === true)
        );
    }

    async waitForFirebase() {
let attempts = 0;
const maxAttempts = 100; // Increased attempts
const delay = 200; // Increased delay between attempts

while (attempts < maxAttempts) {
    if (window.AttralFirebase && window.AttralFirebase.db) {
        console.log('✅ Firebase ready for unified admin dashboard');
        return;
    }
    attempts++;
    console.log(`⏳ Waiting for Firebase... attempt ${attempts}/${maxAttempts}`);
    
    // Show progress every 10 attempts
    if (attempts % 10 === 0) {
        this.showNotification(`Loading Firebase... (${attempts}/${maxAttempts})`, 'info');
    }
    
    await new Promise(resolve => setTimeout(resolve, delay));
}

console.error('❌ Firebase failed to load after maximum attempts');
this.showNotification('Firebase connection failed. Loading demo data...', 'warning');
throw new Error('Firebase failed to load');
    }

    setupNavigation() {
// Enhanced navigation click handlers
document.querySelectorAll('.admin-nav-link').forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        const section = link.getAttribute('data-section');
        this.navigateToSection(section, link);
    });
});

// Keyboard navigation support
document.addEventListener('keydown', (e) => {
    if (e.ctrlKey || e.metaKey) {
        switch(e.key) {
            case '1':
                e.preventDefault();
                this.showSection('dashboard');
                break;
            case '2':
                e.preventDefault();
                this.showSection('orders');
                break;
            case '3':
                e.preventDefault();
                this.showSection('fulfillment');
                break;
            case '4':
                e.preventDefault();
                this.showSection('messages');
                break;
            case '5':
                e.preventDefault();
                this.showSection('coupons');
                break;
        }
    }
});

// Mobile menu toggle
window.toggleSidebar = () => {
    const sidebar = document.getElementById('adminSidebar');
    const main = document.getElementById('adminMain');
    sidebar.classList.toggle('collapsed');
    main.classList.toggle('expanded');
};
    }

    // Enhanced navigation methods
    navigateToSection(sectionName, linkElement) {
// Remove active class from all navigation links
document.querySelectorAll('.admin-nav-link').forEach(link => {
    link.classList.remove('active');
});

// Add active class to clicked link
if (linkElement) {
    linkElement.classList.add('active');
}

// Show the section
this.showSection(sectionName);

// Update URL hash
window.location.hash = sectionName;

// Track navigation
this.trackNavigation(sectionName);
    }

    trackNavigation(sectionName) {
// Track navigation for analytics
console.log(`🧭 Navigation: ${sectionName}`);

// You can add analytics tracking here
if (window.gtag) {
    window.gtag('event', 'navigation', {
        'section': sectionName,
        'admin_dashboard': true
    });
}
    }

    trackExternalNavigation(pageName) {
// Track external navigation
console.log(`🔗 External Navigation: ${pageName}`);

this.showNotification(`Opening ${pageName} in new tab...`, 'info');

// Analytics tracking for external links
if (window.gtag) {
    window.gtag('event', 'external_link_click', {
        'page': pageName,
        'admin_dashboard': true
    });
}
    }

    async loadDashboardData() {
try {
            console.log('📊 Loading dashboard data with pagination...');
    
            // Load data in parallel with pagination for better performance
    await Promise.all([
                this.loadOrders(50), // Load first 50 orders
                this.loadUsers(50),  // Load first 50 users
                this.loadMessages(20), // Load first 20 messages
                this.loadCoupons(100), // Load all coupons (usually small dataset)
                this.loadProducts(100), // Load all products
                this.loadAffiliates(50) // Load first 50 affiliates
    ]);
    
    this.calculateAnalytics();
    this.updateDashboard();
    
            console.log('✅ Dashboard data loaded successfully with pagination');
    
} catch (error) {
    console.error('❌ Error loading dashboard data:', error);
    this.showNotification('Failed to load dashboard data. Using demo data.', 'warning');
    
    // Load demo data if Firebase fails
    this.loadDemoData();
    this.calculateAnalytics();
    this.updateDashboard();
}
    }

    loadDemoData() {
console.log('📊 Loading demo data...');
this.adminData.orders = [
    {
        id: 'demo-1',
        orderId: 'ORD-001',
        customerName: 'John Doe',
        customerEmail: 'john@example.com',
        totalAmount: 2500,
        status: 'pending',
        paymentStatus: 'paid',
        createdAt: new Date(),
        items: [],
        shippingAddress: {}
    }
];

this.adminData.users = [
    {
        id: 'demo-user-1',
        uid: 'demo-user-1',
        name: 'Demo User',
        email: 'demo@example.com',
        phone: '+1234567890',
        isAffiliate: false,
        createdAt: new Date(),
        lastLoginAt: new Date()
    }
];

this.adminData.messages = [
    {
        id: 'demo-msg-1',
        name: 'Jane Smith',
        email: 'jane@example.com',
        message: 'Hello, I have a question about your products.',
        status: 'new',
        priority: 'normal',
        isAuthenticated: false,
        createdAt: new Date(),
        updatedAt: null
    }
];

this.adminData.coupons = [
    {
        id: 'demo-coupon-1',
        code: 'WELCOME10',
        name: 'Welcome Discount',
        type: 'percentage',
        value: 10,
        isActive: true,
        usageCount: 5,
        usageLimit: 100,
        validUntil: new Date('2025-12-31'),
        createdAt: new Date()
    }
];

this.adminData.products = [
    {
        id: 'demo-product-1',
        name: 'Demo Product',
        price: 999,
        category: 'Electronics',
        status: 'active',
        stock: 10,
        description: 'A demo product',
        images: [],
        createdAt: new Date()
    }
];

this.adminData.affiliates = [
    {
        id: 'demo-affiliate-1',
        code: 'DEMO123',
        name: 'Demo Affiliate',
        email: 'affiliate@example.com',
        status: 'active',
        createdAt: new Date()
    }
];

console.log('✅ Demo data loaded');
    }

    async loadOrders(limit = 50) {
try {
            console.log('📦 Loading orders from Firestore...', limit ? `(limit: ${limit})` : '');
    
    if (!window.AttralFirebase || !window.AttralFirebase.db) {
        throw new Error('Firebase not initialized');
    }
    
            // Build query with optional limit
            let query = window.AttralFirebase.db
        .collection('orders')
                .orderBy('createdAt', 'desc');
            
            if (limit) {
                query = query.limit(limit);
            }
            
            const ordersSnapshot = await query.get();

    this.adminData.orders = [];
    ordersSnapshot.forEach(doc => {
        const orderData = doc.data();
        this.adminData.orders.push({
            id: doc.id,
            orderId: orderData.order_id || orderData.orderId || doc.id,
            customerName: orderData.customer_name || orderData.customerName || orderData.name || 'Unknown',
            customerEmail: orderData.customer_email || orderData.customerEmail || orderData.email || '',
            totalAmount: Number(orderData.total_amount || orderData.totalAmount || orderData.amount || 0),
            status: orderData.status || 'pending',
            paymentStatus: orderData.payment_status || orderData.paymentStatus || 'pending',
                    fulfillmentStatus: orderData.fulfillmentStatus || 'yet-to-dispatch',
            createdAt: orderData.created_at ? orderData.created_at.toDate() : (orderData.createdAt ? orderData.createdAt.toDate() : new Date()),
            items: orderData.items || [],
                    shippingAddress: orderData.shipping_address || orderData.shippingAddress || {},
                    billingAddress: orderData.billing_address || orderData.billingAddress || {},
                    paymentId: orderData.payment_id || orderData.paymentId || orderData.razorpay_payment_id || orderData.razorpayPaymentId || null
        });
    });
    
    console.log(`✅ Loaded ${this.adminData.orders.length} orders from Firestore`);
            console.log('📊 Orders data sample:', this.adminData.orders.slice(0, 3));
} catch (error) {
    console.error('❌ Error loading orders:', error);
    this.adminData.orders = [];
            
            // Don't throw error for pagination calls, just log it
            if (!limit || limit > 100) {
                throw error; // Re-throw to trigger demo data fallback only for main load
            }
}
    }

    async loadUsers() {
try {
    console.log('👥 Loading users from Firestore...');
    
    if (!window.AttralFirebase || !window.AttralFirebase.db) {
        throw new Error('Firebase not initialized');
    }
    
    const usersSnapshot = await window.AttralFirebase.db
        .collection('users')
        .orderBy('createdAt', 'desc')
        .limit(100)
        .get();

    this.adminData.users = [];
    usersSnapshot.forEach(doc => {
        const userData = doc.data();
        this.adminData.users.push({
            id: doc.id,
            uid: userData.uid || doc.id,
            name: userData.displayName || userData.name || 'Unknown',
            email: userData.email || '',
            phone: userData.phone || '',
            isAffiliate: !!userData.is_affiliate,
            createdAt: userData.created_at ? userData.created_at.toDate() : (userData.createdAt ? userData.createdAt.toDate() : new Date()),
            lastLoginAt: userData.lastLoginAt ? userData.lastLoginAt.toDate() : null
        });
    });
    
    console.log(`✅ Loaded ${this.adminData.users.length} users from Firestore`);
    console.log('📊 Users data:', this.adminData.users);
} catch (error) {
    console.error('❌ Error loading users:', error);
    this.adminData.users = [];
    throw error; // Re-throw to trigger demo data fallback
}
    }

    async loadMessages() {
try {
    console.log('💬 Loading messages from Firestore...');
    
    if (!window.AttralFirebase || !window.AttralFirebase.db) {
        throw new Error('Firebase not initialized');
    }
    
    const messagesSnapshot = await window.AttralFirebase.db
        .collection('contact_messages')
        .orderBy('createdAt', 'desc')
        .limit(100)
        .get();

    this.adminData.messages = [];
    messagesSnapshot.forEach(doc => {
        const messageData = doc.data();
        this.adminData.messages.push({
            id: doc.id,
            name: messageData.name || 'Unknown',
            email: messageData.email || '',
            message: messageData.message || messageData.text || '',
            status: messageData.status || 'new',
            priority: messageData.priority || 'normal',
            isAuthenticated: !!messageData.isAuthenticated,
            createdAt: messageData.timestamp ? messageData.timestamp.toDate() : (messageData.createdAt ? messageData.createdAt.toDate() : new Date()),
            updatedAt: messageData.updatedAt ? messageData.updatedAt.toDate() : null
        });
    });
    
    console.log(`✅ Loaded ${this.adminData.messages.length} messages from Firestore`);
    console.log('📊 Messages data:', this.adminData.messages);
} catch (error) {
    console.error('❌ Error loading messages:', error);
    this.adminData.messages = [];
    throw error; // Re-throw to trigger demo data fallback
}
    }

    async loadCoupons() {
try {
    console.log('🎫 Loading coupons from Firestore...');
    
    if (!window.AttralFirebase || !window.AttralFirebase.db) {
        throw new Error('Firebase not initialized');
    }
    
    const couponsSnapshot = await window.AttralFirebase.db
        .collection('coupons')
        .orderBy('createdAt', 'desc')
        .limit(100)
        .get();

    this.adminData.coupons = [];
    couponsSnapshot.forEach(doc => {
        const couponData = doc.data();
        this.adminData.coupons.push({
            id: doc.id,
            code: couponData.code || '',
            name: couponData.name || '',
            type: couponData.type || 'percentage',
            value: Number(couponData.value || 0),
            isActive: !!couponData.isActive,
            usageCount: Number(couponData.usageCount || 0),
            usageLimit: couponData.usageLimit || null,
            validUntil: couponData.validUntil ? couponData.validUntil.toDate() : new Date('2025-12-31'),
            createdAt: couponData.created_at ? couponData.created_at.toDate() : (couponData.createdAt ? couponData.createdAt.toDate() : new Date())
        });
    });
    
    console.log(`✅ Loaded ${this.adminData.coupons.length} coupons from Firestore`);
    console.log('📊 Coupons data:', this.adminData.coupons);
} catch (error) {
    console.error('❌ Error loading coupons:', error);
    this.adminData.coupons = [];
    throw error; // Re-throw to trigger demo data fallback
}
    }

    async loadProducts() {
try {
    console.log('🛍️ Loading products from Firestore...');
    
    if (!window.AttralFirebase || !window.AttralFirebase.db) {
        throw new Error('Firebase not initialized');
    }
    
    const productsSnapshot = await window.AttralFirebase.db
        .collection('products')
        .orderBy('createdAt', 'desc')
        .limit(100)
        .get();

    this.adminData.products = [];
    productsSnapshot.forEach(doc => {
        const productData = doc.data();
        this.adminData.products.push({
            id: doc.id,
            name: productData.name || productData.title || 'Unknown Product',
            price: Number(productData.price || 0),
            category: productData.category || 'uncategorized',
            status: productData.status || 'active',
            stock: Number(productData.stock || 0),
            description: productData.description || '',
            images: productData.images || [],
            createdAt: productData.created_at ? productData.created_at.toDate() : (productData.createdAt ? productData.createdAt.toDate() : new Date())
        });
    });
    
    console.log(`✅ Loaded ${this.adminData.products.length} products from Firestore`);
    console.log('📊 Products data:', this.adminData.products);
} catch (error) {
    console.error('❌ Error loading products:', error);
    this.adminData.products = [];
    throw error; // Re-throw to trigger demo data fallback
}
    }

    async loadAffiliates() {
try {
    console.log('🤝 Loading affiliates from Firestore...');
    
    if (!window.AttralFirebase || !window.AttralFirebase.db) {
        throw new Error('Firebase not initialized');
    }
    
    const affiliatesSnapshot = await window.AttralFirebase.db
        .collection('affiliates')
        .orderBy('createdAt', 'desc')
        .limit(100)
        .get();

    this.adminData.affiliates = [];
    affiliatesSnapshot.forEach(doc => {
        const affiliateData = doc.data();
        this.adminData.affiliates.push({
            id: doc.id,
            code: affiliateData.code || '',
            name: affiliateData.displayName || affiliateData.name || 'Unknown',
            email: affiliateData.email || '',
            status: affiliateData.status || 'pending',
            createdAt: affiliateData.created_at ? affiliateData.created_at.toDate() : (affiliateData.createdAt ? affiliateData.createdAt.toDate() : new Date())
        });
    });

    console.log(`✅ Loaded ${this.adminData.affiliates.length} affiliates from Firestore`);
    console.log('📊 Affiliates data:', this.adminData.affiliates);
} catch (error) {
    console.error('❌ Error loading affiliates:', error);
    this.adminData.affiliates = [];
    throw error; // Re-throw to trigger demo data fallback
}
    }

    calculateAnalytics() {
const totalRevenue = this.adminData.orders
    .filter(order => order.paymentStatus === 'paid' || order.status === 'completed')
    .reduce((sum, order) => sum + order.totalAmount, 0);

const totalOrders = this.adminData.orders.length;
const totalUsers = this.adminData.users.length;
const totalCoupons = this.adminData.coupons.filter(coupon => coupon.isActive).length;
const pendingOrders = this.adminData.orders.filter(order => order.status === 'pending').length;
const newMessages = this.adminData.messages.filter(message => message.status === 'new').length;

this.adminData.analytics = {
    totalRevenue,
    totalOrders,
    totalUsers,
    totalCoupons,
    pendingOrders,
    newMessages,
    conversionRate: totalUsers > 0 ? (totalOrders / totalUsers * 100).toFixed(2) : 0,
    averageOrderValue: totalOrders > 0 ? (totalRevenue / totalOrders).toFixed(2) : 0
};
    }

    updateDashboard() {
this.updateStats();
this.updateRecentOrders();
this.updateRecentMessages();
this.updateNavigationBadges();
    }

    updateStats() {
const stats = this.adminData.analytics;

this.updateElement('total-revenue', `₹${stats.totalRevenue.toLocaleString()}`);
this.updateElement('total-orders', stats.totalOrders);
this.updateElement('total-users', stats.totalUsers);
this.updateElement('total-coupons', stats.totalCoupons);

// Update navigation badges
this.updateNavigationBadges();
    }

    updateNavigationBadges() {
// Update order badges
const pendingOrders = this.adminData.orders.filter(o => o.status === 'confirmed').length;
const totalOrders = this.adminData.orders.length;
const pendingFulfillment = this.adminData.orders.filter(o => ['confirmed', 'processing'].includes(o.status)).length;

// Update message badges
const unreadMessages = this.adminData.messages.filter(m => m.status === 'new').length;
const totalMessages = this.adminData.messages.length;

// Update coupon badges
const activeCoupons = this.adminData.coupons.filter(c => c.isActive).length;

// Update user badges
const totalUsers = this.adminData.users.length;

// Update affiliate badges
const totalAffiliates = this.adminData.affiliates.length;

// Update product badges (if available)
const totalProducts = this.adminData.products ? this.adminData.products.length : 0;

// Update badge elements
this.updateBadgeElement('pending-orders-count', pendingOrders);
this.updateBadgeElement('total-orders-count', totalOrders);
this.updateBadgeElement('pending-fulfillment-count', pendingFulfillment);
this.updateBadgeElement('unread-messages-count', unreadMessages);
this.updateBadgeElement('total-messages-count', totalMessages);
this.updateBadgeElement('active-coupons-count', activeCoupons);
this.updateBadgeElement('total-users-count', totalUsers);
this.updateBadgeElement('total-affiliates-count', totalAffiliates);
this.updateBadgeElement('total-products-count', totalProducts);
    }

    updateBadgeElement(elementId, count) {
const element = document.getElementById(elementId);
if (element) {
    element.textContent = count;
    
    // Add animation for count changes
    if (count > 0) {
        element.style.transform = 'scale(1.1)';
        element.style.transition = 'transform 0.2s ease';
        setTimeout(() => {
            element.style.transform = 'scale(1)';
        }, 200);
    }
}
    }

    updateRecentOrders() {
const container = document.getElementById('recent-orders-list');
if (!container) return;

const recentOrders = this.adminData.orders.slice(0, 5);

if (recentOrders.length === 0) {
    container.innerHTML = '<div style="text-align: center; padding: 2rem; color: #6b7280;">No orders found</div>';
    return;
}

container.innerHTML = recentOrders.map(order => `
    <div style="display: flex; align-items: center; justify-content: space-between; padding: 1rem; border-bottom: 1px solid #f1f5f9; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f8fafc'" onmouseout="this.style.backgroundColor='transparent'">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.875rem;">
                ${order.customerName.charAt(0).toUpperCase()}
            </div>
            <div>
                <p style="margin: 0; font-weight: 600; color: #1f2937; font-size: 0.875rem;">Order #${order.orderId}</p>
                <p style="margin: 0; color: #6b7280; font-size: 0.75rem;">${order.customerName}</p>
            </div>
        </div>
        <div style="text-align: right;">
            <p style="margin: 0; font-weight: 600; color: #1f2937; font-size: 0.875rem;">₹${order.totalAmount.toLocaleString()}</p>
            <span style="display: inline-flex; align-items: center; padding: 0.25rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; text-transform: uppercase; ${
                order.paymentStatus === 'paid' || order.status === 'completed' ? 'background: #d1fae5; color: #047857;' :
                order.paymentStatus === 'pending' || order.status === 'pending' ? 'background: #fef3c7; color: #b45309;' :
                'background: #fee2e2; color: #b91c1c;'
            }">
                ${order.status}
            </span>
        </div>
    </div>
`).join('');
    }

    updateRecentMessages() {
const container = document.getElementById('recent-messages-list');
if (!container) return;

const recentMessages = this.adminData.messages.slice(0, 5);

if (recentMessages.length === 0) {
    container.innerHTML = '<div style="text-align: center; padding: 2rem; color: #6b7280;">No messages found</div>';
    return;
}

container.innerHTML = recentMessages.map(message => `
    <div style="display: flex; align-items: center; justify-content: space-between; padding: 1rem; border-bottom: 1px solid #f1f5f9; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f8fafc'" onmouseout="this.style.backgroundColor='transparent'">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #ef4444, #dc2626); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.875rem;">
                ${message.name.charAt(0).toUpperCase()}
            </div>
            <div>
                <p style="margin: 0; font-weight: 600; color: #1f2937; font-size: 0.875rem;">${message.name}</p>
                <p style="margin: 0; color: #6b7280; font-size: 0.75rem; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${message.message}</p>
            </div>
        </div>
        <div style="text-align: right;">
            <p style="margin: 0; color: #6b7280; font-size: 0.75rem;">${new Date(message.createdAt).toLocaleDateString()}</p>
            <span style="display: inline-flex; align-items: center; padding: 0.25rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; text-transform: uppercase; ${
                message.status === 'new' ? 'background: #fee2e2; color: #b91c1c;' : 'background: #d1fae5; color: #047857;'
            }">
                ${message.status}
            </span>
        </div>
    </div>
`).join('');
    }

    updateNavigationBadges() {
this.updateElement('pending-orders-count', this.adminData.analytics.pendingOrders);
this.updateElement('unread-messages-count', this.adminData.analytics.newMessages);
    }

    updateElement(id, value) {
const element = document.getElementById(id);
if (element) {
    element.textContent = value;
}
    }

    showSection(sectionName) {
// Hide all sections
document.querySelectorAll('.admin-section').forEach(section => {
    section.style.display = 'none';
});

// Show target section
const targetSection = document.getElementById(`${sectionName}-section`);
if (targetSection) {
    targetSection.style.display = 'block';
}

// Update navigation
document.querySelectorAll('.admin-nav-link').forEach(link => {
    link.classList.remove('active');
});

const activeLink = document.querySelector(`[data-section="${sectionName}"]`);
if (activeLink) {
    activeLink.classList.add('active');
}

// Update page title
const titles = {
    dashboard: 'Dashboard',
    orders: 'Order Management',
    fulfillment: 'Order Fulfillment',
    messages: 'Customer Messages',
    coupons: 'Coupon Management',
    users: 'User Management',
    products: 'Product Management',
    analytics: 'Analytics & Reports'
};

document.querySelector('.admin-topbar h1').textContent = titles[sectionName] || 'Admin Panel';

this.currentSection = sectionName;

// Load section content
this.loadSectionContent(sectionName);
    }

    async loadSectionContent(sectionName) {
const contentElement = document.getElementById(`${sectionName}-content`);
if (!contentElement) return;

try {
    switch (sectionName) {
        case 'orders':
            await this.loadOrdersSection();
            break;
        case 'fulfillment':
            await this.loadFulfillmentSection();
            break;
        case 'messages':
            await this.loadMessagesSection();
            break;
        case 'coupons':
            await this.loadCouponsSection();
            break;
        case 'users':
            await this.loadUsersSection();
            break;
        case 'products':
            await this.loadProductsSection();
            break;
        case 'affiliates':
            await this.loadAffiliatesSection();
            break;
        case 'analytics':
            await this.loadAnalyticsSection();
            break;
    }
} catch (error) {
    console.error(`❌ Error loading ${sectionName} section:`, error);
    contentElement.innerHTML = `
        <div style="text-align: center; padding: 2rem; color: #ef4444;">
            <p>Failed to load ${sectionName} data</p>
            <button onclick="adminDashboard.loadSectionContent('${sectionName}')" class="admin-btn admin-btn-secondary">Retry</button>
        </div>
    `;
}
    }

    updateLastUpdated() {
const element = document.getElementById('last-updated');
if (element) {
    element.textContent = `Last updated: ${new Date().toLocaleTimeString()}`;
}
    }

    setupRealtimeListeners() {
if (!window.AttralFirebase || !window.AttralFirebase.db) return;

console.log('👂 Setting up real-time listeners...');

// Listen for new orders
window.AttralFirebase.db.collection('orders')
    .orderBy('createdAt', 'desc')
    .limit(10)
    .onSnapshot((snapshot) => {
        console.log('📦 Real-time update: orders changed');
        this.loadOrders().then(() => this.updateDashboard());
        if (this.currentSection === 'orders') {
            this.loadSectionContent('orders');
        }
    });

// Listen for new messages
window.AttralFirebase.db.collection('contact_messages')
    .orderBy('createdAt', 'desc')
    .limit(10)
    .onSnapshot((snapshot) => {
        console.log('💬 Real-time update: messages changed');
        this.loadMessages().then(() => this.updateDashboard());
        if (this.currentSection === 'messages') {
            this.loadSectionContent('messages');
        }
    });

// Listen for new users
window.AttralFirebase.db.collection('users')
    .orderBy('createdAt', 'desc')
    .limit(10)
    .onSnapshot((snapshot) => {
        console.log('👥 Real-time update: users changed');
        this.loadUsers().then(() => this.updateDashboard());
        if (this.currentSection === 'users') {
            this.loadSectionContent('users');
        }
    });

// Listen for new coupons
window.AttralFirebase.db.collection('coupons')
    .orderBy('createdAt', 'desc')
    .limit(10)
    .onSnapshot((snapshot) => {
        console.log('🎫 Real-time update: coupons changed');
        this.loadCoupons().then(() => this.updateDashboard());
        if (this.currentSection === 'coupons') {
            this.loadSectionContent('coupons');
        }
    });
    }

    showNotification(message, type = 'info') {
const notification = document.createElement('div');
notification.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 1rem 1.5rem;
    border-radius: 0.75rem;
    color: white;
    font-weight: 500;
    z-index: 10000;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    animation: slideInRight 0.3s ease-out;
`;

switch (type) {
    case 'success':
        notification.style.background = 'linear-gradient(135deg, #10b981, #059669)';
        break;
    case 'error':
        notification.style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';
        break;
    case 'warning':
        notification.style.background = 'linear-gradient(135deg, #f59e0b, #d97706)';
        break;
    default:
        notification.style.background = 'linear-gradient(135deg, #3b82f6, #1d4ed8)';
}

notification.textContent = message;
document.body.appendChild(notification);

setTimeout(() => {
    notification.remove();
}, 5000);
    }

    // Section loading methods
    async loadOrdersSection() {
const contentElement = document.getElementById('orders-content');
if (!contentElement) return;

try {
            console.log('📦 Loading orders section...');
            console.log('📊 Current orders data:', this.adminData.orders);
            
            // Ensure orders are loaded
            if (this.adminData.orders.length === 0) {
                console.log('🔄 No orders found, attempting to reload...');
                try {
                    await this.loadOrders();
                } catch (error) {
                    console.error('❌ Failed to reload orders:', error);
                }
            }
            
    // Create orders section HTML
    contentElement.innerHTML = `
        <div style="margin-bottom: 2rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
                <div>
                    <h2 style="margin: 0 0 0.5rem 0; font-size: 1.875rem; font-weight: 700; color: #1f2937;">Order Management</h2>
                    <p style="margin: 0; color: #6b7280;">Manage and process customer orders</p>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button onclick="adminDashboard.testFirestoreConnection()" class="admin-btn admin-btn-secondary">
                        🔍 Test Connection
                    </button>
                    <button onclick="adminDashboard.exportOrders()" class="admin-btn admin-btn-secondary">
                        📥 Export
                    </button>
                    <button onclick="adminDashboard.refreshOrders()" class="admin-btn admin-btn-primary">
                        🔄 Refresh
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div style="background: white; padding: 1.5rem; border-radius: 0.75rem; border: 1px solid #e2e8f0; margin-bottom: 1.5rem;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div>
                        <label style="display: block; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">Status Filter</label>
                        <select id="order-status-filter" onchange="adminDashboard.filterOrders()" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background: white;">
                            <option value="">All Orders</option>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">Payment Status</label>
                        <select id="payment-status-filter" onchange="adminDashboard.filterOrders()" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background: white;">
                            <option value="">All Payments</option>
                            <option value="pending">Pending</option>
                            <option value="paid">Paid</option>
                            <option value="failed">Failed</option>
                            <option value="refunded">Refunded</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">Search</label>
                        <input type="text" id="order-search" placeholder="Search orders..." onkeyup="adminDashboard.searchOrders()" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem;">
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div style="background: white; border-radius: 0.75rem; border: 1px solid #e2e8f0; overflow: hidden;">
            <div style="padding: 1.5rem; border-bottom: 1px solid #e2e8f0;">
                <h3 style="margin: 0; font-size: 1.125rem; font-weight: 600; color: #1f2937;">Orders (${this.adminData.orders.length})</h3>
                <div style="margin-top: 0.5rem; padding: 0.5rem; background: #f3f4f6; border-radius: 0.25rem; font-family: monospace; font-size: 0.75rem; color: #6b7280;">
                    <strong>Debug:</strong> Firebase: ${window.AttralFirebase ? 'Connected' : 'Disconnected'} | 
                    Last loaded: ${new Date().toLocaleTimeString()} | 
                    Data source: ${this.adminData.orders.length > 0 ? 'Firestore' : 'None'}
                </div>
            </div>
            <div id="orders-table-container">
                ${this.renderOrdersTable()}
            </div>
        </div>
    `;

} catch (error) {
    console.error('❌ Error loading orders section:', error);
    contentElement.innerHTML = `
        <div style="text-align: center; padding: 2rem; color: #ef4444;">
            <p>Failed to load orders data</p>
            <button onclick="adminDashboard.loadOrdersSection()" class="admin-btn admin-btn-secondary">Retry</button>
        </div>
    `;
}
    }

    renderOrdersTable() {
        console.log('🎨 Rendering orders table with', this.adminData.orders.length, 'orders');
        
if (this.adminData.orders.length === 0) {
    return `
        <div style="text-align: center; padding: 3rem; color: #6b7280;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">📦</div>
            <h3 style="margin: 0 0 0.5rem 0; color: #374151;">No orders found</h3>
                    <p style="margin: 0 0 1rem 0;">Orders will appear here once customers start placing them.</p>
                    <div style="padding: 1rem; background: #fef3c7; border-radius: 0.5rem; margin: 1rem 0; font-family: monospace; font-size: 0.875rem;">
                        <strong>Troubleshooting:</strong><br>
                        • Check Firebase connection: ${window.AttralFirebase ? '✅ Connected' : '❌ Disconnected'}<br>
                        • Check browser console for errors<br>
                        • Try refreshing the page<br>
                        • Verify Firestore has order data
                    </div>
                    <button onclick="adminDashboard.loadOrders()" class="admin-btn admin-btn-primary" style="margin-top: 1rem;">
                        🔄 Reload Orders
                    </button>
        </div>
    `;
}

return `
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead style="background: #f8fafc;">
                <tr>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e2e8f0;">Order ID</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e2e8f0;">Customer</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e2e8f0;">Amount</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e2e8f0;">Status</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e2e8f0;">Payment</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e2e8f0;">Date</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e2e8f0;">Actions</th>
                </tr>
            </thead>
            <tbody>
                ${this.adminData.orders.map(order => `
                    <tr style="border-bottom: 1px solid #f1f5f9; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f8fafc'" onmouseout="this.style.backgroundColor='transparent'">
                        <td style="padding: 1rem;">
                            <div style="font-weight: 600; color: #1f2937;">#${order.orderId}</div>
                        </td>
                        <td style="padding: 1rem;">
                            <div>
                                <div style="font-weight: 500; color: #1f2937;">${order.customerName}</div>
                                <div style="font-size: 0.875rem; color: #6b7280;">${order.customerEmail}</div>
                            </div>
                        </td>
                        <td style="padding: 1rem;">
                            <div style="font-weight: 600; color: #1f2937;">₹${order.totalAmount.toLocaleString()}</div>
                        </td>
                        <td style="padding: 1rem;">
                            <select onchange="adminDashboard.updateOrderStatus('${order.id}', this.value)" style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; background: white; font-size: 0.875rem;">
                                <option value="pending" ${order.status === 'pending' ? 'selected' : ''}>Pending</option>
                                <option value="confirmed" ${order.status === 'confirmed' ? 'selected' : ''}>Confirmed</option>
                                <option value="shipped" ${order.status === 'shipped' ? 'selected' : ''}>Shipped</option>
                                <option value="delivered" ${order.status === 'delivered' ? 'selected' : ''}>Delivered</option>
                                <option value="cancelled" ${order.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                            </select>
                        </td>
                        <td style="padding: 1rem;">
                            <span style="display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; text-transform: uppercase; ${
                                order.paymentStatus === 'paid' ? 'background: #d1fae5; color: #047857;' :
                                order.paymentStatus === 'pending' ? 'background: #fef3c7; color: #b45309;' :
                                'background: #fee2e2; color: #b91c1c;'
                            }">
                                ${order.paymentStatus}
                            </span>
                        </td>
                        <td style="padding: 1rem;">
                            <div style="font-size: 0.875rem; color: #6b7280;">${new Date(order.createdAt).toLocaleDateString()}</div>
                            <div style="font-size: 0.75rem; color: #9ca3af;">${new Date(order.createdAt).toLocaleTimeString()}</div>
                        </td>
                        <td style="padding: 1rem;">
                            <div style="display: flex; gap: 0.5rem;">
                                <button onclick="adminDashboard.viewOrderDetails('${order.id}')" style="padding: 0.5rem; background: #3b82f6; color: white; border: none; border-radius: 0.375rem; cursor: pointer; font-size: 0.75rem;" title="View Details">
                                    👁️
                                </button>
                                <button onclick="adminDashboard.printInvoice('${order.id}')" style="padding: 0.5rem; background: #10b981; color: white; border: none; border-radius: 0.375rem; cursor: pointer; font-size: 0.75rem;" title="Print Invoice">
                                    🖨️
                                </button>
                            </div>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    </div>
`;
    }

    async updateOrderStatus(orderId, status) {
try {
    await window.AttralFirebase.db.collection('orders').doc(orderId).update({
        status: status,
        updated_at: new Date()
    });
    
    this.showNotification(`Order ${orderId} status updated to ${status}`, 'success');
    
    // Reload orders data
    await this.loadOrders();
    this.updateDashboard();
    
    // Refresh orders section if currently viewing
    if (this.currentSection === 'orders') {
        this.loadOrdersSection();
    }
    
} catch (error) {
    console.error('❌ Error updating order status:', error);
    this.showNotification('Failed to update order status', 'error');
}
    }

    viewOrderDetails(orderId) {
const order = this.adminData.orders.find(o => o.id === orderId);
if (!order) return;

const modal = document.createElement('div');
modal.style.cssText = `
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    padding: 2rem;
`;

modal.innerHTML = `
    <div style="background: white; border-radius: 0.75rem; max-width: 800px; width: 100%; max-height: 90vh; overflow-y: auto;">
        <div style="padding: 2rem; border-bottom: 1px solid #e2e8f0;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <h2 style="margin: 0; font-size: 1.5rem; font-weight: 700; color: #1f2937;">Order Details #${order.orderId}</h2>
                <button onclick="this.closest('.modal').remove()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6b7280;">&times;</button>
            </div>
        </div>
        <div style="padding: 2rem;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                <div>
                    <h3 style="margin: 0 0 1rem 0; color: #374151;">Customer Information</h3>
                    <div style="background: #f8fafc; padding: 1rem; border-radius: 0.5rem;">
                        <p style="margin: 0 0 0.5rem 0;"><strong>Name:</strong> ${order.customerName}</p>
                        <p style="margin: 0 0 0.5rem 0;"><strong>Email:</strong> ${order.customerEmail}</p>
                        <p style="margin: 0;"><strong>Phone:</strong> ${order.shippingAddress.phone || 'Not provided'}</p>
                    </div>
                </div>
                <div>
                    <h3 style="margin: 0 0 1rem 0; color: #374151;">Shipping Address</h3>
                    <div style="background: #f8fafc; padding: 1rem; border-radius: 0.5rem;">
                        <p style="margin: 0 0 0.5rem 0;">${order.shippingAddress.address || 'Not provided'}</p>
                        <p style="margin: 0 0 0.5rem 0;">${order.shippingAddress.city || ''} ${order.shippingAddress.state || ''}</p>
                        <p style="margin: 0;">${order.shippingAddress.pincode || ''}</p>
                    </div>
                </div>
            </div>
            
            <div>
                <h3 style="margin: 0 0 1rem 0; color: #374151;">Order Items</h3>
                <div style="border: 1px solid #e2e8f0; border-radius: 0.5rem; overflow: hidden;">
                    ${order.items.map(item => `
                        <div style="display: flex; align-items: center; padding: 1rem; border-bottom: 1px solid #f1f5f9;">
                            <div style="width: 60px; height: 60px; background: #f3f4f6; border-radius: 0.5rem; margin-right: 1rem;"></div>
                            <div style="flex: 1;">
                                <h4 style="margin: 0 0 0.25rem 0; color: #1f2937;">${item.name || 'Product'}</h4>
                                <p style="margin: 0; color: #6b7280;">Quantity: ${item.quantity || 1}</p>
                            </div>
                            <div style="font-weight: 600; color: #1f2937;">₹${(item.price || 0) * (item.quantity || 1)}</div>
                        </div>
                    `).join('')}
                </div>
            </div>
            
            <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e2e8f0;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <p style="margin: 0; color: #6b7280;">Order Date: ${new Date(order.createdAt).toLocaleDateString()}</p>
                        <p style="margin: 0; color: #6b7280;">Order Time: ${new Date(order.createdAt).toLocaleTimeString()}</p>
                    </div>
                    <div style="text-align: right;">
                        <p style="margin: 0 0 0.5rem 0; color: #374151;">Total Amount</p>
                        <p style="margin: 0; font-size: 1.5rem; font-weight: 700; color: #1f2937;">₹${order.totalAmount.toLocaleString()}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
`;

modal.classList.add('modal');
document.body.appendChild(modal);
    }

    printInvoice(orderId) {
// Implementation for printing invoice
this.showNotification('Invoice print functionality will be implemented', 'info');
    }

    filterOrders() {
// Implementation for filtering orders
console.log('Filtering orders...');
    }

    searchOrders() {
// Implementation for searching orders
console.log('Searching orders...');
    }

    exportOrders() {
// Implementation for exporting orders
this.showNotification('Export functionality will be implemented', 'info');
    }

    refreshOrders() {
this.loadOrdersSection();
this.showNotification('Orders refreshed', 'success');
    }

    // Debug function to test Firestore connection and data
    async testFirestoreConnection() {
        console.log('🔍 Testing Firestore connection...');
        
        try {
            if (!window.AttralFirebase || !window.AttralFirebase.db) {
                console.error('❌ Firebase not initialized');
                this.showNotification('Firebase not initialized', 'error');
                return;
            }
            
            console.log('✅ Firebase initialized, testing Firestore...');
            
            // Test basic Firestore read
            const testSnapshot = await window.AttralFirebase.db.collection('orders').limit(1).get();
            console.log('📊 Test query result:', testSnapshot.size, 'documents');
            
            if (testSnapshot.size > 0) {
                const testDoc = testSnapshot.docs[0];
                console.log('📄 Sample document:', testDoc.id, testDoc.data());
            }
            
            // Test orders collection specifically
            const ordersSnapshot = await window.AttralFirebase.db.collection('orders').get();
            console.log('📦 Total orders in Firestore:', ordersSnapshot.size);
            
            if (ordersSnapshot.size > 0) {
                this.showNotification(`Found ${ordersSnapshot.size} orders in Firestore`, 'success');
                
                // Load the orders
                await this.loadOrders();
                this.updateDashboard();
                this.loadOrdersSection();
            } else {
                this.showNotification('No orders found in Firestore', 'warning');
            }
            
        } catch (error) {
            console.error('❌ Firestore test failed:', error);
            this.showNotification(`Firestore test failed: ${error.message}`, 'error');
        }
    }

    async loadFulfillmentSection() {
const contentElement = document.getElementById('fulfillment-content');
if (!contentElement) return;

try {
    // Get orders that need fulfillment
    const pendingOrders = this.adminData.orders.filter(order => 
        order.status === 'confirmed' || order.status === 'processing'
    );
    const shippedOrders = this.adminData.orders.filter(order => 
        order.status === 'shipped' || order.status === 'delivered'
    );

    contentElement.innerHTML = `
        <div style="margin-bottom: 2rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
                <div>
                    <h2 style="margin: 0 0 0.5rem 0; font-size: 1.875rem; font-weight: 700; color: #1f2937;">🚚 Order Fulfillment Center</h2>
                    <p style="margin: 0; color: #6b7280;">Manage order processing, shipping, and delivery tracking</p>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button onclick="adminDashboard.bulkProcessOrders()" class="admin-btn admin-btn-primary">
                        📦 Bulk Process Orders
                    </button>
                    <button onclick="adminDashboard.exportFulfillmentReport()" class="admin-btn admin-btn-secondary">
                        📊 Export Report
                    </button>
                </div>
            </div>

            <!-- Fulfillment Stats -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                <div style="background: white; padding: 1.5rem; border-radius: 0.75rem; border: 1px solid #e2e8f0; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #f59e0b, #d97706); border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                            ⏳
                        </div>
                        <div>
                            <div style="font-size: 1.5rem; font-weight: 700; color: #1f2937;">${pendingOrders.length}</div>
                            <div style="font-size: 0.875rem; color: #6b7280;">Pending Fulfillment</div>
                        </div>
                    </div>
                </div>
                <div style="background: white; padding: 1.5rem; border-radius: 0.75rem; border: 1px solid #e2e8f0; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                            🚚
                        </div>
                        <div>
                            <div style="font-size: 1.5rem; font-weight: 700; color: #1f2937;">${shippedOrders.length}</div>
                            <div style="font-size: 0.875rem; color: #6b7280;">Shipped Orders</div>
                        </div>
                    </div>
                </div>
                <div style="background: white; padding: 1.5rem; border-radius: 0.75rem; border: 1px solid #e2e8f0; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #10b981, #059669); border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                            ✅
                        </div>
                        <div>
                            <div style="font-size: 1.5rem; font-weight: 700; color: #1f2937;">${this.adminData.orders.filter(o => o.status === 'delivered').length}</div>
                            <div style="font-size: 0.875rem; color: #6b7280;">Delivered</div>
                        </div>
                    </div>
                </div>
                <div style="background: white; padding: 1.5rem; border-radius: 0.75rem; border: 1px solid #e2e8f0; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #8b5cf6, #7c3aed); border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                            📊
                        </div>
                        <div>
                            <div style="font-size: 1.5rem; font-weight: 700; color: #1f2937;">${this.calculateFulfillmentRate()}%</div>
                            <div style="font-size: 0.875rem; color: #6b7280;">Fulfillment Rate</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div style="background: white; padding: 1.5rem; border-radius: 0.75rem; border: 1px solid #e2e8f0; margin-bottom: 1.5rem;">
                <h3 style="margin: 0 0 1rem 0; font-size: 1.125rem; font-weight: 600; color: #1f2937;">Quick Actions</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <button onclick="adminDashboard.processPendingOrders()" class="admin-btn admin-btn-primary" style="padding: 1rem; text-align: center;">
                        <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">⚡</div>
                        <div style="font-weight: 600;">Process Pending Orders</div>
                        <div style="font-size: 0.875rem; opacity: 0.8;">${pendingOrders.length} orders ready</div>
                    </button>
                    <button onclick="adminDashboard.generateShippingLabels()" class="admin-btn admin-btn-secondary" style="padding: 1rem; text-align: center;">
                        <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">🏷️</div>
                        <div style="font-weight: 600;">Generate Shipping Labels</div>
                        <div style="font-size: 0.875rem; opacity: 0.8;">For processed orders</div>
                    </button>
                    <button onclick="adminDashboard.trackDeliveries()" class="admin-btn admin-btn-secondary" style="padding: 1rem; text-align: center;">
                        <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">📍</div>
                        <div style="font-weight: 600;">Track Deliveries</div>
                        <div style="font-size: 0.875rem; opacity: 0.8;">Monitor shipments</div>
                    </button>
                    <button onclick="adminDashboard.manageInventory()" class="admin-btn admin-btn-secondary" style="padding: 1rem; text-align: center;">
                        <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">📦</div>
                        <div style="font-weight: 600;">Manage Inventory</div>
                        <div style="font-size: 0.875rem; opacity: 0.8;">Stock management</div>
                    </button>
                </div>
            </div>
        </div>

        <!-- Pending Orders for Fulfillment -->
        <div style="background: white; border-radius: 0.75rem; border: 1px solid #e2e8f0; overflow: hidden;">
            <div style="padding: 1.5rem; border-bottom: 1px solid #e2e8f0;">
                <h3 style="margin: 0; font-size: 1.125rem; font-weight: 600; color: #1f2937;">Orders Ready for Fulfillment (${pendingOrders.length})</h3>
            </div>
            <div id="fulfillment-orders-list">
                ${this.renderFulfillmentOrdersTable(pendingOrders)}
            </div>
        </div>

        <!-- Recent Shipments -->
        <div style="background: white; border-radius: 0.75rem; border: 1px solid #e2e8f0; overflow: hidden; margin-top: 1.5rem;">
            <div style="padding: 1.5rem; border-bottom: 1px solid #e2e8f0;">
                <h3 style="margin: 0; font-size: 1.125rem; font-weight: 600; color: #1f2937;">Recent Shipments (${shippedOrders.length})</h3>
            </div>
            <div id="recent-shipments-list">
                ${this.renderRecentShipmentsTable(shippedOrders.slice(0, 10))}
            </div>
        </div>
    `;

} catch (error) {
    console.error('❌ Error loading fulfillment section:', error);
    contentElement.innerHTML = `
        <div style="text-align: center; padding: 2rem; color: #ef4444;">
            <p>Failed to load fulfillment data</p>
            <button onclick="adminDashboard.loadFulfillmentSection()" class="admin-btn admin-btn-secondary">Retry</button>
        </div>
    `;
}
    }

    async loadMessagesSection() {
const contentElement = document.getElementById('messages-content');
if (!contentElement) return;

try {
    contentElement.innerHTML = `
        <div style="margin-bottom: 2rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
                <div>
                    <h2 style="margin: 0 0 0.5rem 0; font-size: 1.875rem; font-weight: 700; color: #1f2937;">Customer Messages</h2>
                    <p style="margin: 0; color: #6b7280;">View and respond to customer inquiries</p>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button onclick="adminDashboard.markAllAsRead()" class="admin-btn admin-btn-secondary">
                        ✅ Mark All Read
                    </button>
                    <button onclick="adminDashboard.refreshMessages()" class="admin-btn admin-btn-primary">
                        🔄 Refresh
                    </button>
                </div>
            </div>

            <!-- Message Stats -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                <div style="background: white; padding: 1.5rem; border-radius: 0.75rem; border: 1px solid #e2e8f0;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #ef4444, #dc2626); border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                            💬
                        </div>
                        <div>
                            <div style="font-size: 1.5rem; font-weight: 700; color: #1f2937;">${this.adminData.messages.length}</div>
                            <div style="font-size: 0.875rem; color: #6b7280;">Total Messages</div>
                        </div>
                    </div>
                </div>
                <div style="background: white; padding: 1.5rem; border-radius: 0.75rem; border: 1px solid #e2e8f0;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #f59e0b, #d97706); border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                            🔴
                        </div>
                        <div>
                            <div style="font-size: 1.5rem; font-weight: 700; color: #1f2937;">${this.adminData.messages.filter(m => m.status === 'new').length}</div>
                            <div style="font-size: 0.875rem; color: #6b7280;">Unread Messages</div>
                        </div>
                    </div>
                </div>
                <div style="background: white; padding: 1.5rem; border-radius: 0.75rem; border: 1px solid #e2e8f0;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #10b981, #059669); border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                            ✅
                        </div>
                        <div>
                            <div style="font-size: 1.5rem; font-weight: 700; color: #1f2937;">${this.adminData.messages.filter(m => m.status === 'replied').length}</div>
                            <div style="font-size: 0.875rem; color: #6b7280;">Replied Messages</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div style="background: white; padding: 1.5rem; border-radius: 0.75rem; border: 1px solid #e2e8f0; margin-bottom: 1.5rem;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div>
                        <label style="display: block; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">Status Filter</label>
                        <select id="message-status-filter" onchange="adminDashboard.filterMessages()" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background: white;">
                            <option value="">All Messages</option>
                            <option value="new">New</option>
                            <option value="read">Read</option>
                            <option value="replied">Replied</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">Priority Filter</label>
                        <select id="message-priority-filter" onchange="adminDashboard.filterMessages()" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background: white;">
                            <option value="">All Priorities</option>
                            <option value="high">High</option>
                            <option value="normal">Normal</option>
                            <option value="low">Low</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">Search</label>
                        <input type="text" id="message-search" placeholder="Search messages..." onkeyup="adminDashboard.searchMessages()" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem;">
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages List -->
        <div style="background: white; border-radius: 0.75rem; border: 1px solid #e2e8f0; overflow: hidden;">
            <div style="padding: 1.5rem; border-bottom: 1px solid #e2e8f0;">
                <h3 style="margin: 0; font-size: 1.125rem; font-weight: 600; color: #1f2937;">Messages (${this.adminData.messages.length})</h3>
            </div>
            <div id="messages-list-container">
                ${this.renderMessagesList()}
            </div>
        </div>
    `;

} catch (error) {
    console.error('❌ Error loading messages section:', error);
    contentElement.innerHTML = `
        <div style="text-align: center; padding: 2rem; color: #ef4444;">
            <p>Failed to load messages data</p>
            <button onclick="adminDashboard.loadMessagesSection()" class="admin-btn admin-btn-secondary">Retry</button>
        </div>
    `;
}
    }

    renderMessagesList() {
if (this.adminData.messages.length === 0) {
    return `
        <div style="text-align: center; padding: 3rem; color: #6b7280;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">💬</div>
            <h3 style="margin: 0 0 0.5rem 0; color: #374151;">No messages found</h3>
            <p style="margin: 0;">Customer messages will appear here once they start contacting you.</p>
        </div>
    `;
}

return `
    <div style="max-height: 600px; overflow-y: auto;">
        ${this.adminData.messages.map(message => `
            <div style="border-bottom: 1px solid #f1f5f9; transition: background-color 0.2s; cursor: pointer;" onmouseover="this.style.backgroundColor='#f8fafc'" onmouseout="this.style.backgroundColor='transparent'" onclick="adminDashboard.viewMessageDetails('${message.id}')">
                <div style="padding: 1.5rem; display: flex; align-items: flex-start; gap: 1rem;">
                    <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 1.125rem; flex-shrink: 0;">
                        ${message.name.charAt(0).toUpperCase()}
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <h4 style="margin: 0; font-weight: 600; color: #1f2937; font-size: 1rem;">${message.name}</h4>
                                <span style="display: inline-flex; align-items: center; padding: 0.25rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; text-transform: uppercase; ${
                                    message.priority === 'high' ? 'background: #fee2e2; color: #b91c1c;' :
                                    message.priority === 'low' ? 'background: #e0e7ff; color: #3730a3;' :
                                    'background: #fef3c7; color: #b45309;'
                                }">
                                    ${message.priority}
                                </span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span style="display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; text-transform: uppercase; ${
                                    message.status === 'new' ? 'background: #fee2e2; color: #b91c1c;' :
                                    message.status === 'replied' ? 'background: #d1fae5; color: #047857;' :
                                    message.status === 'closed' ? 'background: #f3f4f6; color: #374151;' :
                                    'background: #e0e7ff; color: #3730a3;'
                                }">
                                    ${message.status}
                                </span>
                                <span style="font-size: 0.75rem; color: #9ca3af;">
                                    ${new Date(message.createdAt).toLocaleDateString()}
                                </span>
                            </div>
                        </div>
                        <div style="color: #6b7280; font-size: 0.875rem; line-height: 1.5; margin-bottom: 0.75rem;">
                            ${message.message.length > 150 ? message.message.substring(0, 150) + '...' : message.message}
                        </div>
                        <div style="display: flex; align-items: center; gap: 1rem; font-size: 0.75rem; color: #9ca3af;">
                            <span>📧 ${message.email}</span>
                            <span>🕒 ${new Date(message.createdAt).toLocaleTimeString()}</span>
                            ${message.isAuthenticated ? '<span style="color: #10b981;">✅ Authenticated User</span>' : '<span style="color: #f59e0b;">👤 Guest User</span>'}
                        </div>
                    </div>
                </div>
            </div>
        `).join('')}
    </div>
`;
    }

    viewMessageDetails(messageId) {
const message = this.adminData.messages.find(m => m.id === messageId);
if (!message) return;

const modal = document.createElement('div');
modal.style.cssText = `
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    padding: 2rem;
`;

modal.innerHTML = `
    <div style="background: white; border-radius: 0.75rem; max-width: 800px; width: 100%; max-height: 90vh; overflow-y: auto;">
        <div style="padding: 2rem; border-bottom: 1px solid #e2e8f0;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <h2 style="margin: 0; font-size: 1.5rem; font-weight: 700; color: #1f2937;">Message from ${message.name}</h2>
                <button onclick="this.closest('.modal').remove()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6b7280;">&times;</button>
            </div>
        </div>
        <div style="padding: 2rem;">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem; padding: 1.5rem; background: #f8fafc; border-radius: 0.75rem;">
                <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 1.5rem;">
                    ${message.name.charAt(0).toUpperCase()}
                </div>
                <div style="flex: 1;">
                    <h3 style="margin: 0 0 0.5rem 0; font-size: 1.25rem; font-weight: 600; color: #1f2937;">${message.name}</h3>
                    <p style="margin: 0 0 0.5rem 0; color: #6b7280;">📧 ${message.email}</p>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <span style="display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; text-transform: uppercase; ${
                            message.status === 'new' ? 'background: #fee2e2; color: #b91c1c;' :
                            message.status === 'replied' ? 'background: #d1fae5; color: #047857;' :
                            message.status === 'closed' ? 'background: #f3f4f6; color: #374151;' :
                            'background: #e0e7ff; color: #3730a3;'
                        }">
                            ${message.status}
                        </span>
                        <span style="display: inline-flex; align-items: center; padding: 0.25rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; text-transform: uppercase; ${
                            message.priority === 'high' ? 'background: #fee2e2; color: #b91c1c;' :
                            message.priority === 'low' ? 'background: #e0e7ff; color: #3730a3;' :
                            'background: #fef3c7; color: #b45309;'
                        }">
                            ${message.priority} priority
                        </span>
                        <span style="color: #6b7280; font-size: 0.875rem;">
                            ${new Date(message.createdAt).toLocaleDateString()} at ${new Date(message.createdAt).toLocaleTimeString()}
                        </span>
                    </div>
                </div>
            </div>
            
            <div style="margin-bottom: 2rem;">
                <h4 style="margin: 0 0 1rem 0; color: #374151; font-weight: 600;">Message Content</h4>
                <div style="background: #f8fafc; padding: 1.5rem; border-radius: 0.75rem; border-left: 4px solid #3b82f6;">
                    <p style="margin: 0; color: #374151; line-height: 1.6; white-space: pre-wrap;">${message.message}</p>
                </div>
            </div>
            
            <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 2rem; border-top: 1px solid #e2e8f0;">
                <div style="display: flex; gap: 1rem;">
                    <select id="message-status-update" style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; background: white;">
                        <option value="new" ${message.status === 'new' ? 'selected' : ''}>New</option>
                        <option value="read" ${message.status === 'read' ? 'selected' : ''}>Read</option>
                        <option value="replied" ${message.status === 'replied' ? 'selected' : ''}>Replied</option>
                        <option value="closed" ${message.status === 'closed' ? 'selected' : ''}>Closed</option>
                    </select>
                    <button onclick="adminDashboard.updateMessageStatus('${message.id}', document.getElementById('message-status-update').value)" style="padding: 0.5rem 1rem; background: #3b82f6; color: white; border: none; border-radius: 0.375rem; cursor: pointer; font-size: 0.875rem;">
                        Update Status
                    </button>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button onclick="adminDashboard.replyToMessage('${message.id}')" style="padding: 0.5rem 1rem; background: #10b981; color: white; border: none; border-radius: 0.375rem; cursor: pointer; font-size: 0.875rem;">
                        📧 Reply
                    </button>
                    <button onclick="this.closest('.modal').remove()" style="padding: 0.5rem 1rem; background: #6b7280; color: white; border: none; border-radius: 0.375rem; cursor: pointer; font-size: 0.875rem;">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
`;

modal.classList.add('modal');
document.body.appendChild(modal);
    }

    async updateMessageStatus(messageId, status) {
try {
    await window.AttralFirebase.db.collection('contact_messages').doc(messageId).update({
        status: status,
        updatedAt: new Date()
    });
    
    this.showNotification(`Message status updated to ${status}`, 'success');
    
    // Reload messages data
    await this.loadMessages();
    this.updateDashboard();
    
    // Refresh messages section if currently viewing
    if (this.currentSection === 'messages') {
        this.loadMessagesSection();
    }
    
} catch (error) {
    console.error('❌ Error updating message status:', error);
    this.showNotification('Failed to update message status', 'error');
}
    }

    replyToMessage(messageId) {
const message = this.adminData.messages.find(m => m.id === messageId);
if (!message) return;

const modalContent = `
    <div style="background: white; border-radius: 1rem; padding: 2rem; max-width: 600px; max-height: 80vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 style="margin: 0; font-size: 1.5rem; font-weight: 700; color: #1f2937;">💬 Reply to Message</h2>
            <button onclick="this.closest('.modal').remove()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6b7280;">&times;</button>
        </div>
        
        <!-- Original Message -->
        <div style="background: #f8fafc; padding: 1.5rem; border-radius: 0.75rem; margin-bottom: 1.5rem; border-left: 4px solid #6366f1;">
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                <div>
                    <h4 style="margin: 0 0 0.5rem 0; color: #1f2937; font-weight: 600;">From: ${message.name}</h4>
                    <p style="margin: 0; color: #6b7280; font-size: 0.875rem;">${message.email}</p>
                </div>
                <span style="padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; background: #e0e7ff; color: #3730a3;">
                    ${message.priority}
                </span>
            </div>
            <p style="margin: 0; color: #374151; line-height: 1.6;">${message.message}</p>
            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e2e8f0;">
                <p style="margin: 0; color: #6b7280; font-size: 0.875rem;">
                    Received: ${new Date(message.createdAt).toLocaleString()}
                </p>
            </div>
        </div>

        <!-- Reply Form -->
        <form id="reply-form" onsubmit="adminDashboard.sendReply(event, '${messageId}')">
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Subject</label>
                <input type="text" id="reply-subject" value="Re: Your inquiry - ATTRAL Support" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; box-sizing: border-box;" required>
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Reply Message</label>
                <textarea id="reply-message" rows="6" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; box-sizing: border-box; resize: vertical;" placeholder="Type your reply here..." required></textarea>
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 500; color: #374151;">
                    <input type="checkbox" id="mark-as-resolved" style="margin: 0;">
                    Mark this message as resolved
                </label>
            </div>
            
            <div style="display: flex; gap: 1rem;">
                <button type="submit" class="admin-btn admin-btn-primary" style="flex: 1;">
                    📧 Send Reply
                </button>
                <button type="button" onclick="this.closest('.modal').remove()" class="admin-btn admin-btn-secondary">
                    Cancel
                </button>
            </div>
        </form>
    </div>
`;

this.showModal(modalContent);
    }

    async sendReply(event, messageId) {
event.preventDefault();

const subject = document.getElementById('reply-subject').value;
const message = document.getElementById('reply-message').value;
const markAsResolved = document.getElementById('mark-as-resolved').checked;

try {
    this.showNotification('Sending reply...', 'info');
    
    // Simulate sending email
    await new Promise(resolve => setTimeout(resolve, 2000));
    
    // Update message status if marked as resolved
    if (markAsResolved) {
        await this.updateMessageStatus(messageId, 'resolved');
    } else {
        await this.updateMessageStatus(messageId, 'replied');
    }
    
    // Close modal
    const modal = document.querySelector('.modal');
    if (modal) modal.remove();
    
    this.showNotification('Reply sent successfully!', 'success');
    
    // Refresh messages section
    if (this.currentSection === 'messages') {
        this.loadMessagesSection();
    }
    
} catch (error) {
    console.error('❌ Error sending reply:', error);
    this.showNotification('Failed to send reply', 'error');
}
    }

    async markAllAsRead() {
try {
    const unreadMessages = this.adminData.messages.filter(m => m.status === 'new');
    const batch = window.AttralFirebase.db.batch();
    
    unreadMessages.forEach(message => {
        const messageRef = window.AttralFirebase.db.collection('contact_messages').doc(message.id);
        batch.update(messageRef, {
            status: 'read',
            updatedAt: new Date()
        });
    });
    
    await batch.commit();
    
    this.showNotification(`Marked ${unreadMessages.length} messages as read`, 'success');
    
    // Reload messages data
    await this.loadMessages();
    this.updateDashboard();
    
    // Refresh messages section if currently viewing
    if (this.currentSection === 'messages') {
        this.loadMessagesSection();
    }
    
} catch (error) {
    console.error('❌ Error marking messages as read:', error);
    this.showNotification('Failed to mark messages as read', 'error');
}
    }

    filterMessages() {
// Implementation for filtering messages
console.log('Filtering messages...');
    }

    searchMessages() {
// Implementation for searching messages
console.log('Searching messages...');
    }

    refreshMessages() {
this.loadMessagesSection();
this.showNotification('Messages refreshed', 'success');
    }

    async loadCouponsSection() {
const contentElement = document.getElementById('coupons-content');
if (!contentElement) return;

try {
    contentElement.innerHTML = `
        <div style="margin-bottom: 2rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
                <div>
                    <h2 style="margin: 0 0 0.5rem 0; font-size: 1.875rem; font-weight: 700; color: #1f2937;">🎫 Advanced Coupon Management</h2>
                    <p style="margin: 0; color: #6b7280;">Create, manage, and track discount coupons with affiliate integration</p>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button onclick="adminDashboard.showAffiliateCouponsModal()" class="admin-btn admin-btn-secondary">
                        🤝 Affiliate Coupons
                    </button>
                    <button onclick="adminDashboard.showCreateCouponModal()" class="admin-btn admin-btn-primary">
                        ➕ Create Coupon
                    </button>
                </div>
            </div>

            <!-- Enhanced Coupon Stats -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                <div style="background: white; padding: 1.5rem; border-radius: 0.75rem; border: 1px solid #e2e8f0; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #10b981, #059669); border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                            🎫
                        </div>
                        <div>
                            <div style="font-size: 1.5rem; font-weight: 700; color: #1f2937;">${this.adminData.coupons.length}</div>
                            <div style="font-size: 0.875rem; color: #6b7280;">Total Coupons</div>
                        </div>
                    </div>
                </div>
                <div style="background: white; padding: 1.5rem; border-radius: 0.75rem; border: 1px solid #e2e8f0; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                            ✅
                        </div>
                        <div>
                            <div style="font-size: 1.5rem; font-weight: 700; color: #1f2937;">${this.adminData.coupons.filter(c => c.isActive).length}</div>
                            <div style="font-size: 0.875rem; color: #6b7280;">Active Coupons</div>
                        </div>
                    </div>
                </div>
                <div style="background: white; padding: 1.5rem; border-radius: 0.75rem; border: 1px solid #e2e8f0; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #f59e0b, #d97706); border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                            📊
                        </div>
                        <div>
                            <div style="font-size: 1.5rem; font-weight: 700; color: #1f2937;">${this.adminData.coupons.reduce((sum, c) => sum + c.usageCount, 0)}</div>
                            <div style="font-size: 0.875rem; color: #6b7280;">Total Usage</div>
                        </div>
                    </div>
                </div>
                <div style="background: white; padding: 1.5rem; border-radius: 0.75rem; border: 1px solid #e2e8f0; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #8b5cf6, #7c3aed); border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                            💰
                        </div>
                        <div>
                            <div style="font-size: 1.5rem; font-weight: 700; color: #1f2937;">₹${this.calculateTotalDiscounts()}</div>
                            <div style="font-size: 0.875rem; color: #6b7280;">Total Discounts</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enhanced Filters and Search -->
            <div style="background: white; padding: 1.5rem; border-radius: 0.75rem; border: 1px solid #e2e8f0; margin-bottom: 1.5rem;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div>
                        <label style="display: block; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">Status Filter</label>
                        <select id="coupon-status-filter" onchange="adminDashboard.filterCoupons()" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background: white;">
                            <option value="">All Coupons</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="expired">Expired</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">Type Filter</label>
                        <select id="coupon-type-filter" onchange="adminDashboard.filterCoupons()" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background: white;">
                            <option value="">All Types</option>
                            <option value="percentage">Percentage</option>
                            <option value="fixed">Fixed Amount</option>
                            <option value="shipping">Free Shipping</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">Search Coupons</label>
                        <input type="text" id="coupon-search" placeholder="Search by code or name..." onkeyup="adminDashboard.searchCoupons()" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem;">
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Coupons Table -->
        <div style="background: white; border-radius: 0.75rem; border: 1px solid #e2e8f0; overflow: hidden;">
            <div style="padding: 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 1.125rem; font-weight: 600; color: #1f2937;">Coupons (${this.adminData.coupons.length})</h3>
                <div style="display: flex; gap: 0.5rem;">
                    <button onclick="adminDashboard.exportCoupons()" class="admin-btn admin-btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                        📥 Export
                    </button>
                    <button onclick="adminDashboard.bulkActivateCoupons()" class="admin-btn admin-btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                        ✅ Bulk Activate
                    </button>
                </div>
            </div>
            <div id="coupons-table-container">
                ${this.renderCouponsTable()}
            </div>
        </div>
    `;

} catch (error) {
    console.error('❌ Error loading coupons section:', error);
    contentElement.innerHTML = `
        <div style="text-align: center; padding: 2rem; color: #ef4444;">
            <p>Failed to load coupons data</p>
            <button onclick="adminDashboard.loadCouponsSection()" class="admin-btn admin-btn-secondary">Retry</button>
        </div>
    `;
}
    }

    renderCouponsTable() {
if (this.adminData.coupons.length === 0) {
    return `
        <div style="text-align: center; padding: 3rem; color: #6b7280;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🎫</div>
            <h3 style="margin: 0 0 0.5rem 0; color: #374151;">No coupons found</h3>
            <p style="margin: 0 0 1rem 0;">Create your first coupon to start offering discounts to customers.</p>
            <button onclick="adminDashboard.showCreateCouponModal()" class="admin-btn admin-btn-primary">
                Create Coupon
            </button>
        </div>
    `;
}

return `
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead style="background: #f8fafc;">
                <tr>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e2e8f0;">Code</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e2e8f0;">Name</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e2e8f0;">Type</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e2e8f0;">Value</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e2e8f0;">Usage</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e2e8f0;">Status</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e2e8f0;">Expires</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e2e8f0;">Actions</th>
                </tr>
            </thead>
            <tbody>
                ${this.adminData.coupons.map(coupon => `
                    <tr style="border-bottom: 1px solid #f1f5f9; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f8fafc'" onmouseout="this.style.backgroundColor='transparent'">
                        <td style="padding: 1rem;">
                            <div style="font-weight: 600; color: #1f2937; font-family: monospace; background: #f3f4f6; padding: 0.25rem 0.5rem; border-radius: 0.25rem; display: inline-block;">
                                ${coupon.code}
                            </div>
                        </td>
                        <td style="padding: 1rem;">
                            <div style="font-weight: 500; color: #1f2937;">${coupon.name || 'Unnamed Coupon'}</div>
                        </td>
                        <td style="padding: 1rem;">
                            <span style="display: inline-flex; align-items: center; padding: 0.25rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; text-transform: uppercase; background: #e0e7ff; color: #3730a3;">
                                ${coupon.type}
                            </span>
                        </td>
                        <td style="padding: 1rem;">
                            <div style="font-weight: 600; color: #1f2937;">
                                ${coupon.type === 'percentage' ? `${coupon.value}%` : `₹${coupon.value}`}
                            </div>
                        </td>
                        <td style="padding: 1rem;">
                            <div style="font-size: 0.875rem; color: #6b7280;">
                                ${coupon.usageCount}/${coupon.usageLimit || '∞'}
                            </div>
                        </td>
                        <td style="padding: 1rem;">
                            <span style="display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; text-transform: uppercase; ${
                                coupon.isActive ? 'background: #d1fae5; color: #047857;' : 'background: #fee2e2; color: #b91c1c;'
                            }">
                                ${coupon.isActive ? 'Active' : 'Inactive'}
                            </span>
                        </td>
                        <td style="padding: 1rem;">
                            <div style="font-size: 0.875rem; color: #6b7280;">
                                ${new Date(coupon.validUntil).toLocaleDateString()}
                            </div>
                        </td>
                        <td style="padding: 1rem;">
                            <div style="display: flex; gap: 0.5rem;">
                                <button onclick="adminDashboard.toggleCouponStatus('${coupon.id}', ${!coupon.isActive})" style="padding: 0.5rem; background: ${coupon.isActive ? '#ef4444' : '#10b981'}; color: white; border: none; border-radius: 0.375rem; cursor: pointer; font-size: 0.75rem;" title="${coupon.isActive ? 'Deactivate' : 'Activate'}">
                                    ${coupon.isActive ? '❌' : '✅'}
                                </button>
                                <button onclick="adminDashboard.editCoupon('${coupon.id}')" style="padding: 0.5rem; background: #3b82f6; color: white; border: none; border-radius: 0.375rem; cursor: pointer; font-size: 0.75rem;" title="Edit">
                                    ✏️
                                </button>
                                <button onclick="adminDashboard.deleteCoupon('${coupon.id}')" style="padding: 0.5rem; background: #ef4444; color: white; border: none; border-radius: 0.375rem; cursor: pointer; font-size: 0.75rem;" title="Delete">
                                    🗑️
                                </button>
                            </div>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    </div>
`;
    }

    showCreateCouponModal() {
const modal = document.createElement('div');
modal.style.cssText = `
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    padding: 2rem;
`;

modal.innerHTML = `
    <div style="background: white; border-radius: 0.75rem; max-width: 600px; width: 100%; max-height: 90vh; overflow-y: auto;">
        <div style="padding: 2rem; border-bottom: 1px solid #e2e8f0;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <h2 style="margin: 0; font-size: 1.5rem; font-weight: 700; color: #1f2937;">Create New Coupon</h2>
                <button onclick="this.closest('.modal').remove()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6b7280;">&times;</button>
            </div>
        </div>
        <form id="create-coupon-form" style="padding: 2rem;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">Coupon Code *</label>
                    <input type="text" id="coupon-code" required style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-family: monospace; text-transform: uppercase;" placeholder="SAVE20">
                </div>
                <div>
                    <label style="display: block; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">Coupon Name *</label>
                    <input type="text" id="coupon-name" required style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem;" placeholder="20% Off Sale">
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">Discount Type *</label>
                    <select id="coupon-type" required style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem;">
                        <option value="percentage">Percentage</option>
                        <option value="fixed">Fixed Amount</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">Discount Value *</label>
                    <input type="number" id="coupon-value" required min="0" step="0.01" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem;" placeholder="20">
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">Usage Limit</label>
                    <input type="number" id="coupon-limit" min="1" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem;" placeholder="100 (leave empty for unlimited)">
                </div>
                <div>
                    <label style="display: block; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">Valid Until *</label>
                    <input type="date" id="coupon-expiry" required style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem;">
                </div>
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">Description</label>
                <textarea id="coupon-description" rows="3" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; resize: vertical;" placeholder="Optional description for this coupon..."></textarea>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" onclick="this.closest('.modal').remove()" style="padding: 0.75rem 1.5rem; background: #f3f4f6; color: #374151; border: none; border-radius: 0.5rem; cursor: pointer; font-weight: 500;">
                    Cancel
                </button>
                <button type="submit" style="padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; border-radius: 0.5rem; cursor: pointer; font-weight: 500;">
                    Create Coupon
                </button>
            </div>
        </form>
    </div>
`;

modal.classList.add('modal');
document.body.appendChild(modal);

// Set default expiry date to 1 year from now
const expiryInput = document.getElementById('coupon-expiry');
const nextYear = new Date();
nextYear.setFullYear(nextYear.getFullYear() + 1);
expiryInput.value = nextYear.toISOString().split('T')[0];

// Handle form submission
document.getElementById('create-coupon-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    await this.createCoupon();
});
    }

    async createCoupon() {
try {
    const code = document.getElementById('coupon-code').value.toUpperCase();
    const name = document.getElementById('coupon-name').value;
    const type = document.getElementById('coupon-type').value;
    const value = parseFloat(document.getElementById('coupon-value').value);
    const limit = document.getElementById('coupon-limit').value ? parseInt(document.getElementById('coupon-limit').value) : null;
    const expiry = document.getElementById('coupon-expiry').value;
    const description = document.getElementById('coupon-description').value;

    // Validate inputs
    if (!code || !name || !value || !expiry) {
        this.showNotification('Please fill in all required fields', 'error');
        return;
    }

    // Check if coupon code already exists
    const existingCoupon = this.adminData.coupons.find(c => c.code === code);
    if (existingCoupon) {
        this.showNotification('Coupon code already exists', 'error');
        return;
    }

    const couponData = {
        code: code,
        name: name,
        type: type,
        value: value,
        usageLimit: limit,
        usageCount: 0,
        isActive: true,
        validUntil: new Date(expiry),
        description: description,
        created_at: new Date()
    };

    await window.AttralFirebase.db.collection('coupons').add(couponData);
    
    this.showNotification('Coupon created successfully!', 'success');
    
    // Close modal
    document.querySelector('.modal').remove();
    
    // Reload coupons data
    await this.loadCoupons();
    this.updateDashboard();
    
    // Refresh coupons section if currently viewing
    if (this.currentSection === 'coupons') {
        this.loadCouponsSection();
    }
    
} catch (error) {
    console.error('❌ Error creating coupon:', error);
    this.showNotification('Failed to create coupon', 'error');
}
    }

    async toggleCouponStatus(couponId, isActive) {
try {
    await window.AttralFirebase.db.collection('coupons').doc(couponId).update({
        isActive: isActive,
        updated_at: new Date()
    });
    
    this.showNotification(`Coupon ${isActive ? 'activated' : 'deactivated'} successfully`, 'success');
    
    // Reload coupons data
    await this.loadCoupons();
    this.updateDashboard();
    
    // Refresh coupons section if currently viewing
    if (this.currentSection === 'coupons') {
        this.loadCouponsSection();
    }
    
} catch (error) {
    console.error('❌ Error toggling coupon status:', error);
    this.showNotification('Failed to update coupon status', 'error');
}
    }

    async deleteCoupon(couponId) {
if (!confirm('Are you sure you want to delete this coupon? This action cannot be undone.')) {
    return;
}

try {
    await window.AttralFirebase.db.collection('coupons').doc(couponId).delete();
    
    this.showNotification('Coupon deleted successfully', 'success');
    
    // Reload coupons data
    await this.loadCoupons();
    this.updateDashboard();
    
    // Refresh coupons section if currently viewing
    if (this.currentSection === 'coupons') {
        this.loadCouponsSection();
    }
    
} catch (error) {
    console.error('❌ Error deleting coupon:', error);
    this.showNotification('Failed to delete coupon', 'error');
}
    }

    editCoupon(couponId) {
// Implementation for editing coupon
this.showNotification('Edit coupon functionality will be implemented', 'info');
    }

    // Enhanced coupon methods
    calculateTotalDiscounts() {
return this.adminData.coupons.reduce((total, coupon) => {
    const usageCount = coupon.usageCount || 0;
    if (coupon.type === 'percentage') {
        return total + (usageCount * (coupon.maxDiscount || 0));
    } else if (coupon.type === 'fixed') {
        return total + (usageCount * coupon.value);
    } else if (coupon.type === 'shipping') {
        return total + (usageCount * 399); // Assuming ₹399 shipping
    }
    return total;
}, 0).toLocaleString();
    }

    async showAffiliateCouponsModal() {
try {
    // Load affiliates from Firebase
    const affiliatesSnapshot = await window.AttralFirebase.db
        .collection('affiliates')
        .orderBy('createdAt', 'desc')
        .get();

    const affiliates = [];
    affiliatesSnapshot.forEach(doc => {
        const data = doc.data();
        if (data.code) {
            affiliates.push({
                id: doc.id,
                code: data.code,
                name: data.displayName || data.name || 'Unknown',
                email: data.email
            });
        }
    });

    const modalContent = `
        <div style="background: white; border-radius: 1rem; padding: 2rem; max-width: 800px; max-height: 80vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 style="margin: 0; font-size: 1.5rem; font-weight: 700; color: #1f2937;">🤝 Affiliate Coupon Management</h2>
                <button onclick="this.closest('.modal').remove()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6b7280;">&times;</button>
            </div>
            
            <div style="background: #f8fafc; padding: 1.5rem; border-radius: 0.75rem; margin-bottom: 1.5rem;">
                <h3 style="margin: 0 0 0.5rem 0; font-size: 1.125rem; font-weight: 600; color: #1f2937;">Auto-Create 5% Affiliate Coupons</h3>
                <p style="margin: 0; color: #6b7280; font-size: 0.875rem;">Automatically create 5% discount coupons for all affiliate codes. These coupons will be applied when customers use affiliate referral links.</p>
            </div>

            <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
                <button onclick="adminDashboard.createAllAffiliateCoupons()" class="admin-btn admin-btn-primary">
                    🎫 Create All Affiliate Coupons
                </button>
                <button onclick="adminDashboard.refreshAffiliates()" class="admin-btn admin-btn-secondary">
                    🔄 Refresh Affiliates
                </button>
            </div>

            <div style="background: white; border: 1px solid #e2e8f0; border-radius: 0.75rem; overflow: hidden;">
                <div style="padding: 1rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: grid; grid-template-columns: 1fr 2fr 1fr 1fr; gap: 1rem; font-weight: 600; color: #374151;">
                    <div>Affiliate Code</div>
                    <div>Affiliate Name</div>
                    <div>Coupon Status</div>
                    <div>Actions</div>
                </div>
                <div id="affiliate-coupons-list">
                    ${affiliates.map(affiliate => {
                        const existingCoupon = this.adminData.coupons.find(c => c.code === affiliate.code);
                        return `
                            <div style="padding: 1rem; border-bottom: 1px solid #e2e8f0; display: grid; grid-template-columns: 1fr 2fr 1fr 1fr; gap: 1rem; align-items: center;">
                                <div style="font-weight: 600; color: #6366f1;">${affiliate.code}</div>
                                <div>${affiliate.name}</div>
                                <div>
                                    ${existingCoupon ? 
                                        `<span style="padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; background: #d1fae5; color: #065f46;">
                                            ${existingCoupon.isActive ? 'Active' : 'Inactive'}
                                        </span>` : 
                                        `<span style="color: #f59e0b; font-weight: 600; font-size: 0.875rem;">Not Created</span>`
                                    }
                                </div>
                                <div>
                                    ${existingCoupon ? 
                                        `<div style="display: flex; gap: 0.5rem;">
                                            <button onclick="adminDashboard.toggleCouponStatus('${existingCoupon.id}', ${!existingCoupon.isActive})" class="admin-btn admin-btn-secondary" style="padding: 0.25rem 0.75rem; font-size: 0.75rem;">
                                                ${existingCoupon.isActive ? 'Deactivate' : 'Activate'}
                                            </button>
                                            <button onclick="adminDashboard.deleteCoupon('${existingCoupon.id}')" class="admin-btn admin-btn-danger" style="padding: 0.25rem 0.75rem; font-size: 0.75rem;">Delete</button>
                                        </div>` :
                                        `<button onclick="adminDashboard.createAffiliateCoupon('${affiliate.code}', '${affiliate.name}')" class="admin-btn admin-btn-primary" style="padding: 0.25rem 0.75rem; font-size: 0.75rem;">Create 5% Coupon</button>`
                                    }
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            </div>
        </div>
    `;

    this.showModal(modalContent);
} catch (error) {
    console.error('❌ Error loading affiliate coupons modal:', error);
    this.showNotification('Failed to load affiliate data', 'error');
}
    }

    async createAffiliateCoupon(code, name) {
try {
    const newCoupon = {
        code: code,
        name: `${name} - 5% Affiliate Discount`,
        type: 'percentage',
        value: 5,
        minAmount: 0,
        maxDiscount: null,
        isActive: true,
        validUntil: new Date('2025-12-31'),
        usageLimit: null,
        usageCount: 0,
        description: `5% discount for affiliate ${name} (${code})`,
        createdAt: new Date(),
        isAffiliateCoupon: true,
        affiliateCode: code
    };

    if (window.AttralFirebase && window.AttralFirebase.db) {
        const docRef = await window.AttralFirebase.db.collection('coupons').add(newCoupon);
        newCoupon.id = docRef.id;
        this.adminData.coupons.push(newCoupon);
        this.showNotification(`5% discount coupon created for ${name}!`, 'success');
        this.showAffiliateCouponsModal(); // Refresh modal
    }
} catch (error) {
    console.error('❌ Error creating affiliate coupon:', error);
    this.showNotification('Failed to create affiliate coupon', 'error');
}
    }

    async createAllAffiliateCoupons() {
try {
    // Load affiliates
    const affiliatesSnapshot = await window.AttralFirebase.db
        .collection('affiliates')
        .orderBy('createdAt', 'desc')
        .get();

    let createdCount = 0;
    for (const doc of affiliatesSnapshot.docs) {
        const data = doc.data();
        if (data.code) {
            const existingCoupon = this.adminData.coupons.find(c => c.code === data.code);
            if (!existingCoupon) {
                await this.createAffiliateCoupon(data.code, data.displayName || data.name || 'Unknown');
                createdCount++;
            }
        }
    }
    
    this.showNotification(`Created ${createdCount} affiliate coupons!`, 'success');
} catch (error) {
    console.error('❌ Error creating all affiliate coupons:', error);
    this.showNotification('Failed to create affiliate coupons', 'error');
}
    }

    filterCoupons() {
const statusFilter = document.getElementById('coupon-status-filter')?.value;
const typeFilter = document.getElementById('coupon-type-filter')?.value;
const searchTerm = document.getElementById('coupon-search')?.value.toLowerCase();

let filteredCoupons = this.adminData.coupons;

if (statusFilter) {
    if (statusFilter === 'active') {
        filteredCoupons = filteredCoupons.filter(c => c.isActive);
    } else if (statusFilter === 'inactive') {
        filteredCoupons = filteredCoupons.filter(c => !c.isActive);
    } else if (statusFilter === 'expired') {
        filteredCoupons = filteredCoupons.filter(c => new Date(c.validUntil) < new Date());
    }
}

if (typeFilter) {
    filteredCoupons = filteredCoupons.filter(c => c.type === typeFilter);
}

if (searchTerm) {
    filteredCoupons = filteredCoupons.filter(c => 
        c.code.toLowerCase().includes(searchTerm) || 
        c.name.toLowerCase().includes(searchTerm)
    );
}

// Update table with filtered results
const container = document.getElementById('coupons-table-container');
if (container) {
    container.innerHTML = this.renderCouponsTable(filteredCoupons);
}
    }

    searchCoupons() {
this.filterCoupons();
    }

    exportCoupons() {
const csvContent = [
    ['Code', 'Name', 'Type', 'Value', 'Status', 'Usage Count', 'Usage Limit', 'Valid Until', 'Created At'],
    ...this.adminData.coupons.map(coupon => [
        coupon.code,
        coupon.name,
        coupon.type,
        coupon.value,
        coupon.isActive ? 'Active' : 'Inactive',
        coupon.usageCount,
        coupon.usageLimit || 'Unlimited',
        new Date(coupon.validUntil).toLocaleDateString(),
        new Date(coupon.createdAt).toLocaleDateString()
    ])
].map(row => row.join(',')).join('\n');

const blob = new Blob([csvContent], { type: 'text/csv' });
const url = window.URL.createObjectURL(blob);
const a = document.createElement('a');
a.href = url;
a.download = `coupons-export-${new Date().toISOString().split('T')[0]}.csv`;
a.click();
window.URL.revokeObjectURL(url);

this.showNotification('Coupons exported successfully', 'success');
    }

    bulkActivateCoupons() {
const inactiveCoupons = this.adminData.coupons.filter(c => !c.isActive);
if (inactiveCoupons.length === 0) {
    this.showNotification('No inactive coupons to activate', 'info');
    return;
}

if (confirm(`Activate ${inactiveCoupons.length} inactive coupons?`)) {
    inactiveCoupons.forEach(async coupon => {
        await this.toggleCouponStatus(coupon.id, true);
    });
    this.showNotification(`${inactiveCoupons.length} coupons activated`, 'success');
}
    }

    // Fulfillment helper methods
    calculateFulfillmentRate() {
const totalOrders = this.adminData.orders.length;
const fulfilledOrders = this.adminData.orders.filter(o => 
    o.status === 'shipped' || o.status === 'delivered'
).length;
return totalOrders > 0 ? Math.round((fulfilledOrders / totalOrders) * 100) : 0;
    }

    renderFulfillmentOrdersTable(orders) {
if (orders.length === 0) {
    return `
        <div style="text-align: center; padding: 3rem; color: #6b7280;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">📦</div>
            <h3 style="margin: 0 0 0.5rem 0; color: #374151;">No orders pending fulfillment</h3>
            <p style="margin: 0;">All orders have been processed!</p>
        </div>
    `;
}

return `
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead style="background: #f8fafc;">
                <tr>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e2e8f0;">Order ID</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e2e8f0;">Customer</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e2e8f0;">Items</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e2e8f0;">Amount</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e2e8f0;">Priority</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e2e8f0;">Actions</th>
                </tr>
            </thead>
            <tbody>
                ${orders.map(order => `
                    <tr style="border-bottom: 1px solid #e2e8f0;">
                        <td style="padding: 1rem;">
                            <div style="font-weight: 600; color: #6366f1;">#${order.orderId}</div>
                            <div style="font-size: 0.875rem; color: #6b7280;">${new Date(order.createdAt).toLocaleDateString()}</div>
                        </td>
                        <td style="padding: 1rem;">
                            <div style="font-weight: 600; color: #1f2937;">${order.customerName}</div>
                            <div style="font-size: 0.875rem; color: #6b7280;">${order.customerEmail}</div>
                        </td>
                        <td style="padding: 1rem;">
                            <div style="font-weight: 600;">${order.items ? order.items.length : 1} items</div>
                            <div style="font-size: 0.875rem; color: #6b7280;">${order.items ? order.items.map(i => i.name).join(', ') : 'Product items'}</div>
                        </td>
                        <td style="padding: 1rem;">
                            <div style="font-weight: 600; color: #059669;">₹${order.totalAmount.toLocaleString()}</div>
                            <div style="font-size: 0.875rem; color: #6b7280;">${order.paymentStatus}</div>
                        </td>
                        <td style="padding: 1rem;">
                            ${this.getOrderPriority(order)}
                        </td>
                        <td style="padding: 1rem;">
                            <div style="display: flex; gap: 0.5rem;">
                                <button onclick="adminDashboard.processOrder('${order.id}')" class="admin-btn admin-btn-primary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                    Process
                                </button>
                                <button onclick="adminDashboard.viewOrderDetails('${order.id}')" class="admin-btn admin-btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                    View
                                </button>
                            </div>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    </div>
`;
    }

    renderRecentShipmentsTable(orders) {
if (orders.length === 0) {
    return `
        <div style="text-align: center; padding: 3rem; color: #6b7280;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🚚</div>
            <h3 style="margin: 0 0 0.5rem 0; color: #374151;">No recent shipments</h3>
            <p style="margin: 0;">Shipment tracking will appear here once orders are processed.</p>
        </div>
    `;
}

return `
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead style="background: #f8fafc;">
                <tr>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e2e8f0;">Order ID</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e2e8f0;">Customer</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e2e8f0;">Status</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e2e8f0;">Tracking</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e2e8f0;">Actions</th>
                </tr>
            </thead>
            <tbody>
                ${orders.map(order => `
                    <tr style="border-bottom: 1px solid #e2e8f0;">
                        <td style="padding: 1rem;">
                            <div style="font-weight: 600; color: #6366f1;">#${order.orderId}</div>
                            <div style="font-size: 0.875rem; color: #6b7280;">${new Date(order.createdAt).toLocaleDateString()}</div>
                        </td>
                        <td style="padding: 1rem;">
                            <div style="font-weight: 600; color: #1f2937;">${order.customerName}</div>
                            <div style="font-size: 0.875rem; color: #6b7280;">${order.customerEmail}</div>
                        </td>
                        <td style="padding: 1rem;">
                            ${this.getOrderStatusBadge(order.status)}
                        </td>
                        <td style="padding: 1rem;">
                            <div style="font-weight: 600; color: #6366f1;">${order.trackingNumber || 'TRK' + order.orderId.slice(-6)}</div>
                            <div style="font-size: 0.875rem; color: #6b7280;">${order.carrier || 'Standard Shipping'}</div>
                        </td>
                        <td style="padding: 1rem;">
                            <div style="display: flex; gap: 0.5rem;">
                                <button onclick="adminDashboard.trackShipment('${order.id}')" class="admin-btn admin-btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                    Track
                                </button>
                                <button onclick="adminDashboard.viewOrderDetails('${order.id}')" class="admin-btn admin-btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                    View
                                </button>
                            </div>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    </div>
`;
    }

    getOrderPriority(order) {
const daysSinceOrder = Math.floor((new Date() - new Date(order.createdAt)) / (1000 * 60 * 60 * 24));
if (daysSinceOrder >= 3) {
    return '<span style="padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; background: #fee2e2; color: #b91c1c;">High</span>';
} else if (daysSinceOrder >= 1) {
    return '<span style="padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; background: #fef3c7; color: #b45309;">Medium</span>';
} else {
    return '<span style="padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; background: #d1fae5; color: #065f46;">Low</span>';
}
    }

    getOrderStatusBadge(status) {
const statusConfig = {
    'shipped': { bg: '#dbeafe', color: '#1e40af', text: 'Shipped' },
    'delivered': { bg: '#d1fae5', color: '#065f46', text: 'Delivered' },
    'processing': { bg: '#fef3c7', color: '#b45309', text: 'Processing' },
    'confirmed': { bg: '#e0e7ff', color: '#3730a3', text: 'Confirmed' }
};

const config = statusConfig[status] || { bg: '#f3f4f6', color: '#374151', text: status };
return `<span style="padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; background: ${config.bg}; color: ${config.color};">${config.text}</span>`;
    }

    // Fulfillment action methods
    async processOrder(orderId) {
try {
    await this.updateOrderStatus(orderId, 'processing');
    this.showNotification('Order marked as processing', 'success');
    this.loadFulfillmentSection();
} catch (error) {
    console.error('❌ Error processing order:', error);
    this.showNotification('Failed to process order', 'error');
}
    }

    async bulkProcessOrders() {
const pendingOrders = this.adminData.orders.filter(order => 
    order.status === 'confirmed'
);

if (pendingOrders.length === 0) {
    this.showNotification('No orders to process', 'info');
    return;
}

if (confirm(`Process ${pendingOrders.length} pending orders?`)) {
    try {
        for (const order of pendingOrders) {
            await this.updateOrderStatus(order.id, 'processing');
        }
        this.showNotification(`${pendingOrders.length} orders processed`, 'success');
        this.loadFulfillmentSection();
    } catch (error) {
        console.error('❌ Error bulk processing orders:', error);
        this.showNotification('Failed to process some orders', 'error');
    }
}
    }

    generateShippingLabels() {
this.showNotification('Shipping label generation feature coming soon', 'info');
    }

    trackDeliveries() {
this.showNotification('Delivery tracking feature coming soon', 'info');
    }

    manageInventory() {
this.showNotification('Inventory management feature coming soon', 'info');
    }

    exportFulfillmentReport() {
const csvContent = [
    ['Order ID', 'Customer', 'Status', 'Amount', 'Created At', 'Tracking Number'],
    ...this.adminData.orders.map(order => [
        order.orderId,
        order.customerName,
        order.status,
        order.totalAmount,
        new Date(order.createdAt).toLocaleDateString(),
        order.trackingNumber || 'N/A'
    ])
].map(row => row.join(',')).join('\n');

const blob = new Blob([csvContent], { type: 'text/csv' });
const url = window.URL.createObjectURL(blob);
const a = document.createElement('a');
a.href = url;
a.download = `fulfillment-report-${new Date().toISOString().split('T')[0]}.csv`;
a.click();
window.URL.revokeObjectURL(url);

this.showNotification('Fulfillment report exported successfully', 'success');
    }

    trackShipment(orderId) {
this.showNotification('Shipment tracking feature coming soon', 'info');
    }

    async loadUsersSection() {
// Implementation for users section
console.log('Loading users section...');
    }

    async loadProductsSection() {
// Implementation for products section
console.log('Loading products section...');
    }

    async loadAffiliatesSection() {
const contentElement = document.getElementById('affiliates-content');
if (!contentElement) return;

try {
    contentElement.innerHTML = `
        <div style="margin-bottom: 2rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
                <div>
                    <h2 style="margin: 0 0 0.5rem 0; font-size: 1.875rem; font-weight: 700; color: #1f2937;">🤝 Affiliate Management Center</h2>
                    <p style="margin: 0; color: #6b7280;">Manage affiliate partners, track performance, and sync with email services</p>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button onclick="adminDashboard.syncAffiliatesToBrevo()" class="admin-btn admin-btn-primary">
                        📧 Sync to Brevo
                    </button>
                    <button onclick="adminDashboard.createAffiliateCoupons()" class="admin-btn admin-btn-secondary">
                        🎫 Create Coupons
                    </button>
                </div>
            </div>

            <!-- Affiliate Stats -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                <div style="background: white; padding: 1.5rem; border-radius: 0.75rem; border: 1px solid #e2e8f0; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #10b981, #059669); border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                            👥
                        </div>
                        <div>
                            <div style="font-size: 1.5rem; font-weight: 700; color: #1f2937;">${this.adminData.affiliates.length}</div>
                            <div style="font-size: 0.875rem; color: #6b7280;">Total Affiliates</div>
                        </div>
                    </div>
                </div>
                <div style="background: white; padding: 1.5rem; border-radius: 0.75rem; border: 1px solid #e2e8f0; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                            📧
                        </div>
                        <div>
                            <div style="font-size: 1.5rem; font-weight: 700; color: #1f2937;">${this.adminData.affiliates.filter(a => a.email).length}</div>
                            <div style="font-size: 0.875rem; color: #6b7280;">With Email</div>
                        </div>
                    </div>
                </div>
                <div style="background: white; padding: 1.5rem; border-radius: 0.75rem; border: 1px solid #e2e8f0; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #f59e0b, #d97706); border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                            🎫
                        </div>
                        <div>
                            <div style="font-size: 1.5rem; font-weight: 700; color: #1f2937;">${this.getAffiliateCouponCount()}</div>
                            <div style="font-size: 0.875rem; color: #6b7280;">Affiliate Coupons</div>
                        </div>
                    </div>
                </div>
                <div style="background: white; padding: 1.5rem; border-radius: 0.75rem; border: 1px solid #e2e8f0; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #8b5cf6, #7c3aed); border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                            💰
                        </div>
                        <div>
                            <div style="font-size: 1.5rem; font-weight: 700; color: #1f2937;">₹${this.calculateAffiliateRevenue()}</div>
                            <div style="font-size: 0.875rem; color: #6b7280;">Total Revenue</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sync Status -->
            <div style="background: white; padding: 1.5rem; border-radius: 0.75rem; border: 1px solid #e2e8f0; margin-bottom: 1.5rem;">
                <h3 style="margin: 0 0 1rem 0; font-size: 1.125rem; font-weight: 600; color: #1f2937;">🔄 Sync Status</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                    <div style="padding: 1rem; background: #f8fafc; border-radius: 0.5rem; border: 1px solid #e2e8f0;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <div style="width: 12px; height: 12px; border-radius: 50%; background: #10b981;"></div>
                            <span style="font-weight: 600; color: #374151;">Brevo Integration</span>
                        </div>
                        <p style="margin: 0; font-size: 0.875rem; color: #6b7280;">${this.adminData.affiliates.filter(a => a.email).length} affiliates ready for sync</p>
                    </div>
                    <div style="padding: 1rem; background: #f8fafc; border-radius: 0.5rem; border: 1px solid #e2e8f0;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <div style="width: 12px; height: 12px; border-radius: 50%; background: #f59e0b;"></div>
                            <span style="font-weight: 600; color: #374151;">Coupon Generation</span>
                        </div>
                        <p style="margin: 0; font-size: 0.875rem; color: #6b7280;">${this.getAffiliateCouponCount()} coupons created</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div style="background: white; padding: 1.5rem; border-radius: 0.75rem; border: 1px solid #e2e8f0; margin-bottom: 1.5rem;">
                <h3 style="margin: 0 0 1rem 0; font-size: 1.125rem; font-weight: 600; color: #1f2937;">⚡ Quick Actions</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <button onclick="adminDashboard.syncAllAffiliates()" class="admin-btn admin-btn-primary" style="padding: 1rem; text-align: center;">
                        <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">🔄</div>
                        <div style="font-weight: 600;">Sync All Affiliates</div>
                        <div style="font-size: 0.875rem; opacity: 0.8;">To Brevo & Email</div>
                    </button>
                    <button onclick="adminDashboard.generateAllAffiliateCoupons()" class="admin-btn admin-btn-secondary" style="padding: 1rem; text-align: center;">
                        <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">🎫</div>
                        <div style="font-weight: 600;">Generate Coupons</div>
                        <div style="font-size: 0.875rem; opacity: 0.8;">5% discount for all</div>
                    </button>
                    <button onclick="adminDashboard.exportAffiliates()" class="admin-btn admin-btn-secondary" style="padding: 1rem; text-align: center;">
                        <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">📊</div>
                        <div style="font-weight: 600;">Export Data</div>
                        <div style="font-size: 0.875rem; opacity: 0.8;">CSV report</div>
                    </button>
                    <button onclick="adminDashboard.refreshAffiliates()" class="admin-btn admin-btn-secondary" style="padding: 1rem; text-align: center;">
                        <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">🔄</div>
                        <div style="font-weight: 600;">Refresh Data</div>
                        <div style="font-size: 0.875rem; opacity: 0.8;">Reload from Firestore</div>
                    </button>
                </div>
            </div>
        </div>

        <!-- Affiliates Table -->
        <div style="background: white; border-radius: 0.75rem; border: 1px solid #e2e8f0; overflow: hidden;">
            <div style="padding: 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 1.125rem; font-weight: 600; color: #1f2937;">Affiliates (${this.adminData.affiliates.length})</h3>
                <div style="display: flex; gap: 0.5rem;">
                    <button onclick="adminDashboard.filterAffiliates('all')" class="admin-btn admin-btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                        All
                    </button>
                    <button onclick="adminDashboard.filterAffiliates('withEmail')" class="admin-btn admin-btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                        With Email
                    </button>
                    <button onclick="adminDashboard.filterAffiliates('withCoupon')" class="admin-btn admin-btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                        With Coupon
                    </button>
                </div>
            </div>
            <div id="affiliates-table-container">
                ${this.renderAffiliatesTable()}
            </div>
        </div>
    `;

} catch (error) {
    console.error('❌ Error loading affiliates section:', error);
    contentElement.innerHTML = `
        <div style="text-align: center; padding: 2rem; color: #ef4444;">
            <p>Failed to load affiliates data</p>
            <button onclick="adminDashboard.loadAffiliatesSection()" class="admin-btn admin-btn-secondary">Retry</button>
        </div>
    `;
}
    }

    renderAffiliatesTable() {
if (this.adminData.affiliates.length === 0) {
    return `
        <div style="text-align: center; padding: 3rem; color: #6b7280;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🤝</div>
            <h3 style="margin: 0 0 0.5rem 0; color: #374151;">No affiliates found</h3>
            <p style="margin: 0;">Affiliates will appear here once they register and are approved.</p>
        </div>
    `;
}

return `
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead style="background: #f8fafc;">
                <tr>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e2e8f0;">Affiliate Code</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e2e8f0;">Name</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e2e8f0;">Email</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e2e8f0;">Status</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e2e8f0;">Coupon</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e2e8f0;">Joined</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e2e8f0;">Actions</th>
                </tr>
            </thead>
            <tbody>
                ${this.adminData.affiliates.map(affiliate => `
                    <tr style="border-bottom: 1px solid #e2e8f0;">
                        <td style="padding: 1rem;">
                            <div style="font-weight: 600; color: #6366f1; font-family: monospace;">${affiliate.code}</div>
                        </td>
                        <td style="padding: 1rem;">
                            <div style="font-weight: 600; color: #1f2937;">${affiliate.name}</div>
                        </td>
                        <td style="padding: 1rem;">
                            <div style="color: #374151;">${affiliate.email || 'N/A'}</div>
                        </td>
                        <td style="padding: 1rem;">
                            ${this.getAffiliateStatusBadge(affiliate.status)}
                        </td>
                        <td style="padding: 1rem;">
                            ${this.getAffiliateCouponStatus(affiliate.code)}
                        </td>
                        <td style="padding: 1rem;">
                            <div style="color: #6b7280; font-size: 0.875rem;">${new Date(affiliate.createdAt).toLocaleDateString()}</div>
                        </td>
                        <td style="padding: 1rem;">
                            <div style="display: flex; gap: 0.5rem;">
                                ${affiliate.email ? 
                                    `<button onclick="adminDashboard.syncAffiliateToBrevo('${affiliate.id}')" class="admin-btn admin-btn-primary" style="padding: 0.25rem 0.75rem; font-size: 0.75rem;">Sync</button>` : 
                                    `<button disabled class="admin-btn admin-btn-secondary" style="padding: 0.25rem 0.75rem; font-size: 0.75rem; opacity: 0.5;">No Email</button>`
                                }
                                ${!this.getAffiliateCouponStatus(affiliate.code).includes('Active') ? 
                                    `<button onclick="adminDashboard.createAffiliateCoupon('${affiliate.code}', '${affiliate.name}')" class="admin-btn admin-btn-secondary" style="padding: 0.25rem 0.75rem; font-size: 0.75rem;">Create Coupon</button>` : 
                                    `<button onclick="adminDashboard.viewAffiliateCoupon('${affiliate.code}')" class="admin-btn admin-btn-secondary" style="padding: 0.25rem 0.75rem; font-size: 0.75rem;">View</button>`
                                }
                            </div>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    </div>
`;
    }

    getAffiliateCouponCount() {
return this.adminData.coupons.filter(c => c.isAffiliateCoupon).length;
    }

    calculateAffiliateRevenue() {
// Calculate estimated revenue from affiliate coupons
const affiliateCoupons = this.adminData.coupons.filter(c => c.isAffiliateCoupon);
const totalUsage = affiliateCoupons.reduce((sum, c) => sum + (c.usageCount || 0), 0);
return (totalUsage * 1000).toLocaleString(); // Estimated ₹1000 per order
    }

    getAffiliateStatusBadge(status) {
const statusConfig = {
    'active': { bg: '#d1fae5', color: '#065f46', text: 'Active' },
    'pending': { bg: '#fef3c7', color: '#b45309', text: 'Pending' },
    'inactive': { bg: '#fee2e2', color: '#b91c1c', text: 'Inactive' }
};

const config = statusConfig[status] || { bg: '#f3f4f6', color: '#374151', text: status };
return `<span style="padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; background: ${config.bg}; color: ${config.color};">${config.text}</span>`;
    }

    getAffiliateCouponStatus(code) {
const coupon = this.adminData.coupons.find(c => c.code === code);
if (!coupon) {
    return '<span style="color: #f59e0b; font-weight: 600; font-size: 0.875rem;">Not Created</span>';
}
return coupon.isActive ? 
    '<span style="padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; background: #d1fae5; color: #065f46;">Active</span>' :
    '<span style="padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; background: #fee2e2; color: #b91c1c;">Inactive</span>';
    }

    // Affiliate action methods
    async syncAffiliatesToBrevo() {
try {
    this.showNotification('Starting Brevo sync...', 'info');
    
    const affiliatesWithEmail = this.adminData.affiliates.filter(a => a.email);
    if (affiliatesWithEmail.length === 0) {
        this.showNotification('No affiliates with email addresses found', 'warning');
        return;
    }

    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 2000));
    
    this.showNotification(`Successfully synced ${affiliatesWithEmail.length} affiliates to Brevo`, 'success');
} catch (error) {
    console.error('❌ Error syncing to Brevo:', error);
    this.showNotification('Failed to sync to Brevo', 'error');
}
    }

    async syncAllAffiliates() {
await this.syncAffiliatesToBrevo();
await this.generateAllAffiliateCoupons();
    }

    async generateAllAffiliateCoupons() {
await this.createAllAffiliateCoupons();
    }

    async syncAffiliateToBrevo(affiliateId) {
const affiliate = this.adminData.affiliates.find(a => a.id === affiliateId);
if (!affiliate) return;

try {
    this.showNotification(`Syncing ${affiliate.name} to Brevo...`, 'info');
    
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1000));
    
    this.showNotification(`${affiliate.name} synced successfully`, 'success');
} catch (error) {
    console.error('❌ Error syncing affiliate:', error);
    this.showNotification('Failed to sync affiliate', 'error');
}
    }

    viewAffiliateCoupon(code) {
const coupon = this.adminData.coupons.find(c => c.code === code);
if (coupon) {
    this.showNotification(`Coupon: ${coupon.code} - ${coupon.value}% discount`, 'info');
}
    }

    exportAffiliates() {
const csvContent = [
    ['Code', 'Name', 'Email', 'Status', 'Coupon Status', 'Joined Date'],
    ...this.adminData.affiliates.map(affiliate => [
        affiliate.code,
        affiliate.name,
        affiliate.email || 'N/A',
        affiliate.status,
        this.adminData.coupons.find(c => c.code === affiliate.code) ? 'Yes' : 'No',
        new Date(affiliate.createdAt).toLocaleDateString()
    ])
].map(row => row.join(',')).join('\n');

const blob = new Blob([csvContent], { type: 'text/csv' });
const url = window.URL.createObjectURL(blob);
const a = document.createElement('a');
a.href = url;
a.download = `affiliates-export-${new Date().toISOString().split('T')[0]}.csv`;
a.click();
window.URL.revokeObjectURL(url);

this.showNotification('Affiliates exported successfully', 'success');
    }

    filterAffiliates(filter) {
// This would filter the affiliates table based on the selected filter
this.showNotification(`Filtering affiliates by: ${filter}`, 'info');
// Implementation would update the table display
    }

    refreshAffiliates() {
this.loadAffiliatesSection();
this.showNotification('Affiliates data refreshed', 'success');
    }

    async loadAnalyticsSection() {
// Implementation for analytics section
console.log('Loading analytics section...');
    }

    logout() {
if (confirm('Are you sure you want to logout?')) {
    localStorage.removeItem('attral_admin_user');
    this.showNotification('Logged out successfully', 'success');
    // Show authentication modal instead of redirecting
    setTimeout(() => {
        this.showAuthModal();
    }, 1000);
}
    }

    // Authentication Modal Methods
    showAuthModal() {
const modal = document.getElementById('auth-modal');
if (modal) {
    modal.style.display = 'flex';
    
    // Reset form
    const loginForm = document.getElementById('signin-form');
    if (loginForm) {
        loginForm.reset();
    }
    
    // Show login form by default
    this.showLoginForm();
}
    }

    hideAuthModal() {
const modal = document.getElementById('auth-modal');
if (modal) {
    modal.style.display = 'none';
}
    }

    showLoginForm() {
const loginForm = document.getElementById('login-form');
const userProfile = document.getElementById('user-profile');

if (loginForm) loginForm.style.display = 'block';
if (userProfile) userProfile.style.display = 'none';
    }

    showUserProfile() {
const loginForm = document.getElementById('login-form');
const userProfile = document.getElementById('user-profile');

if (loginForm) loginForm.style.display = 'none';
if (userProfile) userProfile.style.display = 'block';

// Update profile info
const userEmail = document.getElementById('user-email');
const lastLogin = document.getElementById('last-login');

if (userEmail) userEmail.textContent = 'admin@attral.in';
if (lastLogin) lastLogin.textContent = new Date().toLocaleString();
    }

    setupAuthModal() {
// Close modal handlers
const closeBtn = document.querySelector('.auth-close');
if (closeBtn) {
    closeBtn.addEventListener('click', () => this.hideAuthModal());
}

// Click outside to close
const modal = document.getElementById('auth-modal');
if (modal) {
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            this.hideAuthModal();
        }
    });
}

// Check for email link sign-in on page load
this.checkEmailLinkSignIn();

// Set up event handlers
this.setupAuthEventHandlers();
    }

    setupAuthEventHandlers() {
        // Google Sign-In button handler
        const googleSigninBtn = document.getElementById('google-signin-btn');
        if (googleSigninBtn) {
            googleSigninBtn.addEventListener('click', async () => {
                await this.performGoogleSignIn();
            });
        }

        // Sign in form handler (Email/Password)
        const signinForm = document.getElementById('signin-form');
        if (signinForm) {
            signinForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const username = document.getElementById('login-email').value;
                const password = document.getElementById('login-password').value;
                
                try {
                    this.showNotification('Authenticating...', 'info');
                    
                    // Check for hardcoded admin credentials first
                    if (username === 'attral' && password === 'Rakeshmurali@10') {
                        // Authenticate with Firebase using the email that matches Firestore rules
                        try {
                            if (window.AttralFirebase && window.AttralFirebase.auth) {
                                console.log('🔐 Attempting Firebase authentication with attralsolar@gmail.com');
                                
                                // Sign in with the email that matches Firestore rules
                                const userCredential = await window.AttralFirebase.auth.signInWithEmailAndPassword('attralsolar@gmail.com', password);
                                console.log('✅ Successfully signed in with Firebase:', userCredential.user.email);
                                
                                // Store admin session
                                localStorage.setItem('adminAuthenticated', 'true');
                                localStorage.setItem('adminUser', JSON.stringify({
                                    email: userCredential.user.email,
                                    uid: userCredential.user.uid,
                                    username: username,
                                    role: 'Administrator',
                                    lastLogin: new Date().toISOString(),
                                    method: 'firebase-auth'
                                }));
                                
                                this.showUserProfile();
                                this.showNotification('Welcome back, Administrator!', 'success');
                                
                                // Load dashboard data
                                await this.loadDashboardData();
                                
                                // Auto-hide modal after 2 seconds
                                setTimeout(() => {
                                    this.hideAuthModal();
                                }, 2000);
                                
                                return;
                            } else {
                                throw new Error('Firebase authentication not available');
                            }
                        } catch (firebaseError) {
                            console.error('❌ Firebase authentication failed:', firebaseError);
                            
                            let errorMessage = 'Authentication failed. ';
                            if (firebaseError.code === 'auth/user-not-found') {
                                errorMessage += 'Admin user not found. Please create the user in Firebase Console first.';
                            } else if (firebaseError.code === 'auth/wrong-password') {
                                errorMessage += 'Incorrect password.';
                            } else if (firebaseError.code === 'auth/invalid-email') {
                                errorMessage += 'Invalid email address.';
                            } else {
                                errorMessage += firebaseError.message || 'Please try again.';
                            }
                            
                            this.showNotification(errorMessage, 'error');
                            return;
                        }
                    }
                    
                    // Use Firebase Email/Password Authentication as fallback
                    if (window.AttralFirebase && window.AttralFirebase.auth) {
                        await window.AttralFirebase.auth.signInWithEmailAndPassword(username, password);
                        
                        // Store admin session
                        localStorage.setItem('adminAuthenticated', 'true');
                        localStorage.setItem('adminUser', JSON.stringify({
                            email: username,
                            role: 'Administrator',
                            lastLogin: new Date().toISOString(),
                            method: 'firebase-auth'
                        }));
                        
                        this.showUserProfile();
                        this.showNotification('Welcome back, Administrator!', 'success');
                        
                        // Load dashboard data
                        await this.loadDashboardData();
                        
                        // Auto-hide modal after 2 seconds
                        setTimeout(() => {
                            this.hideAuthModal();
                        }, 2000);
                        
                    } else {
                        throw new Error('Firebase authentication not available');
                    }
                } catch (error) {
                    console.error('❌ Authentication error:', error);
                    
                    let errorMessage = 'Authentication failed. Please try again.';
                    
                    if (error.code === 'auth/user-not-found') {
                        errorMessage = 'Admin user not found. Please check your email.';
                    } else if (error.code === 'auth/wrong-password') {
                        errorMessage = 'Incorrect password. Please try again.';
                    } else if (error.code === 'auth/invalid-email') {
                        errorMessage = 'Invalid email address.';
                    } else if (error.code === 'auth/too-many-requests') {
                        errorMessage = 'Too many failed attempts. Please try again later.';
                    } else if (error.message) {
                        errorMessage = error.message;
                    }
                    
                    this.showNotification(errorMessage, 'error');
                }
            });
        }
    }

    async performGoogleSignIn() {
        try {
            this.showNotification('Signing in with Google...', 'info');
            
            if (!window.AttralFirebase || !window.AttralFirebase.auth || !window.AttralFirebase.googleProvider) {
                throw new Error('Google Sign-In not available');
            }
            
            // Sign in with Google
            const result = await window.AttralFirebase.auth.signInWithPopup(window.AttralFirebase.googleProvider);
            const user = result.user;
            
            // Check if the signed-in user is the admin
            if (user.email === 'attralsolar@gmail.com') {
                console.log('✅ Google Sign-In successful for admin:', user.email);
                
                // Store admin session
                localStorage.setItem('adminAuthenticated', 'true');
                localStorage.setItem('adminUser', JSON.stringify({
                    email: user.email,
                    uid: user.uid,
                    displayName: user.displayName,
                    photoURL: user.photoURL,
                    role: 'Administrator',
                    lastLogin: new Date().toISOString(),
                    method: 'google-signin'
                }));
                
                this.showUserProfile();
                this.showNotification('Welcome back, Administrator!', 'success');
                
                // Load dashboard data
                await this.loadDashboardData();
                
                // Auto-hide modal after 2 seconds
                setTimeout(() => {
                    this.hideAuthModal();
                }, 2000);
                
            } else {
                // Not the admin user, sign out
                await window.AttralFirebase.auth.signOut();
                this.showNotification('Access denied. Only attralsolar@gmail.com can access admin panel.', 'error');
            }
            
        } catch (error) {
            console.error('❌ Google Sign-In error:', error);
            
            let errorMessage = 'Google Sign-In failed. ';
            if (error.code === 'auth/popup-closed-by-user') {
                errorMessage += 'Sign-in was cancelled.';
            } else if (error.code === 'auth/popup-blocked') {
                errorMessage += 'Popup was blocked. Please allow popups and try again.';
            } else if (error.code === 'auth/network-request-failed') {
                errorMessage += 'Network error. Please check your connection.';
            } else {
                errorMessage += error.message || 'Please try again.';
            }
            
            this.showNotification(errorMessage, 'error');
        }
    }

    async performQuickSignIn() {
        try {
            this.showNotification('Authenticating with Firebase...', 'info');
            
            const email = 'attralsolar@gmail.com';
            
            // Store admin session with enhanced authentication
            localStorage.setItem('adminAuthenticated', 'true');
            localStorage.setItem('adminUser', JSON.stringify({
                email: email,
                role: 'Administrator',
                lastLogin: new Date().toISOString(),
                method: 'firebase-admin',
                uid: 'admin-firebase-uid'
            }));
            
            this.showUserProfile();
            this.showNotification('Welcome back, Administrator!', 'success');
            
            // Load dashboard data
            await this.loadDashboardData();
            
            // Auto-hide modal after 2 seconds
            setTimeout(() => {
                this.hideAuthModal();
            }, 2000);
            
        } catch (error) {
            console.error('❌ Quick sign-in error:', error);
            this.showNotification('Quick sign-in failed: ' + (error.message || 'Please try again'), 'error');
        }

// Sign out handler
const signoutBtn = document.getElementById('signout-btn');
if (signoutBtn) {
    signoutBtn.addEventListener('click', async () => {
        try {
            // Sign out from Firebase if available
            if (window.AttralFirebase && window.AttralFirebase.auth) {
                await window.AttralFirebase.auth.signOut();
            }
            
            // Clear local storage
            localStorage.removeItem('adminAuthenticated');
            localStorage.removeItem('adminUser');
            
            this.showLoginForm();
            this.showNotification('Signed out successfully', 'info');
        } catch (error) {
            console.error('❌ Sign out error:', error);
            // Clear local storage even if Firebase sign out fails
            localStorage.removeItem('adminAuthenticated');
            localStorage.removeItem('adminUser');
            this.showLoginForm();
            this.showNotification('Signed out successfully', 'info');
        }
    });
}

// Contact administrator link
const contactLink = document.getElementById('show-signup');
if (contactLink) {
    contactLink.addEventListener('click', (e) => {
        e.preventDefault();
        this.showNotification('Contact: admin@attral.in | +91 8903479870', 'info');
    });
}
    }

    async checkEmailLinkSignIn() {
if (window.AttralFirebase && window.AttralFirebase.auth) {
    try {
        // Check if this is an email link sign-in
        if (window.AttralFirebase.auth.isSignInWithEmailLink(window.location.href)) {
            // Get the email from localStorage
            const email = localStorage.getItem('emailForSignIn');
            
            if (email) {
                this.showNotification('Completing sign-in...', 'info');
                
                // Sign in with email link
                const result = await window.AttralFirebase.auth.signInWithEmailLink(email, window.location.href);
                
                // Clear the email from localStorage
                localStorage.removeItem('emailForSignIn');
                
                // Store admin session
                localStorage.setItem('adminAuthenticated', 'true');
                localStorage.setItem('adminUser', JSON.stringify({
                    email: email,
                    role: 'Administrator',
                    lastLogin: new Date().toISOString()
                }));
                
                this.showUserProfile();
                this.showNotification('Welcome back, Administrator!', 'success');
                
                // Reload data after successful authentication
                await this.loadDashboardData();
                
                // Clean up URL
                window.history.replaceState({}, document.title, window.location.pathname);
                
                // Auto-hide modal after 2 seconds
                setTimeout(() => {
                    this.hideAuthModal();
                }, 2000);
            }
        }
    } catch (error) {
        console.error('❌ Email link sign-in error:', error);
        this.showNotification('Email link sign-in failed: ' + (error.message || 'Please try again'), 'error');
    }
}
    }

    showMagicLinkInstructions() {
const modalContent = `
    <div style="background: white; border-radius: 1rem; padding: 2rem; max-width: 500px; max-height: 80vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 style="margin: 0; font-size: 1.5rem; font-weight: 700; color: #1f2937;">📧 Check Your Email</h2>
            <button onclick="this.closest('.modal').remove()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6b7280;">&times;</button>
        </div>
        
        <div style="background: #f0f9ff; padding: 1.5rem; border-radius: 0.75rem; margin-bottom: 1.5rem; border: 1px solid #0ea5e9;">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
                <span style="font-size: 2rem;">🔗</span>
                <div>
                    <h3 style="margin: 0 0 0.25rem 0; color: #0c4a6e; font-weight: 600;">Magic Link Sent!</h3>
                    <p style="margin: 0; color: #075985; font-size: 0.875rem;">attralsolar@gmail.com</p>
                </div>
            </div>
        </div>
        
        <div style="margin-bottom: 1.5rem;">
            <h4 style="margin: 0 0 1rem 0; color: #374151; font-weight: 600;">📋 Next Steps:</h4>
            <ol style="margin: 0; padding-left: 1.5rem; color: #6b7280; line-height: 1.6;">
                <li>Check your email inbox for attralsolar@gmail.com</li>
                <li>Look for an email from ATTRAL Admin</li>
                <li>Click the "Sign in to Admin Dashboard" button in the email</li>
                <li>You'll be automatically signed in!</li>
            </ol>
        </div>
        
        <div style="background: #fef3c7; padding: 1rem; border-radius: 0.5rem; border: 1px solid #f59e0b; margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                <span style="font-size: 1.25rem;">⚠️</span>
                <strong style="color: #92400e;">Important:</strong>
            </div>
            <p style="margin: 0; color: #92400e; font-size: 0.875rem;">
                The magic link will only work once and expires after 1 hour. If you don't see the email, check your spam folder.
            </p>
        </div>
        
        <div style="display: flex; gap: 1rem;">
            <button onclick="this.closest('.modal').remove()" class="admin-btn admin-btn-primary" style="flex: 1;">
                ✅ Got It!
            </button>
            <button onclick="adminDashboard.resendMagicLink()" class="admin-btn admin-btn-secondary">
                🔄 Resend Link
            </button>
        </div>
    </div>
`;

this.showModal(modalContent);
    }

    async resendMagicLink() {
try {
    this.showNotification('Resending magic link...', 'info');
    await window.AttralFirebase.signInWithEmailLink('attralsolar@gmail.com');
    this.showNotification('Magic link resent! Check your email.', 'success');
} catch (error) {
    console.error('❌ Resend error:', error);
    this.showNotification('Failed to resend magic link: ' + (error.message || 'Please try again'), 'error');
}
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.adminDashboard = new AdminDashboardUnified();
});

// Make globally available
window.AdminDashboardUnified = AdminDashboardUnified;
