<?php
/**
 * PayMongo Webhook Receiver
 * 
 * This endpoint receives payment confirmations from PayMongo
 * When a student successfully pays, this webhook credits their e-wallet balance
 * 
 * Setup in PayMongo Dashboard:
 * 1. Go to Developers > Webhooks
 * 2. Add webhook URL: https://yourdomain.com/e-wallet-website/e-wallet-website/backend/webhooks/paymongo_webhook.php
 * 3. Subscribe to events: payment.paid
 */

require_once __DIR__ . '/../dbConfiguration/Database.php';
require_once __DIR__ . '/../dbConfiguration/PayMongoConfig.php';
require_once __DIR__ . '/../service/TransactionService.php';
require_once __DIR__ . '/../service/SMSService.php';
require_once __DIR__ . '/../service/EmailService.php';

header('Content-Type: application/json');

// Get raw request body
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_PAYMONGO_SIGNATURE'] ?? '';

// Log webhook for debugging
$logFile = __DIR__ . '/webhook_log.txt';
file_put_contents($logFile, "\n\n[" . date('Y-m-d H:i:s') . "] Webhook received:\n" . $payload, FILE_APPEND);

// Verify webhook signature for security
if (!PayMongoConfig::verifyWebhookSignature($payload, $signature)) {
    file_put_contents($logFile, "\n❌ INVALID SIGNATURE - Request rejected", FILE_APPEND);
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid signature']);
    exit;
}

file_put_contents($logFile, "\n✅ Signature verified", FILE_APPEND);

// Parse webhook data
$data = json_decode($payload, true);

if (!isset($data['data']['attributes']['status'])) {
    file_put_contents($logFile, "\n❌ Invalid webhook format", FILE_APPEND);
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid format']);
    exit;
}

$status = $data['data']['attributes']['status'];
$payment_id = $data['data']['id'];
$amount = $data['data']['attributes']['amount'] / 100; // Convert from centavos
$metadata = $data['data']['attributes']['metadata'] ?? [];
$student_id = $metadata['student_id'] ?? null;

file_put_contents($logFile, "\nPayment Details:\n- Status: $status\n- Amount: $amount\n- Student ID: $student_id\n- Payment ID: $payment_id", FILE_APPEND);

// Process only successful payments
if ($status === 'paid' && $student_id) {
    $conn = require_once __DIR__ . '/../dbConfiguration/Database.php';
    
    if ($conn) {
        try {
            // Begin transaction
            $conn->begin_transaction();
            
            // Get current balance and student details
            $query = "SELECT balance, full_name, email, phone FROM students WHERE student_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $student_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $student = $result->fetch_assoc();
            
            if (!$student) {
                throw new Exception("Student not found");
            }
            
            $new_balance = $student['balance'] + $amount;
            
            // Update balance
            $updateQuery = "UPDATE students SET balance = balance + ? WHERE student_id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param('di', $amount, $student_id);
            $updateStmt->execute();
            
            // Record transaction
            $transactionQuery = "INSERT INTO transactions (student_id, transaction_type, amount, description, transaction_date, transaction_time, created_at) VALUES (?, 'CASH_IN', ?, ?, CURDATE(), CURTIME(), NOW())";
            $transStmt = $conn->prepare($transactionQuery);
            $desc = "Cash In via PayMongo (Payment ID: $payment_id)";
            $transStmt->bind_param('ids', $student_id, $amount, $desc);
            $transStmt->execute();
            
            // Record cash-in request
            $cashInQuery = "INSERT INTO cash_in_requests (student_id, amount, reference_number, status, requested_at, processed_at) VALUES (?, ?, ?, 'COMPLETED', NOW(), NOW())";
            $cashInStmt = $conn->prepare($cashInQuery);
            $ref = 'PAYMONGO_' . $payment_id;
            $cashInStmt->bind_param('ids', $student_id, $amount, $ref);
            $cashInStmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            file_put_contents($logFile, "\n✅ SUCCESS: Balance updated for student $student_id\nOld Balance: {$student['balance']}\nNew Balance: $new_balance\nAmount Added: $amount", FILE_APPEND);
            
            // Send SMS notification
            if ($student['phone']) {
                $smsResult = SMSService::sendCashInNotification(
                    $student['phone'],
                    $student['full_name'],
                    $amount,
                    $new_balance
                );
                file_put_contents($logFile, "\n📱 SMS: " . json_encode($smsResult), FILE_APPEND);
            }
            
            // Send Email notification
            if ($student['email']) {
                $emailResult = EmailService::sendCashInNotification(
                    $student['email'],
                    $student['full_name'],
                    $amount,
                    $new_balance,
                    'PAYMONGO_' . $payment_id
                );
                file_put_contents($logFile, "\n📧 Email: " . json_encode($emailResult), FILE_APPEND);
            }
            
            // Return success
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'message' => 'Balance credited successfully',
                'student_id' => $student_id,
                'amount' => $amount,
                'new_balance' => $new_balance
            ]);
            
        } catch (Exception $e) {
            $conn->rollback();
            file_put_contents($logFile, "\n❌ ERROR: " . $e->getMessage(), FILE_APPEND);
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
        
        $conn->close();
    } else {
        file_put_contents($logFile, "\n❌ Database connection failed", FILE_APPEND);
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
} else {
    file_put_contents($logFile, "\n⚠️  Webhook processed but no action taken (status: $status, student_id: $student_id)", FILE_APPEND);
    http_response_code(200);
    echo json_encode(['status' => 'info', 'message' => 'Webhook received']);
}
?>
