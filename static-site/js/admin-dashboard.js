// Admin Dashboard JavaScript
class AdminDashboard {
    constructor() {
        this.currentUser = null;
        this.stats = {
            totalOrders: 0,
            totalRevenue: 0,
            totalUsers: 0,
            totalAffiliates: 0,
            pendingOrders: 0,
            completedOrders: 0
        };
        this.init();
    }

    async init() {
        await this.loadDashboardData();
        this.setupEventListeners();
        this.startRealTimeUpdates();
    }

    updateUserInfo() {
        const userInfo = document.getElementById('user-info');
        if (userInfo && this.currentUser) {
            userInfo.innerHTML = `
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full flex items-center justify-center">
                        <span class="text-white font-semibold text-sm">${this.currentUser.name.charAt(0).toUpperCase()}</span>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">${this.currentUser.name}</p>
                        <p class="text-xs text-gray-500">Admin</p>
                    </div>
                </div>
            `;
        }
    }

    async loadDashboardData() {
        try {
            await Promise.all([
                this.loadStats(),
                this.loadRecentOrders(),
                this.loadRecentUsers(),
                this.loadRecentMessages(),
                this.loadAnalytics()
            ]);
        } catch (error) {
            console.error('Failed to load dashboard data:', error);
            this.showNotification('Failed to load dashboard data', 'error');
        }
    }

    async loadStats() {
        try {
            const fb = window.AttralFirebase;
            if (!fb || !fb.db) { return; }

            const db = fb.db;

            // Orders count and revenue
            const ordersSnap = await db.collection('orders').get();
            let totalRevenue = 0;
            let pendingOrders = 0;
            let completedOrders = 0;
            ordersSnap.forEach(doc => {
                const o = doc.data() || {};
                const paymentStatus = (o.payment_status || '').toLowerCase();
                const amount = Number(o.total_amount || o.amount || 0);
                // Consider revenue only for paid/captured orders
                if (paymentStatus === 'paid' || paymentStatus === 'captured' || paymentStatus === 'completed') {
                    totalRevenue += amount;
                    completedOrders += 1;
                } else if (paymentStatus === 'pending') {
                    pendingOrders += 1;
                }
            });

            // Users count
            const usersSnap = await db.collection('users').get();
            // Affiliates count (users with is_affiliate = true)
            const affiliatesSnap = await db.collection('users').where('is_affiliate', '==', true).get();

            this.stats = {
                totalOrders: ordersSnap.size,
                totalRevenue,
                totalUsers: usersSnap.size,
                totalAffiliates: affiliatesSnap.size,
                pendingOrders,
                completedOrders
            };

            this.updateStatsDisplay();
        } catch (error) {
            console.error('Failed to load stats:', error);
        }
    }

    updateStatsDisplay() {
        const statElements = {
            'total-orders': this.stats.totalOrders,
            'total-revenue': `₹${this.stats.totalRevenue.toLocaleString()}`,
            'total-users': this.stats.totalUsers,
            'total-affiliates': this.stats.totalAffiliates,
            'pending-orders': this.stats.pendingOrders,
            'completed-orders': this.stats.completedOrders
        };

        Object.entries(statElements).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = value;
            }
        });
    }

    async loadRecentOrders() {
        try {
            const fb = window.AttralFirebase;
            if (!fb || !fb.db) { return; }
            const db = fb.db;

            const snap = await db
                .collection('orders')
                .orderBy('created_at', 'desc')
                .limit(5)
                .get();

            const orders = [];
            snap.forEach(doc => {
                const o = doc.data() || {};
                orders.push({
                    order_id: o.order_id || doc.id,
                    customer_name: o.customer_name || o.name || '',
                    total_amount: Number(o.total_amount || o.amount || 0),
                    status: this.mapDisplayStatus(o.status, o.payment_status),
                    payment_status: (o.payment_status || '').toLowerCase(),
                    created_at: o.created_at
                });
            });

            this.displayRecentOrders(orders);
        } catch (error) {
            console.error('Failed to load recent orders:', error);
        }
    }

    displayRecentOrders(orders) {
        const container = document.getElementById('recent-orders-list');
        if (!container) return;

        container.innerHTML = orders.map(order => `
            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">Order #${order.order_id}</p>
                        <p class="text-xs text-gray-500">${order.customer_name}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-sm font-medium text-gray-900">₹${order.total_amount}</p>
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                        order.payment_status === 'paid' || order.status === 'completed' ? 'bg-green-100 text-green-800' :
                        order.payment_status === 'pending' || order.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                        'bg-red-100 text-red-800'
                    }">
                        ${order.status}
                    </span>
                </div>
            </div>
        `).join('');
    }

    async loadRecentUsers() {
        try {
            const fb = window.AttralFirebase;
            if (!fb || !fb.db) { return; }
            const db = fb.db;

            const snap = await db
                .collection('users')
                .orderBy('created_at', 'desc')
                .limit(5)
                .get();

            const users = [];
            snap.forEach(doc => {
                const u = doc.data() || {};
                users.push({
                    name: u.name || u.displayName || 'User',
                    email: u.email || '',
                    created_at: u.created_at || Date.now(),
                    is_affiliate: !!u.is_affiliate
                });
            });

            this.displayRecentUsers(users);
        } catch (error) {
            console.error('Failed to load recent users:', error);
        }
    }

    displayRecentUsers(users) {
        const container = document.getElementById('recent-users-list');
        if (!container) return;

        container.innerHTML = users.map(user => `
            <div class="flex items-center justify-between p-4 border-b border-gray-200">
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
                    <p class="text-xs text-gray-500">${new Date(user.created_at).toLocaleDateString()}</p>
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                        user.is_affiliate ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'
                    }">
                        ${user.is_affiliate ? 'Affiliate' : 'Customer'}
                    </span>
                </div>
            </div>
        `).join('');
    }

    async loadRecentMessages() {
        try {
            const fb = window.AttralFirebase;
            if (!fb || !fb.db) { return; }
            const db = fb.db;

            const snap = await db
                .collection('messages')
                .orderBy('created_at', 'desc')
                .limit(5)
                .get();

            const messages = [];
            snap.forEach(doc => {
                const m = doc.data() || {};
                messages.push({
                    name: m.name || m.from || 'User',
                    message: m.message || m.text || '',
                    created_at: m.created_at || Date.now(),
                    is_read: !!m.is_read
                });
            });

            this.displayRecentMessages(messages);
        } catch (error) {
            console.error('Failed to load recent messages:', error);
        }
    }

    displayRecentMessages(messages) {
        const container = document.getElementById('recent-messages');
        if (!container) return;

        container.innerHTML = messages.map(message => `
            <div class="flex items-center justify-between p-4 border-b border-gray-200">
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
                    <p class="text-xs text-gray-500">${new Date(message.created_at).toLocaleDateString()}</p>
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                        message.is_read ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                    }">
                        ${message.is_read ? 'Read' : 'Unread'}
                    </span>
                </div>
            </div>
        `).join('');
    }

    async loadAnalytics() {
        try {
            // Example placeholder: derive simple analytics from Firestore orders
            const fb = window.AttralFirebase;
            if (!fb || !fb.db) { return; }
            const db = fb.db;

            const snap = await db.collection('orders').orderBy('created_at', 'desc').limit(30).get();
            const analytics = { revenue_data: [], order_data: [], user_growth: [] };
            const byDay = new Map();
            snap.forEach(doc => {
                const o = doc.data() || {};
                const d = o.created_at ? new Date(o.created_at) : new Date();
                const day = d.toISOString().slice(0,10);
                const amount = Number(o.total_amount || o.amount || 0);
                const paid = (o.payment_status || '').toLowerCase() === 'paid' || (o.status||'').toLowerCase() === 'completed';
                if (!byDay.has(day)) byDay.set(day, { revenue: 0, orders: 0 });
                const rec = byDay.get(day);
                rec.orders += 1;
                if (paid) rec.revenue += amount;
            });
            analytics.revenue_data = Array.from(byDay.entries()).map(([day, v]) => ({ day, value: v.revenue }));
            analytics.order_data = Array.from(byDay.entries()).map(([day, v]) => ({ day, value: v.orders }));

            this.displayAnalytics(analytics);
        } catch (error) {
            console.error('Failed to load analytics:', error);
        }
    }

    mapDisplayStatus(orderStatus, paymentStatus) {
        const s = (orderStatus || '').toLowerCase();
        const p = (paymentStatus || '').toLowerCase();
        if (p === 'failed' || p === 'cancelled' || p === 'refunded' || p === 'void') return 'cancelled';
        if (p === 'pending' || s === 'pending') return 'pending';
        if (p === 'paid' || p === 'captured' || s === 'completed' || s === 'delivered') return 'completed';
        return s || p || 'pending';
    }

    displayAnalytics(analytics) {
        // Update analytics charts and metrics
        this.updateRevenueChart(analytics.revenue_data);
        this.updateOrderChart(analytics.order_data);
        this.updateUserGrowthChart(analytics.user_growth);
    }

    updateRevenueChart(revenueData) {
        // Implementation for revenue chart
        const ctx = document.getElementById('revenue-chart');
        if (ctx && revenueData) {
            // Chart.js implementation would go here
            console.log('Revenue data:', revenueData);
        }
    }

    updateOrderChart(orderData) {
        // Implementation for order chart
        const ctx = document.getElementById('order-chart');
        if (ctx && orderData) {
            // Chart.js implementation would go here
            console.log('Order data:', orderData);
        }
    }

    updateUserGrowthChart(userGrowth) {
        // Implementation for user growth chart
        const ctx = document.getElementById('user-growth-chart');
        if (ctx && userGrowth) {
            // Chart.js implementation would go here
            console.log('User growth data:', userGrowth);
        }
    }

    setupEventListeners() {
        // Navigation
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const target = item.getAttribute('data-target');
                this.showSection(target);
            });
        });

        // Quick actions
        document.querySelectorAll('.quick-action').forEach(action => {
            action.addEventListener('click', (e) => {
                e.preventDefault();
                const actionType = action.getAttribute('data-action');
                this.handleQuickAction(actionType);
            });
        });

        // Back to dashboard
        const backBtn = document.getElementById('back-btn');
        if (backBtn) {
            backBtn.addEventListener('click', () => {
                window.location.href = 'dashboard.html';
            });
        }

        // Search functionality
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.handleSearch(e.target.value);
            });
        }
    }

    showSection(sectionId) {
        // Hide all sections
        document.querySelectorAll('.section').forEach(section => {
            section.classList.add('hidden');
        });

        // Show target section
        const targetSection = document.getElementById(sectionId);
        if (targetSection) {
            targetSection.classList.remove('hidden');
        }

        // Update active nav item
        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.remove('bg-blue-50', 'text-blue-700', 'border-blue-200');
            item.classList.add('text-gray-700', 'border-transparent');
        });

        const activeNavItem = document.querySelector(`[data-target="${sectionId}"]`);
        if (activeNavItem) {
            activeNavItem.classList.remove('text-gray-700', 'border-transparent');
            activeNavItem.classList.add('bg-blue-50', 'text-blue-700', 'border-blue-200');
        }
    }

    handleQuickAction(actionType) {
        switch (actionType) {
            case 'create-order':
                this.showCreateOrderModal();
                break;
            case 'add-user':
                this.showAddUserModal();
                break;
            case 'send-email':
                this.showSendEmailModal();
                break;
            case 'view-reports':
                this.showReports();
                break;
        }
    }

    handleSearch(query) {
        if (query.length < 2) return;

        // Implement search functionality
        console.log('Searching for:', query);
        // This would trigger search across orders, users, messages, etc.
    }

    startRealTimeUpdates() {
        // Set up real-time updates every 30 seconds
        setInterval(() => {
            this.loadDashboardData();
        }, 30000);

        // Set up WebSocket connection for real-time updates if available
        // this.setupWebSocket();
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


    // Modal functions
    showCreateOrderModal() {
        // Implementation for create order modal
        console.log('Show create order modal');
    }

    showAddUserModal() {
        // Implementation for add user modal
        console.log('Show add user modal');
    }

    showSendEmailModal() {
        // Implementation for send email modal
        console.log('Show send email modal');
    }

    showReports() {
        // Implementation for reports
        console.log('Show reports');
    }
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new AdminDashboard();
});
