# 🚀 ATTRAL E-commerce Platform

A complete e-commerce solution with admin dashboard, payment integration, and modern UI.

## ✨ Features

- 🛍️ **Modern E-commerce Store** - Beautiful, responsive design
- 🔐 **Admin Dashboard** - Complete business management system
- 💳 **Razorpay Integration** - Secure payment processing
- 🔥 **Firebase Integration** - Real-time data and authentication
- 📱 **Mobile Responsive** - Works on all devices
- 🎨 **Modern UI** - Clean, professional design

## 🚀 Quick Start

### Prerequisites
- Node.js (https://nodejs.org/)
- Modern web browser

### Installation

1. **Clone or download** this project
2. **Open terminal/command prompt** in the project folder

### Deploy Firebase Cloud Function for affiliate codes

1. Install Firebase CLI and log in:
   - `npm i -g firebase-tools`
   - `firebase login`
2. Initialize functions if not already:
   - `firebase init functions` (select JavaScript, keep ESLint as you prefer, region: `asia-south1`)
   - If prompted to overwrite, keep the existing `functions/index.js` we added.
3. Set the default project to `e-commerce-1d40f` (or your target):
   - `firebase use --add`
4. Deploy the function:
   - `firebase deploy --only functions:createAffiliateProfile`
5. Update Firebase Security Rules (recommended outline):
   - Only authenticated users can read/write their document at `affiliates/{uid}` where `request.auth.uid == uid`.
   - Prevent updates to `code` after creation.
   - Allow reads on `affiliateCodes` for existence checks; writes only by Cloud Functions.
6. Client prerequisites:
   - Ensure `static-site/js/config.js` contains your Firebase web config.
   - The client loader already includes Functions and calls the callable named `createAffiliateProfile`.
7. Test flow:
   - Visit `static-site/affiliate-dashboard.html` while signed in.
   - First-time users will get a reserved unique code from the function; link is generated client-side for the current origin.
3. **Install dependencies:**
   ```bash
   npm install
   ```
4. **Start the server:**
   ```bash
   node server.js
   ```
   Or double-click `start.bat` on Windows

5. **Open your browser** and go to:
   - **Main Website:** http://localhost:3000
   - **Business Dashboard:** http://localhost:3000/dashboard.html (Direct access - no login required)
   - **Admin Panel:** http://localhost:3000/admin-login.html (Full admin features)

### Dashboard Access
- **Direct Dashboard:** No login required - shows real-time business data
- **Admin Panel:** Username: `admin`, Password: `Admin@123`

## 📁 Project Structure

```
eCommerce/
├── server.js              # Main server file
├── start.bat              # Windows startup script
├── package.json           # Dependencies
├── api/                   # Backend API files
│   ├── config.php
│   ├── create_order.php
│   ├── verify.php
│   └── webhook.php
├── static-site/           # Frontend website
│   ├── index.html
│   ├── products.html
│   ├── cart.html
│   ├── admin-dashboard.html
│   ├── assets/            # Images and videos
│   ├── css/               # Stylesheets
│   ├── js/                # JavaScript files
│   └── data/              # Product data
└── README.md              # This file
```

## 🔧 Configuration

### Razorpay Setup
1. Get your Razorpay keys from https://razorpay.com/
2. Update `server.js` with your keys:
   ```javascript
   const RAZORPAY_KEY_ID = 'your_key_id';
   const RAZORPAY_KEY_SECRET = 'your_key_secret';
   ```

### Firebase Setup (Optional)
1. Create a Firebase project at https://firebase.google.com/
2. Update `static-site/js/config.js` with your Firebase config
3. Enable Authentication and Firestore in Firebase Console

## 🗄️ Firestore Data Model

Top-level collections used by the site:

- `users/{uid}`: profile, addresses, preferences.
- `orders/{orderId}`: order header with denormalized user and payment info.
- `products/{productId}`: catalog data, pricing, inventory.
- `categories/{categoryId}`: optional taxonomy for products.
- `coupons/{code}`: discount configuration (admin managed).
- `affiliates/{uid}` and `affiliateCodes/{code}`: affiliate profiles and unique code mapping (Cloud Function managed).
- `newsletter/{subscriberId}`: email opt-ins.

Order document required fields:

```
{
  uid: string,                 // owner uid
  items: [ { productId, name, price, qty } ],
  amount: number,              // total in smallest currency unit
  currency: string,            // e.g. "INR"
  status: 'created'|'paid'|'failed'|'refunded',
  createdAt: timestamp,
  payment: { provider, orderId?, paymentId?, signature?, isTestPayment? },
  shipping?: { name, phone, addressLine1, city, state, postalCode, country },
  affiliate?: { code?, uid? }
}
```

Security highlights (see `static-site/firestore.rules`):

- Users can read their own `users/{uid}` and `orders`.
- Clients can create orders for themselves; updates are admin-only.
- Products read-only public; writes admin-only.
- Affiliate profiles and code reservations are Cloud Function/admin-managed.

Composite indexes (see `static-site/firestore.indexes.json`):

- `orders`: `(uid asc, createdAt desc)`, `(status asc, createdAt desc)`, `(uid asc, status asc, createdAt desc)`
- `products`: `(featured asc, price asc)`, `(category asc, price asc)`
- `affiliates`: `(code asc, createdAt desc)`

## 🔁 Firestore Migration

We include a Node script to normalize historic orders and backfill missing fields. Run in dry-run first.

```
npm run migrate:orders -- --project=e-commerce-1d40f --dry
```

Then apply:

```
npm run migrate:orders -- --project=e-commerce-1d40f
```

Environment:

- Place your Admin SDK JSON as `api-firebase-service-account.json` (or set `GOOGLE_APPLICATION_CREDENTIALS`).


## 🌐 Deployment

### Local Development
- Run `node server.js` or double-click `start.bat`
- Access at http://localhost:3000

### Production Deployment
1. Upload `static-site/` contents to your web server
2. Upload `api/` folder to your server
3. Configure environment variables for Razorpay keys
4. Set up SSL certificate for HTTPS

## 📱 Admin Features

- **Dashboard** - Overview of orders, revenue, and statistics
- **Order Management** - View and manage all orders
- **User Management** - Manage customers and affiliates
- **Message Center** - Handle customer inquiries
- **Analytics** - Detailed business insights
- **Settings** - Configure your store

## 🛍️ Store Features

- **Product Catalog** - Browse and search products
- **Shopping Cart** - Add/remove items
- **Checkout** - Secure payment processing
- **User Accounts** - Registration and login
- **Order Tracking** - Track your orders
- **Contact Form** - Get in touch

## 🔒 Security

- Secure payment processing with Razorpay
- Input validation and sanitization
- CORS protection
- Environment variable configuration
- Secure admin authentication

## 🆘 Troubleshooting

### Server Won't Start
- Make sure Node.js is installed
- Check if port 3000 is available
- Try running as administrator

### Payment Issues
- Verify Razorpay keys are correct
- Check network connection
- Ensure HTTPS is enabled for production

### Admin Login Issues
- Use default credentials: admin / Admin@123
- Clear browser cache
- Check browser console for errors

## 📞 Support

For support or questions:
- Email: info@attral.in
- Check the console for error messages
- Verify all configuration settings

## 🎉 Ready to Go!

Your ATTRAL e-commerce platform is now ready! Start by:
1. Logging into the admin dashboard
2. Adding your products
3. Configuring payment settings
4. Customizing your store

Happy selling! 🚀
