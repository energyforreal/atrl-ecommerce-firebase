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
  
  // Customer Routes
  static const String homeRoute = '/index.html';
  static const String shopRoute = '/shop.html';
  static const String cartRoute = '/cart.html';
  static const String productDetailRoute = '/product-detail.html';
  static const String orderRoute = '/order.html';
  static const String myOrdersRoute = '/my-orders.html';
  static const String accountRoute = '/account.html';
  static const String blogRoute = '/blog.html';
  static const String affiliateRoute = '/affiliate-dashboard.html';
  static const String aboutRoute = '/about.html';
  static const String contactRoute = '/contact.html';
  
  // Admin Routes (for admin app)
  static const String adminLoginRoute = '/admin-login.html';
  static const String adminDashboardRoute = '/admin-dashboard-unified.html';
  static const String adminOrdersRoute = '/admin-orders.html';
  static const String adminMessagesRoute = '/admin-messages.html';
  static const String adminAffiliateSyncRoute = '/admin-affiliate-sync.html';
  
  // API Endpoints
  static String get apiBase => '$baseUrl/api';
  static String get createOrderApi => '$apiBase/create_order.php';
  static String get validateCouponApi => '$apiBase/validate_coupon.php';
  static String get myOrdersApi => '$apiBase/get_my_orders.php';
  
  // App Settings
  static const String appName = 'ATTRAL';
  static const String appVersion = '1.0.0';
  static const bool enableDebugMode = true;
  
  // Firebase Configuration
  static const String firebaseProjectId = 'e-commerce-1d40f';
  
  // Push Notification Settings
  static const String fcmTopicOrders = 'orders';
  static const String fcmTopicPromotions = 'promotions';
}
