# 🚀 ATTRAL Mobile Apps

Complete Flutter mobile applications for ATTRAL eCommerce platform with both Customer and Admin interfaces.

## 📱 Apps Overview

### Customer App (`attral_customer_app`)
- **Package Name:** `com.attral.customer`
- **Purpose:** Customer shopping experience
- **Features:** Shop, Cart, Orders, Account, Blog, Affiliate Program

### Admin App (`attral_admin_app`)
- **Package Name:** `com.attral.admin`
- **Purpose:** Business management dashboard
- **Features:** Dashboard, Orders, Messages, Affiliate Management

## 🛠️ Setup Instructions

### Step 1: Copy Firebase Configuration Files

You have 4 Firebase configuration files in your Downloads folder. Copy them to the correct locations:

#### Customer App Android:
```powershell
Copy-Item "C:\Users\lohit\Downloads\customer_android_google-services.json" "C:\Users\lohit\OneDrive\Documents\ATTRAL\Projects\eCommerce\mobile-apps\attral_customer_app\android\app\google-services.json"
```

#### Customer App iOS:
```powershell
Copy-Item "C:\Users\lohit\Downloads\customer_ios_GoogleService-Info.plist" "C:\Users\lohit\OneDrive\Documents\ATTRAL\Projects\eCommerce\mobile-apps\attral_customer_app\ios\Runner\GoogleService-Info.plist"
```

#### Admin App Android:
```powershell
Copy-Item "C:\Users\lohit\Downloads\admin_android_google-services.json" "C:\Users\lohit\OneDrive\Documents\ATTRAL\Projects\eCommerce\mobile-apps\attral_admin_app\android\app\google-services.json"
```

#### Admin App iOS:
```powershell
Copy-Item "C:\Users\lohit\Downloads\admin_ios_GoogleService-Info.plist" "C:\Users\lohit\OneDrive\Documents\ATTRAL\Projects\eCommerce\mobile-apps\attral_admin_app\ios\Runner\GoogleService-Info.plist"
```

### Step 2: Configure Firebase with FlutterFire CLI

#### For Customer App:
```powershell
cd attral_customer_app
flutterfire configure --project=e-commerce-1d40f
```

#### For Admin App:
```powershell
cd attral_admin_app
flutterfire configure --project=e-commerce-1d40f
```

### Step 3: Update Base URL Configuration

Edit the base URL in both apps:

#### Customer App:
File: `attral_customer_app/lib/config/app_config.dart`
```dart
static const String localBaseUrl = 'http://YOUR_LOCAL_IP:3000'; // Replace with your local IP
```

#### Admin App:
File: `attral_admin_app/lib/config/app_config.dart`
```dart
static const String localBaseUrl = 'http://YOUR_LOCAL_IP:3000'; // Replace with your local IP
```

### Step 4: Test the Apps

#### Run Customer App:
```powershell
cd attral_customer_app
flutter run -d chrome  # For web testing
# OR
flutter run -d windows # For Windows desktop
```

#### Run Admin App:
```powershell
cd attral_admin_app
flutter run -d chrome  # For web testing
# OR
flutter run -d windows # For Windows desktop
```

## 📋 Features Implemented

### Customer App Features:
- ✅ WebView integration with all customer pages
- ✅ Bottom navigation (Home, Shop, Cart, Account)
- ✅ Drawer menu with all sections
- ✅ Firebase authentication ready
- ✅ Push notifications ready
- ✅ Responsive design
- ✅ Custom splash screen

### Admin App Features:
- ✅ WebView integration with all admin pages
- ✅ Bottom navigation (Dashboard, Orders, Messages, Affiliates)
- ✅ Admin drawer menu
- ✅ Firebase authentication ready
- ✅ Push notifications ready
- ✅ Admin login system
- ✅ Custom splash screen

## 🔧 Configuration

### Firebase Project:
- **Project ID:** `e-commerce-1d40f`
- **Customer Android:** `com.attral.customer`
- **Customer iOS:** `com.attral.customer`
- **Admin Android:** `com.attral.admin`
- **Admin iOS:** `com.attral.admin`

### Admin Login Credentials:
- **Username:** `admin`
- **Password:** `Admin@123`

## 📱 Supported Platforms

- ✅ Android
- ✅ iOS
- ✅ Web (Chrome)
- ✅ Windows Desktop
- ✅ macOS Desktop
- ✅ Linux Desktop

## 🚀 Next Steps

1. **Copy Firebase config files** (Step 1 above)
2. **Run FlutterFire configuration** (Step 2 above)
3. **Update local IP addresses** (Step 3 above)
4. **Test both apps** (Step 4 above)
5. **Install Android Studio** for Android device testing
6. **Configure push notifications** (optional)
7. **Customize app icons** (optional)

## 📞 Support

If you encounter any issues:
1. Check that all Firebase config files are in the correct locations
2. Ensure your local server is running on the specified IP
3. Verify FlutterFire CLI configuration completed successfully
4. Check Flutter doctor for any missing dependencies

## 🎉 Ready to Use!

Your ATTRAL mobile apps are now ready! Both apps use WebView to display your existing web interface, providing a native mobile experience while leveraging all your existing functionality.

**Happy coding!** 🚀📱
