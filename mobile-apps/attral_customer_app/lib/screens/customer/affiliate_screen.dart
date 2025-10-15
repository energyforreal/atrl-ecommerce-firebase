import 'package:flutter/material.dart';
import '../../config/app_config.dart';
import '../../widgets/custom_webview.dart';

class AffiliateScreen extends StatelessWidget {
  const AffiliateScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const CustomWebView(
      route: AppConfig.affiliateRoute,
      title: 'Affiliate Program',
    );
  }
}
