# Google Analytics 4 Setup Guide for ATTRAL eCommerce

This guide will help you set up Google Analytics 4 (GA4) tracking for your ATTRAL eCommerce website hosted on Hostinger.

## Step 1: Create a Google Analytics 4 Account

### 1.1 Sign Up for Google Analytics
1. Go to [Google Analytics](https://analytics.google.com/)
2. Click "Start measuring" or "Sign in" (if you already have a Google account)
3. Sign in with your Google account (or create one if needed)

### 1.2 Create an Account
1. Click "Admin" (gear icon) in the bottom left
2. Click "Create Account" in the Account column
3. Enter an account name (e.g., "ATTRAL")
4. Configure data sharing settings (optional)
5. Click "Next"

### 1.3 Create a Property
1. Enter a property name (e.g., "ATTRAL Website")
2. Select your reporting time zone (India Standard Time)
3. Select your currency (INR - Indian Rupee)
4. Click "Next"

### 1.4 Business Information
1. Select your industry category (e.g., "Electronics")
2. Select business size (e.g., "Small" - 1-10 employees)
3. Select how you plan to use Google Analytics
4. Click "Create"
5. Accept the Terms of Service

### 1.5 Set Up Data Collection
1. Select platform: **Web**
2. Enter your website URL: `https://yourdomain.com` (your Hostinger domain)
3. Enter a stream name: "ATTRAL Website"
4. Click "Create stream"

## Step 2: Get Your Measurement ID

After creating the web data stream, you'll see your **Measurement ID** displayed prominently.

**Format:** `G-XXXXXXXXXX` (starts with "G-" followed by alphanumeric characters)

**Example:** `G-1A2B3C4D5E`

### Where to Find It Later:
1. Go to Admin (gear icon)
2. Under "Property" column → Data Streams
3. Click on your web stream
4. Your Measurement ID is at the top

**Important:** Copy this ID - you'll need it in Step 3!

## Step 3: Configure Your Website

### 3.1 Add Your Measurement ID
1. Open the file: `static-site/js/analytics.js`
2. Find this line near the top:
   ```javascript
   const GA4_MEASUREMENT_ID = 'G-XXXXXXXXXX'; // Replace with your actual Measurement ID
   ```
3. Replace `G-XXXXXXXXXX` with your actual Measurement ID
4. Save the file

**Example:**
```javascript
const GA4_MEASUREMENT_ID = 'G-1A2B3C4D5E'; // Your real ID
```

### 3.2 Upload to Hostinger
1. Log in to your Hostinger control panel
2. Go to **File Manager** or use **FTP**
3. Navigate to your website's root directory (usually `public_html`)
4. Upload the modified `analytics.js` file to the `js/` folder
5. Make sure all HTML files have been uploaded with the analytics script tag

## Step 4: Test Your Implementation

### 4.1 Enable Debug Mode (Recommended)
To test if events are firing correctly:

1. Install the **Google Analytics Debugger** Chrome extension:
   - [Download from Chrome Web Store](https://chrome.google.com/webstore/detail/google-analytics-debugger/jnkmfdileelhofjcijamephohjechhna)
2. Open your website
3. Open Chrome DevTools (F12)
4. Go to the "Console" tab
5. Enable the GA Debugger extension (click the icon to turn it on)
6. You'll see detailed GA4 event logs in the console

### 4.2 Use GA4 DebugView
1. In Google Analytics, go to **Admin** → **DebugView** (under Property column)
2. Open your website in a new tab
3. You should see real-time events appearing in DebugView
4. Test these actions:
   - View a product (should fire `view_item`)
   - Add to cart (should fire `add_to_cart`)
   - View cart (should fire `view_cart`)
   - Go to checkout (should fire `begin_checkout`)
   - Complete a test purchase (should fire `purchase`)

### 4.3 Check Realtime Reports
1. In Google Analytics, go to **Reports** → **Realtime**
2. Open your website in another tab
3. You should see yourself as an active user
4. Navigate through your site and watch events appear in real-time

## Step 5: Verify Enhanced eCommerce Events

Your implementation tracks these important eCommerce events:

| Event Name | When It Fires | Where to Check |
|------------|---------------|----------------|
| `page_view` | Every page load | All pages |
| `view_item` | Product detail view | product-detail.html |
| `add_to_cart` | Item added to cart | Any page with "Add to Cart" |
| `view_cart` | Cart page viewed | cart.html |
| `begin_checkout` | Checkout started | order.html |
| `purchase` | Order completed | order-success.html |

### Expected Data for Purchase Event:
- Transaction ID
- Total value
- Currency (INR)
- Items purchased (with ID, name, price, quantity)

## Step 6: Wait for Data Collection

- **Real-time data:** Appears within seconds in DebugView/Realtime reports
- **Standard reports:** May take 24-48 hours to populate fully
- **eCommerce reports:** Available under **Reports** → **Monetization** → **eCommerce purchases**

## Troubleshooting

### No Data Appearing?
1. **Check Measurement ID:** Make sure you replaced `G-XXXXXXXXXX` with your actual ID
2. **Clear Browser Cache:** Force refresh (Ctrl+Shift+R or Cmd+Shift+R)
3. **Check Browser Console:** Look for errors (F12 → Console tab)
4. **Verify File Upload:** Ensure `analytics.js` is uploaded to the correct location
5. **Check Ad Blockers:** Disable ad blockers that might block GA4

### Events Not Firing?
1. Open browser console (F12)
2. Look for GA4 event logs (if debugger is enabled)
3. Check that `analytics.js` is loaded (Network tab in DevTools)
4. Verify the event functions are being called in `app.js`

### Purchase Events Not Recording?
1. Complete a real test order
2. Make sure you reach `order-success.html`
3. Check URL parameters include order details
4. Verify the `trackPurchase()` function is called on that page

## Privacy & GDPR Compliance

Since you're collecting data from Indian users, consider:

1. **Privacy Policy:** Update your privacy policy (privacy.html) to mention Google Analytics usage
2. **Cookie Consent:** Consider adding a cookie consent banner (optional for Indian users, but good practice)
3. **IP Anonymization:** GA4 automatically anonymizes IPs by default
4. **Data Retention:** Configure in GA4 Admin → Data Settings → Data Retention

## Getting Help

- **Google Analytics Help:** [support.google.com/analytics](https://support.google.com/analytics)
- **GA4 Setup Guide:** [Google's Official Documentation](https://support.google.com/analytics/answer/9304153)
- **Community Forum:** [GA4 Community](https://support.google.com/analytics/community)

## Next Steps

Once your analytics is working:

1. **Set up Goals/Conversions** in GA4 (purchases should be tracked automatically)
2. **Create Custom Reports** for your specific business needs
3. **Set up Audiences** for remarketing
4. **Link Google Ads** (if you plan to run ads)
5. **Review Reports Weekly** to understand user behavior and optimize your site

---

**Your analytics implementation is complete! 🎉**

All the tracking code has been integrated into your website. Just add your Measurement ID and upload to Hostinger to start collecting valuable insights about your visitors and sales.

