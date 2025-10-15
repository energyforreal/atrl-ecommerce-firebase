import 'package:flutter/material.dart';
import '../utils/constants.dart';
import '../screens/customer/blog_screen.dart';
import '../screens/customer/affiliate_screen.dart';
import '../screens/customer/orders_screen.dart';
import '../screens/admin/admin_login_screen.dart';

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
              gradient: AppConstants.primaryGradient,
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
                    Icons.shopping_bag,
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
                
                // My Orders
                _buildCompactMenuItem(
                  context,
                  icon: Icons.receipt_long,
                  title: 'My Orders',
                  onTap: () {
                    Navigator.pop(context);
                    Navigator.push(
                      context,
                      MaterialPageRoute(builder: (_) => const OrdersScreen()),
                    );
                  },
                ),
                
                // Blog
                _buildCompactMenuItem(
                  context,
                  icon: Icons.article,
                  title: 'Blog',
                  onTap: () {
                    Navigator.pop(context);
                    Navigator.push(
                      context,
                      MaterialPageRoute(builder: (_) => const BlogScreen()),
                    );
                  },
                ),
                
                // Affiliate Program
                _buildCompactMenuItem(
                  context,
                  icon: Icons.groups,
                  title: 'Affiliate Program',
                  onTap: () {
                    Navigator.pop(context);
                    Navigator.push(
                      context,
                      MaterialPageRoute(builder: (_) => const AffiliateScreen()),
                    );
                  },
                ),
                
                const Divider(height: 16, indent: 16, endIndent: 16),
                
                // About Us
                _buildCompactMenuItem(
                  context,
                  icon: Icons.info,
                  title: 'About Us',
                  onTap: () {
                    Navigator.pop(context);
                    // Navigate to about page
                  },
                ),
                
                // Contact
                _buildCompactMenuItem(
                  context,
                  icon: Icons.contact_mail,
                  title: 'Contact',
                  onTap: () {
                    Navigator.pop(context);
                    // Navigate to contact page
                  },
                ),
                
                const Divider(height: 16, indent: 16, endIndent: 16),
                
                // Admin Login
                _buildCompactMenuItem(
                  context,
                  icon: Icons.admin_panel_settings,
                  title: 'Admin Login',
                  textColor: AppConstants.primaryColor,
                  onTap: () {
                    Navigator.pop(context);
                    Navigator.push(
                      context,
                      MaterialPageRoute(builder: (_) => const AdminLoginScreen()),
                    );
                  },
                ),
                
                const Divider(height: 16, indent: 16, endIndent: 16),
                
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
                
                const SizedBox(height: 16),
              ],
            ),
          ),
          
          // Compact footer
          Container(
            padding: const EdgeInsets.all(16),
            child: Column(
              children: [
                const Text(
                  'ATTRAL Mobile',
                  style: TextStyle(
                    color: AppConstants.textSecondary,
                    fontSize: 12,
                  ),
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
}

