import 'package:flutter/material.dart';
import '../utils/constants.dart';
import '../screens/customer/blog_screen.dart';
import '../screens/customer/affiliate_screen.dart';
import '../screens/customer/orders_screen.dart';
import '../screens/admin/admin_login_screen.dart';

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
              gradient: AppConstants.primaryGradient,
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
                        Icons.shopping_bag,
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
                
                // My Orders
                _buildMenuItem(
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
                _buildMenuItem(
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
                _buildMenuItem(
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
                
                const Divider(height: AppConstants.spacingM), // Reduced divider spacing
                
                // About Us
                _buildMenuItem(
                  context,
                  icon: Icons.info,
                  title: 'About Us',
                  onTap: () {
                    Navigator.pop(context);
                    // Navigate to about page
                  },
                ),
                
                // Contact
                _buildMenuItem(
                  context,
                  icon: Icons.contact_mail,
                  title: 'Contact',
                  onTap: () {
                    Navigator.pop(context);
                    // Navigate to contact page
                  },
                ),
                
                const Divider(height: AppConstants.spacingM), // Reduced divider spacing
                
                // Admin Login
                _buildMenuItem(
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
                
                const Divider(height: AppConstants.spacingM), // Reduced divider spacing
                
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
                
                const SizedBox(height: AppConstants.spacingM), // Reduced spacing
              ],
            ),
          ),
          
          // Footer
          Container(
            padding: const EdgeInsets.all(AppConstants.spacingL),
            child: Column(
              children: [
                const Text(
                  'ATTRAL Mobile App',
                  style: TextStyle(
                    color: AppConstants.textSecondary,
                    fontSize: AppConstants.fontSizeS,
                  ),
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
}
