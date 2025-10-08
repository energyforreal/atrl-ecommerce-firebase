// Dashboard Initialization and Main Functions

// Initialize dashboard when page loads
document.addEventListener('DOMContentLoaded', () => {
  console.log('🚀 Starting Dashboard with Firestore...');
  window.dashboardManager = new DashboardManager();
  
  // Add global debug function
  window.debugDashboard = () => {
    console.log('🔍 Dashboard Debug Info:');
    console.log('Firebase:', window.AttralFirebase);
    console.log('Dashboard Manager:', window.dashboardManager);
    console.log('Dashboard Data:', window.dashboardManager?.data);
    console.log('Is Initialized:', window.dashboardManager?.isInitialized);
    
    // Test Firestore connection
    if (window.AttralFirebase?.db) {
      window.AttralFirebase.db.collection('orders').limit(1).get()
        .then(snapshot => {
          console.log('✅ Firestore test successful:', snapshot.size, 'documents');
        })
        .catch(error => {
          console.error('❌ Firestore test failed:', error);
        });
    } else {
      console.error('❌ Firebase not available');
    }
  };
  
  // Add manual refresh function
  window.refreshDashboard = () => {
    if (window.dashboardManager) {
      console.log('🔄 Manual dashboard refresh...');
      window.dashboardManager.loadAllData();
    }
  };
  
  console.log('💡 Debug functions available: debugDashboard(), refreshDashboard()');
});

// Update the year in footer
document.addEventListener('DOMContentLoaded', function() {
  const yearElement = document.getElementById('year');
  if (yearElement) {
    yearElement.textContent = new Date().getFullYear();
  }
});
