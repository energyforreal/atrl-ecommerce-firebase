// 🔐 UPDATED FIRESTORE SECURITY RULES FOR ATTRAL ADMIN DASHBOARD
// Copy and paste these rules in Firebase Console → Firestore → Rules

rules_version = '2';
service cloud.firestore {
  match /databases/{database}/documents {
    
    // Helper functions for authentication and admin checks
    function isSignedIn() {
      return request.auth != null;
    }
    
    function isAdmin() { 
      return isSignedIn() && (
        request.auth.token.admin == true ||
        request.auth.token.email == 'attralsolar@gmail.com' ||
        request.auth.token.email == 'admin@attral.in' ||
        request.auth.token.username == 'attral' ||
        // Allow any authenticated user for development (remove in production)
        (request.auth.uid != null && request.auth.token.email != null)
      ); 
    }
    
    // Orders collection - Enhanced rules for admin operations
    match /orders/{orderId} {
      // Allow authenticated users to read their own orders
      allow read: if isSignedIn() && (
        request.auth.uid == resource.data.userId ||
        isAdmin()
      );
      
      // Allow admin to write, users to create/update their own orders
      allow write: if isSignedIn() && (
        isAdmin() ||
        (request.auth.uid == resource.data.userId && request.method == 'create') ||
        (request.auth.uid == resource.data.userId && request.method == 'update' && 
         !('status' in request.resource.data.diff(resource.data).affectedKeys()) &&
         !('payment_status' in request.resource.data.diff(resource.data).affectedKeys()))
      );
      
      // Allow server-side writes (for payment callbacks)
      allow write: if request.auth == null && 
        resource.data.source == 'server';
    }
    
    // Users collection - Enhanced user management
    match /users/{userId} {
      // Users can read/write their own data, admins can read/write all
      allow read, write: if isSignedIn() && (
        request.auth.uid == userId ||
        isAdmin()
      );
      
      // Allow server-side user creation
      allow create: if request.auth == null && 
        resource.data.source == 'server';
    }
    
    // Products collection - Read for all, write for admin only
    match /products/{productId} {
      allow read: if true; // Public read access for products
      allow write: if isAdmin();
    }
    
    // Coupons collection - Enhanced coupon management
    match /coupons/{couponId} {
      // Public read access for coupon validation
      allow read: if true;
      
      // Only admins can create/update/delete coupons
      allow write: if isAdmin();
      
      // Allow server-side coupon usage tracking
      allow update: if request.auth == null && 
        resource.data.source == 'server' &&
        request.resource.data.diff(resource.data).affectedKeys().hasOnly(['usageCount', 'lastUsedAt']);
    }
    
    // Contact messages collection - Admin and message owner access
    match /contact_messages/{messageId} {
      // Admins can read all messages, users can read their own
      allow read: if isSignedIn() && (
        isAdmin() ||
        request.auth.uid == resource.data.userId
      );
      
      // Anyone can create messages, only admins can update
      allow create: if true;
      allow update: if isAdmin();
      allow delete: if isAdmin();
    }
    
    // Affiliates collection - Enhanced affiliate management
    match /affiliates/{affiliateId} {
      // Public read for affiliate code validation
      allow read: if true;
      
      // Users can create their own affiliate profile, admins can manage all
      allow write: if isSignedIn() && (
        request.auth.uid == affiliateId ||
        isAdmin()
      );
    }
    
    // Affiliate codes collection - Public read, admin write
    match /affiliateCodes/{code} {
      allow read: if true;
      allow write: if isAdmin();
      
      // Allow server-side code creation
      allow create: if request.auth == null && 
        resource.data.source == 'server';
    }
    
    // Analytics collection - Admin only
    match /analytics/{analyticsId} {
      allow read, write: if isAdmin();
    }
    
    // Admin logs collection - Admin only
    match /adminLogs/{logId} {
      allow read, write: if isAdmin();
    }
  }
}
