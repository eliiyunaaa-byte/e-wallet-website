# QR Code Cash-In Workflow

## Overview
Students can load money into their e-wallet using GCash QR payment. The system generates a unique QR code for each transaction containing the student's ID, amount, and a reference code.

## Workflow Steps

### 1. Student Initiates Cash-In
- Student navigates to **Cash In** page
- Sees instructions and a sample QR code placeholder
- Enters the amount they want to load (minimum ₱100, increments of ₱50)

### 2. QR Code Generation
- Student clicks **"Generate QR Code"** button
- System calls `generateQR()` function which:
  - Validates the amount (must be ≥ ₱100)
  - Generates a unique reference code: `SCT{timestamp}{random}`
  - Creates QR data: `{school_id}|{amount}|{reference}`
  - Uses QRCode.js library to render dynamic QR code
  - Auto-fills the reference field
  - Updates UI state:
    - Hides instructions and amount input
    - Shows generated QR code
    - Displays amount to be paid
    - Shows reference field (read-only)

### 3. Payment Processing
- Student opens GCash app (or any QR payment app)
- Taps **"Pay QR"** option
- Scans the displayed QR code
- Authorizes payment of the specified amount
- GCash returns a reference number

### 4. Verification & Confirmation
- Student sees the auto-generated reference number in the reference field
- Can copy/verify it with their GCash confirmation
- Clicks **"Confirm Payment"** button
- System calls `confirmPayment()` function which:
  - Validates reference is not empty
  - Calls API endpoint: `requestCashIn()`
  - Submits to backend for verification
  - Returns success/error message

### 5. Backend Processing
- API endpoint receives cash-in request with:
  - `student_id`: Student who initiated payment
  - `amount`: Amount to be loaded
  - `reference`: GCash reference number
- Creates record in `cash_in_requests` table with status: `PENDING`
- Later, admin verifies payment against GCash records
- Once verified, updates student balance and marks request as `COMPLETED`

## Key Code Components

### Frontend (cashin.php)

**generateQR() Function:**
```javascript
function generateQR() {
    // 1. Validate amount
    // 2. Generate unique reference: SCT + timestamp + random string
    // 3. Create QR data: school_id|amount|reference
    // 4. Render QR using QRCode.js library
    // 5. Update UI (hide instructions, show QR and reference field)
    // 6. Auto-fill reference field
}
```

**confirmPayment() Function:**
```javascript
async function confirmPayment() {
    // 1. Validate reference input
    // 2. Call EWalletAPI.requestCashIn()
    // 3. Handle success/error response
    // 4. Redirect to dashboard on success
}
```

### Backend (api.php)

**requestCashIn Endpoint:**
- Route: `action=request_cashin`
- Parameters: `student_id`, `amount`, `reference`
- Creates pending cash-in request record
- Returns success/error status

### Database (cash_in_requests table)

```sql
CREATE TABLE cash_in_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT FOREIGN KEY,
    amount DECIMAL(10, 2),
    reference VARCHAR(50),
    status ENUM('PENDING', 'COMPLETED', 'REJECTED'),
    requested_at TIMESTAMP,
    verified_at TIMESTAMP,
    admin_id INT FOREIGN KEY
);
```

## Reference Code Format

Format: `SCT{10-digit timestamp}{9-char random string}`

Example: `SCT1704285600ABC12XYZ`

- **SCT** = Siena Cash Transaction prefix
- **Timestamp** = Milliseconds since epoch (uniqueness)
- **Random** = Random alphanumeric string (collision prevention)

## UI State Transitions

```
Initial State
    ↓
[Show] Instructions + Amount Input + Generate Button
    ↓ (User enters amount & clicks Generate)
    ↓
[Show] QR Code + Reference Field (read-only) + Confirm Button
[Hide] Instructions + Amount Input
    ↓ (User scans with GCash and gets reference)
    ↓
[Show] Success Message
    ↓ (After 3 seconds)
    ↓
[Redirect] to Dashboard
```

## Error Handling

### Frontend Validation
- ✅ Amount must be ≥ ₱100
- ✅ Amount must be provided
- ✅ Reference field must not be empty before confirmation
- ✅ Displays error messages in red
- ✅ Displays success messages in green

### Backend Validation
- ✅ Student must be logged in
- ✅ Amount within acceptable limits
- ✅ Reference format validation
- ✅ Duplicate reference prevention
- ✅ Sufficient transaction quota checks

## Testing Reference Data

**Test Student:** School ID 123456, Password: password123

**Generate QR for:** ₱500 (or any amount ≥ ₱100)

**Expected Reference Format:** `SCT1704285600ABC12XYZ` (auto-generated)

## Future Enhancements

1. **Real GCash Integration**
   - Use GCash API SDK for actual payment processing
   - Receive webhook callbacks for payment confirmation
   - Auto-verify transactions without manual admin review

2. **Enhanced Reference Verification**
   - QR codes expire after 5 minutes
   - One-time use reference codes
   - Reference database lookup for fraud prevention

3. **Admin Dashboard**
   - View pending cash-in requests
   - Approve/reject with reason
   - View transaction history and reference numbers

4. **Student Notifications**
   - SMS/Email when cash-in is approved
   - SMS/Email when cash-in is rejected
   - Transaction receipts

5. **Limits & Restrictions**
   - Daily cash-in limits per student
   - Maximum single transaction limit
   - Cumulative limits for security

## Current Status

✅ **Implemented:**
- Dynamic QR code generation
- Reference number auto-generation
- Frontend form validation
- UI state management
- API integration

⏳ **Pending:**
- Backend QR validation endpoint
- Admin dashboard for review
- Real GCash integration
- Email/SMS notifications
- Advanced fraud detection
