(function(){
  const STORAGE_KEYS = { CART: 'attral_cart', REF: 'attral_ref' };

  // Capture ?ref and common UTM params, store for 30 days
  (function captureReferral(){
    try {
      const params = new URLSearchParams(window.location.search);
      const ref = params.get('ref');
      const utm_source = params.get('utm_source');
      const utm_medium = params.get('utm_medium');
      const utm_campaign = params.get('utm_campaign');
      if (ref || utm_source || utm_medium || utm_campaign) {
        const payload = {
          ref: ref || null,
          utm: { source: utm_source || null, medium: utm_medium || null, campaign: utm_campaign || null },
          ts: Date.now()
        };
        localStorage.setItem(STORAGE_KEYS.REF, JSON.stringify(payload));
      }
    } catch (e) { /* no-op */ }
  })();

  function readReferral(){
    try {
      const raw = localStorage.getItem(STORAGE_KEYS.REF);
      if (!raw) return null;
      const data = JSON.parse(raw);
      const THIRTY_DAYS = 30 * 24 * 60 * 60 * 1000;
      if (!data.ts || (Date.now() - data.ts) > THIRTY_DAYS) { localStorage.removeItem(STORAGE_KEYS.REF); return null; }
      return data;
    } catch { return null; }
  }

  function readCart(){
    try { return JSON.parse(localStorage.getItem(STORAGE_KEYS.CART) || '[]'); } catch { return []; }
  }
  function writeCart(items){ localStorage.setItem(STORAGE_KEYS.CART, JSON.stringify(items)); }
  
  // 🛒 AUTOMATIC CART VALIDATION - Ensures cart is always clean
  async function validateAndCleanCart() {
    try {
      const cart = readCart();
      if (cart.length === 0) {
        console.log('✅ Cart is empty - no validation needed');
        return cart;
      }
      
      console.log('🔍 Validating cart items:', cart.length, 'items');
      
      // Load current products to validate against
      const products = await fetchProducts().catch(() => []);
      
      if (products.length === 0) {
        console.warn('⚠️ Could not load products for validation - keeping cart as is');
        return cart;
      }
      
      // Validate each cart item
      const validatedCart = cart.filter(item => {
        // Remove test/demo items (common test patterns)
        const testPatterns = ['test', 'demo', 'sample', 'example', 'placeholder'];
        const isTestItem = testPatterns.some(pattern => 
          String(item.id).toLowerCase().includes(pattern) ||
          String(item.title).toLowerCase().includes(pattern)
        );
        
        if (isTestItem) {
          console.warn('🗑️ Removing test/demo cart item:', item.id, item.title);
          return false;
        }
        
        // Check if product still exists in current product list
        const productExists = products.some(p => p.id === item.id);
        
        if (!productExists) {
          console.warn('🗑️ Removing invalid cart item (product not found):', item.id, item.title);
          return false;
        }
        
        // Check if item has required fields
        if (!item.id || !item.price || !item.title) {
          console.warn('🗑️ Removing invalid cart item (missing required fields):', item);
          return false;
        }
        
        // Item is valid
        return true;
      });
      
      // If cart changed, update localStorage
      if (validatedCart.length !== cart.length) {
        console.log(`🧹 Cleaned cart: ${cart.length} → ${validatedCart.length} items`);
        writeCart(validatedCart);
        return validatedCart;
      }
      
      console.log('✅ Cart validation complete - all items valid');
      return validatedCart;
      
    } catch (error) {
      console.error('❌ Cart validation error:', error);
      return readCart(); // Return current cart on error
    }
  }
  
  function updateHeaderCount(){
    const items = readCart();
    const count = items.reduce((a,i)=>a + (i.quantity||1), 0);
    const el = document.getElementById('cart-count');
    if (el) el.textContent = String(count);
  }

  async function fetchProducts(){
    console.log('📦 fetchProducts called');
    
    // Try Firestore first if available
    if (window.AttralFirebase && window.AttralFirebase.db) {
      try {
        console.log('🔥 Trying Firestore first');
        const db = window.AttralFirebase.db;
        const snap = await db.collection('products').where('status','in',['active', true]).get();
        const firestoreProducts = snap.docs.map(d=>{
          const p = d.data();
          return {
            id: d.id,
            title: p.name || p.title || d.id,
            price: p.price || 0,
            image: Array.isArray(p.images) && p.images.length ? p.images[0] : p.image,
            featured: !!p.featured,
            description: p.description || ''
          };
        });
        console.log('🔥 Firestore products loaded:', firestoreProducts);
        return firestoreProducts;
      } catch (error) {
        console.warn('🔥 Firestore products failed, falling back to JSON:', error);
      }
    } else {
      console.log('🔥 Firestore not available, using JSON fallback');
    }
    
    // Fallback to products.json
    try {
      console.log('📄 Loading products from data/products.json');
      const response = await fetch('data/products.json');
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      const products = await response.json();
      console.log('📄 Raw products from JSON:', products);
      
      const mappedProducts = products.map(p => ({
        id: p.id,
        title: p.title,
        price: p.price,
        image: p.image,
        featured: !!p.featured,
        description: p.description || ''
      }));
      
      console.log('📄 Mapped products:', mappedProducts);
      return mappedProducts;
    } catch (error) {
      console.error('❌ Failed to load products from JSON:', error);
      throw new Error('Unable to load products from any source');
    }
  }

  function createProductCard(product){
    const card = document.createElement('div');
    card.className = 'card';
    const img = document.createElement('img');
    img.className = 'img';
    img.loading = 'lazy';
    img.src = product.image || 'https://via.placeholder.com/640x480?text=Product';
    img.alt = product.title;
    const body = document.createElement('div');
    body.className = 'body';
    const h = document.createElement('div');
    h.className = 'title';
    h.textContent = product.title;
    const p = document.createElement('div');
    p.className = 'price';
    p.textContent = `₹${product.price}`;
    const actions = document.createElement('div');
    actions.className = 'actions';
    const addBtn = document.createElement('button');
    addBtn.className = 'btn btn-primary';
    addBtn.textContent = 'Add to cart';
    addBtn.onclick = ()=> addToCart(product.id);
    actions.appendChild(addBtn);
    body.append(h,p,actions);
    card.append(img, body);
    return card;
  }

  function addToCart(productId){
    console.log('🛒 Attral addToCart called with productId:', productId);
    return fetchProducts().then(products=>{
      console.log('📦 Products loaded in addToCart:', products);
      console.log('🔍 Looking for product with ID:', productId, 'type:', typeof productId);
      console.log('🔍 Product IDs in array:', products.map(p => ({ id: p.id, type: typeof p.id })));
      
      // Simple direct comparison - the issue might be in the mapping
      let product = products.find(p => p.id === productId);
      console.log('🔍 Direct comparison result:', product);
      
      // ✅ REMOVED TESTING FALLBACK - Do NOT add first product if not found
      // This was causing cart to populate with items automatically
      
      console.log('🔍 Final product selected:', product);
      
      if(!product) {
        console.error('❌ Product not found with ID:', productId);
        console.error('❌ Available products:', products.map(p => ({ id: p.id, title: p.title })));
        console.error('❌ Aborting addToCart - will not add random product');
        notify('Product not found');
        return null; // Return null instead of undefined
      }
      
      const items = readCart();
      console.log('🛒 Current cart items:', items);
      const existing = items.find(i => i.id === productId);
      console.log('🔍 Existing item in cart:', existing);
      
      if(existing){ 
        existing.quantity += 1; 
        console.log('➕ Updated quantity for existing item:', existing);
      } else { 
        items.push({ id: product.id, title: product.title, price: product.price, image: product.image, quantity: 1 }); 
        console.log('➕ Added new item to cart:', items[items.length - 1]);
      }
      
      console.log('🛒 Updated cart items:', items);
      writeCart(items);
      updateHeaderCount();
      notify('Added to cart 🛒');
      console.log('✅ Product added to cart successfully');
      
      // Track add to cart event in GA4
      if (window.trackAddToCart) {
        window.trackAddToCart(product, 1);
      }
      
      return product; // Return the product for success confirmation
    }).catch(error => {
      console.error('❌ Failed to add to cart:', error);
      throw error; // Re-throw to be caught by the caller
    });
  }

  function renderProductsInto(containerId, products){
    const root = document.getElementById(containerId);
    if(!root) return;
    root.innerHTML = '';
    products.forEach(p=> root.appendChild(createProductCard(p)));
  }

  function renderFeaturedProducts(containerId){
    fetchProducts().then(products=>{
      const featured = products.filter(p=>p.featured).slice(0,4);
      renderProductsInto(containerId, featured);
    }).catch(()=>{
      const root = document.getElementById(containerId);
      if(root) root.textContent = 'Failed to load products.';
    });
  }

  function renderAllProducts(containerId, query){
    fetchProducts().then(products=>{
      let list = products;
      if(query){
        const q = query.toLowerCase();
        list = list.filter(p=> (p.title||'').toLowerCase().includes(q) || (p.description||'').toLowerCase().includes(q));
      }
      renderProductsInto(containerId, list);
    }).catch(()=>{
      const root = document.getElementById(containerId);
      if(root) root.textContent = 'Failed to load products.';
    });
  }

  async function renderCart(containerId){
    const root = document.getElementById(containerId);
    if(!root) return;
    const items = readCart();
    root.innerHTML = '';
    
    // Track cart view in GA4 if cart has items
    if (items.length > 0 && window.trackViewCart) {
      window.trackViewCart(items);
    }
    
    if(items.length===0){ 
      root.innerHTML = `
        <div class="cart-empty">
          <div class="cart-empty-icon">
            <svg width="60" height="60" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M7 4V2C7 1.45 7.45 1 8 1H16C16.55 1 17 1.45 17 2V4H20C20.55 4 21 4.45 21 5S20.55 6 20 6H19V19C19 20.1 18.1 21 17 21H7C5.9 21 5 20.1 5 19V6H4C3.45 6 3 5.55 3 5S3.45 4 4 4H7ZM9 3V4H15V3H9ZM7 6V19H17V6H7Z" fill="currentColor"/>
            </svg>
          </div>
          <h3>Your cart is empty</h3>
          <p>Looks like you haven't added any items to your cart yet. Start shopping to find amazing products!</p>
          <a href="shop.html" class="continue-btn">Continue Shopping</a>
        </div>
      `;
      return; 
    }
    
    // Load current prices from products.json
    let currentPrices = {};
    try {
      const response = await fetch('data/products.json');
      const products = await response.json();
      products.forEach(product => {
        // Map both numeric ID and slug ID to current price
        currentPrices[product.id] = product.price;
        const slugId = product.title.toLowerCase().replace(/[^a-z0-9\s-]/g,'').replace(/\s+/g,'-').replace(/-+/g,'-').trim();
        currentPrices[slugId] = product.price;
      });
    } catch (error) {
      console.warn('Failed to load current prices, using stored prices:', error);
    }
    
    let total = 0;
    items.forEach(item=>{
      // Use current price from products.json if available, otherwise fallback to stored price
      const currentPrice = currentPrices[item.id] || item.price || 0;
      total += currentPrice * item.quantity;
      
      const line = document.createElement('div');
      line.className = 'line';
      
      // Product meta section
      const meta = document.createElement('div');
      meta.className = 'meta';
      
      const img = document.createElement('img'); 
      img.src = item.image; 
      img.alt = item.title;
      
      const productInfo = document.createElement('div');
      productInfo.className = 'product-info';
      
      const title = document.createElement('div'); 
      title.className = 'product-title';
      title.textContent = item.title;
      
      const price = document.createElement('div'); 
      price.className = 'product-price';
      price.textContent = `₹${currentPrice.toLocaleString()}`;
      
      productInfo.append(title, price);
      meta.append(img, productInfo);
      
      // Quantity controls
      const qty = document.createElement('div'); 
      qty.className = 'qty';
      
      const qtyControls = document.createElement('div');
      qtyControls.className = 'qty-controls';
      
      const minus = document.createElement('button'); 
      minus.className = 'qty-btn'; 
      minus.textContent = '−'; 
      minus.onclick = () => changeQty(item.id, -1, containerId);
      
      const span = document.createElement('span'); 
      span.className = 'qty-display';
      span.textContent = String(item.quantity);
      
      const plus = document.createElement('button'); 
      plus.className = 'qty-btn'; 
      plus.textContent = '+'; 
      plus.onclick = () => changeQty(item.id, 1, containerId);
      
      qtyControls.append(minus, span, plus);
      
      const remove = document.createElement('button'); 
      remove.className = 'remove-btn'; 
      remove.textContent = 'Remove'; 
      remove.onclick = () => removeItem(item.id, containerId);
      
      qty.append(qtyControls, remove);
      line.append(meta, qty);
      root.appendChild(line);
    });
    
    // Enhanced cart summary
    const summary = document.createElement('div'); 
    summary.className = 'cart-summary';
    
    const summaryContent = document.createElement('div');
    summaryContent.className = 'cart-summary-content';
    
    const totalLabel = document.createElement('div'); 
    totalLabel.className = 'total-label';
    totalLabel.textContent = 'Total Amount';
    
    const totalEl = document.createElement('div'); 
    totalEl.className = 'total-amount';
    totalEl.textContent = `₹${total.toLocaleString()}`;
    
    const checkout = document.createElement('button'); 
    checkout.className = 'checkout-btn'; 
    checkout.textContent = 'Proceed to Checkout';
    checkout.onclick = () => initiateCartCheckout(items, total);
    
    summaryContent.append(totalLabel, totalEl, checkout);
    summary.appendChild(summaryContent);
    root.appendChild(summary);
  }

  function changeQty(productId, delta, containerId){
    const items = readCart();
    const item = items.find(i=>i.id===productId);
    if(!item) return;
    item.quantity += delta;
    if(item.quantity<=0){
      const idx = items.findIndex(i=>i.id===productId);
      items.splice(idx,1);
    }
    writeCart(items);
    updateHeaderCount();
    renderCart(containerId).catch(console.error);
  }
  function removeItem(productId, containerId){
    const items = readCart().filter(i=>i.id!==productId);
    writeCart(items);
    updateHeaderCount();
    renderCart(containerId).catch(console.error);
  }

  function notify(message){
    console.log('Notification:', message);
  }

  // Enhanced Firebase tracking functions
  function trackProductView(productId, productTitle) {
    if (window.AttralFirebase && window.AttralFirebase.trackEvent) {
      window.AttralFirebase.trackEvent('view_item', {
        item_id: productId,
        item_name: productTitle,
        item_category: 'electronics'
      });
    }
  }

  function trackAddToCart(productId, productTitle, price) {
    if (window.AttralFirebase && window.AttralFirebase.trackEvent) {
      window.AttralFirebase.trackEvent('add_to_cart', {
        currency: 'INR',
        value: price,
        items: [{
          item_id: productId,
          item_name: productTitle,
          price: price,
          quantity: 1
        }]
      });
    }
  }

  function trackPurchase(orderId, items, total) {
    if (window.AttralFirebase && window.AttralFirebase.trackEvent) {
      window.AttralFirebase.trackEvent('purchase', {
        transaction_id: orderId,
        value: total,
        currency: 'INR',
        items: items.map(item => ({
          item_id: item.id,
          item_name: item.title,
          price: item.price,
          quantity: item.quantity
        }))
      });
    }
  }

  // Enhanced product card with tracking
  function createProductCard(product){
    const card = document.createElement('div');
    card.className = 'card';
    const img = document.createElement('img');
    img.className = 'img';
    img.loading = 'lazy';
    img.src = product.image || 'https://via.placeholder.com/640x480?text=Product';
    img.alt = product.title;
    const body = document.createElement('div');
    body.className = 'body';
    const h = document.createElement('div');
    h.className = 'title';
    h.textContent = product.title;
    const p = document.createElement('div');
    p.className = 'price';
    p.textContent = `₹${product.price}`;
    const actions = document.createElement('div');
    actions.className = 'actions';
    const addBtn = document.createElement('button');
    addBtn.className = 'btn btn-primary';
    addBtn.textContent = 'Add to cart';
    addBtn.onclick = ()=> {
      addToCart(product.id);
      trackAddToCart(product.id, product.title, product.price);
    };
    actions.appendChild(addBtn);
    body.append(h,p,actions);
    card.append(img, body);
    
    // Track product view when card is visible
    card.addEventListener('mouseenter', () => {
      trackProductView(product.id, product.title);
    });
    
    return card;
  }

  // Cart checkout functionality
  async function initiateCartCheckout(items, total) {
    try {
      // Store cart items for checkout
      sessionStorage.setItem('cartCheckout', JSON.stringify({
        items: items,
        total: total,
        type: 'cart'
      }));
      
      // Redirect to order page
      window.location.href = 'order.html?type=cart';
    } catch (error) {
      console.error('Cart checkout error:', error);
      notify('Error initiating checkout');
    }
  }

  // 🛒 CART CLEARING UTILITY - NO REDIRECTS, JUST DATA CLEANUP
  function clearCartSafely() {
    try {
      localStorage.removeItem('attral_cart');
      updateHeaderCount();
      console.log('✅ Cart cleared safely - no redirects involved');
      return true;
    } catch (error) {
      console.error('❌ Failed to clear cart:', error);
      return false;
    }
  }

  // Helper function to track begin checkout
  function trackBeginCheckoutFromCart() {
    const items = readCart();
    if (items.length > 0 && window.trackBeginCheckout) {
      window.trackBeginCheckout(items);
      console.log('📊 GA4: Begin checkout tracked');
    }
  }

  window.Attral = {
    initHeaderCartCount: updateHeaderCount,
    renderFeaturedProducts,
    renderAllProducts,
    renderCart,
    notify,
    addToCart,
    clearCartSafely, // 🆕 Safe cart clearing utility
    validateAndCleanCart, // 🆕 Automatic cart validation
    trackBeginCheckoutFromCart, // 🆕 GA4 begin checkout tracking
    calculateCartTotalPaise: function(){
      const items = readCart();
      const total = items.reduce((a,i)=> a + (i.price * i.quantity), 0);
      return Math.round(total * 100);
    },
    onPaymentSuccess: async function(order, response){
      console.log('🎉 Payment successful! Processing order...', {
        orderId: order.id,
        paymentId: response.razorpay_payment_id,
        amount: order.amount
      });

      // This function is now DEPRECATED - all payment success handling is done in order.html
      // This prevents competing redirect mechanisms
      console.log('⚠️ onPaymentSuccess called but redirect handled by order.html');
      
      // Only do background processing, no redirects
      try {
        const items = readCart();
        const total = items.reduce((a,i)=> a + (i.price * i.quantity), 0);
        
        // Track purchase event
        if (window.AttralFirebase && window.AttralFirebase.trackEvent) {
          trackPurchase(order.id, items, total);
        }
        
        console.log('✅ Background processing complete');
      } catch (e) {
        console.error('❌ Background processing error:', e);
      }
    },
    // Firebase helper methods
    trackProductView,
    trackAddToCart,
    trackPurchase
  };

  // Newsletter Form Handler
  function initializeNewsletterForm() {
    const form = document.getElementById('newsletter-form');
    const submitButton = document.getElementById('newsletter-submit');
    const successMessage = document.getElementById('newsletter-success');
    const errorMessage = document.getElementById('newsletter-error');
    
    if (!form || !submitButton) return;

    form.addEventListener('submit', async function(e) {
      e.preventDefault();
      
      // Get form data
      const formData = new FormData(form);
      const name = formData.get('FIRSTNAME').trim();
      const email = formData.get('EMAIL').trim();
      
      // Basic validation
      if (!name || !email) {
        showMessage(errorMessage, 'Please fill in all fields.');
        return;
      }
      
      if (!isValidEmail(email)) {
        showMessage(errorMessage, 'Please enter a valid email address.');
        return;
      }
      
      // Show loading state
      setLoadingState(true);
      hideMessages();
      
      try {
        // Submit to Brevo
        const response = await fetch(form.action, {
          method: 'POST',
          body: formData,
          headers: {
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
          }
        });
        
        // Check if submission was successful
        if (response.ok) {
          showMessage(successMessage, 'Welcome aboard! Your free shipping code is on its way!');
          form.reset();
          // Also record subscription in Firestore if available
          try {
            if (window.AttralFirebase && window.AttralFirebase.db) {
              await window.AttralFirebase.db.collection('newsletter').doc(email.toLowerCase()).set({
                email: email.toLowerCase(),
                subscribedAt: new Date(),
                source: 'footer',
                status: 'subscribed'
              }, { merge: true });
            }
          } catch (e) {
            console.warn('Failed to record newsletter signup to Firestore:', e);
          }
          
          // Track successful signup (optional analytics)
          if (typeof gtag !== 'undefined') {
            gtag('event', 'newsletter_signup', {
              event_category: 'engagement',
              event_label: 'free_shipping_code'
            });
          }
        } else {
          throw new Error('Submission failed');
        }
      } catch (error) {
        console.error('Newsletter submission error:', error);
        showMessage(errorMessage, 'Something went wrong. Please try again or contact support.');
      } finally {
        setLoadingState(false);
      }
    });
    
    function setLoadingState(loading) {
      const buttonText = submitButton.querySelector('.button-text');
      const buttonIcon = submitButton.querySelector('.button-icon');
      const buttonArrow = submitButton.querySelector('.button-arrow');
      const loadingSpinner = submitButton.querySelector('.loading-spinner');
      
      if (loading) {
        submitButton.disabled = true;
        buttonText.style.display = 'none';
        buttonIcon.style.display = 'none';
        buttonArrow.style.display = 'none';
        loadingSpinner.style.display = 'block';
      } else {
        submitButton.disabled = false;
        buttonText.style.display = 'inline';
        buttonIcon.style.display = 'inline';
        buttonArrow.style.display = 'inline';
        loadingSpinner.style.display = 'none';
      }
    }
    
    function showMessage(messageElement, customText = null) {
      hideMessages();
      if (customText && messageElement.querySelector('.message-content p')) {
        messageElement.querySelector('.message-content p').textContent = customText;
      }
      messageElement.style.display = 'flex';
      
      // Auto-hide after 5 seconds
      setTimeout(() => {
        hideMessages();
      }, 5000);
    }
    
    function hideMessages() {
      successMessage.style.display = 'none';
      errorMessage.style.display = 'none';
    }
    
    function isValidEmail(email) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return emailRegex.test(email);
    }
    
    // Add input validation feedback
    const inputs = form.querySelectorAll('.form-input');
    inputs.forEach(input => {
      input.addEventListener('blur', function() {
        validateField(input);
      });
      
      input.addEventListener('input', function() {
        // Clear error styling on input
        input.style.borderColor = '';
        const errorMsg = input.parentNode.parentNode.querySelector('.field-error');
        if (errorMsg) errorMsg.remove();
      });
    });
    
    function validateField(input) {
      const value = input.value.trim();
      const isEmail = input.type === 'email';
      const isName = input.name === 'FIRSTNAME';
      
      let isValid = false;
      let errorMessage = '';
      
      if (!value) {
        errorMessage = isName ? 'Name is required' : 'Email is required';
      } else if (isEmail && !isValidEmail(value)) {
        errorMessage = 'Please enter a valid email address';
      } else if (isName && value.length < 2) {
        errorMessage = 'Name must be at least 2 characters';
      } else {
        isValid = true;
      }
      
      if (!isValid) {
        input.style.borderColor = '#ef4444';
        showFieldError(input, errorMessage);
      } else {
        input.style.borderColor = '#10b981';
        hideFieldError(input);
      }
      
      return isValid;
    }
    
    function showFieldError(input, message) {
      hideFieldError(input);
      const errorDiv = document.createElement('div');
      errorDiv.className = 'field-error';
      errorDiv.style.cssText = 'color: #ef4444; font-size: 12px; margin-top: 4px; text-align: left;';
      errorDiv.textContent = message;
      input.parentNode.parentNode.appendChild(errorDiv);
    }
    
    function hideFieldError(input) {
      const errorDiv = input.parentNode.parentNode.querySelector('.field-error');
      if (errorDiv) errorDiv.remove();
    }
  }

  // Initialize newsletter form when DOM is ready
  document.addEventListener('DOMContentLoaded', function() {
    initializeNewsletterForm();
  });

})();


