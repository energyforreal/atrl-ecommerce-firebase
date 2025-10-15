import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../utils/constants.dart';
import '../services/auth_service.dart';
import '../screens/admin/admin_dashboard_screen.dart';
import '../screens/admin/admin_orders_screen.dart';
import '../screens/admin/admin_messages_screen.dart';
import '../screens/admin/admin_affiliate_screen.dart';
import '../screens/customer/customer_home_screen.dart';

class CompactSideMenu extends StatefulWidget {
  const CompactSideMenu({super.key});

  @override
  State<CompactSideMenu> createState() => _CompactSideMenuState();
}

class _CompactSideMenuState extends State<CompactSideMenu> {
  @override
  Widget build(BuildContext context) {
    return Container(
      width: 200, // Fixed narrow width
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withOpacity(0.3),
            spreadRadius: 2,
            blurRadius: 5,
            offset: const Offset(2, 0),
          ),
        ],
      ),
      child: Column(
        children: [
          // Compact header
          Container(
            height: 80,
            padding: const EdgeInsets.all(16),
            decoration: const BoxDecoration(
              gradient: AppConstants.adminGradient,
            ),
            child: Row(
              children: [
                Container(
                  width: 32,
                  height: 32,
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: const Icon(
                    Icons.admin_panel_settings,
                    color: AppConstants.primaryColor,
                    size: 18,
                  ),
                ),
                const SizedBox(width: 8),
                const Expanded(
                  child: Text(
                    'ATTRAL',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              ],
            ),
          ),
          
          // Menu items
          Expanded(
            child: ListView(
              padding: EdgeInsets.zero,
              children: [
                const SizedBox(height: 8),
                
                // Dashboard
                _buildCompactMenuItem(
                  context,
                  icon: Icons.dashboard,
                  title: 'Dashboard',
                  onTap: () {
                    Navigator.pop(context);
                    Navigator.pushReplacement(
                      context,
                      MaterialPageRoute(builder: (_) => const AdminDashboardScreen()),
                    );
                  },
                ),
                
                // Orders
                _buildCompactMenuItem(
                  context,
                  icon: Icons.receipt_long,
                  title: 'Orders',
                  onTap: () {
                    Navigator.pop(context);
                    Navigator.pushReplacement(
                      context,
                      MaterialPageRoute(builder: (_) => const AdminOrdersScreen()),
                    );
                  },
                ),
                
                // Messages
                _buildCompactMenuItem(
                  context,
                  icon: Icons.message,
                  title: 'Messages',
                  onTap: () {
                    Navigator.pop(context);
                    Navigator.pushReplacement(
                      context,
                      MaterialPageRoute(builder: (_) => const AdminMessagesScreen()),
                    );
                  },
                ),
                
                // Affiliate Management
                _buildCompactMenuItem(
                  context,
                  icon: Icons.groups,
                  title: 'Affiliate',
                  onTap: () {
                    Navigator.pop(context);
                    Navigator.pushReplacement(
                      context,
                      MaterialPageRoute(builder: (_) => const AdminAffiliateScreen()),
                    );
                  },
                ),
                
                const Divider(height: 16, indent: 16, endIndent: 16),
                
                // View Customer Site
                _buildCompactMenuItem(
                  context,
                  icon: Icons.store,
                  title: 'Customer Site',
                  textColor: AppConstants.infoColor,
                  onTap: () {
                    Navigator.pop(context);
                    Navigator.push(
                      context,
                      MaterialPageRoute(builder: (_) => const CustomerHomeScreen()),
                    );
                  },
                ),
                
                const Divider(height: 16, indent: 16, endIndent: 16),
                
                // Analytics
                _buildCompactMenuItem(
                  context,
                  icon: Icons.analytics,
                  title: 'Analytics',
                  onTap: () {
                    Navigator.pop(context);
                    // Navigate to analytics
                  },
                ),
                
                // Settings
                _buildCompactMenuItem(
                  context,
                  icon: Icons.settings,
                  title: 'Settings',
                  onTap: () {
                    Navigator.pop(context);
                    // Navigate to settings
                  },
                ),
                
                const Divider(height: 16, indent: 16, endIndent: 16),
                
                // Logout
                _buildCompactMenuItem(
                  context,
                  icon: Icons.logout,
                  title: 'Logout',
                  textColor: AppConstants.errorColor,
                  onTap: () {
                    Navigator.pop(context);
                    _showLogoutDialog(context);
                  },
                ),
                
                const SizedBox(height: 16),
              ],
            ),
          ),
          
          // Compact footer
          Container(
            padding: const EdgeInsets.all(16),
            child: Column(
              children: [
                Consumer<AuthService>(
                  builder: (context, authService, child) {
                    return Text(
                      'Admin: ${authService.adminUsername ?? 'User'}',
                      style: const TextStyle(
                        color: AppConstants.textSecondary,
                        fontSize: 12,
                      ),
                      textAlign: TextAlign.center,
                    );
                  },
                ),
                const SizedBox(height: 4),
                const Text(
                  'v1.0.0',
                  style: TextStyle(
                    color: AppConstants.textLight,
                    fontSize: 10,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
  
  Widget _buildCompactMenuItem(
    BuildContext context, {
    required IconData icon,
    required String title,
    required VoidCallback onTap,
    Color? textColor,
  }) {
    return InkWell(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        child: Row(
          children: [
            Icon(
              icon,
              color: textColor ?? AppConstants.textPrimary,
              size: 18,
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                title,
                style: TextStyle(
                  color: textColor ?? AppConstants.textPrimary,
                  fontSize: 14,
                  fontWeight: FontWeight.w500,
                ),
                overflow: TextOverflow.ellipsis,
              ),
            ),
          ],
        ),
      ),
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


