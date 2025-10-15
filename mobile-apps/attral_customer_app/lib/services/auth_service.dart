import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

class AuthService extends ChangeNotifier {
  bool _isAuthenticated = false;
  bool _isAdmin = false;
  String? _userToken;
  String? _adminToken;
  String? _userId;
  String? _userEmail;

  // Getters
  bool get isAuthenticated => _isAuthenticated;
  bool get isAdmin => _isAdmin;
  String? get userToken => _userToken;
  String? get adminToken => _adminToken;
  String? get userId => _userId;
  String? get userEmail => _userEmail;

  // Initialize auth service
  Future<void> init() async {
    await checkAuthStatus();
  }

  // Check authentication status from storage
  Future<void> checkAuthStatus() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      _userToken = prefs.getString('user_token');
      _adminToken = prefs.getString('admin_token');
      _userId = prefs.getString('user_id');
      _userEmail = prefs.getString('user_email');
      
      _isAuthenticated = _userToken != null;
      _isAdmin = _adminToken != null;
      
      notifyListeners();
    } catch (e) {
      if (kDebugMode) {
        print('Error checking auth status: $e');
      }
    }
  }

  // Login user
  Future<bool> loginUser(String token, {String? userId, String? email}) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('user_token', token);
      
      if (userId != null) {
        await prefs.setString('user_id', userId);
        _userId = userId;
      }
      
      if (email != null) {
        await prefs.setString('user_email', email);
        _userEmail = email;
      }
      
      _userToken = token;
      _isAuthenticated = true;
      notifyListeners();
      return true;
    } catch (e) {
      if (kDebugMode) {
        print('Error logging in user: $e');
      }
      return false;
    }
  }

  // Login admin
  Future<bool> loginAdmin(String token) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('admin_token', token);
      
      _adminToken = token;
      _isAdmin = true;
      notifyListeners();
      return true;
    } catch (e) {
      if (kDebugMode) {
        print('Error logging in admin: $e');
      }
      return false;
    }
  }

  // Logout
  Future<void> logout() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove('user_token');
      await prefs.remove('admin_token');
      await prefs.remove('user_id');
      await prefs.remove('user_email');
      
      _userToken = null;
      _adminToken = null;
      _userId = null;
      _userEmail = null;
      _isAuthenticated = false;
      _isAdmin = false;
      
      notifyListeners();
    } catch (e) {
      if (kDebugMode) {
        print('Error logging out: $e');
      }
    }
  }

  // Update user info
  Future<void> updateUserInfo({String? userId, String? email}) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      
      if (userId != null) {
        await prefs.setString('user_id', userId);
        _userId = userId;
      }
      
      if (email != null) {
        await prefs.setString('user_email', email);
        _userEmail = email;
      }
      
      notifyListeners();
    } catch (e) {
      if (kDebugMode) {
        print('Error updating user info: $e');
      }
    }
  }

  // Clear all data
  Future<void> clearAllData() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.clear();
      
      _userToken = null;
      _adminToken = null;
      _userId = null;
      _userEmail = null;
      _isAuthenticated = false;
      _isAdmin = false;
      
      notifyListeners();
    } catch (e) {
      if (kDebugMode) {
        print('Error clearing all data: $e');
      }
    }
  }
}
