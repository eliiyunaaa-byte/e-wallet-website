# E-Wallet Backend Setup Guide

## Overview
This backend provides API endpoints for the Siena College E-Wallet system. It handles authentication, transactions, and wallet management.

## Database Setup

### 1. Import Database Schema
Open phpMyAdmin and import the schema file:
```
backend/database/schema.sql
```

Or run via MySQL client:
```bash
mysql -u root -p < backend/database/schema.sql
```

### 2. Verify Database Creation
```bash
mysql -u root -p
USE ewallet_db;
SHOW TABLES;
```

## Configuration

### Database Credentials
Edit `backend/dbConfiguration/Database.php`:
- **Host**: localhost
- **User**: root
- **Password**: (empty by default for XAMPP)
- **Database**: ewallet_db

## API Endpoints

### Base URL
```
http://localhost/e-wallet-website/e-wallet-website/backend/service/api.php
```

### 1. Login
**Endpoint**: `POST /api.php?action=login`

**Request**:
```json
{
  "school_id": "123456",
  "password": "password123"
}
```

**Response**:
```json
{
  "status": "success",
  "message": "Login successful",
  "data": {
    "student_id": 1,
    "school_id": "123456",
    "name": "John Doe",
    "balance": 1000.00,
    "grade_section": "10-Eucharist Centered"
  }
}
```

### 2. Get Balance
**Endpoint**: `GET /api.php?action=get_balance&student_id=1`

**Response**:
```json
{
  "status": "success",
  "balance": 1000.00
}
```

### 3. Get Transactions
**Endpoint**: `GET /api.php?action=get_transactions&student_id=1&limit=50&offset=0`

**Response**:
```json
{
  "status": "success",
  "transactions": [
    {
      "transaction_id": 1,
      "transaction_type": "PURCHASE",
      "amount": 50.00,
      "location": "SDB Canteen",
      "item_name": "Siomai & Rice",
      "transaction_date": "2024-01-03",
      "transaction_time": "11:20:00",
      "status": "COMPLETED"
    }
  ],
  "count": 1
}
```

### 4. Process Purchase
**Endpoint**: `POST /api.php?action=process_purchase`

**Request**:
```json
{
  "student_id": 1,
  "amount": 50.00,
  "item_name": "Siomai & Rice",
  "location": "SDB Canteen"
}
```

**Response**:
```json
{
  "status": "success",
  "message": "Purchase successful",
  "new_balance": 950.00,
  "amount_spent": 50.00
}
```

### 5. Request Cash-In
**Endpoint**: `POST /api.php?action=request_cashin`

**Request**:
```json
{
  "student_id": 1,
  "amount": 500.00,
  "reference_number": "GCash123456"
}
```

**Response**:
```json
{
  "status": "success",
  "message": "Cash-in request submitted",
  "cash_in_id": 1
}
```

### 6. Get Weekly Spending
**Endpoint**: `GET /api.php?action=get_weekly_spending&student_id=1`

**Response**:
```json
{
  "status": "success",
  "weekly_spending": 150.00
}
```

## File Structure
```
backend/
├── database/
│   └── schema.sql          (Database schema with sample data)
├── dbConfiguration/
│   └── Database.php        (Database connection)
├── service/
│   ├── AuthService.php     (Authentication logic)
│   ├── TransactionService.php  (Transaction handling)
│   └── api.php             (API endpoints)
├── otp/                    (OTP handling - to be implemented)
└── readME.md              (This file)
```

## Services

### AuthService
Handles:
- Student login
- Student registration
- Password reset requests
- OTP verification

### TransactionService
Handles:
- Get balance
- Process purchases
- Get transaction history
- Request cash-in
- Calculate weekly spending

## Security Notes

1. **Passwords**: Stored using bcrypt hashing
2. **Database**: Use utf8mb4 encoding for emoji/special characters
3. **CORS**: Currently allows all origins (change for production)
4. **Sessions**: Should be implemented for production
5. **Validation**: All inputs are validated before processing

## Testing with Postman

1. Import or create requests for each endpoint
2. Set Content-Type to `application/json`
3. Use POST for login, purchase, cash-in
4. Use GET for balance, transactions, spending

## Development Notes

- All responses use JSON format
- Transactions use database transactions for data consistency
- Password reset includes OTP expiration (15 minutes)
- Balance is maintained at student record level

## Production Checklist

- [ ] Change database password
- [ ] Update CORS settings
- [ ] Implement proper session management
- [ ] Add email service for OTP
- [ ] Enable HTTPS
- [ ] Add rate limiting
- [ ] Implement logging/monitoring
- [ ] Add admin dashboard
