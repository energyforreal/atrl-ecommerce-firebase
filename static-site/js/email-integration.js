/**
 * 📧 Email Integration Module
 * Handles automated email triggers across the website
 */

(function() {
  'use strict';

  const API_BASE_URL = window.ATTRAL_PUBLIC?.API_BASE_URL || '';
  const EMAIL_SERVICE_URL = `${API_BASE_URL}/api/brevo_email_service.php`;

  /**
   * 🎉 Send welcome email when user signs up
   */
  async function sendWelcomeEmail(email, firstName) {
    try {
      const response = await fetch(EMAIL_SERVICE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'welcome',
          email: email,
          firstName: firstName || 'Friend'
        })
      });

      const result = await response.json();
      
      if (result.success) {
        console.log('✅ Welcome email sent to:', email);
        return true;
      } else {
        console.warn('⚠️ Welcome email failed:', result.error);
        return false;
      }
    } catch (error) {
      console.error('❌ Welcome email error:', error);
      return false;
    }
  }

  /**
   * 📧 Add contact to newsletter list (customers)
   */
  async function addToNewsletterList(email, firstName) {
    try {
      const response = await fetch(EMAIL_SERVICE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'add_to_customer_list',
          email: email,
          firstName: firstName || ''
        })
      });

      const result = await response.json();
      
      if (result.success) {
        console.log('✅ Added to customer list:', email);
        return true;
      } else {
        console.warn('⚠️ Newsletter signup failed:', result.error);
        return false;
      }
    } catch (error) {
      console.error('❌ Newsletter signup error:', error);
      return false;
    }
  }

  /**
   * 🎁 Add contact to affiliate list
   */
  async function addToAffiliateList(email, firstName, affiliateCode, attributes = {}) {
    try {
      const affiliateAttributes = {
        AFFILIATE_CODE: affiliateCode,
        SIGNUP_DATE: new Date().toISOString().split('T')[0],
        STATUS: 'active',
        ...attributes
      };

      const response = await fetch(EMAIL_SERVICE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'add_to_affiliate_list',
          email: email,
          firstName: firstName || '',
          attributes: affiliateAttributes
        })
      });

      const result = await response.json();
      
      if (result.success) {
        console.log('✅ Added to affiliate list:', email);
        return true;
      } else {
        console.warn('⚠️ Affiliate list signup failed:', result.error);
        return false;
      }
    } catch (error) {
      console.error('❌ Affiliate list error:', error);
      return false;
    }
  }

  /**
   * 📋 Get contact information
   */
  async function getContactInfo(email) {
    try {
      const response = await fetch(EMAIL_SERVICE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'get_contact',
          email: email
        })
      });

      const result = await response.json();
      return result;
    } catch (error) {
      console.error('❌ Get contact error:', error);
      return { success: false, error: error.message };
    }
  }

  /**
   * 📬 Send contact form emails
   */
  async function sendContactFormEmails(name, email, message, phone = '') {
    try {
      const response = await fetch(`${API_BASE_URL}/api/contact_handler.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          name: name,
          email: email,
          message: message,
          phone: phone
        })
      });

      const result = await response.json();
      return result;
    } catch (error) {
      console.error('❌ Contact form error:', error);
      return { success: false, error: error.message };
    }
  }

  /**
   * 🎁 Send affiliate welcome email
   */
  async function sendAffiliateWelcomeEmail(email, name, affiliateCode) {
    try {
      const response = await fetch(EMAIL_SERVICE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'affiliate_welcome',
          email: email,
          name: name,
          affiliateCode: affiliateCode
        })
      });

      const result = await response.json();
      
      if (result.success) {
        console.log('✅ Affiliate welcome email sent to:', email);
        return true;
      } else {
        console.warn('⚠️ Affiliate email failed:', result.error);
        return false;
      }
    } catch (error) {
      console.error('❌ Affiliate email error:', error);
      return false;
    }
  }

  /**
   * 🛒 Track abandoned cart
   */
  function trackAbandonedCart() {
    // Check if cart has items
    const cart = JSON.parse(localStorage.getItem('attral_cart') || '[]');
    
    if (cart.length === 0) {
      localStorage.removeItem('attral_cart_timestamp');
      return;
    }

    // Store timestamp when cart was last modified
    const now = Date.now();
    const lastModified = localStorage.getItem('attral_cart_timestamp');
    
    if (!lastModified) {
      localStorage.setItem('attral_cart_timestamp', now.toString());
    }

    // Save cart to Firestore for abandoned cart email triggers
    if (window.AttralFirebase && window.AttralFirebase.db && window.AttralFirebase.auth) {
      const user = window.AttralFirebase.auth.currentUser;
      if (user && user.email) {
        window.AttralFirebase.db.collection('abandoned_carts').doc(user.uid).set({
          userId: user.uid,
          email: user.email,
          displayName: user.displayName || 'Friend',
          cart: cart,
          lastModified: new Date(),
          emailSent: false
        }, { merge: true }).catch(err => {
          console.warn('Failed to track abandoned cart:', err);
        });
      }
    }
  }

  /**
   * ✅ Clear abandoned cart tracking
   */
  function clearAbandonedCart() {
    localStorage.removeItem('attral_cart_timestamp');
    
    if (window.AttralFirebase && window.AttralFirebase.db && window.AttralFirebase.auth) {
      const user = window.AttralFirebase.auth.currentUser;
      if (user) {
        window.AttralFirebase.db.collection('abandoned_carts').doc(user.uid).delete()
          .catch(err => console.warn('Failed to clear abandoned cart:', err));
      }
    }
  }

  // Export functions globally
  window.AttralEmailService = {
    sendWelcomeEmail,
    addToNewsletterList,
    addToAffiliateList,
    getContactInfo,
    sendContactFormEmails,
    sendAffiliateWelcomeEmail,
    trackAbandonedCart,
    clearAbandonedCart
  };

  console.log('📧 Email Integration Module loaded');
  console.log('📊 List IDs - Customers: #3, Affiliates: #10');
})();



