import 'package:flutter/material.dart';
import '../../config/app_config.dart';
import '../../widgets/custom_webview.dart';

class CartScreen extends StatelessWidget {
  const CartScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const CustomWebView(
      route: AppConfig.cartRoute,
      title: 'Shopping Cart',
    );
  }
}
