# 🚀 HOSTINGER DEPLOYMENT GUIDE - Siena College E-Wallet

## 📋 PRE-DEPLOYMENT CHECKLIST

### 1️⃣ **Prepare Your Hostinger Account**
- [ ] Login to [Hostinger](https://www.hostinger.ph/)
- [ ] Go to your hosting dashboard
- [ ] Note your domain (e.g., `yoursite.com` or subdomain)

---

## 🗄️ STEP 1: CREATE DATABASE ON HOSTINGER

### **A. Access Database Manager**
1. Login to **Hostinger hPanel**
2. Go to **"Databases"** → **"MySQL Databases"**
3. Click **"Create New Database"**

### **B. Create Database**
```
Database Name: [choose_name]_ewallet
Database User: [choose_username]
Password: [strong_password]
```
**⚠️ SAVE THESE CREDENTIALS!** You'll need them later.

### **C. Import Database Schema**
1. Click **"phpMyAdmin"** next to your database
2. Select your database from left sidebar
3. Click **"Import"** tab
4. Choose file: `backend/dbConfiguration/schema.sql`
5. Click **"Go"**

### **D. Verify Tables Created**
Check if these tables exist:
- ✅ students
- ✅ transactions
- ✅ cash_in_requests
- ✅ admin_users
- ✅ password_resets

---

## 📁 STEP 2: UPDATE CONFIGURATION FILES

### **A. Update Database Configuration**

**File:** `backend/dbConfiguration/Database.php`

```php
<?php
// CHANGE THESE TO YOUR HOSTINGER CREDENTIALS:
define('DB_HOST', 'localhost');  // Usually 'localhost' on Hostinger
define('DB_USER', '[your_db_username]');  // From Step 1B
define('DB_PASS', '[your_db_password]');  // From Step 1B
define('DB_NAME', '[your_db_name]');      // From Step 1B
define('DB_PORT', 3306);

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]));
}

$conn->set_charset("utf8mb4");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
return $conn;
?>
```

### **B. Update PayMongo Webhook URL**

**File:** `backend/dbConfiguration/PayMongoConfig.php`

```php
// CHANGE THIS to your actual domain:
const WEBHOOK_URL = 'https://yourdomain.com/backend/webhooks/paymongo_webhook.php';
```

### **C. Update Email Configuration (Optional)**

**File:** `backend/dbConfiguration/NotificationConfig.php`

If using Hostinger email:
```php
const EMAIL_SMTP_HOST = 'smtp.hostinger.com';
const EMAIL_SMTP_PORT = 587;
const EMAIL_FROM_ADDRESS = 'noreply@yourdomain.com';
const EMAIL_PASSWORD = '[your_email_password]';
```

---

## 📤 STEP 3: UPLOAD FILES TO HOSTINGER

### **Option A: Using File Manager (Easiest)**

1. **Login to hPanel** → Go to **"File Manager"**
2. Navigate to **`public_html`** folder
3. **Delete default files** (index.html, etc.)
4. **Upload your project:**
   - Select all files from `e-wallet-website` folder
   - Click **"Upload"**
   - Wait for completion

### **Option B: Using FTP (FileZilla)**

1. **Get FTP Credentials:**
   - hPanel → **"FTP Accounts"**
   - Note: Host, Username, Password, Port

2. **Connect via FileZilla:**
   ```
   Host: ftp.yourdomain.com
   Username: [your_ftp_username]
   Password: [your_ftp_password]
   Port: 21
   ```

3. **Upload Files:**
   - Navigate to `public_html` on remote
   - Upload entire `e-wallet-website` folder

---

## 🔧 STEP 4: SET CORRECT FILE STRUCTURE

Your Hostinger `public_html` should look like:

```
public_html/
├── backend/
│   ├── dbConfiguration/
│   │   ├── Database.php (✏️ UPDATED)
│   │   ├── PayMongoConfig.php (✏️ UPDATED)
│   │   └── NotificationConfig.php
│   ├── service/
│   │   ├── api.php
│   │   ├── admin-api.php
│   │   ├── AuthService.php
│   │   ├── TransactionService.php
│   │   └── PayMongoService.php
│   └── webhooks/
│       └── paymongo_webhook.php
├── frontend/
│   └── components/
│       ├── index.php
│       ├── dashboard.php
│       ├── admin-dashboard.php
│       ├── transaction.php
│       ├── profile.php
│       ├── cashin.php
│       ├── forgot-password.php
│       ├── verify.php
│       └── reset-password.php
└── .htaccess (optional)
```

---

## 🔒 STEP 5: SECURE YOUR BACKEND (Optional)

Create `.htaccess` in `backend/` folder:

**File:** `backend/.htaccess`
```apache
# Deny direct access to PHP files
<Files "*.php">
    Order Deny,Allow
    Deny from all
</Files>

# Allow API endpoints
<Files "api.php">
    Allow from all
</Files>

<Files "admin-api.php">
    Allow from all
</Files>
```

---

## 🧪 STEP 6: TEST YOUR DEPLOYMENT

### **A. Test Database Connection**
Visit: `https://yourdomain.com/backend/dbConfiguration/test_connection.php`

Create this test file if needed:
```php
<?php
require_once 'Database.php';
echo json_encode(['status' => 'success', 'message' => 'Database connected!']);
?>
```

### **B. Test Student Login**
1. Go to: `https://yourdomain.com/frontend/components/index.php`
2. Login with test account:
   ```
   School ID: 123456
   Password: password123
   ```

### **C. Test Admin Login**
1. Go to: `https://yourdomain.com/frontend/components/index.php`
2. Login with admin:
   ```
   Username: admin
   Password: admin123
   ```

### **D. Test API Endpoints**
```
✅ https://yourdomain.com/backend/service/api.php?action=get_balance&student_id=1
✅ https://yourdomain.com/backend/service/admin-api.php?action=get_students
```

---

## 🎨 STEP 7: SET YOUR HOMEPAGE

### **Option A: Redirect root to login page**
Create `public_html/index.php`:
```php
<?php
header('Location: /frontend/components/index.php');
exit;
?>
```

### **Option B: Move frontend to root**
Move all files from `frontend/components/` to `public_html/` and update paths.

---

## 🔔 STEP 8: CONFIGURE PAYMONGO WEBHOOK (For Live Payments)

1. **Login to PayMongo Dashboard**
2. Go to **Developers** → **Webhooks**
3. Create webhook with URL:
   ```
   https://yourdomain.com/backend/webhooks/paymongo_webhook.php
   ```
4. Select events:
   - ✅ `checkout_session.payment.paid`
   - ✅ `payment.paid`

---

## 📧 STEP 9: CONFIGURE EMAIL (For OTP/Notifications)

### **Using Hostinger Email:**
1. **Create Email Account:**
   - hPanel → **"Email Accounts"**
   - Create: `noreply@yourdomain.com`

2. **Update NotificationConfig.php:**
   ```php
   const EMAIL_SMTP_HOST = 'smtp.hostinger.com';
   const EMAIL_SMTP_PORT = 587;
   const EMAIL_FROM_ADDRESS = 'noreply@yourdomain.com';
   const EMAIL_FROM_NAME = 'Siena College E-Wallet';
   const EMAIL_PASSWORD = '[your_email_password]';
   ```

---

## ⚡ QUICK DEPLOYMENT CHECKLIST

```
□ Create Hostinger database
□ Import schema.sql via phpMyAdmin
□ Update Database.php with Hostinger credentials
□ Update PayMongoConfig.php webhook URL
□ Upload all files to public_html
□ Test database connection
□ Test student login
□ Test admin login
□ Test forgot password flow
□ Configure PayMongo webhook (for live)
□ Set up Hostinger email (optional)
```

---

## 🆘 COMMON ISSUES & FIXES

### **Issue 1: Database Connection Failed**
```
✅ Check Database.php credentials
✅ Verify database exists in phpMyAdmin
✅ Check if DB_HOST is 'localhost'
```

### **Issue 2: 404 Not Found**
```
✅ Check file paths are correct
✅ Ensure files uploaded to public_html
✅ Check file permissions (755 for folders, 644 for files)
```

### **Issue 3: PayMongo Not Working**
```
✅ Update webhook URL to your domain
✅ Switch to LIVE keys for production
✅ Register webhook in PayMongo dashboard
```

### **Issue 4: Email Not Sending**
```
✅ Create email account in Hostinger
✅ Update SMTP settings in NotificationConfig.php
✅ Check spam folder
```

### **Issue 5: Session/Login Issues**
```
✅ Check session_start() is at top of files
✅ Verify cookies are enabled in browser
✅ Clear browser cache
```

---

## 🎯 FOR YOUR DEFENSE PRESENTATION

### **Demo Accounts:**
```
STUDENT LOGIN:
School ID: 123456
Password: password123

ADMIN LOGIN:
Username: admin
Password: admin123
```

### **Demo Flow:**
1. ✅ Student login → View dashboard
2. ✅ Check balance & transactions
3. ✅ Request cash-in via PayMongo
4. ✅ Forgot password → OTP email
5. ✅ Admin login → CRUD students
6. ✅ Add/Edit/Delete student

---

## 📞 SUPPORT

**Hostinger Support:**
- Live Chat: Available 24/7 in hPanel
- Email: support@hostinger.com
- Knowledge Base: https://support.hostinger.com/

**PayMongo Support:**
- Email: support@paymongo.com
- Docs: https://developers.paymongo.com/

---

## ✅ DEPLOYMENT COMPLETE!

Your e-wallet system is now live! 🎉

**Access URLs:**
- Student/Admin Login: `https://yourdomain.com/frontend/components/index.php`
- Admin Panel: `https://yourdomain.com/frontend/components/admin-dashboard.php`
- API Endpoint: `https://yourdomain.com/backend/service/api.php`

**Good luck with your defense!** 🚀
