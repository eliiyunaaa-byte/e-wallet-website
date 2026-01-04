# PayMongo Integration Setup Guide

## Overview
PayMongo has been integrated into the e-wallet system to handle secure QR code payments. When students click "Create Payment Link", they're redirected to PayMongo where they can pay via GCash, Maya, or credit card. Upon successful payment, their balance is **automatically credited**.

---

## Setup Steps

### 1. Get PayMongo Credentials
1. Go to [PayMongo Dashboard](https://dashboard.paymongo.com)
2. Sign up or log in to your merchant account
3. Navigate to **Developers** → **API Keys**
4. Copy your keys:
   - **Public Key** (pk_test_...)
   - **Secret Key** (sk_test_...)

### 2. Set Up Webhook
1. In PayMongo Dashboard, go to **Developers** → **Webhooks**
2. Add New Webhook:
   - **URL:** `https://yourdomain.com/e-wallet-website/e-wallet-website/backend/webhooks/paymongo_webhook.php`
   - **Events:** Select `payment.paid`
   - Copy the **Webhook Secret** (whsk_test_...)

### 3. Update Configuration
Edit this file:
```
backend/dbConfiguration/PayMongoConfig.php
```

Replace with your credentials:
```php
const PUBLIC_KEY = 'pk_test_YOUR_PUBLIC_KEY_HERE';
const SECRET_KEY = 'sk_test_YOUR_SECRET_KEY_HERE';
const WEBHOOK_SECRET = 'whsk_test_YOUR_WEBHOOK_SECRET_HERE';
const WEBHOOK_URL = 'https://yourdomain.com/e-wallet-website/e-wallet-website/backend/webhooks/paymongo_webhook.php';
```

### 4. Update Domain URLs
If your domain is `https://siena.edu.ph`:

Update in **PayMongoConfig.php**:
```php
const WEBHOOK_URL = 'https://siena.edu.ph/e-wallet-website/e-wallet-website/backend/webhooks/paymongo_webhook.php';
```

Update in **PayMongoService.php** (success/cancel URLs):
```php
'success_url' => 'https://siena.edu.ph/e-wallet-website/e-wallet-website/frontend/components/dashboard.php?payment=success',
'cancel_url' => 'https://siena.edu.ph/e-wallet-website/e-wallet-website/frontend/components/cashin.php?payment=cancelled',
```

---

## How It Works

### Payment Flow:
```
Student opens Cash In page
    ↓
Enters amount (₱10 minimum)
    ↓
Clicks "Create Payment Link"
    ↓
Backend creates PayMongo checkout session
    ↓
Student redirected to PayMongo
    ↓
Student selects payment method (GCash/Maya/Card)
    ↓
Student completes payment
    ↓
PayMongo webhook fires to our backend
    ↓
Backend verifies payment
    ↓
Backend auto-credits student balance
    ↓
Student redirected to dashboard with "Success" message
    ↓
Balance shows updated amount immediately
```

### Database Updates:
When webhook is received and payment is confirmed:
1. ✅ `students.balance` → Increased by payment amount
2. ✅ `transactions` → New "cash_in" transaction recorded
3. ✅ `cash_in_requests` → Request marked as COMPLETED

---

## Testing (Sandbox Mode)

### Test Credentials:
- **Public Key Format:** `pk_test_...`
- **Secret Key Format:** `sk_test_...`
- **Webhook Secret Format:** `whsk_test_...`

### Test Payment Methods:
PayMongo provides test card numbers:
- **GCash:** Simulated in sandbox (instant)
- **Maya:** Simulated in sandbox (instant)
- **Credit Card:** Use card number 4111111111111111

### Test Flow:
1. Login with student (ID: 123456, Password: password123)
2. Go to Cash In page
3. Enter test amount: ₱50
4. Click "Create Payment Link"
5. Complete test payment
6. Check dashboard - balance should update

---

## File Structure

```
backend/
├── dbConfiguration/
│   ├── PayMongoConfig.php      ← UPDATE WITH YOUR CREDENTIALS
│   └── Database.php
├── service/
│   ├── PayMongoService.php     ← Payment creation logic
│   ├── api.php                 ← API endpoint router
│   └── TransactionService.php  ← Balance updates
└── webhooks/
    ├── paymongo_webhook.php    ← Receives payment confirmations
    └── webhook_log.txt         ← Debug log (auto-created)

frontend/
└── components/
    ├── cashin.php              ← Cash-in UI
    └── api.js                  ← Frontend API client
```

---

## API Endpoints

### Create Payment Link
**Endpoint:** `/backend/service/api.php?action=create_payment_link`  
**Method:** POST  
**Body:**
```json
{
    "student_id": 123456,
    "amount": 500
}
```
**Response:**
```json
{
    "status": "success",
    "checkout_url": "https://checkout.paymongo.com/...",
    "session_id": "cs_...",
    "amount": 500
}
```

### Webhook Endpoint
**URL:** `/backend/webhooks/paymongo_webhook.php`  
**Method:** POST  
**Triggered By:** PayMongo on successful payment  
**Auto-Actions:**
- Verifies webhook signature
- Updates student balance
- Records transaction
- Creates cash-in request record
- Returns success response

---

## Debugging

### Check Webhook Log:
```
backend/webhooks/webhook_log.txt
```

This file logs all webhook events:
- ✅ Valid signatures
- ❌ Invalid signatures
- ✅ Successful payments processed
- ❌ Errors and failures

### Test Webhook Locally:
If testing on localhost (http://localhost):
1. Use ngrok to expose local server: `ngrok http 80`
2. Update PayMongo webhook URL to: `https://your-ngrok-url.ngrok.io/...`
3. Test payments will trigger your local webhook

### Check Database:
```sql
-- See if balance was updated
SELECT id, balance FROM students WHERE id = 123456;

-- See if transaction was recorded
SELECT * FROM transactions WHERE student_id = 123456 ORDER BY created_at DESC;

-- See if cash-in request was recorded
SELECT * FROM cash_in_requests WHERE student_id = 123456 ORDER BY requested_at DESC;
```

---

## Common Issues

### Issue: Payment succeeds but balance doesn't update
**Cause:** Webhook not configured or webhook secret is wrong
**Fix:** 
1. Check `webhook_log.txt` for errors
2. Verify webhook URL in PayMongo dashboard
3. Verify webhook secret in PayMongoConfig.php

### Issue: "Invalid signature" error in webhook log
**Cause:** Webhook secret doesn't match PayMongo's secret
**Fix:** Get fresh webhook secret from PayMongo Dashboard and update PayMongoConfig.php

### Issue: Student redirected to PayMongo but payment link looks broken
**Cause:** Public key might be wrong or URLs not updated
**Fix:** Verify PUBLIC_KEY in PayMongoConfig.php is correct format (pk_test_...)

### Issue: Payment goes through but shows "cancelled" page
**Cause:** Success URL not reached
**Fix:** Check browser console for errors, verify domain URLs are correct

---

## Security Notes

1. ✅ **Secret Key** - Keep in backend PHP only, never expose to frontend
2. ✅ **Webhook Signature** - Always verify webhook signature (already implemented)
3. ✅ **HTTPS Required** - PayMongo requires HTTPS in production
4. ✅ **PCI Compliance** - PayMongo handles all card data, we never see it

---

## Production Checklist

Before going live:
- [ ] Switch from test keys to live keys
- [ ] Update all URLs from test domain to production domain
- [ ] Enable HTTPS on your domain
- [ ] Update PayMongo webhook URL to production
- [ ] Test a real payment with small amount
- [ ] Monitor webhook_log.txt for errors
- [ ] Set up alerts for payment failures
- [ ] Back up your credentials securely

---

## Support

**PayMongo Documentation:** https://developers.paymongo.com  
**PayMongo Support Email:** support@paymongo.com  
**Your School Contact:** [Admin email]

---

## Status
- ✅ Frontend UI updated (cashin.php)
- ✅ Backend API integration (api.php)
- ✅ Webhook receiver implemented (paymongo_webhook.php)
- ✅ PayMongoService class created
- ✅ Database schema supports transactions
- ⏳ **PENDING:** Add your PayMongo credentials to PayMongoConfig.php
- ⏳ **PENDING:** Test with real credentials
