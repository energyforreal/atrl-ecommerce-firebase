/**
 * 🎛️ ATTRAL Admin System - Unified Admin Dashboard
 * Comprehensive admin management system with proper Firestore integration
 */

class AttralAdminSystem {
    constructor() {
        this.currentUser = null;
        this.isAuthenticated = false;
        this.firebaseReady = false;
        this.adminData = {
            orders: [],
            users: [],
            messages: [],
            affiliates: [],
            products: [],
            coupons: [],
            analytics: {}
        };
        
        this.init();
    }

    async init() {
        console.log('🚀 Initializing ATTRAL Admin System...');
        
        // Wait for Firebase to be ready
        await this.waitForFirebase();
        
        // Initialize admin authentication
        await this.initializeAuth();
        
        // Load admin data
        await this.loadAdminData();
        
        // Setup real-time listeners
        this.setupRealtimeListeners();
        
        console.log('✅ Admin System initialized successfully');
    }

    async waitForFirebase() {
        let attempts = 0;
        const maxAttempts = 50; // 5 seconds max wait
        
        while (attempts < maxAttempts) {
            if (window.AttralFirebase && window.AttralFirebase.db) {
                this.firebaseReady = true;
                console.log('✅ Firebase is ready for admin system');
                return;
            }
            attempts++;
            console.log(`⏳ Waiting for Firebase... attempt ${attempts}/${maxAttempts}`);
            await new Promise(resolve => setTimeout(resolve, 100));
        }
        
        throw new Error('Firebase failed to load for admin system');
    }

    async initializeAuth() {
        // Check if user is already authenticated
        const adminUser = localStorage.getItem('attral_admin_user');
        if (adminUser) {
            try {
                this.currentUser = JSON.parse(adminUser);
                this.isAuthenticated = true;
                console.log('✅ Admin user already authenticated:', this.currentUser.username);
                return;
            } catch (error) {
                console.error('❌ Error parsing admin user data:', error);
                localStorage.removeItem('attral_admin_user');
            }
        }

        // Show login form if not authenticated
        this.showAdminLogin();
    }

    showAdminLogin() {
        const loginHtml = `
            <div id="admin-login-overlay" style="
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.8);
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: center;
            ">
                <div style="
                    background: white;
                    padding: 2rem;
                    border-radius: 12px;
                    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
                    max-width: 400px;
                    width: 90%;
                ">
                    <h2 style="text-align: center; margin-bottom: 1.5rem; color: #1f2937;">
                        🔐 Admin Access
                    </h2>
                    <form id="admin-login-form">
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Username</label>
                            <input type="text" id="admin-username" required 
                                   style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px;"
                                   placeholder="Enter admin username">
                        </div>
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Password</label>
                            <input type="password" id="admin-password" required 
                                   style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px;"
                                   placeholder="Enter admin password">
                        </div>
                        <button type="submit" style="
                            width: 100%;
                            background: linear-gradient(135deg, #667eea, #764ba2);
                            color: white;
                            border: none;
                            padding: 0.75rem;
                            border-radius: 8px;
                            font-weight: 600;
                            cursor: pointer;
                        ">
                            Login to Admin Panel
                        </button>
                    </form>
                    <div style="margin-top: 1rem; text-align: center; color: #6b7280; font-size: 0.875rem;">
                        <p><strong>Default Credentials:</strong></p>
                        <p>Username: <code>attral</code></p>
                        <p>Password: <code>Rakeshmurali@10</code></p>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', loginHtml);
        
        // Handle login form submission
        document.getElementById('admin-login-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.handleAdminLogin();
        });
    }

    async handleAdminLogin() {
        const username = document.getElementById('admin-username').value;
        const password = document.getElementById('admin-password').value;
        
        // Check credentials
        if (username === 'attral' && password === 'Rakeshmurali@10') {
            this.currentUser = {
                username: username,
                loginTime: new Date().toISOString(),
                role: 'admin'
            };
            
            this.isAuthenticated = true;
            localStorage.setItem('attral_admin_user', JSON.stringify(this.currentUser));
            
            // Remove login overlay
            document.getElementById('admin-login-overlay').remove();
            
            console.log('✅ Admin login successful');
            this.showNotification('Welcome to Admin Panel!', 'success');
            
            // Load admin data after successful login
            await this.loadAdminData();
        } else {
            this.showNotification('Invalid credentials. Please try again.', 'error');
        }
    }

    async loadAdminData() {
        if (!this.firebaseReady || !this.isAuthenticated) {
            console.log('⏳ Skipping data load - Firebase not ready or not authenticated');
            return;
        }

        try {
            console.log('📊 Loading admin data from Firestore...');
            
            await Promise.all([
                this.loadOrders(),
                this.loadUsers(),
                this.loadMessages(),
                this.loadAffiliates(),
                this.loadProducts(),
                this.loadCoupons(),
                this.loadAnalytics()
            ]);
            
            console.log('✅ All admin data loaded successfully');
            this.updateAdminDashboard();
            
        } catch (error) {
            console.error('❌ Error loading admin data:', error);
            this.showNotification('Failed to load admin data: ' + error.message, 'error');
        }
    }

    async loadOrders() {
        try {
            const ordersSnapshot = await window.AttralFirebase.db
                .collection('orders')
                .orderBy('created_at', 'desc')
                .get();

            this.adminData.orders = [];
            ordersSnapshot.forEach(doc => {
                const orderData = doc.data();
                this.adminData.orders.push({
                    id: doc.id,
                    orderId: orderData.order_id || doc.id,
                    customerName: orderData.customer_name || orderData.name || 'Unknown',
                    customerEmail: orderData.customer_email || orderData.email || '',
                    totalAmount: Number(orderData.total_amount || orderData.amount || 0),
                    status: orderData.status || 'pending',
                    paymentStatus: orderData.payment_status || 'pending',
                    createdAt: orderData.created_at ? orderData.created_at.toDate() : new Date(),
                    items: orderData.items || [],
                    shippingAddress: orderData.shipping_address || {}
                });
            });
            
            console.log(`📦 Loaded ${this.adminData.orders.length} orders`);
        } catch (error) {
            console.error('❌ Error loading orders:', error);
        }
    }

    async loadUsers() {
        try {
            const usersSnapshot = await window.AttralFirebase.db
                .collection('users')
                .orderBy('created_at', 'desc')
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
                    createdAt: userData.created_at ? userData.created_at.toDate() : new Date(),
                    lastLoginAt: userData.lastLoginAt ? userData.lastLoginAt.toDate() : null
                });
            });
            
            console.log(`👥 Loaded ${this.adminData.users.length} users`);
        } catch (error) {
            console.error('❌ Error loading users:', error);
        }
    }

    async loadMessages() {
        try {
            const messagesSnapshot = await window.AttralFirebase.db
                .collection('contact_messages')
                .orderBy('timestamp', 'desc')
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
                    createdAt: messageData.timestamp ? messageData.timestamp.toDate() : new Date(),
                    updatedAt: messageData.updatedAt ? messageData.updatedAt.toDate() : null
                });
            });
            
            console.log(`💬 Loaded ${this.adminData.messages.length} messages`);
        } catch (error) {
            console.error('❌ Error loading messages:', error);
        }
    }

    async loadAffiliates() {
        try {
            const affiliatesSnapshot = await window.AttralFirebase.db
                .collection('affiliates')
                .orderBy('created_at', 'desc')
                .get();

            this.adminData.affiliates = [];
            affiliatesSnapshot.forEach(doc => {
                const affiliateData = doc.data();
                this.adminData.affiliates.push({
                    id: doc.id,
                    uid: affiliateData.uid || doc.id,
                    name: affiliateData.displayName || affiliateData.name || 'Unknown',
                    email: affiliateData.email || '',
                    code: affiliateData.code || '',
                    status: affiliateData.status || 'active',
                    commissionRate: affiliateData.commission_rate || 5,
                    totalEarnings: affiliateData.total_earnings || 0,
                    createdAt: affiliateData.created_at ? affiliateData.created_at.toDate() : new Date()
                });
            });
            
            console.log(`💰 Loaded ${this.adminData.affiliates.length} affiliates`);
        } catch (error) {
            console.error('❌ Error loading affiliates:', error);
        }
    }

    async loadProducts() {
        try {
            const productsSnapshot = await window.AttralFirebase.db
                .collection('products')
                .orderBy('created_at', 'desc')
                .get();

            this.adminData.products = [];
            productsSnapshot.forEach(doc => {
                const productData = doc.data();
                this.adminData.products.push({
                    id: doc.id,
                    name: productData.name || 'Unknown Product',
                    price: Number(productData.price || 0),
                    category: productData.category || 'uncategorized',
                    status: productData.status || 'active',
                    stock: Number(productData.stock || 0),
                    description: productData.description || '',
                    images: productData.images || [],
                    createdAt: productData.created_at ? productData.created_at.toDate() : new Date()
                });
            });
            
            console.log(`🛍️ Loaded ${this.adminData.products.length} products`);
        } catch (error) {
            console.error('❌ Error loading products:', error);
        }
    }

    async loadCoupons() {
        try {
            const couponsSnapshot = await window.AttralFirebase.db
                .collection('coupons')
                .orderBy('created_at', 'desc')
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
                    createdAt: couponData.created_at ? couponData.created_at.toDate() : new Date()
                });
            });
            
            console.log(`🎫 Loaded ${this.adminData.coupons.length} coupons`);
        } catch (error) {
            console.error('❌ Error loading coupons:', error);
        }
    }

    async loadAnalytics() {
        try {
            // Calculate analytics from loaded data
            const totalRevenue = this.adminData.orders
                .filter(order => order.paymentStatus === 'paid' || order.status === 'completed')
                .reduce((sum, order) => sum + order.totalAmount, 0);

            const totalOrders = this.adminData.orders.length;
            const totalUsers = this.adminData.users.length;
            const totalAffiliates = this.adminData.affiliates.length;
            const pendingOrders = this.adminData.orders.filter(order => order.status === 'pending').length;
            const newMessages = this.adminData.messages.filter(message => message.status === 'new').length;

            this.adminData.analytics = {
                totalRevenue,
                totalOrders,
                totalUsers,
                totalAffiliates,
                pendingOrders,
                newMessages,
                conversionRate: totalUsers > 0 ? (totalOrders / totalUsers * 100).toFixed(2) : 0,
                averageOrderValue: totalOrders > 0 ? (totalRevenue / totalOrders).toFixed(2) : 0
            };
            
            console.log('📈 Analytics calculated:', this.adminData.analytics);
        } catch (error) {
            console.error('❌ Error calculating analytics:', error);
        }
    }

    updateAdminDashboard() {
        // Update dashboard statistics
        this.updateDashboardStats();
        
        // Update recent orders
        this.updateRecentOrders();
        
        // Update recent users
        this.updateRecentUsers();
        
        // Update recent messages
        this.updateRecentMessages();
        
        // Update navigation badges
        this.updateNavigationBadges();
    }

    updateDashboardStats() {
        const stats = this.adminData.analytics;
        
        // Update stat cards
        this.updateElement('total-revenue', `₹${stats.totalRevenue.toLocaleString()}`);
        this.updateElement('total-orders', stats.totalOrders);
        this.updateElement('total-users', stats.totalUsers);
        this.updateElement('total-affiliates', stats.totalAffiliates);
        this.updateElement('pending-orders', stats.pendingOrders);
        this.updateElement('new-messages', stats.newMessages);
    }

    updateRecentOrders() {
        const container = document.getElementById('recent-orders-list');
        if (!container) return;

        const recentOrders = this.adminData.orders.slice(0, 5);
        
        if (recentOrders.length === 0) {
            container.innerHTML = '<div class="text-center text-gray-500 py-4">No orders found</div>';
            return;
        }

        container.innerHTML = recentOrders.map(order => `
            <div class="flex items-center justify-between p-4 border-b border-gray-200 hover:bg-gray-50">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">Order #${order.orderId}</p>
                        <p class="text-xs text-gray-500">${order.customerName}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-sm font-medium text-gray-900">₹${order.totalAmount.toLocaleString()}</p>
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                        order.paymentStatus === 'paid' || order.status === 'completed' ? 'bg-green-100 text-green-800' :
                        order.paymentStatus === 'pending' || order.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                        'bg-red-100 text-red-800'
                    }">
                        ${order.status}
                    </span>
                </div>
            </div>
        `).join('');
    }

    updateRecentUsers() {
        const container = document.getElementById('recent-users-list');
        if (!container) return;

        const recentUsers = this.adminData.users.slice(0, 5);
        
        if (recentUsers.length === 0) {
            container.innerHTML = '<div class="text-center text-gray-500 py-4">No users found</div>';
            return;
        }

        container.innerHTML = recentUsers.map(user => `
            <div class="flex items-center justify-between p-4 border-b border-gray-200 hover:bg-gray-50">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">${user.name}</p>
                        <p class="text-xs text-gray-500">${user.email}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-500">${new Date(user.createdAt).toLocaleDateString()}</p>
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                        user.isAffiliate ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'
                    }">
                        ${user.isAffiliate ? 'Affiliate' : 'Customer'}
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
            container.innerHTML = '<div class="text-center text-gray-500 py-4">No messages found</div>';
            return;
        }

        container.innerHTML = recentMessages.map(message => `
            <div class="flex items-center justify-between p-4 border-b border-gray-200 hover:bg-gray-50">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">${message.name}</p>
                        <p class="text-xs text-gray-500 truncate">${message.message.substring(0, 50)}...</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-500">${new Date(message.createdAt).toLocaleDateString()}</p>
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                        message.status === 'new' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'
                    }">
                        ${message.status}
                    </span>
                </div>
            </div>
        `).join('');
    }

    updateNavigationBadges() {
        // Update navigation badges with real data
        this.updateElement('new-orders-count', this.adminData.analytics.pendingOrders);
        this.updateElement('unread-messages-count', this.adminData.analytics.newMessages);
    }

    updateElement(id, value) {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = value;
        }
    }

    setupRealtimeListeners() {
        if (!this.firebaseReady) return;

        console.log('👂 Setting up real-time listeners...');

        // Listen for new orders
        window.AttralFirebase.db.collection('orders')
            .orderBy('created_at', 'desc')
            .limit(10)
            .onSnapshot((snapshot) => {
                console.log('📦 Real-time update: orders changed');
                this.loadOrders().then(() => this.updateAdminDashboard());
            });

        // Listen for new messages
        window.AttralFirebase.db.collection('contact_messages')
            .orderBy('timestamp', 'desc')
            .limit(10)
            .onSnapshot((snapshot) => {
                console.log('💬 Real-time update: messages changed');
                this.loadMessages().then(() => this.updateAdminDashboard());
            });

        // Listen for new users
        window.AttralFirebase.db.collection('users')
            .orderBy('created_at', 'desc')
            .limit(10)
            .onSnapshot((snapshot) => {
                console.log('👥 Real-time update: users changed');
                this.loadUsers().then(() => this.updateAdminDashboard());
            });
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
            type === 'success' ? 'bg-green-500 text-white' :
            type === 'error' ? 'bg-red-500 text-white' :
            type === 'warning' ? 'bg-yellow-500 text-white' :
            'bg-blue-500 text-white'
        }`;
        notification.textContent = message;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 5000);
    }

    // Admin logout
    logout() {
        this.isAuthenticated = false;
        this.currentUser = null;
        localStorage.removeItem('attral_admin_user');
        this.showNotification('Logged out successfully', 'success');
        window.location.reload();
    }

    // Get admin data for external use
    getAdminData() {
        return this.adminData;
    }

    // Authenticate admin (for external login forms)
    async authenticateAdmin(username, password) {
        try {
            // Check credentials
            if (username === 'attral' && password === 'Rakeshmurali@10') {
                this.currentUser = {
                    username: username,
                    loginTime: new Date().toISOString(),
                    role: 'admin'
                };
                
                this.isAuthenticated = true;
                localStorage.setItem('attral_admin_user', JSON.stringify(this.currentUser));
                
                console.log('✅ Admin authentication successful via external method');
                
                return {
                    success: true,
                    message: 'Authentication successful',
                    user: this.currentUser
                };
            } else {
                return {
                    success: false,
                    message: 'Invalid username or password'
                };
            }
        } catch (error) {
            console.error('❌ Admin authentication error:', error);
            return {
                success: false,
                message: 'Authentication failed: ' + error.message
            };
        }
    }

    // Update order status
    async updateOrderStatus(orderId, status) {
        try {
            await window.AttralFirebase.db.collection('orders').doc(orderId).update({
                status: status,
                updated_at: new Date()
            });
            
            this.showNotification(`Order ${orderId} status updated to ${status}`, 'success');
            await this.loadOrders();
            this.updateAdminDashboard();
        } catch (error) {
            console.error('❌ Error updating order status:', error);
            this.showNotification('Failed to update order status', 'error');
        }
    }

    // Update message status
    async updateMessageStatus(messageId, status) {
        try {
            await window.AttralFirebase.db.collection('contact_messages').doc(messageId).update({
                status: status,
                updatedAt: new Date()
            });
            
            this.showNotification(`Message status updated to ${status}`, 'success');
            await this.loadMessages();
            this.updateAdminDashboard();
        } catch (error) {
            console.error('❌ Error updating message status:', error);
            this.showNotification('Failed to update message status', 'error');
        }
    }
}

// Initialize admin system when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.AttralAdmin = new AttralAdminSystem();
});

// Make admin system globally available
window.AttralAdminSystem = AttralAdminSystem;
