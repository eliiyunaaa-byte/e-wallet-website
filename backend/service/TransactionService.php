<?php
/**
 * Transaction Service
 * Handles wallet transactions, balance updates, and cash-in requests
 */

class TransactionService {
    private $conn;

    public function __construct($connection) {
        $this->conn = $connection;
    }

    /**
     * Get student balance
     */
    public function getBalance($student_id) {
        try {
            $stmt = $this->conn->prepare("SELECT balance FROM students WHERE student_id = ?");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $stmt->close();
                return ['status' => 'error', 'message' => 'Student not found'];
            }

            $student = $result->fetch_assoc();
            $stmt->close();

            return [
                'status' => 'success',
                'balance' => (float)$student['balance']
            ];

        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Process a purchase transaction
     */
    public function processPurchase($student_id, $amount, $item_name, $location, $vendor_id = null) {
        try {
            // Get current balance
            $balance_result = $this->getBalance($student_id);
            if ($balance_result['status'] === 'error') {
                return $balance_result;
            }

            $current_balance = $balance_result['balance'];

            // Check if sufficient balance
            if ($current_balance < $amount) {
                return [
                    'status' => 'error',
                    'message' => 'Insufficient balance',
                    'current_balance' => $current_balance,
                    'required' => $amount
                ];
            }

            // Calculate new balance
            $new_balance = $current_balance - $amount;

            // Start transaction
            $this->conn->begin_transaction();

            try {
                // Update student balance
                $update_stmt = $this->conn->prepare("UPDATE students SET balance = ? WHERE student_id = ?");
                $update_stmt->bind_param("di", $new_balance, $student_id);
                $update_stmt->execute();
                $update_stmt->close();

                // Record transaction
                $transaction_date = date('Y-m-d');
                $transaction_time = date('H:i:s');
                $trans_type = 'PURCHASE';
                $status = 'COMPLETED';

                $trans_stmt = $this->conn->prepare("INSERT INTO transactions (student_id, transaction_type, amount, previous_balance, new_balance, location, item_name, transaction_date, transaction_time, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $trans_stmt->bind_param("isdddsssss", $student_id, $trans_type, $amount, $current_balance, $new_balance, $location, $item_name, $transaction_date, $transaction_time, $status);
                $trans_stmt->execute();
                $trans_stmt->close();

                $this->conn->commit();

                return [
                    'status' => 'success',
                    'message' => 'Purchase successful',
                    'new_balance' => $new_balance,
                    'amount_spent' => $amount
                ];

            } catch (Exception $e) {
                $this->conn->rollback();
                throw $e;
            }

        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Transaction failed: ' . $e->getMessage()];
        }
    }

    /**
     * Get student transactions
     */
    public function getTransactions($student_id, $limit = 50, $offset = 0) {
        try {
            $stmt = $this->conn->prepare("SELECT transaction_id, transaction_type, amount, location, item_name, transaction_date, transaction_time, status FROM transactions WHERE student_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
            $stmt->bind_param("iii", $student_id, $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();

            $transactions = [];
            while ($row = $result->fetch_assoc()) {
                $transactions[] = $row;
            }
            $stmt->close();

            return [
                'status' => 'success',
                'transactions' => $transactions,
                'count' => count($transactions)
            ];

        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Request cash-in
     */
    public function requestCashIn($student_id, $amount, $reference_number = null) {
        try {
            if ($amount <= 0) {
                return ['status' => 'error', 'message' => 'Amount must be greater than 0'];
            }

            $stmt = $this->conn->prepare("INSERT INTO cash_in_requests (student_id, amount, reference_number, status) VALUES (?, ?, ?, 'PENDING')");
            $stmt->bind_param("ids", $student_id, $amount, $reference_number);

            if ($stmt->execute()) {
                $cash_in_id = $stmt->insert_id;
                $stmt->close();

                return [
                    'status' => 'success',
                    'message' => 'Cash-in request submitted',
                    'cash_in_id' => $cash_in_id
                ];
            } else {
                throw new Exception("Insert failed");
            }

        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Cash-in request failed: ' . $e->getMessage()];
        }
    }

    /**
     * Get weekly spending
     */
    public function getWeeklySpending($student_id) {
        try {
            $stmt = $this->conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE student_id = ? AND transaction_type = 'PURCHASE' AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();

            return [
                'status' => 'success',
                'weekly_spending' => (float)$row['total']
            ];

        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
?>
