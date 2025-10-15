import 'package:flutter/material.dart';
import '../../config/app_config.dart';
import '../../widgets/custom_webview.dart';

class AdminAffiliateScreen extends StatelessWidget {
  const AdminAffiliateScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const CustomWebView(
      route: AppConfig.adminAffiliateSyncRoute,
      title: 'Affiliate Management',
    );
  }
}
