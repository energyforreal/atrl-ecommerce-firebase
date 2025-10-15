class AppConfig {
  // Base URLs - Update these with your actual domain
  static const String productionBaseUrl = 'https://attral.in';
  static const String stagingBaseUrl = 'https://staging.attral.in';
  static const String localBaseUrl = 'http://10.0.2.2:8000'; // Android emulator access to host
  
  // Choose active environment
  static const bool isProduction = true;
  static const bool isStaging = false;
  
  static String get baseUrl {
    if (isProduction) return productionBaseUrl;
    if (isStaging) return stagingBaseUrl;
    return localBaseUrl;
  }
  
  // Admin Routes (Primary for this app)
  static const String adminLoginRoute = '/admin-login.html';
  static const String adminDashboardRoute = '/admin-dashboard-unified.html';
  static const String adminOrdersRoute = '/admin-orders.html';
  static const String adminMessagesRoute = '/admin-messages.html';
  static const String adminAffiliateSyncRoute = '/admin-affiliate-sync.html';
  static const String adminAccessRoute = '/admin-access.html';
  
  // Customer Routes (Secondary for admin to view customer experience)
  static const String customerHomeRoute = '/index.html';
  static const String customerShopRoute = '/shop.html';
  static const String customerCartRoute = '/cart.html';
  static const String customerOrdersRoute = '/my-orders.html';
  
  // API Endpoints
  static String get apiBase => '$baseUrl/api';
  static String get adminApiBase => '$apiBase/admin';
  static String get createOrderApi => '$apiBase/create_order.php';
  static String get validateCouponApi => '$apiBase/validate_coupon.php';
  static String get myOrdersApi => '$apiBase/get_my_orders.php';
  static String get adminOrdersApi => '$apiBase/admin_orders.php';
  static String get adminStatsApi => '$apiBase/admin_stats.php';
  static String get adminUsersApi => '$apiBase/admin_users.php';
  static String get adminMessagesApi => '$apiBase/admin_messages.php';
  
  // App Settings
  static const String appName = 'ATTRAL Admin';
  static const String appVersion = '1.0.0';
  static const bool enableDebugMode = true;
  
  // Firebase Configuration
  static const String firebaseProjectId = 'e-commerce-1d40f';
  
  // Push Notification Settings
  static const String fcmTopicOrders = 'admin_orders';
  static const String fcmTopicMessages = 'admin_messages';
  static const String fcmTopicAlerts = 'admin_alerts';
  
  // Admin Security
  static const String defaultAdminUsername = 'admin';
  static const String defaultAdminPassword = 'Admin@123';
}
