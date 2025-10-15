import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../utils/constants.dart';
import '../services/auth_service.dart';
import '../screens/admin/admin_dashboard_screen.dart';
import '../screens/admin/admin_orders_screen.dart';
import '../screens/admin/admin_messages_screen.dart';
import '../screens/admin/admin_affiliate_screen.dart';
import '../screens/customer/customer_home_screen.dart';

class DrawerMenu extends StatelessWidget {
  const DrawerMenu({super.key});

  @override
  Widget build(BuildContext context) {
    return Drawer(
      width: MediaQuery.of(context).size.width * 0.8, // Limit drawer width to 80% of screen
      child: Column(
        children: [
          // Compact Header
          Container(
            height: 120, // Reduced from 200
            decoration: const BoxDecoration(
              gradient: AppConstants.adminGradient,
            ),
            child: SafeArea(
              child: Padding(
                padding: const EdgeInsets.all(AppConstants.spacingM), // Reduced padding
                child: Row(
                  children: [
                    Container(
                      width: 40, // Reduced from 60
                      height: 40, // Reduced from 60
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(AppConstants.radiusM), // Reduced radius
                      ),
                      child: const Icon(
                        Icons.admin_panel_settings,
                        size: 20, // Reduced from 30
                        color: AppConstants.primaryColor,
                      ),
                    ),
                    const SizedBox(width: AppConstants.spacingM),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          const Text(
                            AppConstants.appName,
                            style: TextStyle(
                              color: Colors.white,
                              fontSize: AppConstants.fontSizeL, // Reduced from XXL
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          const SizedBox(height: 2), // Minimal spacing
                          const Text(
                            AppConstants.appTagline,
                            style: TextStyle(
                              color: Colors.white70,
                              fontSize: AppConstants.fontSizeXS, // Reduced from S
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
          
          // Menu Items
          Expanded(
            child: ListView(
              padding: EdgeInsets.zero,
              children: [
                const SizedBox(height: AppConstants.spacingS), // Reduced spacing
                
                // Dashboard
                _buildMenuItem(
                  context,
                  icon: Icons.dashboard,
                  title: 'Dashboard Overview',
                  onTap: () {
                    Navigator.pop(context);
                    Navigator.pushReplacement(
                      context,
                      MaterialPageRoute(builder: (_) => const AdminDashboardScreen()),
                    );
                  },
                ),
                
                // Orders
                _buildMenuItem(
                  context,
                  icon: Icons.receipt_long,
                  title: 'Order Management',
                  onTap: () {
                    Navigator.pop(context);
                    Navigator.pushReplacement(
                      context,
                      MaterialPageRoute(builder: (_) => const AdminOrdersScreen()),
                    );
                  },
                ),
                
                // Messages
                _buildMenuItem(
                  context,
                  icon: Icons.message,
                  title: 'Message Center',
                  onTap: () {
                    Navigator.pop(context);
                    Navigator.pushReplacement(
                      context,
                      MaterialPageRoute(builder: (_) => const AdminMessagesScreen()),
                    );
                  },
                ),
                
                // Affiliate Management
                _buildMenuItem(
                  context,
                  icon: Icons.groups,
                  title: 'Affiliate Management',
                  onTap: () {
                    Navigator.pop(context);
                    Navigator.pushReplacement(
                      context,
                      MaterialPageRoute(builder: (_) => const AdminAffiliateScreen()),
                    );
                  },
                ),
                
                const Divider(height: AppConstants.spacingM), // Reduced divider spacing
                
                // View Customer Site
                _buildMenuItem(
                  context,
                  icon: Icons.store,
                  title: 'View Customer Site',
                  textColor: AppConstants.infoColor,
                  onTap: () {
                    Navigator.pop(context);
                    Navigator.push(
                      context,
                      MaterialPageRoute(builder: (_) => const CustomerHomeScreen()),
                    );
                  },
                ),
                
                const Divider(height: AppConstants.spacingM), // Reduced divider spacing
                
                // Analytics
                _buildMenuItem(
                  context,
                  icon: Icons.analytics,
                  title: 'Analytics & Reports',
                  onTap: () {
                    Navigator.pop(context);
                    // Navigate to analytics
                  },
                ),
                
                // Settings
                _buildMenuItem(
                  context,
                  icon: Icons.settings,
                  title: 'Settings',
                  onTap: () {
                    Navigator.pop(context);
                    // Navigate to settings
                  },
                ),
                
                const Divider(height: AppConstants.spacingM), // Reduced divider spacing
                
                // Logout
                _buildMenuItem(
                  context,
                  icon: Icons.logout,
                  title: 'Logout',
                  textColor: AppConstants.errorColor,
                  onTap: () {
                    Navigator.pop(context);
                    _showLogoutDialog(context);
                  },
                ),
                
                const SizedBox(height: AppConstants.spacingM), // Reduced spacing
              ],
            ),
          ),
          
          // Footer
          Container(
            padding: const EdgeInsets.all(AppConstants.spacingL),
            child: Column(
              children: [
                Consumer<AuthService>(
                  builder: (context, authService, child) {
                    return Text(
                      'Logged in as: ${authService.adminUsername ?? 'Admin'}',
                      style: const TextStyle(
                        color: AppConstants.textSecondary,
                        fontSize: AppConstants.fontSizeS,
                      ),
                    );
                  },
                ),
                const SizedBox(height: AppConstants.spacingXS),
                const Text(
                  'Version 1.0.0',
                  style: TextStyle(
                    color: AppConstants.textLight,
                    fontSize: AppConstants.fontSizeXS,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMenuItem(
    BuildContext context, {
    required IconData icon,
    required String title,
    required VoidCallback onTap,
    Color? textColor,
  }) {
    return ListTile(
      leading: Icon(
        icon,
        color: textColor ?? AppConstants.textPrimary,
        size: AppConstants.iconS, // Reduced icon size
      ),
      title: Text(
        title,
        style: TextStyle(
          color: textColor ?? AppConstants.textPrimary,
          fontSize: AppConstants.fontSizeS, // Reduced font size
          fontWeight: FontWeight.w500,
        ),
      ),
      onTap: onTap,
      dense: true, // Makes the tile more compact
      contentPadding: const EdgeInsets.symmetric(
        horizontal: AppConstants.spacingM, // Reduced horizontal padding
        vertical: 2, // Minimal vertical padding
      ),
      minLeadingWidth: 20, // Reduced leading width
    );
  }

  void _showLogoutDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          title: const Text('Logout'),
          content: const Text('Are you sure you want to logout?'),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('Cancel'),
            ),
            TextButton(
              onPressed: () {
                Navigator.of(context).pop();
                Provider.of<AuthService>(context, listen: false).logout();
              },
              style: TextButton.styleFrom(
                foregroundColor: AppConstants.errorColor,
              ),
              child: const Text('Logout'),
            ),
          ],
        );
      },
    );
  }
}
