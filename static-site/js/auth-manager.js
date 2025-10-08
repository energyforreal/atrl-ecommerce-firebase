// Unified Authentication Manager
// This prevents authentication clashes between different dashboards

class AuthManager {
  constructor() {
    this.authStateListeners = [];
    this.currentUser = null;
    this.isInitialized = false;
  }

  // Initialize the auth manager
  init() {
    if (this.isInitialized) return;
    
    const fb = window.AttralFirebase;
    if (!fb || !fb.auth) {
      console.warn('Firebase not available for AuthManager');
      return;
    }

    // Single auth state listener
    fb.auth.onAuthStateChanged((user) => {
      this.currentUser = user;
      this.notifyListeners(user);
    });

    this.isInitialized = true;
    console.log('AuthManager initialized');
  }

  // Add auth state listener
  addAuthStateListener(callback) {
    this.authStateListeners.push(callback);
    
    // If user is already available, call immediately
    if (this.currentUser !== null) {
      callback(this.currentUser);
    }
  }

  // Remove auth state listener
  removeAuthStateListener(callback) {
    const index = this.authStateListeners.indexOf(callback);
    if (index > -1) {
      this.authStateListeners.splice(index, 1);
    }
  }

  // Notify all listeners
  notifyListeners(user) {
    this.authStateListeners.forEach(callback => {
      try {
        callback(user);
      } catch (error) {
        console.error('Error in auth state listener:', error);
      }
    });
  }

  // Get current user
  getCurrentUser() {
    return this.currentUser;
  }

  // Check if user is authenticated
  isAuthenticated() {
    return this.currentUser && !this.currentUser.isAnonymous;
  }

  // Sign out
  signOut() {
    const fb = window.AttralFirebase;
    if (fb && fb.auth) {
      return fb.auth.signOut();
    }
    return Promise.reject('Firebase not available');
  }
}

// Create global instance
window.AuthManager = new AuthManager();

// Auto-initialize when Firebase is ready
function initializeAuthManager() {
  if (window.AttralFirebase && window.AttralFirebase.auth) {
    window.AuthManager.init();
  } else {
    // Wait for Firebase to load
    setTimeout(initializeAuthManager, 100);
  }
}

// Start initialization
initializeAuthManager();
