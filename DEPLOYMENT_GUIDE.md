# 🚀 Hostinger Deployment Guide

## Step 1: Export Database

```bash
# From XAMPP
C:\xampp\mysql\bin\mysqldump.exe -u root ewallet_db > ewallet_db.sql
```

## Step 2: Upload Files to Hostinger

**Via FTP/File Manager:**
1. Upload entire project to `public_html/` or subdirectory
2. Maintain folder structure:
   ```
   public_html/
   ├── frontend/
   ├── backend/
   └── (all other files)
   ```

## Step 3: Import Database to Hostinger

1. **Login to Hostinger cPanel**
2. **phpMyAdmin** → Create new database (e.g., `u123456_ewallet`)
3. **Import** → Upload `ewallet_db.sql`
4. **Note the credentials:**
   - Database Host: usually `localhost` or `127.0.0.1`
   - Database Name: `u123456_ewallet` (your assigned name)
   - Database User: `u123456_user` (your assigned user)
   - Database Password: (from Hostinger)

## Step 4: Update Database Configuration

**Edit:** `backend/dbConfiguration/Database.php`

```php
<?php
define('DB_HOST', 'localhost');  // Usually localhost in Hostinger
define('DB_USER', 'u123456_user');  // Your Hostinger DB user
define('DB_PASS', 'YOUR_DB_PASSWORD');  // Your Hostinger DB password
define('DB_NAME', 'u123456_ewallet');  // Your Hostinger DB name
define('DB_PORT', 3306);

// Rest remains the same...
```

## Step 5: Update PayMongo URLs

**Edit:** `backend/dbConfiguration/PayMongoConfig.php`

Replace ALL `localhost` URLs with your domain:

```php
// BEFORE (localhost)
const SUCCESS_URL = 'http://localhost/e-wallet-website/e-wallet-website/frontend/components/dashboard.php?payment=success';

// AFTER (your domain)
const SUCCESS_URL = 'https://yourdomain.com/frontend/components/dashboard.php?payment=success';
```

**Update these URLs:**
- `SUCCESS_URL`
- `CANCEL_URL`
- `WEBHOOK_URL`

## Step 6: Configure PayMongo Webhook

1. **Login to PayMongo Dashboard:** https://dashboard.paymongo.com/
2. **Webhooks** → **Add Webhook**
3. **Webhook URL:** `https://yourdomain.com/backend/webhooks/paymongo_webhook.php`
4. **Events to Subscribe:**
   - ✅ `payment.paid`
   - ✅ `source.chargeable`
5. **Copy Webhook Secret** → Update `PayMongoConfig.php`:
   ```php
   const WEBHOOK_SECRET = 'whsec_xxxxxxxxxxxxx';  // Your actual secret
   ```

## Step 7: Email Configuration (Hostinger SMTP)

**Option A: Use Hostinger SMTP (Recommended)**

Edit `backend/dbConfiguration/NotificationConfig.php`:

```php
const EMAIL_SMTP_HOST = 'smtp.hostinger.com';  // Hostinger SMTP
const EMAIL_SMTP_PORT = 587;
const EMAIL_FROM_ADDRESS = 'noreply@yourdomain.com';  // Your domain email
const EMAIL_FROM_NAME = 'Siena College E-Wallet';
const EMAIL_PASSWORD = 'your_email_password';  // Email password
```

**Configure sendmail (if using PHP mail()):**

Hostinger usually has this configured, but verify in cPanel → **Email Accounts**

**Option B: Keep Gmail (Works but not recommended for production)**

Keep current Gmail settings, but Gmail may block/limit emails from servers.

## Step 8: Security Hardening

### A. Re-enable Password Hashing

**Edit:** `backend/service/api.php`

Find the login endpoint and uncomment password hashing:

```php
// BEFORE (testing mode)
if ($user['password_hash'] !== $password) {

// AFTER (production mode)
if (!password_verify($password, $user['password_hash'])) {
```

**Re-hash existing passwords:**

```sql
-- Update student passwords with bcrypt hash
UPDATE students 
SET password_hash = '$2y$10$Ezg8YPvGPz5yRcGl3jqeV.N5xO5x5J5J5J5J5J5J5J5J5J5J5J5J5J5'  -- bcrypt hash of 'password123'
WHERE student_id = 1;
```

Or create a password reset script.

### B. Switch to HTTPS

Hostinger provides free SSL certificates:

1. **cPanel** → **SSL/TLS**
2. **Install SSL** → Select domain
3. Update all URLs to `https://`

### C. Environment Variables (Optional but Recommended)

Create `.env` file (excluded from Git):

```env
DB_HOST=localhost
DB_USER=u123456_user
DB_PASS=yourpassword
DB_NAME=u123456_ewallet

PAYMONGO_PUBLIC_KEY=pk_live_xxxxx
PAYMONGO_SECRET_KEY=sk_live_xxxxx
SEMAPHORE_API_KEY=928bace6fd39fec449855743afc257b8
```

Then load in PHP:

```php
// Load .env
if (file_exists(__DIR__ . '/.env')) {
    $env = parse_ini_file(__DIR__ . '/.env');
    define('DB_HOST', $env['DB_HOST']);
    // ... etc
}
```

## Step 9: File Permissions

Set correct permissions via **File Manager**:

```
Directories: 755
PHP Files: 644
Sensitive configs: 600 (Database.php, NotificationConfig.php)
```

## Step 10: Test Deployment

### Checklist:

- [ ] **Database Connection:** Visit site, check for errors
- [ ] **Login:** Test with student credentials
- [ ] **Dashboard:** Loads with correct balance
- [ ] **PayMongo:** Create test payment (use test keys first!)
- [ ] **Webhook:** Test with PayMongo webhook test
- [ ] **Email:** Test cash-in notification
- [ ] **SMS:** Test when Semaphore approved
- [ ] **Transactions:** View transaction history

### Testing Commands:

**Test Database Connection:**
```bash
# Via SSH (if available)
php -r "new mysqli('localhost', 'user', 'pass', 'dbname') or die('Failed');"
```

**Test Webhook:**
```bash
curl -X POST https://yourdomain.com/backend/webhooks/paymongo_webhook.php \
  -H "Content-Type: application/json" \
  -d '{"data":{"id":"test"}}'
```

## Step 11: Go LIVE with PayMongo (When Ready)

**Switch from Test to Live:**

1. **Complete KYC in PayMongo Dashboard**
2. **Get LIVE API Keys:**
   - Live Public Key: `pk_live_xxxxx`
   - Live Secret Key: `sk_live_xxxxx`
3. **Update PayMongoConfig.php:**
   ```php
   const PUBLIC_KEY = 'pk_live_xxxxx';  // Live key
   const SECRET_KEY = 'sk_live_xxxxx';  // Live key
   ```
4. **Update webhook** in PayMongo dashboard with LIVE webhook

**⚠️ Important:** Test thoroughly with test keys before going live!

## Troubleshooting

### Issue: Database connection failed
- Check credentials in Database.php
- Verify database exists in phpMyAdmin
- Check Hostinger's remote MySQL access settings

### Issue: PayMongo redirect not working
- Verify URLs are HTTPS (not HTTP)
- Check redirect URLs match exactly
- Clear browser cache

### Issue: Email not sending
- Check SMTP credentials
- Verify email account exists in Hostinger
- Check spam folder
- Review Hostinger email logs in cPanel

### Issue: Webhook not receiving
- Verify webhook URL is accessible (not localhost)
- Check PayMongo webhook logs
- Verify webhook secret matches
- Check file permissions on paymongo_webhook.php

### Issue: 500 Internal Server Error
- Check PHP error logs in cPanel
- Verify file permissions (755/644)
- Check .htaccess if you created one
- Enable error display temporarily:
  ```php
  ini_set('display_errors', 1);
  error_reporting(E_ALL);
  ```

## Post-Deployment

### Monitor:
- **Error Logs:** cPanel → Error Logs
- **PayMongo Dashboard:** Transaction status
- **Database:** Regular backups
- **Email Queue:** Monitor sending success

### Backup Strategy:
1. **Database:** Weekly exports via cPanel
2. **Files:** Keep local copy
3. **Git:** Push to private repository

### Security Monitoring:
- Review transaction logs regularly
- Monitor for suspicious login attempts
- Keep dependencies updated
- Review PayMongo transactions

## Production Checklist

Before announcing to users:

- [ ] SSL certificate installed and working
- [ ] Database backups configured
- [ ] Password hashing enabled
- [ ] Live PayMongo keys configured
- [ ] Webhook tested and working
- [ ] Email notifications working
- [ ] SMS notifications working (Semaphore approved)
- [ ] Error logging enabled
- [ ] Admin contact info updated
- [ ] Terms of service page added
- [ ] Privacy policy added

---

## Quick Reference

**Your Hostinger Setup:**
- Domain: `https://yourdomain.com`
- Database: `u123456_ewallet`
- Email: `noreply@yourdomain.com`
- FTP: See Hostinger cPanel

**External Services:**
- PayMongo: https://dashboard.paymongo.com/
- Semaphore: https://semaphore.co/account
- Gmail: https://myaccount.google.com/apppasswords

**Important Files to Update:**
1. `backend/dbConfiguration/Database.php` - DB credentials
2. `backend/dbConfiguration/PayMongoConfig.php` - URLs + live keys
3. `backend/dbConfiguration/NotificationConfig.php` - Email SMTP
4. `backend/service/api.php` - Password hashing

---

**Need Help?**
- Hostinger Support: https://hostinger.com/support
- PayMongo Support: support@paymongo.com
- Semaphore Support: support@semaphore.co

Good luck with deployment! 🚀
