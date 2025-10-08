# 📧 ATTRAL Order Email System

## Overview
This system automatically sends order confirmation emails to customers when a payment is successfully completed using Razorpay. The email is sent from `info@attral.in` with the sender name "ATTRAL Electronics".

## How It Works

### 1. Payment Flow
1. Customer completes payment on `order.html`
2. Payment is processed by Razorpay
3. Order data is stored in Firestore database under 'orders' collection
4. User is redirected to `order-success.html`

### 2. Automatic Email Trigger
When `order-success.html` loads:
1. It extracts the order ID from URL parameters
2. Automatically calls the email API endpoint
3. Fetches order data from Firestore
4. Sends professional order confirmation email via Brevo
5. Shows subtle notification to user

### 3. Email Content
The email includes:
- ✅ Order confirmation message
- 📦 Order details and items
- 💰 Pricing breakdown (subtotal, shipping, discount, total)
- 🏠 Shipping address
- 📧 Customer information
- 🔗 Links to track order and contact support

## Files Involved

### API Endpoints
- **`/api/send_order_email.php`** - Main email sender endpoint
- **`/api/brevo_email_service.php`** - Brevo email service integration

### Frontend
- **`order-success.html`** - Modified to trigger email sending
- **`order.html`** - Payment processing page

### Database
- **Firestore** - Stores order data in 'orders' collection
- **SQLite** - Local order management (backup)

## Configuration

### Brevo Settings
```php
define('BREVO_API_KEY', 'your-brevo-api-key');
define('FROM_EMAIL', 'info@attral.in');
define('FROM_NAME', 'ATTRAL Electronics');
```

### Firebase Settings
- Project ID: `e-commerce-1d40f`
- Service Account: `firebase-service-account.json`
- Collection: `orders`

## Email Template Features

### Professional Design
- 🎨 Modern HTML email template
- 📱 Mobile-responsive design
- 🎯 ATTRAL branding and colors
- ⚡ Professional layout with clear sections

### Order Information
- Order number and date
- Customer details
- Product information
- Pricing breakdown
- Shipping address
- Payment confirmation

### Call-to-Actions
- Track order button
- Contact support link
- Continue shopping link
- Social media links

## Testing

### Manual Test
1. Complete a test order
2. Check if email is sent to customer
3. Verify email content and formatting
4. Test with different order types (single product vs cart)

### Automated Test
Run the test script:
```bash
php api/test_order_email.php
```

## Error Handling

### Graceful Failures
- Email sending failures don't affect order completion
- Errors are logged for debugging
- User experience remains smooth
- Fallback mechanisms in place

### Logging
- All email attempts are logged
- Success and failure messages
- Detailed error information
- Performance monitoring

## Security

### Data Protection
- Customer email addresses are protected
- Order data is securely transmitted
- API endpoints have proper validation
- No sensitive data in logs

### Access Control
- Email API requires valid order ID
- Firestore access is restricted
- Brevo API uses secure authentication

## Monitoring

### Success Metrics
- Email delivery rates
- Customer engagement
- Order completion rates
- Support ticket reduction

### Alerts
- Failed email attempts
- API errors
- Database connection issues
- Brevo service problems

## Troubleshooting

### Common Issues
1. **Email not sent**: Check Brevo API credentials
2. **Order not found**: Verify Firestore connection
3. **Template errors**: Check HTML formatting
4. **Delivery issues**: Check Brevo account status

### Debug Steps
1. Check server logs for errors
2. Verify Firebase service account
3. Test Brevo API connection
4. Validate order data structure

## Future Enhancements

### Planned Features
- 📊 Email analytics and tracking
- 🎨 A/B testing for email templates
- 📱 SMS notifications
- 🔔 Push notifications
- 📈 Customer engagement metrics

### Integration Opportunities
- Marketing automation
- Customer segmentation
- Personalized recommendations
- Loyalty program integration

---

## Quick Start

1. **Deploy the files** to your server
2. **Configure Brevo** API credentials
3. **Set up Firebase** service account
4. **Test with a real order**
5. **Monitor email delivery**

The system is now ready to automatically send professional order confirmation emails to your customers! 🎉
