(function(){
  // Wait for config to load if not available immediately
  function getFirebaseConfig() {
    if (window.ATTRAL_PUBLIC && window.ATTRAL_PUBLIC.FIREBASE_CONFIG) {
      return window.ATTRAL_PUBLIC.FIREBASE_CONFIG;
    }
    
    // Fallback config with your actual credentials
    return {
      apiKey: "AIzaSyCMzmyqQ-WJuYrK0dNsTqljlDsCkmOIXOk",
      authDomain: "e-commerce-1d40f.firebaseapp.com",
      projectId: "e-commerce-1d40f",
      storageBucket: "e-commerce-1d40f.firebasestorage.app",
      messagingSenderId: "972578972293",
      appId: "1:972578972293:web:3aa31f43650c08cdc17fec",
      measurementId: "G-F47FSRF835"
    };
  }

  const firebaseConfig = getFirebaseConfig();

  if (!firebaseConfig.apiKey || firebaseConfig.apiKey === "your-api-key-here" || firebaseConfig.apiKey === "") { 
    console.warn('Firebase not configured. Please update config.js with your Firebase credentials.');
    window.AttralFirebase = null; 
    return; 
  }
  
  console.log('Firebase config loaded:', firebaseConfig);

  // Load Firebase from CDN (Functions SDK removed - now using PHP APIs)
  const scripts = [
    'https://www.gstatic.com/firebasejs/10.12.5/firebase-app-compat.js',
    'https://www.gstatic.com/firebasejs/10.12.5/firebase-auth-compat.js',
    'https://www.gstatic.com/firebasejs/10.12.5/firebase-firestore-compat.js',
    'https://www.gstatic.com/firebasejs/10.12.5/firebase-analytics-compat.js',
    'https://www.gstatic.com/firebasejs/10.12.5/firebase-storage-compat.js'
    // Firebase Functions SDK removed - now using PHP APIs (affiliate_functions.php)
  ];

  let loadedScripts = 0;
  
  scripts.forEach(src => {
    const script = document.createElement('script');
    script.src = src;
    script.onload = function() {
      console.log('Firebase script loaded:', src);
      loadedScripts++;
      if (loadedScripts === scripts.length) {
        console.log('All Firebase scripts loaded, initializing...');
        initializeFirebase();
      }
    };
    script.onerror = function() {
      console.error('Failed to load Firebase script:', src);
      loadedScripts++;
      if (loadedScripts === scripts.length) {
        console.log('All Firebase scripts processed, initializing...');
        initializeFirebase();
      }
    };
    document.head.appendChild(script);
  });

  function initializeFirebase() {
    try {
      console.log('Initializing Firebase with config:', firebaseConfig);
      
      if (typeof firebase === 'undefined') {
        console.error('Firebase SDK not loaded');
        window.AttralFirebase = null;
        return;
      }
      
      const app = firebase.initializeApp(firebaseConfig);
      const auth = firebase.auth();
      const db = firebase.firestore();
      const analytics = firebase.analytics ? firebase.analytics() : null;
      const storage = firebase.storage ? firebase.storage() : null;
      // Functions removed - now using PHP APIs via callFunction()
      const functions = null; // Kept for backward compatibility, always null
      
      // Initialize Google Auth Provider
      const googleProvider = new firebase.auth.GoogleAuthProvider();
      googleProvider.addScope('email');
      googleProvider.addScope('profile');
      
      console.log('Firebase initialized successfully');
      
      window.AttralFirebase = { 
        app, 
        auth, 
        db, 
        analytics, 
        storage,
        functions,
        googleProvider,
        // Helper methods
        signInWithEmail: signInWithEmail,
        signInWithEmailLink: signInWithEmailLink,
        signUpWithEmail: signUpWithEmail,
        signOut: signOut,
        getCurrentUser: getCurrentUser,
        saveUserData: saveUserData,
        saveUserProfile: saveUserProfile,
        callFunction: callFunction,
        trackEvent: trackEvent,
        health: { ok: false, lastCheckedAt: null, details: null }
      };
      
      // Perform a lightweight runtime connectivity health check
      runFirebaseHealthCheck();

      // Auth state change handler with admin bypass
      auth.onAuthStateChanged(function(user) { 
        if (user) {
          console.log('User signed in:', user.uid, user.email || 'Anonymous');
          // Track user login
          if (analytics) {
            analytics.logEvent('login', { method: user.isAnonymous ? 'anonymous' : 'email' });
          }
          // Upsert user profile document for authenticated users only
          if (!user.isAnonymous) {
            try {
              saveUserProfile(user).catch(function(e){ console.warn('Failed to save user profile:', e && e.message); });
            } catch (e) { console.warn('Profile upsert error:', e && e.message); }
          }
          // Re-run health check once a user is available
          runFirebaseHealthCheck();
        } else {
          console.log('No user signed in');
          
            // Note: Admin authentication now handled through proper Firebase auth
            // No mock user context needed
        }
      });
      
      // Initialize analytics
      if (analytics) {
        analytics.logEvent('page_view');
      }
      
    } catch (error) {
      console.error('Firebase initialization failed:', error);
      window.AttralFirebase = null;
    }
  }

  // Authentication helper methods
  function signInWithEmail(email, password) {
    return firebase.auth().signInWithEmailAndPassword(email, password);
  }

  function signInWithEmailLink(email) {
    const actionCodeSettings = {
      // URL you want to redirect back to
      url: window.location.origin + window.location.pathname,
      // This must be true for email link sign-in
      handleCodeInApp: true,
    };
    
    return firebase.auth().sendSignInLinkToEmail(email, actionCodeSettings);
  }

  function signUpWithEmail(email, password, userData = {}) {
    return firebase.auth().createUserWithEmailAndPassword(email, password)
      .then(userCredential => {
        // Save additional user data
        if (Object.keys(userData).length > 0) {
          return saveUserData(userCredential.user.uid, userData);
        }
        return userCredential;
      });
  }

  function signOut() {
    return firebase.auth().signOut();
  }

  function getCurrentUser() {
    return firebase.auth().currentUser;
  }

  function saveUserData(uid, data) {
    return firebase.firestore().collection('users').doc(uid).set(data, { merge: true });
  }

  // Upsert minimal user profile into users/{uid}
  function saveUserProfile(user) {
    try {
      var db = firebase.firestore();
      var uid = user && user.uid;
      if (!uid) { return Promise.resolve(); }
      var profile = {
        uid: uid,
        email: user.email || null,
        displayName: user.displayName || null,
        phone: user.phoneNumber || null,
        photoURL: user.photoURL || null,
        isAnonymous: !!user.isAnonymous,
        providerIds: (user.providerData || []).map(function(p){ return p && p.providerId; }).filter(Boolean),
        lastLoginAt: new Date()
      };
      // Only set createdAt once
      return db.collection('users').doc(uid).set({ createdAt: firebase.firestore.FieldValue.serverTimestamp() }, { merge: true })
        .then(function(){ return db.collection('users').doc(uid).set(profile, { merge: true }); });
    } catch (e) {
      return Promise.reject(e);
    }
  }

  function trackEvent(eventName, parameters = {}) {
    if (window.AttralFirebase && window.AttralFirebase.analytics) {
      window.AttralFirebase.analytics.logEvent(eventName, parameters);
    }
  }

  function callFunction(name, data) {
    // Use PHP API instead of Firebase Functions for Hostinger compatibility
    const apiUrl = `api/affiliate_functions.php?action=${name}`;
    
    return fetch(apiUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(data || {})
    })
    .then(response => {
      if (!response.ok) {
        throw new Error(`API error: ${response.status}`);
      }
      return response.json();
    })
    .then(result => {
      if (result.success === false) {
        throw new Error(result.error || 'API request failed');
      }
      return result;
    })
    .catch(error => {
      console.error(`Function ${name} failed:`, error);
      throw error;
    });
  }

  // Connectivity health check: verifies SDK init and Firestore read access
  function runFirebaseHealthCheck() {
    try {
      var now = new Date().toISOString();
      if (!window.AttralFirebase || !window.AttralFirebase.app) {
        updateHealth(false, now, 'App not initialized');
        return;
      }
      var db = window.AttralFirebase.db;
      if (!db) {
        updateHealth(false, now, 'Firestore not available');
        return;
      }
      // Try a minimal public read: fetch 1 product if collection exists
      db.collection('products').limit(1).get()
        .then(function(snapshot) {
          var ok = true;
          var details = 'Connected. products count≥0, docsFetched=' + snapshot.size;
          updateHealth(ok, now, details);
        })
        .catch(function(err) {
          // Still mark SDK reachable, but include error details
          console.warn('Firestore health check failed:', err && err.code, err && err.message);
          var details = 'Firestore query failed: ' + (err && (err.code || err.message) || 'unknown');
          updateHealth(false, now, details);
        });
    } catch (e) {
      var now2 = new Date().toISOString();
      updateHealth(false, now2, 'Health check exception: ' + (e && e.message));
    }
  }

  function updateHealth(ok, when, details) {
    if (!window.AttralFirebase) return;
    window.AttralFirebase.health = { ok: !!ok, lastCheckedAt: when, details: details };
    try {
      window.dispatchEvent(new CustomEvent('attral-firebase-ready', { detail: window.AttralFirebase.health }));
    } catch (e) {
      // ignore
    }
    console.log('Firebase health:', window.AttralFirebase.health);
  }
})();


