SYSTEM EVALUATION — SIENA COLLEGE E-WALLET

Scope
- Objective: Answer three evaluation questions about the implemented cashless payment system using the repository code.
- Files scanned (key): `backend/service/PayMongoService.php`, `backend/service/TransactionService.php`, `backend/service/admin-api.php`, `backend/service/admin-api.php`, `backend/service/AuthService.php`, `backend/service/admin-api.php`, `backend/webhooks/paymongo_webhook.php`, `frontend/components/api.js`, SQL schema `backend/database/schema.sql`.

Methodology
- Static code inspection of payment, transaction, webhook, auth, and frontend API helper files.
- Validate how payments are initiated, how transactions are recorded, and what data is shown to users.

1) Effectiveness

1.1 Processing payments
- Implementation: Payment links are created by `PayMongoService::createPaymentLink()` which calls PayMongo checkout_sessions and returns a `checkout_url` and `session_id`.
  - Evidence: `backend/service/PayMongoService.php` builds a checkout session payload (amount converted to centavos, metadata includes `student_id` and `amount`).
- Strengths:
  - Integrates with PayMongo for GCASH checkout, with standard fields and metadata so payments can be tied to a student.
  - API returns `checkout_url` so frontend can redirect students.
- Caveats / Limitations:
  - Uses test keys by default (see `backend/dbConfiguration/PayMongoConfig.php`), so production keys need to be swapped.
  - Reliance on webhook to finalize cash-in requires webhook endpoint reachable and validated.
  - Error handling: service returns status/error when cURL fails; frontend (`api.js`) logs raw response and falls back to an error message.
- Practical effectiveness: Good for demonstration; requires webhook and live keys for production reliability.

1.2 Recording transactions
- Implementation: Two recording flows:
  - Purchases: `TransactionService::processPurchase()` updates `students.balance` and inserts a `transactions` row inside a DB transaction (begin/commit/rollback).
    - Evidence: `backend/service/TransactionService.php` shows `UPDATE students SET balance = ?` then `INSERT INTO transactions(...)` then commit.
  - Cash-ins: the PayMongo webhook (`backend/webhooks/paymongo_webhook.php`) and manual cashin webhook insert/commit logic record cash_in transactions and update balances.
    - Evidence: `backend/webhooks/paymongo_webhook.php` uses `$conn->begin_transaction()` and inserts into `transactions`/`cash_in_requests` then commit.
- Strengths:
  - Use of DB transactions ensures atomicity: balance update + transaction insert are committed or rolled back together.
  - Transactions table schema includes previous_balance/new_balance, timestamps, status — suitable for audit and reconciliation (`backend/database/schema.sql`).
- Caveats:
  - Duplicate key constraints (e.g., email uniqueness) may cause fatal exceptions if not caught (seen in Apache error log). Need try/catch around `$stmt->execute()` to return JSON error instead of throwing.
- Practical effectiveness: Transaction recording is robust due to transactional DB operations; error handling around DB exceptions should be hardened.

2) Accuracy

2.1 Displaying user ID information
- Flow: `AuthService::login()` returns `student_id`, `school_id` and `name`; `api.js` saves these to `localStorage` via `EWalletAPI.saveSession()` and frontend reads from `EWalletAPI.getSession()`.
  - Evidence: `backend/service/AuthService.php` login returns `student_id`/`school_id`/`name`; `frontend/components/api.js` stores `student_id`, `school_id`, `userName`.
- Accuracy assessment:
  - Likely accurate because the frontend reads values returned by backend at login and reuses them. The system binds UI to stored session values.
  - Risk: If session/localStorage gets stale or manipulated client-side, displayed IDs can be wrong; no server-side re-validation is done on most pages beyond API calls that require `student_id` parameter.

2.2 Showing the correct transaction amount
- Flow: Amounts come from either `TransactionService` for purchases (passed by POS) or PayMongo webhook metadata for cash-in (amount returned in webhook payload). `transactions.amount` column stores the numeric value.
  - Evidence: `TransactionService::processPurchase()` uses the provided `amount` variable and stores it in `transactions` insert; `PayMongoService` sends `amount` in metadata and webhook reads it.
- Accuracy assessment:
  - Database columns use `DECIMAL(10,2)` so values keep two-decimal precision; amounts recorded as numeric types—accurate for currency.
  - Risk: Frontend formatting uses string concatenation (e.g., `lastTrans.amount`) without explicit Number parsing/rounding; minor display rounding issues possible. Backend uses numeric types so core accuracy is preserved.

2.3 Updating the user’s balance
- Flow: `processPurchase()` computes new balance by fetch then update, then inserts transaction and commits. Cash-ins update balance via webhook/manual cashin flow with transactional DB operations.
  - Evidence: `TransactionService::processPurchase()` fetches current balance via `getBalance()`, computes `new_balance`, updates `students.balance`, and inserts a transaction in the same DB transaction.
- Accuracy assessment:
  - Using DB transactions (begin/commit/rollback) ensures atomic balance updates and transaction recording—this prevents partial updates and maintains consistency.
  - Race condition risk: If multiple concurrent purchases happen for the same student, the code fetches balance then updates; without `SELECT ... FOR UPDATE` or explicit row locking there is a small risk of lost update in very high concurrency. For school demo scale this is unlikely.

3) Security (transaction data)

3.1 Data handling within database
- What exists:
  - DB design stores `previous_balance` and `new_balance` for each transaction, `created_at` timestamps, and foreign keys with `ON DELETE CASCADE` to keep referential integrity (`backend/database/schema.sql`).
  - Passwords currently stored as plaintext in `password_hash` fields (explicitly done for debugging in `AuthService.php` and in admin APIs). This is insecure but noted as intentional for demo.
- How it helps security:
  - Use of `DECIMAL` for monetary fields and transaction logs support auditing.
  - Use of transactions protects integrity when updating balances and inserting transaction rows.
- Security gaps:
  - Plaintext passwords—must be replaced with `password_hash()`/`password_verify()` for any real deployment.
  - Hard-coded secret keys and SMTP passwords in `backend/dbConfiguration/*` and `NotificationConfig.php` are secrets and must be moved to environment variables for production.
  - No encryption at rest for DB fields (not required for demo but recommended for PII in production).

3.2 Basic user verification during transactions
- What exists:
  - API methods often require `student_id` parameter from frontend; `EWalletAPI` attaches `student_id` from localStorage. Many API endpoints do not appear to re-check a server-side session token—`api.php` earlier included session logic but most calls rely on parameters.
  - Admin actions use `admin-api.php` which expects an admin session (but actual session verification is minimal in current code; UI checks `localStorage` for `admin_id`).
- How it helps security:
  - The system requires login through `AuthService::login()` and front-end stores session values; this prevents anonymous UX but is weak server-side.
- Security gaps:
  - Lack of robust server-side session/authorization checks for every action (e.g., verifying `$_SESSION['student_id']` or a signed token) means a malicious client could call APIs with arbitrary `student_id`.
  - No CSRF protection on form requests (relies on POST JSON via fetch but no anti-CSRF token).
  - No rate-limiting or brute-force protections on login.

Conclusions & Recommendations (prioritized for defense demo)

Short conclusions
- Effectiveness: Payment processing and transaction recording are implemented and effective for demo use: `PayMongoService` + webhook + `TransactionService` provide end-to-end flow.
- Accuracy: Generally accurate—amounts stored as `DECIMAL(10,2)`, balances updated within DB transactions; small concurrency risk exists but acceptable at demo scale.
- Security: Basic protections exist (transaction atomicity), but critical hardening needed for production: password hashing, move secrets to env, stronger server-side session validation.

Actionable recommendations (for defense talking points)
1. For demo (short-term):
   - Keep current flow; document that passwords are plaintext for local demo only.
   - Show successful payment in UI by initiating `createPaymentLink()` and then demonstrate webhook-backed cash-in recorded in `transactions` table.
2. Near-term fixes (low effort):
   - Wrap `$stmt->execute()` calls with try/catch to avoid fatal exceptions from unique constraint violations (fixes the admin panel 500 seen in logs).
   - Validate/escape frontend inputs before sending to backend.
3. Medium-term improvements (recommended):
   - Implement server-side session checks for each API call (validate `$_SESSION['student_id']` or use signed JWTs).
   - Replace plaintext password logic with `password_hash()` and `password_verify()`.
   - Move API keys and SMTP credentials into environment variables or a non-committed config.
4. Long-term (production):
   - Add row-level locking for balance updates or use `SELECT ... FOR UPDATE` to prevent race conditions under concurrency.
   - Add audit log retention and encryption for PII.

Appendix — Quick evidence references
- Payment link creation: `backend/service/PayMongoService.php` (checkout_sessions payload)
- Webhook recording of cash-ins: `backend/webhooks/paymongo_webhook.php`
- Purchase processing & recording: `backend/service/TransactionService.php` (begin_transaction, update students, insert transactions)
- Frontend session handling: `frontend/components/api.js` (`saveSession` and `getSession`)
- DB schema & fields: `backend/database/schema.sql` (transactions, students tables)

If you want, I can:
- Patch `admin-api.php` to catch duplicate-email exceptions and return JSON (prevent the 500 error you saw).
- Add a short demo script you can run during defense to show a purchase and cash-in flow with screenshots / console logs.

