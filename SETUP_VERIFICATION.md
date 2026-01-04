# PayMongo Setup Verification Checklist

## System Overview
✅ **This is a DEMO/SANDBOX system** - NOT real money  
✅ Uses PayMongo test credentials (pk_test_..., sk_test_...)  
✅ All payments go to test environment only  
✅ For production, switch to live keys

---

## Files Created/Modified

### Backend Files ✅

**1. PayMongoConfig.php** (NEW)
- Location: `backend/dbConfiguration/PayMongoConfig.php`
- Purpose: Store API credentials
- Status: ✅ Created with placeholder keys
- Action Needed: Replace with real PayMongo test keys

**2. PayMongoService.php** (NEW)
- Location: `backend/service/PayMongoService.php`
- Purpose: Handle PayMongo API calls
- Methods:
  - `createPaymentLink($amount, $student_id)` - Creates checkout session
  - `getPaymentStatus($payment_id)` - Verifies payment status
- Status: ✅ Fully implemented

**3. paymongo_webhook.php** (NEW)
- Location: `backend/webhooks/paymongo_webhook.php`
- Purpose: Receive payment confirmations from PayMongo
- Functionality:
  - Verifies webhook signature (security)
  - Updates student balance
  - Records transaction
  - Logs all events to `webhook_log.txt`
- Status: ✅ Fully implemented

**4. api.php** (UPDATED)
- Added PayMongoConfig include
- Added PayMongoService include
- New endpoint: `action=create_payment_link`
- Status: ✅ Updated

### Frontend Files ✅

**5. cashin.php** (UPDATED)
- Removed: QRCode.js library
- Removed: Manual QR generation logic
- Added: PayMongo redirect functionality
- Simplified UI with PayMongo instructions
- Status: ✅ Updated
- Function: `generatePaymentLink()` - Creates payment and redirects to PayMongo

**6. api.js** (UPDATED)
- Added method: `createPaymentLink(student_id, amount)`
- Calls backend `create_payment_link` endpoint
- Status: ✅ Updated

### Documentation Files ✅

**7. PAYMONGO_SETUP.md** (NEW)
- Complete setup guide
- Testing instructions
- Debugging tips
- Common issues & solutions

---

## Payment Flow Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                    PAYMENT FLOW ARCHITECTURE                     │
└─────────────────────────────────────────────────────────────────┘

FRONTEND (Student Browser)
├─ cashin.php
├─ Inputs: Amount (₱10 minimum)
├─ Button: "Create Payment Link"
└─ Calls: EWalletAPI.createPaymentLink()

         ↓ HTTP POST

BACKEND (PHP Server)
├─ api.php (action=create_payment_link)
├─ Validates amount
├─ Calls: PayMongoService::createPaymentLink()
└─ Returns: { checkout_url, session_id }

         ↓ cURL Request

PAYMONGO API (Payment Gateway)
├─ Creates checkout session
├─ Generates checkout URL
├─ Returns session details
└─ Responds with checkout_url

         ↓ Browser Redirect

STUDENT (PayMongo Checkout Page)
├─ URL: https://checkout.paymongo.com/...
├─ Sees payment methods (GCash, Maya, Card, etc.)
├─ Completes payment
└─ Success → Redirected to dashboard.php?payment=success

         ↓ Meanwhile...

PAYMONGO WEBHOOK (Fire after payment)
├─ Event: "payment.paid"
├─ Target: backend/webhooks/paymongo_webhook.php
├─ Payload: Payment confirmation data
└─ Authorization: HMAC-SHA256 signature

         ↓

WEBHOOK HANDLER (Backend)
├─ Verifies signature (security check)
├─ Extracts: student_id, amount, payment_id
├─ Updates: students.balance += amount
├─ Records: NEW transaction row
├─ Records: NEW cash_in_request row
├─ Logs: webhook_log.txt
└─ Returns: 200 OK response

         ↓

DATABASE (MySQL)
├─ students.balance = ₱1000 + ₱500 = ₱1500
├─ transactions.type = 'cash_in'
├─ cash_in_requests.status = 'COMPLETED'
└─ All timestamped automatically

         ↓

DASHBOARD REFRESH (Student sees)
├─ "Your Balance: ₱1500.00"
├─ New "cash_in" transaction in history
└─ Success message displayed
```

---

## Complete Setup Checklist

### Step 1: Configuration ⏳ PENDING
- [ ] Get PayMongo account (https://paymongo.com)
- [ ] Get API keys from dashboard
- [ ] Copy into PayMongoConfig.php:
  ```php
  const PUBLIC_KEY = 'pk_test_...';
  const SECRET_KEY = 'sk_test_...';
  const WEBHOOK_SECRET = 'whsk_test_...';
  ```

### Step 2: URLs ⏳ PENDING
- [ ] Update domain URLs in PayMongoService.php:
  ```php
  'success_url' => 'https://yourdomain.com/...dashboard.php?payment=success',
  'cancel_url' => 'https://yourdomain.com/...cashin.php?payment=cancelled',
  ```
- [ ] Update webhook URL in PayMongoConfig.php

### Step 3: Webhook Setup ⏳ PENDING
- [ ] In PayMongo Dashboard → Developers → Webhooks
- [ ] Click "Add Webhook"
- [ ] Enter webhook URL
- [ ] Subscribe to: `payment.paid` event
- [ ] Save and copy webhook secret
- [ ] Update webhook secret in PayMongoConfig.php

### Step 4: Test Flow ⏳ PENDING
- [ ] Login with test student (ID: 123456, Pass: password123)
- [ ] Go to Cash In page
- [ ] Enter amount (e.g., ₱50)
- [ ] Click "Create Payment Link"
- [ ] Should be redirected to PayMongo checkout
- [ ] Complete test payment
- [ ] Check if balance updated

### Step 5: Verify Database ⏳ PENDING
- [ ] Check `students.balance` for student 123456
- [ ] Check `transactions` table for new cash_in entry
- [ ] Check `cash_in_requests` table for entry

### Step 6: Check Logs ⏳ PENDING
- [ ] Review `backend/webhooks/webhook_log.txt`
- [ ] Look for success messages
- [ ] Check for signature validation

---

## Key Endpoints

### Frontend to Backend
```
POST /backend/service/api.php?action=create_payment_link
{
    "student_id": 123456,
    "amount": 500
}
```

### Backend to PayMongo
```
POST https://api.paymongo.com/v1/checkout_sessions
Auth: Basic (base64 encoded secret_key)
```

### PayMongo to Backend (Webhook)
```
POST /backend/webhooks/paymongo_webhook.php
Header: X-PayMongo-Signature: (HMAC-SHA256)
```

---

## Database Tables Affected

### students
```sql
UPDATE students SET balance = balance + 500 WHERE id = 123456;
-- Before: balance = 1000.00
-- After:  balance = 1500.00
```

### transactions
```sql
INSERT INTO transactions 
(student_id, type, amount, description, created_at)
VALUES 
(123456, 'cash_in', 500, 'Cash In via PayMongo (Payment ID: ...)', NOW());
```

### cash_in_requests
```sql
INSERT INTO cash_in_requests 
(student_id, amount, reference, status, requested_at, verified_at)
VALUES 
(123456, 500, 'PAYMONGO_py_...', 'COMPLETED', NOW(), NOW());
```

---

## Testing Credentials

### Student Account (for testing)
- School ID: `123456`
- Password: `password123`
- Starting Balance: `₱1000.00`

### PayMongo Test Payment Methods
- **GCash**: Instant in sandbox
- **Maya**: Instant in sandbox
- **Credit Card**: `4111111111111111` (test card)

### Expected Test Results
```
Before Payment:
- Balance: ₱1000.00
- Transactions: 4 entries

After ₱500 Payment:
- Balance: ₱1500.00 ✅
- Transactions: 5 entries (new cash_in) ✅
- Cash-In Request: COMPLETED ✅
- webhook_log.txt: Success logged ✅
```

---

## What's NOT Real Cash

✅ Test API keys (pk_test_, sk_test_) don't process real money  
✅ PayMongo sandbox environment is isolated  
✅ No actual charges to any card  
✅ Webhook events are simulated  
✅ Balance updates are in your test database only  

---

## Security Notes

### Already Implemented
✅ Webhook signature verification (HMAC-SHA256)  
✅ Secret key kept on backend only  
✅ API credentials in config file (not hardcoded)  
✅ Error logging without exposing sensitive data  

### Still Needed for Production
⏳ HTTPS certificate (PayMongo requires it)  
⏳ Switch from test to live API keys  
⏳ Rate limiting on payment endpoint  
⏳ CSRF token validation  
⏳ API key rotation strategy  

---

## Common Issues & Solutions

### Issue: "Invalid signature" in webhook_log.txt
**Solution:** Webhook secret doesn't match
- Get fresh secret from PayMongo dashboard
- Update PayMongoConfig.php

### Issue: Redirect to PayMongo doesn't work
**Solution:** Check for errors
- Look at browser console (F12)
- Check if SECRET_KEY is in correct format (sk_test_...)
- Verify PayMongoService.php endpoint is correct

### Issue: Payment completes but balance doesn't update
**Solution:** Webhook not configured
- Verify webhook URL in PayMongo dashboard
- Check webhook_log.txt for errors
- Ensure webhook event is set to "payment.paid"

### Issue: "cURL error" message
**Solution:** PHP cURL not working
- Check if php_curl extension is enabled in php.ini
- Ensure XAMPP Apache is running
- Try test endpoint manually

---

## Status Summary

| Component | Status | Notes |
|-----------|--------|-------|
| PayMongoConfig.php | ✅ Created | Needs credentials |
| PayMongoService.php | ✅ Created | API integration ready |
| paymongo_webhook.php | ✅ Created | Auto-balance update ready |
| api.php | ✅ Updated | New endpoint added |
| cashin.php | ✅ Updated | PayMongo redirect ready |
| api.js | ✅ Updated | New method added |
| Database schema | ✅ Ready | All tables exist |
| Documentation | ✅ Complete | PAYMONGO_SETUP.md |
| **PENDING** | **⏳** | **Add PayMongo credentials** |
| **PENDING** | **⏳** | **Setup webhook in PayMongo** |
| **PENDING** | **⏳** | **Test with real credentials** |

---

## Next Actions

1. **Register** at https://paymongo.com
2. **Get API keys** from developer dashboard
3. **Update PayMongoConfig.php** with your keys
4. **Setup webhook** in PayMongo dashboard
5. **Test the flow** with student account
6. **Check logs** in webhook_log.txt
7. **Verify balance** updated in database

---

**Last Updated:** January 3, 2026  
**System:** E-Wallet for Siena College  
**Payment Processor:** PayMongo  
**Environment:** TEST/SANDBOX (Not Real Money)
