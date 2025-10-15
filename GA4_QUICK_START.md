# GA4 Quick Start Guide - 3 Simple Steps

## Your Google Analytics 4 tracking is ready! Just follow these 3 steps:

---

## Step 1: Get Your GA4 Measurement ID (5 minutes)

1. Go to https://analytics.google.com/
2. Sign in with your Google account
3. Click "**Admin**" (gear icon, bottom left)
4. Click "**Create Property**" or select existing property
5. Under "Data Streams" → Click "**Web**"
6. Add your website URL
7. Copy your **Measurement ID** (looks like: `G-1A2B3C4D5E`)

**Need detailed help?** See `GOOGLE_ANALYTICS_SETUP_GUIDE.md`

---

## Step 2: Add Your Measurement ID (1 minute)

1. Open: `static-site/js/analytics.js`
2. Find line 15:
   ```javascript
   const GA4_MEASUREMENT_ID = 'G-XXXXXXXXXX';
   ```
3. Replace with your ID:
   ```javascript
   const GA4_MEASUREMENT_ID = 'G-1A2B3C4D5E'; // Your actual ID
   ```
4. **Save the file**

---

## Step 3: Upload to Hostinger (2 minutes)

### Via File Manager:
1. Log in to Hostinger
2. Go to **File Manager**
3. Navigate to `public_html` (or your site root)
4. Upload/replace these files:
   - `js/analytics.js` (NEW - must upload)
   - All HTML files (*.html) - (updated with tracking script)
   - `js/app.js` (updated with event calls)

### Via FTP:
1. Connect to your Hostinger FTP
2. Navigate to your website root
3. Upload the files listed above

---

## ✅ That's It! You're Done!

### Test It Works:
1. Open your website
2. Press **F12** (open browser console)
3. You should see:
   ```
   ✅ Google Analytics 4 initialized with ID: G-XXXXXXXXXX
   ```

4. Browse your site and watch for:
   ```
   📊 GA4 Event: view_item
   📊 GA4 Event: add_to_cart
   📊 GA4 Event: purchase
   ```

### View Your Data:
- **Real-time:** Google Analytics → Reports → Realtime
- **DebugView:** Admin → DebugView (for testing)
- **Reports:** Wait 24-48 hours for full reports

---

## What's Being Tracked?

✅ **Page Views** - Every page visit
✅ **Product Views** - Product detail page visits  
✅ **Add to Cart** - When users add items  
✅ **Cart Views** - Shopping cart page visits  
✅ **Checkout** - When checkout starts  
✅ **Purchases** - Completed orders (💰 Revenue!)

---

## Need Help?

- **Full setup guide:** `GOOGLE_ANALYTICS_SETUP_GUIDE.md`
- **Implementation details:** `GA4_IMPLEMENTATION_COMPLETE.md`
- **Google Support:** https://support.google.com/analytics

---

**Pro Tip:** Use GA4's DebugView feature to see events in real-time while testing!

