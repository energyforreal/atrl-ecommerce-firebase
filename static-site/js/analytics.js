/**
 * Google Analytics 4 (GA4) Enhanced eCommerce Tracking
 * ATTRAL eCommerce Website
 * 
 * This file provides centralized analytics tracking for the entire website.
 * It includes page view tracking and enhanced eCommerce event tracking.
 * 
 * SETUP INSTRUCTIONS:
 * 1. Get your GA4 Measurement ID from Google Analytics (format: G-XXXXXXXXXX)
 * 2. Replace the GA4_MEASUREMENT_ID value below with your actual ID
 * 3. Upload this file to your Hostinger hosting in the js/ folder
 * 
 * Compatible with: Hostinger shared hosting (no Node.js required)
 */

// ============================================================================
// CONFIGURATION - YOUR MEASUREMENT ID
// ============================================================================
const GA4_MEASUREMENT_ID = 'G-5BKD99T9M8'; // Your Google Analytics 4 Measurement ID

// ============================================================================
// GA4 INITIALIZATION
// ============================================================================

// Load Google Analytics gtag.js script
(function() {
  // Only initialize if a valid Measurement ID is provided
  if (GA4_MEASUREMENT_ID === 'G-XXXXXXXXXX') {
    console.warn('⚠️ Google Analytics: Please add your GA4 Measurement ID in analytics.js');
    return;
  }

  // Create and inject the gtag.js script
  const script = document.createElement('script');
  script.async = true;
  script.src = `https://www.googletagmanager.com/gtag/js?id=${GA4_MEASUREMENT_ID}`;
  document.head.appendChild(script);

  // Initialize gtag
  window.dataLayer = window.dataLayer || [];
  function gtag() {
    dataLayer.push(arguments);
  }
  window.gtag = gtag;

  gtag('js', new Date());
  gtag('config', GA4_MEASUREMENT_ID, {
    'send_page_view': true, // Automatic page view tracking
    'currency': 'INR' // Default currency for eCommerce events
  });

  console.log('✅ Google Analytics 4 initialized with ID:', GA4_MEASUREMENT_ID);
})();

// ============================================================================
// ENHANCED ECOMMERCE TRACKING FUNCTIONS
// ============================================================================

/**
 * Track product view (view_item event)
 * Call this when a user views a product detail page
 * 
 * @param {Object} product - Product object
 * @param {string} product.id - Product ID
 * @param {string} product.title - Product name
 * @param {number} product.price - Product price
 * @param {string} product.category - Product category (optional)
 */
window.trackViewItem = function(product) {
  if (!window.gtag || !product) return;

  try {
    gtag('event', 'view_item', {
      currency: 'INR',
      value: product.price,
      items: [{
        item_id: product.id,
        item_name: product.title || product.name,
        price: product.price,
        item_category: product.category || 'Electronics',
        quantity: 1
      }]
    });

    console.log('📊 GA4 Event: view_item', product);
  } catch (error) {
    console.error('GA4 trackViewItem error:', error);
  }
};

/**
 * Track add to cart (add_to_cart event)
 * Call this when a user adds a product to their cart
 * 
 * @param {Object} product - Product object
 * @param {string} product.id - Product ID
 * @param {string} product.title - Product name
 * @param {number} product.price - Product price
 * @param {number} quantity - Quantity added (default: 1)
 */
window.trackAddToCart = function(product, quantity = 1) {
  if (!window.gtag || !product) return;

  try {
    const value = product.price * quantity;

    gtag('event', 'add_to_cart', {
      currency: 'INR',
      value: value,
      items: [{
        item_id: product.id,
        item_name: product.title || product.name,
        price: product.price,
        item_category: product.category || 'Electronics',
        quantity: quantity
      }]
    });

    console.log('📊 GA4 Event: add_to_cart', { product, quantity, value });
  } catch (error) {
    console.error('GA4 trackAddToCart error:', error);
  }
};

/**
 * Track cart view (view_cart event)
 * Call this when a user views their shopping cart
 * 
 * @param {Array} cartItems - Array of cart items
 * Each item should have: id, title/name, price, quantity
 */
window.trackViewCart = function(cartItems) {
  if (!window.gtag || !cartItems || cartItems.length === 0) return;

  try {
    const items = cartItems.map(item => ({
      item_id: item.id,
      item_name: item.title || item.name,
      price: item.price,
      item_category: item.category || 'Electronics',
      quantity: item.quantity || 1
    }));

    const totalValue = cartItems.reduce((sum, item) => {
      return sum + (item.price * (item.quantity || 1));
    }, 0);

    gtag('event', 'view_cart', {
      currency: 'INR',
      value: totalValue,
      items: items
    });

    console.log('📊 GA4 Event: view_cart', { items: cartItems, totalValue });
  } catch (error) {
    console.error('GA4 trackViewCart error:', error);
  }
};

/**
 * Track begin checkout (begin_checkout event)
 * Call this when a user starts the checkout process
 * 
 * @param {Array} cartItems - Array of cart items
 * Each item should have: id, title/name, price, quantity
 */
window.trackBeginCheckout = function(cartItems) {
  if (!window.gtag || !cartItems || cartItems.length === 0) return;

  try {
    const items = cartItems.map(item => ({
      item_id: item.id,
      item_name: item.title || item.name,
      price: item.price,
      item_category: item.category || 'Electronics',
      quantity: item.quantity || 1
    }));

    const totalValue = cartItems.reduce((sum, item) => {
      return sum + (item.price * (item.quantity || 1));
    }, 0);

    gtag('event', 'begin_checkout', {
      currency: 'INR',
      value: totalValue,
      items: items
    });

    console.log('📊 GA4 Event: begin_checkout', { items: cartItems, totalValue });
  } catch (error) {
    console.error('GA4 trackBeginCheckout error:', error);
  }
};

/**
 * Track purchase completion (purchase event)
 * Call this on the order success page after a successful purchase
 * 
 * @param {Object} orderData - Order information
 * @param {string} orderData.transactionId - Unique order ID
 * @param {number} orderData.total - Total order value
 * @param {number} orderData.tax - Tax amount (optional)
 * @param {number} orderData.shipping - Shipping cost (optional)
 * @param {string} orderData.coupon - Coupon code used (optional)
 * @param {Array} orderData.items - Array of purchased items
 */
window.trackPurchase = function(orderData) {
  if (!window.gtag || !orderData) return;

  try {
    const items = orderData.items.map(item => ({
      item_id: item.id,
      item_name: item.title || item.name,
      price: item.price,
      item_category: item.category || 'Electronics',
      quantity: item.quantity || 1
    }));

    const eventData = {
      transaction_id: orderData.transactionId,
      value: orderData.total,
      currency: 'INR',
      items: items
    };

    // Add optional parameters if provided
    if (orderData.tax) eventData.tax = orderData.tax;
    if (orderData.shipping) eventData.shipping = orderData.shipping;
    if (orderData.coupon) eventData.coupon = orderData.coupon;

    gtag('event', 'purchase', eventData);

    console.log('📊 GA4 Event: purchase', eventData);
  } catch (error) {
    console.error('GA4 trackPurchase error:', error);
  }
};

/**
 * Track custom event
 * Use this for any custom tracking needs
 * 
 * @param {string} eventName - Name of the event
 * @param {Object} eventParams - Event parameters
 */
window.trackCustomEvent = function(eventName, eventParams = {}) {
  if (!window.gtag || !eventName) return;

  try {
    gtag('event', eventName, eventParams);
    console.log('📊 GA4 Custom Event:', eventName, eventParams);
  } catch (error) {
    console.error('GA4 trackCustomEvent error:', error);
  }
};

// ============================================================================
// AUTOMATIC PAGE VIEW TRACKING
// ============================================================================

// Page views are automatically tracked by GA4 config
// Additional page-specific tracking can be added here if needed

console.log('📊 Google Analytics tracking initialized for ATTRAL eCommerce');

