<?php
/**
 * API Endpoints for Frontend
 * Handle login, logout, and dashboard data
 */

// Start session
session_start();

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't show errors in output
ini_set('log_errors', 1);

// Set error handler to catch fatal errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $errstr . ' in ' . basename($errfile) . ':' . $errline
    ]);
    exit;
});

$action = isset($_GET['action']) ? $_GET['action'] : '';

// Include services
require_once __DIR__ . '/../dbConfiguration/Database.php';
require_once __DIR__ . '/../dbConfiguration/PayMongoConfig.php';
require_once __DIR__ . '/AuthService.php';
require_once __DIR__ . '/TransactionService.php';
require_once __DIR__ . '/PayMongoService.php';
require_once __DIR__ . '/../dbConfiguration/Database.php';
require_once __DIR__ . '/../dbConfiguration/PayMongoConfig.php';

// Type hints for IntelliSense (classes are loaded via require_once above)
if (false) {
    class AuthService {}
    class TransactionService {}
    class PayMongoService {}
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

// Get database connection
if (!isset($conn) || !$conn) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed. Make sure MySQL is running.'
    ]);
    exit;
}

// Route actions
switch($action) {
    case 'login':
        handleLogin($conn);
        break;
    
    case 'get_balance':
        getBalance($conn);
        break;
    
    case 'get_transactions':
        getTransactions($conn);
        break;
    
    case 'process_purchase':
        processPurchase($conn);
        break;
    
    case 'request_cashin':
        requestCashIn($conn);
        break;
    
    case 'create_payment_link':
        createPaymentLink($conn);
        break;
    
    case 'get_weekly_spending':
        getWeeklySpending($conn);
        break;
    
    case 'request_password_reset':
        requestPasswordReset($conn);
        break;
    
    case 'verify_otp':
        verifyOTP($conn);
        break;
    
    case 'reset_password':
        resetPassword($conn);
        break;
    
    default:
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Action not found']);
}

// ============================================
// FUNCTION: Handle Login (Auto-detect Admin or Student)
// ============================================
function handleLogin($conn) {
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'POST request required']);
            return;
        }

        $input = file_get_contents("php://input");
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid JSON in request body']);
            return;
        }
        
        $school_id = $data['school_id'] ?? null;
        $password = $data['password'] ?? null;

        $auth = new AuthService($conn);
        $result = $auth->login($school_id, $password);

        if ($result['status'] === 'success') {
            // Set session
            $_SESSION['student_id'] = $result['data']['student_id'];
            $_SESSION['school_id'] = $result['data']['school_id'];
            $_SESSION['name'] = $result['data']['name'];
        }

        echo json_encode($result);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Server error: ' . $e->getMessage()
        ]);
    }
}

// ============================================
// FUNCTION: Get Balance
// ============================================
function getBalance($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'GET request required']);
        return;
    }

    $student_id = $_GET['student_id'] ?? null;

    if (!$student_id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Student ID required']);
        return;
    }

    /** @var TransactionService $trans */
    $trans = new TransactionService($conn);
    echo json_encode($trans->getBalance($student_id));
}

// ============================================
// FUNCTION: Get Transactions
// ============================================
function getTransactions($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'GET request required']);
        return;
    }

    $student_id = $_GET['student_id'] ?? null;
    $limit = $_GET['limit'] ?? 50;
    $offset = $_GET['offset'] ?? 0;

    if (!$student_id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Student ID required']);
        return;
    }

    /** @var TransactionService $trans */
    $trans = new TransactionService($conn);
    echo json_encode($trans->getTransactions($student_id, $limit, $offset));
}

// ============================================
// FUNCTION: Process Purchase
// ============================================
function processPurchase($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'POST request required']);
        return;
    }

    $data = json_decode(file_get_contents("php://input"), true);
    $student_id = $data['student_id'] ?? null;
    $amount = $data['amount'] ?? null;
    $item_name = $data['item_name'] ?? null;
    $location = $data['location'] ?? null;

    if (!$student_id || !$amount || !$item_name) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Required fields missing']);
        return;
    }

    /** @var TransactionService $trans */
    $trans = new TransactionService($conn);
    echo json_encode($trans->processPurchase($student_id, $amount, $item_name, $location));
}

// ============================================
// FUNCTION: Request Cash-In
// ============================================
function requestCashIn($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'POST request required']);
        return;
    }

    $data = json_decode(file_get_contents("php://input"), true);
    $student_id = $data['student_id'] ?? null;
    $amount = $data['amount'] ?? null;
    $reference_number = $data['reference_number'] ?? null;

    if (!$student_id || !$amount) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Student ID and amount required']);
        return;
    }

    /** @var TransactionService $trans */
    $trans = new TransactionService($conn);
    echo json_encode($trans->requestCashIn($student_id, $amount, $reference_number));
}

// ============================================
// FUNCTION: Get Weekly Spending
// ============================================
function getWeeklySpending($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'GET request required']);
        return;
    }

    $student_id = $_GET['student_id'] ?? null;

    if (!$student_id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Student ID required']);
        return;
    }

    /** @var TransactionService $trans */
    $trans = new TransactionService($conn);
    echo json_encode($trans->getWeeklySpending($student_id));
}

// ============================================
// FUNCTION: Create PayMongo Payment Link
// ============================================
function createPaymentLink($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'POST request required']);
        return;
    }

    $data = json_decode(file_get_contents("php://input"), true);
    $student_id = $data['student_id'] ?? null;
    $amount = $data['amount'] ?? null;

    if (!$student_id || !$amount) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Student ID and amount required']);
        return;
    }

    // Validate amount (minimum ₱10)
    if ($amount < 10) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Minimum amount is ₱10']);
        return;
    }

    // Create payment link via PayMongo
    /** @var array $result */
    $result = PayMongoService::createPaymentLink($amount, $student_id);
    echo json_encode($result);
}

// ============================================
// FUNCTION: Request Password Reset (Send OTP)
// ============================================
function requestPasswordReset($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'POST request required']);
        return;
    }

    $data = json_decode(file_get_contents("php://input"), true);
    $school_id = $data['school_id'] ?? null;

    if (!$school_id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'School ID required']);
        return;
    }

    $auth = new AuthService($conn);
    $result = $auth->requestPasswordReset($school_id);
    echo json_encode($result);
}

// ============================================
// FUNCTION: Verify OTP
// ============================================
function verifyOTP($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'POST request required']);
        return;
    }

    $data = json_decode(file_get_contents("php://input"), true);
    $school_id = $data['school_id'] ?? null;
    $otp = $data['otp'] ?? null;

    if (!$school_id || !$otp) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'School ID and OTP required']);
        return;
    }

    $auth = new AuthService($conn);
    $result = $auth->verifyOTP($school_id, $otp);
    echo json_encode($result);
}

// ============================================
// FUNCTION: Reset Password
// ============================================
function resetPassword($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'POST request required']);
        return;
    }

    $data = json_decode(file_get_contents("php://input"), true);
    $school_id = $data['school_id'] ?? null;
    $new_password = $data['new_password'] ?? null;

    if (!$school_id || !$new_password) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'School ID and new password required']);
        return;
    }

    $auth = new AuthService($conn);
    $result = $auth->resetPassword($school_id, $new_password);
    echo json_encode($result);
}
?>
