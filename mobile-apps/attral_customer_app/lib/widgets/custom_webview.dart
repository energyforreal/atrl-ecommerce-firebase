import 'package:flutter/material.dart';
import 'package:flutter_inappwebview/flutter_inappwebview.dart';
import 'package:provider/provider.dart';
import '../config/app_config.dart';
import '../services/auth_service.dart';

class CustomWebView extends StatefulWidget {
  final String route;
  final String title;
  final bool showAppBar;
  final Map<String, String>? headers;

  const CustomWebView({
    super.key,
    required this.route,
    required this.title,
    this.showAppBar = true,
    this.headers,
  });

  @override
  State<CustomWebView> createState() => _CustomWebViewState();
}

class _CustomWebViewState extends State<CustomWebView> {
  InAppWebViewController? webViewController;
  double progress = 0;
  bool isLoading = true;

  @override
  Widget build(BuildContext context) {
    final authService = Provider.of<AuthService>(context);
    final url = Uri.parse('${AppConfig.baseUrl}${widget.route}');

    return Scaffold(
      appBar: widget.showAppBar
          ? AppBar(
              title: Text(widget.title),
              backgroundColor: const Color(0xFF667eea),
              foregroundColor: Colors.white,
              actions: [
                IconButton(
                  icon: const Icon(Icons.refresh),
                  onPressed: () => webViewController?.reload(),
                ),
                IconButton(
                  icon: const Icon(Icons.home),
                  onPressed: () => webViewController?.loadUrl(
                    urlRequest: URLRequest(url: WebUri.uri(Uri.parse('${AppConfig.baseUrl}${AppConfig.homeRoute}'))),
                  ),
                ),
              ],
            )
          : null,
      body: Stack(
        children: [
          InAppWebView(
            initialUrlRequest: URLRequest(
              url: WebUri.uri(url),
              headers: widget.headers ?? {},
            ),
            initialSettings: InAppWebViewSettings(
              useShouldOverrideUrlLoading: true,
              mediaPlaybackRequiresUserGesture: false,
              allowsInlineMediaPlayback: true,
              javaScriptEnabled: true,
              domStorageEnabled: true,
              databaseEnabled: true,
              clearCache: false,
              cacheEnabled: true,
              supportZoom: true,
              builtInZoomControls: true,
              disableHorizontalScroll: false,
              disableVerticalScroll: false,
              userAgent: 'ATTRAL-Mobile-App/1.0.0 (Flutter)',
            ),
            onWebViewCreated: (controller) {
              webViewController = controller;
              
              // Add JavaScript handlers for mobile-specific features
              controller.addJavaScriptHandler(
                handlerName: 'flutterHandler',
                callback: (args) {
                  // Handle messages from web
                  print('Message from web: $args');
                  return {'status': 'received'};
                },
              );
            },
            onLoadStart: (controller, url) {
              setState(() {
                isLoading = true;
              });
            },
            onLoadStop: (controller, url) async {
              setState(() {
                isLoading = false;
              });
              
              // Inject custom JavaScript for mobile optimization
              await controller.evaluateJavascript(source: '''
                // Add mobile-specific behaviors
                window.isMobileApp = true;
                window.flutter = {
                  postMessage: function(data) {
                    window.flutter_inappwebview.callHandler('flutterHandler', data);
                  }
                };
                
                // Prevent long-press context menu
                document.addEventListener('contextmenu', function(e) {
                  e.preventDefault();
                }, false);
                
                // Add mobile app indicator
                var mobileIndicator = document.createElement('div');
                mobileIndicator.style.cssText = 'position:fixed;top:0;right:0;background:#667eea;color:white;padding:2px 8px;font-size:10px;z-index:9999;border-radius:0 0 0 8px;';
                mobileIndicator.textContent = 'ATTRAL App';
                document.body.appendChild(mobileIndicator);
                
                // Optimize for mobile
                var viewport = document.querySelector('meta[name="viewport"]');
                if (viewport) {
                  viewport.setAttribute('content', 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no');
                }
              ''');
            },
            onProgressChanged: (controller, progress) {
              setState(() {
                this.progress = progress / 100;
              });
            },
            shouldOverrideUrlLoading: (controller, navigationAction) async {
              final uri = navigationAction.request.url;
              
              // Handle external links
              if (uri != null && !uri.toString().startsWith(AppConfig.baseUrl)) {
                // You can add logic to open external links in browser
                return NavigationActionPolicy.CANCEL;
              }
              
              return NavigationActionPolicy.ALLOW;
            },
            onReceivedError: (controller, request, error) {
              print('WebView Error: ${error.description}');
            },
          ),
          if (isLoading && progress < 1.0)
            LinearProgressIndicator(
              value: progress,
              backgroundColor: Colors.grey[200],
              valueColor: const AlwaysStoppedAnimation<Color>(Color(0xFF667eea)),
            ),
        ],
      ),
    );
  }
}
