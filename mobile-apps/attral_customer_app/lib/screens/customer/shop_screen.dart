import 'package:flutter/material.dart';
import '../../config/app_config.dart';
import '../../widgets/custom_webview.dart';

class ShopScreen extends StatelessWidget {
  const ShopScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const CustomWebView(
      route: AppConfig.shopRoute,
      title: 'Shop',
    );
  }
}
