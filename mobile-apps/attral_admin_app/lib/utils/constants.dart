import 'package:flutter/material.dart';

class AppConstants {
  // Admin App Colors
  static const Color primaryColor = Color(0xFF667eea);
  static const Color secondaryColor = Color(0xFF764ba2);
  static const Color accentColor = Color(0xFF4CAF50);
  static const Color errorColor = Color(0xFFE53E3E);
  static const Color warningColor = Color(0xFFF6AD55);
  static const Color successColor = Color(0xFF48BB78);
  static const Color infoColor = Color(0xFF3182CE);
  
  // Admin Specific Colors
  static const Color adminDark = Color(0xFF2D3748);
  static const Color adminLight = Color(0xFFF7FAFC);
  static const Color adminAccent = Color(0xFF667eea);
  
  // Text Colors
  static const Color textPrimary = Color(0xFF2D3748);
  static const Color textSecondary = Color(0xFF718096);
  static const Color textLight = Color(0xFFA0AEC0);
  
  // Background Colors
  static const Color backgroundLight = Color(0xFFF7FAFC);
  static const Color backgroundDark = Color(0xFF1A202C);
  static const Color surfaceLight = Color(0xFFFFFFFF);
  static const Color surfaceDark = Color(0xFF2D3748);
  
  // Gradients
  static const LinearGradient primaryGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [primaryColor, secondaryColor],
  );
  
  static const LinearGradient adminGradient = LinearGradient(
    begin: Alignment.topCenter,
    end: Alignment.bottomCenter,
    colors: [adminDark, adminAccent],
  );
  
  // Spacing
  static const double spacingXS = 4.0;
  static const double spacingS = 8.0;
  static const double spacingM = 16.0;
  static const double spacingL = 24.0;
  static const double spacingXL = 32.0;
  static const double spacingXXL = 48.0;
  
  // Border Radius
  static const double radiusS = 4.0;
  static const double radiusM = 8.0;
  static const double radiusL = 12.0;
  static const double radiusXL = 16.0;
  static const double radiusXXL = 24.0;
  
  // Font Sizes
  static const double fontSizeXS = 12.0;
  static const double fontSizeS = 14.0;
  static const double fontSizeM = 16.0;
  static const double fontSizeL = 18.0;
  static const double fontSizeXL = 20.0;
  static const double fontSizeXXL = 24.0;
  static const double fontSizeHuge = 32.0;
  
  // Icon Sizes
  static const double iconS = 16.0;
  static const double iconM = 24.0;
  static const double iconL = 32.0;
  static const double iconXL = 48.0;
  
  // Animation Durations
  static const Duration animationFast = Duration(milliseconds: 200);
  static const Duration animationNormal = Duration(milliseconds: 300);
  static const Duration animationSlow = Duration(milliseconds: 500);
  
  // App Specific
  static const String appName = 'ATTRAL Admin';
  static const String appTagline = 'Business Management Dashboard';
  static const String appDescription = 'Complete admin panel for managing orders, customers, and business operations.';
  
  // Storage Keys
  static const String keyAdminToken = 'admin_token';
  static const String keyAdminUsername = 'admin_username';
  static const String keyLastLogin = 'last_login';
  static const String keyPushNotifications = 'push_notifications_enabled';
  static const String keyBiometricEnabled = 'biometric_enabled';
  static const String keyAdminPreferences = 'admin_preferences';
  
  // Admin Features
  static const List<String> adminFeatures = [
    'Dashboard Overview',
    'Order Management',
    'Customer Management',
    'Message Center',
    'Affiliate Management',
    'Analytics & Reports',
    'Settings & Configuration',
  ];
  
  // Order Status Colors
  static const Color statusPending = Color(0xFFF6AD55);
  static const Color statusProcessing = Color(0xFF3182CE);
  static const Color statusShipped = Color(0xFF48BB78);
  static const Color statusDelivered = Color(0xFF38A169);
  static const Color statusCancelled = Color(0xFFE53E3E);
  static const Color statusRefunded = Color(0xFF805AD5);
}
