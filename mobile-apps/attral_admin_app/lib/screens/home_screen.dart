import 'package:flutter/material.dart';
import '../widgets/compact_side_menu.dart';
import '../widgets/bottom_nav_bar.dart';
import 'admin/admin_dashboard_screen.dart';
import 'admin/admin_orders_screen.dart';
import 'admin/admin_messages_screen.dart';
import 'admin/admin_affiliate_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int _currentIndex = 0;
  
  final List<Widget> _screens = [
    const AdminDashboardScreen(),
    const AdminOrdersScreen(),
    const AdminMessagesScreen(),
    const AdminAffiliateScreen(),
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      drawer: const CompactSideMenu(),
      body: IndexedStack(
        index: _currentIndex,
        children: _screens,
      ),
      bottomNavigationBar: CustomBottomNavBar(
        currentIndex: _currentIndex,
        onTap: (index) {
          setState(() {
            _currentIndex = index;
          });
        },
      ),
    );
  }
}
