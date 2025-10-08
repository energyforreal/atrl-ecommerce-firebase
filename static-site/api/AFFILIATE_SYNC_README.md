# 🔄 ATTRAL Affiliate Sync to Brevo

This system automatically syncs affiliate data from Firestore to Brevo email list #10, ensuring your affiliate contacts are always up-to-date in your email marketing platform.

## 📋 Features

- **Automatic Sync**: Syncs all affiliates from Firestore to Brevo
- **Batch Processing**: Handles large datasets efficiently with configurable batch sizes
- **Error Handling**: Comprehensive error handling and logging
- **Duplicate Prevention**: Prevents duplicate entries in Brevo
- **Real-time Status**: Track sync progress and statistics
- **Manual Controls**: Sync specific affiliates or trigger full sync
- **Admin Interface**: Web-based dashboard for easy management

## 🚀 Quick Start

### 1. Test the System
```bash
cd api
php test_affiliate_sync.php
```

### 2. Sync All Affiliates
```bash
php sync_affiliates_cli.php sync-all
```

### 3. Access Web Interface
Visit: `https://attral.in/admin-affiliate-sync.html`

## 📁 Files Overview

| File | Purpose |
|------|---------|
| `sync_affiliates_to_brevo.php` | Main API endpoint for sync operations |
| `sync_affiliates_cli.php` | Command-line interface for manual sync |
| `test_affiliate_sync.php` | Test script to verify functionality |
| `admin-affiliate-sync.html` | Web-based admin interface |
| `brevo_email_service.php` | Brevo API integration (existing) |

## 🔧 Configuration

### Firestore Setup
- Project ID: `e-commerce-1d40f`
- Collection: `affiliates`
- Service Account: `firebase-service-account.json`

### Brevo Setup
- API Key: Configured in `brevo_email_service.php`
- Affiliate List ID: `10`
- Customer List ID: `3`

## 📊 Data Mapping

| Firestore Field | Brevo Attribute | Description |
|----------------|-----------------|-------------|
| `email` | Email | Primary contact email |
| `displayName` | FIRSTNAME | Affiliate's display name |
| `code` | AFFILIATE_CODE | Unique affiliate code |
| `id` | AFFILIATE_ID | Firestore document ID |
| `uid` | AFFILIATE_UID | User ID |
| `status` | STATUS | Active/Inactive status |
| `totalEarnings` | TOTAL_EARNINGS | Total commission earned |
| `totalReferrals` | TOTAL_REFERRALS | Number of referrals |
| `createdAt` | SIGNUP_DATE | Signup date |
| `lastSync` | LAST_SYNC | Last sync timestamp |

## 🖥️ Command Line Usage

### Sync All Affiliates
```bash
# Basic sync
php sync_affiliates_cli.php sync-all

# With custom batch size and delay
php sync_affiliates_cli.php sync-all --batch-size 5 --delay 3
```

### Sync Specific Affiliate
```bash
php sync_affiliates_cli.php sync-specific abc123def456
```

### Check Status
```bash
php sync_affiliates_cli.php status
```

### Fetch Affiliates
```bash
php sync_affiliates_cli.php fetch
```

### Test Connections
```bash
php sync_affiliates_cli.php test
```

## 🌐 Web Interface Usage

1. **Access Dashboard**: Visit `/admin-affiliate-sync.html`
2. **View Statistics**: See total affiliates, sync status, and percentages
3. **Sync All**: Use the "Sync All Affiliates" section with custom settings
4. **Sync Specific**: Enter an affiliate ID to sync a specific affiliate
5. **Monitor Progress**: Watch real-time logs and status updates
6. **View Affiliates**: Browse recent affiliates and their sync status

## 📝 API Endpoints

### POST `/api/sync_affiliates_to_brevo.php`

#### Sync All Affiliates
```json
{
  "action": "sync_all",
  "batch_size": 10,
  "delay_between_batches": 2
}
```

#### Sync Specific Affiliate
```json
{
  "action": "sync_specific",
  "affiliate_id": "abc123def456"
}
```

#### Get Status
```json
{
  "action": "get_status"
}
```

#### Fetch Affiliates
```json
{
  "action": "fetch_affiliates"
}
```

## 📋 Response Format

### Success Response
```json
{
  "success": true,
  "summary": {
    "total": 150,
    "success": 145,
    "errors": 3,
    "skipped": 2,
    "timestamp": "2024-01-15 14:30:00"
  },
  "message": "Successfully synced 145 out of 150 affiliates to Brevo"
}
```

### Error Response
```json
{
  "success": false,
  "error": "Error message description"
}
```

## 📊 Logging

Logs are stored in `api/logs/affiliate_sync_YYYY-MM-DD.log` with the following format:
```
[2024-01-15 14:30:00] [INFO] Starting affiliate sync to Brevo...
[2024-01-15 14:30:05] [SUCCESS] Successfully synced affiliate abc123 (user@example.com) to Brevo
[2024-01-15 14:30:10] [ERROR] Failed to sync affiliate def456: Invalid email address
```

## ⚙️ Configuration Options

### Batch Processing
- **Default Batch Size**: 10 affiliates per batch
- **Default Delay**: 2 seconds between batches
- **Max Batch Size**: 50 (to avoid API rate limits)
- **Max Delay**: 10 seconds

### Error Handling
- **Retry Logic**: Automatic retry for transient errors
- **Skip Invalid**: Skip affiliates without email addresses
- **Logging**: Comprehensive logging for debugging

## 🔒 Security Considerations

- **API Keys**: Stored securely in configuration files
- **Service Account**: Firebase service account file is protected
- **Rate Limiting**: Built-in delays to respect API limits
- **Error Logging**: Sensitive data is not logged

## 🚨 Troubleshooting

### Common Issues

1. **Firestore Connection Failed**
   - Check `firebase-service-account.json` exists
   - Verify Firebase project ID is correct
   - Ensure service account has proper permissions

2. **Brevo API Errors**
   - Verify API key is correct
   - Check API rate limits
   - Ensure list ID 10 exists in Brevo

3. **No Affiliates Found**
   - Check Firestore collection name is `affiliates`
   - Verify data structure matches expected format
   - Check Firestore security rules

4. **Sync Failures**
   - Check logs in `api/logs/` directory
   - Verify email addresses are valid
   - Check Brevo API response for specific errors

### Debug Mode
Enable detailed logging by setting `LOG_LEVEL=DEBUG` in your environment or configuration.

## 📈 Monitoring

### Key Metrics
- **Total Affiliates**: Number of affiliates in Firestore
- **With Email**: Affiliates with valid email addresses
- **Synced**: Successfully synced to Brevo
- **Sync Rate**: Percentage of affiliates synced

### Health Checks
- Firestore connectivity
- Brevo API connectivity
- Data integrity validation
- Sync status verification

## 🔄 Automation

### Cron Job Setup
Add to your crontab for automatic daily sync:
```bash
# Sync affiliates daily at 2 AM
0 2 * * * cd /path/to/api && php sync_affiliates_cli.php sync-all >> /var/log/affiliate_sync.log 2>&1
```

### Webhook Integration
The system can be triggered via webhook for real-time syncing when new affiliates are added.

## 📞 Support

For issues or questions:
1. Check the logs in `api/logs/`
2. Run the test script: `php test_affiliate_sync.php`
3. Verify configuration settings
4. Check API connectivity

## 🎯 Best Practices

1. **Regular Syncs**: Run daily syncs to keep data current
2. **Monitor Logs**: Check logs regularly for errors
3. **Test Changes**: Always test with a small batch first
4. **Backup Data**: Keep backups of important affiliate data
5. **Rate Limiting**: Respect API rate limits to avoid blocks

---

**Last Updated**: January 2024  
**Version**: 1.0.0  
**Maintainer**: ATTRAL Development Team
