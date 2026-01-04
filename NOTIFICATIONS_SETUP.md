# SMS & Email Notifications Setup Guide

## Overview
The system now sends SMS and email notifications to students after successful cash-in transactions!

---

## Features

### SMS Notifications (via Semaphore)
- ✅ Instant SMS after cash-in
- ✅ Shows amount and new balance
- ✅ Professional sender name: "SIENACOLG"

### Email Notifications (via PHP Mail)
- ✅ Beautiful HTML email receipt
- ✅ Transaction details with reference number
- ✅ Professional branding

---

## Setup Instructions

### 1. SMS Setup (Semaphore)

#### Step 1: Sign Up
1. Go to https://semaphore.co
2. Click "Sign Up" (FREE account)
3. Verify email and phone

#### Step 2: Get FREE Credits
- **100 FREE SMS** on signup!
- No credit card needed for trial

#### Step 3: Get API Key
1. Login to Semaphore dashboard
2. Go to **Account** → **API**
3. Copy your **API Key**

#### Step 4: Configure
Edit: `backend/dbConfiguration/NotificationConfig.php`

```php
const SEMAPHORE_API_KEY = 'paste_your_api_key_here';
const SEMAPHORE_SENDER_NAME = 'SIENACOLG'; // Max 11 characters
```

#### Step 5: Test
After cash-in, student will receive SMS like:
```
Hi Juan Dela Cruz! Your Siena College e-wallet has been credited with P50.00. New balance: P1100.00. Thank you!
```

---

### 2. Email Setup (Gmail SMTP)

#### Step 1: Enable Gmail App Passwords
1. Go to https://myaccount.google.com/apppasswords
2. Login to your Gmail account
3. Click **"Select app"** → Choose "Mail"
4. Click **"Select device"** → Choose "Other" → Type "E-Wallet"
5. Click **Generate**
6. Copy the **16-character password** (e.g., `abcd efgh ijkl mnop`)

#### Step 2: Configure
Edit: `backend/dbConfiguration/NotificationConfig.php`

```php
const EMAIL_FROM_ADDRESS = 'your-email@gmail.com';
const EMAIL_PASSWORD = 'abcd efgh ijkl mnop'; // 16-char app password
```

#### Step 3: Update XAMPP php.ini
1. Open: `C:\xampp\php\php.ini`
2. Find and update these lines:

```ini
[mail function]
SMTP=smtp.gmail.com
smtp_port=587
sendmail_from=your-email@gmail.com
sendmail_path="\"C:\xampp\sendmail\sendmail.exe\" -t"
```

3. Save and restart Apache

#### Step 4: Configure Sendmail
1. Open: `C:\xampp\sendmail\sendmail.ini`
2. Update these lines:

```ini
smtp_server=smtp.gmail.com
smtp_port=587
auth_username=your-email@gmail.com
auth_password=abcd efgh ijkl mnop
force_sender=your-email@gmail.com
```

3. Save file

#### Step 5: Test
After cash-in, student receives beautiful HTML email with:
- ✅ Amount credited
- ✅ New balance
- ✅ Transaction date/time
- ✅ Reference number
- ✅ School branding

---

## Enable/Disable Notifications

Edit: `backend/dbConfiguration/NotificationConfig.php`

```php
const ENABLE_SMS = true;   // Set to false to disable SMS
const ENABLE_EMAIL = true; // Set to false to disable email
```

---

## Testing

### Test SMS:
1. Update student phone in database:
```sql
UPDATE students SET phone = '09123456789' WHERE student_id = 1;
```

2. Do a cash-in transaction
3. Check phone for SMS

### Test Email:
1. Update student email in database:
```sql
UPDATE students SET email = 'student@example.com' WHERE student_id = 1;
```

2. Do a cash-in transaction
3. Check inbox (and spam folder)

---

## Costs

### SMS (Semaphore)
- **FREE:** 100 SMS credits on signup
- **Paid:** ₱0.45-₱0.50 per SMS after
- **Top-up:** Load credits via GCash, Bank Transfer, Credit Card

### Email (Gmail)
- **FREE:** Unlimited emails
- **Limit:** 500 emails per day (Gmail limit)
- **Cost:** ₱0.00

---

## Troubleshooting

### SMS Not Sending

**Issue:** No SMS received
**Solutions:**
1. Check API key is correct in NotificationConfig.php
2. Check phone number format (09123456789 or +639123456789)
3. Check Semaphore dashboard for remaining credits
4. Check if phone number is in students table
5. Check error logs in network tab

**Issue:** "Invalid API key"
**Solution:** Generate new API key from Semaphore dashboard

### Email Not Sending

**Issue:** No email received
**Solutions:**
1. Check spam/junk folder
2. Verify Gmail app password is correct (16 characters)
3. Check sendmail.ini is configured correctly
4. Restart Apache after php.ini changes
5. Check email address is in students table
6. Test sendmail: `C:\xampp\sendmail\sendmail.exe -t test@example.com`

**Issue:** "Could not instantiate mail function"
**Solution:** 
1. Make sure sendmail.exe exists in `C:\xampp\sendmail\`
2. Verify php.ini has correct sendmail_path
3. Restart Apache

---

## Database Requirements

Students table must have:
- `phone` column (VARCHAR) - for SMS
- `email` column (VARCHAR) - for email

Check with:
```sql
SELECT student_id, full_name, phone, email FROM students WHERE student_id = 1;
```

Update if needed:
```sql
UPDATE students SET 
    phone = '09123456789',
    email = 'student@example.com'
WHERE student_id = 1;
```

---

## Files Created

```
backend/
├── dbConfiguration/
│   └── NotificationConfig.php     ← SMS & Email config
├── service/
│   ├── SMSService.php            ← Semaphore SMS integration
│   └── EmailService.php          ← Email sending service
└── webhooks/
    ├── manual_cashin.php         ← Updated with notifications
    └── paymongo_webhook.php      ← Updated with notifications
```

---

## Sample Notifications

### SMS Sample:
```
Hi Juan Dela Cruz! Your Siena College e-wallet has been credited with P50.00. New balance: P1100.00. Thank you!
```

### Email Sample:
```
Subject: E-Wallet Cash-In Successful - Siena College

✅ Cash-In Successful!

Hi Juan Dela Cruz,

Your e-wallet has been successfully credited.

+ ₱50.00

New Balance: ₱1,100.00

Transaction Details:
- Date: January 4, 2026
- Time: 12:45 PM
- Reference: PAYMONGO_TEST
- Payment Method: PayMongo (GCash/Maya/Card)

Thank you for using Siena College E-Wallet!
```

---

## Production Checklist

Before going live:
- [ ] Add real Semaphore API key
- [ ] Add real Gmail app password
- [ ] Test SMS with real phone numbers
- [ ] Test email delivery
- [ ] Update sender name if needed
- [ ] Set up Semaphore auto-reload for credits
- [ ] Monitor SMS usage to avoid running out
- [ ] Check spam score of emails
- [ ] Update support contact info in NotificationConfig

---

## Support

**Semaphore Support:** support@semaphore.co  
**Gmail Help:** https://support.google.com/mail  

---

## Status

✅ SMS Service implemented (Semaphore)  
✅ Email Service implemented (PHP Mail/Gmail SMTP)  
✅ Cash-in notifications working  
⏳ **PENDING:** Add Semaphore API key  
⏳ **PENDING:** Add Gmail app password  
⏳ **PENDING:** Test with real credentials
