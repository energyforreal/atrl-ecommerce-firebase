import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../config/app_config.dart';

class AuthService extends ChangeNotifier {
  bool _isAuthenticated = false;
  bool _isAdmin = false;
  String? _adminToken;
  String? _adminUsername;
  String? _adminId;

  // Getters
  bool get isAuthenticated => _isAuthenticated;
  bool get isAdmin => _isAdmin;
  String? get adminToken => _adminToken;
  String? get adminUsername => _adminUsername;
  String? get adminId => _adminId;

  // Initialize auth service
  Future<void> init() async {
    await checkAuthStatus();
  }

  // Check authentication status from storage
  Future<void> checkAuthStatus() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      _adminToken = prefs.getString('admin_token');
      _adminUsername = prefs.getString('admin_username');
      _adminId = prefs.getString('admin_id');
      
      _isAuthenticated = _adminToken != null;
      _isAdmin = _adminToken != null;
      
      notifyListeners();
    } catch (e) {
      if (kDebugMode) {
        print('Error checking admin auth status: $e');
      }
    }
  }

  // Login admin
  Future<bool> loginAdmin(String username, String password) async {
    try {
      // For now, use hardcoded credentials (you can integrate with your API later)
      if (username == AppConfig.defaultAdminUsername && 
          password == AppConfig.defaultAdminPassword) {
        
        final prefs = await SharedPreferences.getInstance();
        final token = 'admin_token_${DateTime.now().millisecondsSinceEpoch}';
        
        await prefs.setString('admin_token', token);
        await prefs.setString('admin_username', username);
        await prefs.setString('admin_id', 'admin_001');
        
        _adminToken = token;
        _adminUsername = username;
        _adminId = 'admin_001';
        _isAuthenticated = true;
        _isAdmin = true;
        
        notifyListeners();
        return true;
      }
      return false;
    } catch (e) {
      if (kDebugMode) {
        print('Error logging in admin: $e');
      }
      return false;
    }
  }

  // Login admin with token (for API integration)
  Future<bool> loginAdminWithToken(String token, {String? username, String? adminId}) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('admin_token', token);
      
      if (username != null) {
        await prefs.setString('admin_username', username);
        _adminUsername = username;
      }
      
      if (adminId != null) {
        await prefs.setString('admin_id', adminId);
        _adminId = adminId;
      }
      
      _adminToken = token;
      _isAuthenticated = true;
      _isAdmin = true;
      
      notifyListeners();
      return true;
    } catch (e) {
      if (kDebugMode) {
        print('Error logging in admin with token: $e');
      }
      return false;
    }
  }

  // Logout
  Future<void> logout() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove('admin_token');
      await prefs.remove('admin_username');
      await prefs.remove('admin_id');
      
      _adminToken = null;
      _adminUsername = null;
      _adminId = null;
      _isAuthenticated = false;
      _isAdmin = false;
      
      notifyListeners();
    } catch (e) {
      if (kDebugMode) {
        print('Error logging out admin: $e');
      }
    }
  }

  // Update admin info
  Future<void> updateAdminInfo({String? username, String? adminId}) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      
      if (username != null) {
        await prefs.setString('admin_username', username);
        _adminUsername = username;
      }
      
      if (adminId != null) {
        await prefs.setString('admin_id', adminId);
        _adminId = adminId;
      }
      
      notifyListeners();
    } catch (e) {
      if (kDebugMode) {
        print('Error updating admin info: $e');
      }
    }
  }

  // Clear all data
  Future<void> clearAllData() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.clear();
      
      _adminToken = null;
      _adminUsername = null;
      _adminId = null;
      _isAuthenticated = false;
      _isAdmin = false;
      
      notifyListeners();
    } catch (e) {
      if (kDebugMode) {
        print('Error clearing all admin data: $e');
      }
    }
  }

  // Validate admin session
  Future<bool> validateSession() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('admin_token');
      
      if (token != null) {
        // You can add token validation logic here
        // For now, just check if token exists
        return true;
      }
      return false;
    } catch (e) {
      if (kDebugMode) {
        print('Error validating admin session: $e');
      }
      return false;
    }
  }
}
