import 'package:flutter/material.dart';
import '../../config/app_config.dart';
import '../../widgets/custom_webview.dart';

class AdminLoginScreen extends StatelessWidget {
  const AdminLoginScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const CustomWebView(
      route: AppConfig.adminLoginRoute,
      title: 'Admin Login',
    );
  }
}
