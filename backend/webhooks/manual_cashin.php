<?php
/**
 * Manual Cash-In Processor (For Testing Without Webhook)
 * 
 * This allows you to manually process payments from PayMongo
 * In production, the webhook will do this automatically
 */

// Prevent any HTML output
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

$conn = require_once __DIR__ . '/../dbConfiguration/Database.php';
require_once __DIR__ . '/../service/SMSService.php';
require_once __DIR__ . '/../service/EmailService.php';

// Get student_id and amount from URL
$student_id = $_GET['student_id'] ?? null;
$amount = $_GET['amount'] ?? null;

if (!$student_id || !$amount) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing student_id or amount'
    ]);
    exit;
}

try {
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    $conn->begin_transaction();
    
    // Get student details
    $studentQuery = "SELECT full_name, email, phone, balance FROM students WHERE student_id = ?";
    $studentStmt = $conn->prepare($studentQuery);
    $studentStmt->bind_param('i', $student_id);
    $studentStmt->execute();
    $studentResult = $studentStmt->get_result();
    $studentData = $studentResult->fetch_assoc();
    
    if (!$studentData) {
        throw new Exception('Student not found');
    }
    
    $oldBalance = $studentData['balance'];
    $newBalance = $oldBalance + $amount;
    
    // Update balance
    $updateQuery = "UPDATE students SET balance = balance + ? WHERE student_id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param('di', $amount, $student_id);
    $stmt->execute();
    
    // Record transaction
    $transQuery = "INSERT INTO transactions (student_id, transaction_type, amount, description, transaction_date, transaction_time, created_at) VALUES (?, 'CASH_IN', ?, 'Cash In via PayMongo (Test)', CURDATE(), CURTIME(), NOW())";
    $transStmt = $conn->prepare($transQuery);
    $transStmt->bind_param('id', $student_id, $amount);
    $transStmt->execute();
    
    // Record cash-in request
    $cashInQuery = "INSERT INTO cash_in_requests (student_id, amount, reference_number, status, requested_at, processed_at) VALUES (?, ?, 'PAYMONGO_TEST', 'COMPLETED', NOW(), NOW())";
    $cashInStmt = $conn->prepare($cashInQuery);
    $cashInStmt->bind_param('id', $student_id, $amount);
    $cashInStmt->execute();
    
    $conn->commit();
    $conn->close();
    
    // Send SMS notification
    $smsResult = null;
    if ($studentData['phone']) {
        $smsResult = SMSService::sendCashInNotification(
            $studentData['phone'],
            $studentData['full_name'],
            $amount,
            $newBalance
        );
    }
    
    // Send Email notification
    $emailResult = null;
    if ($studentData['email']) {
        $emailResult = EmailService::sendCashInNotification(
            $studentData['email'],
            $studentData['full_name'],
            $amount,
            $newBalance,
            'PAYMONGO_TEST'
        );
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Balance updated successfully',
        'amount' => floatval($amount),
        'new_balance' => $newBalance,
        'notifications' => [
            'sms' => $smsResult,
            'email' => $emailResult
        ]
    ]);
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
        $conn->close();
    }
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
