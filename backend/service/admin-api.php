<?php
/**
 * Admin API Endpoints
 * Handle admin operations for student management
 */

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$action = isset($_GET['action']) ? $_GET['action'] : '';

// Get database connection
try {
    $conn = include __DIR__ . '/../dbConfiguration/Database.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed'
    ]);
    exit;
}

// Route actions
switch($action) {
    case 'get_students':
        getStudents($conn);
        break;
    
    case 'get_student':
        getStudent($conn);
        break;
    
    case 'create_student':
        createStudent($conn);
        break;
    
    case 'update_student':
        updateStudent($conn);
        break;
    
    case 'delete_student':
        deleteStudent($conn);
        break;
    
    default:
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Action not found']);
}

// ============================================
// FUNCTION: Get All Students
// ============================================
function getStudents($conn) {
    $stmt = $conn->prepare("SELECT student_id, school_id, first_name, last_name, full_name, email, phone, grade_section, balance, is_active, created_at FROM students ORDER BY created_at DESC");
    $stmt->execute();
    $result = $stmt->get_result();

    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }

    $stmt->close();

    echo json_encode([
        'status' => 'success',
        'students' => $students
    ]);
}

// ============================================
// FUNCTION: Get Single Student
// ============================================
function getStudent($conn) {
    $student_id = $_GET['student_id'] ?? null;

    if (!$student_id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Student ID required']);
        return;
    }

    $stmt = $conn->prepare("SELECT student_id, school_id, first_name, last_name, full_name, email, phone, grade_section, balance, is_active FROM students WHERE student_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Student not found']);
        return;
    }

    $student = $result->fetch_assoc();
    $stmt->close();

    echo json_encode([
        'status' => 'success',
        'student' => $student
    ]);
}

// ============================================
// FUNCTION: Create Student
// ============================================
function createStudent($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'POST request required']);
        return;
    }

    $data = json_decode(file_get_contents("php://input"), true);
    
    $school_id = $data['school_id'] ?? null;
    $first_name = $data['first_name'] ?? null;
    $last_name = $data['last_name'] ?? null;
    $email = $data['email'] ?? null;
    $phone = $data['phone'] ?? null;
    $grade_section = $data['grade_section'] ?? null;
    $password = $data['password'] ?? null;
    $balance = $data['balance'] ?? 0;

    if (!$school_id || !$first_name || !$last_name || !$password) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Required fields missing']);
        return;
    }

    // Check if school_id already exists
    $stmt = $conn->prepare("SELECT student_id FROM students WHERE school_id = ?");
    $stmt->bind_param("s", $school_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $stmt->close();
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'School ID already exists']);
        return;
    }
    $stmt->close();

    // Create full name
    $full_name = $first_name . ' ' . $last_name;

    // Hash password (plain text for debugging - use password_hash in production)
    $password_hash = $password;

    // Insert student
    $stmt = $conn->prepare("INSERT INTO students (school_id, first_name, last_name, full_name, email, phone, grade_section, password_hash, balance) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssd", $school_id, $first_name, $last_name, $full_name, $email, $phone, $grade_section, $password_hash, $balance);

    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode([
            'status' => 'success',
            'message' => 'Student created successfully'
        ]);
    } else {
        $stmt->close();
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to create student']);
    }
}

// ============================================
// FUNCTION: Update Student
// ============================================
function updateStudent($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'POST request required']);
        return;
    }

    $data = json_decode(file_get_contents("php://input"), true);
    
    $student_id = $data['student_id'] ?? null;
    $school_id = $data['school_id'] ?? null;
    $first_name = $data['first_name'] ?? null;
    $last_name = $data['last_name'] ?? null;
    $email = $data['email'] ?? null;
    $phone = $data['phone'] ?? null;
    $grade_section = $data['grade_section'] ?? null;
    $password = $data['password'] ?? null;
    $balance = $data['balance'] ?? 0;

    if (!$student_id || !$school_id || !$first_name || !$last_name) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Required fields missing']);
        return;
    }

    // Create full name
    $full_name = $first_name . ' ' . $last_name;

    // Update query
    if ($password) {
        // Update with password
        $password_hash = $password;
        $stmt = $conn->prepare("UPDATE students SET school_id = ?, first_name = ?, last_name = ?, full_name = ?, email = ?, phone = ?, grade_section = ?, password_hash = ?, balance = ? WHERE student_id = ?");
        $stmt->bind_param("ssssssssdi", $school_id, $first_name, $last_name, $full_name, $email, $phone, $grade_section, $password_hash, $balance, $student_id);
    } else {
        // Update without password
        $stmt = $conn->prepare("UPDATE students SET school_id = ?, first_name = ?, last_name = ?, full_name = ?, email = ?, phone = ?, grade_section = ?, balance = ? WHERE student_id = ?");
        $stmt->bind_param("sssssssdi", $school_id, $first_name, $last_name, $full_name, $email, $phone, $grade_section, $balance, $student_id);
    }

    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode([
            'status' => 'success',
            'message' => 'Student updated successfully'
        ]);
    } else {
        $stmt->close();
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to update student']);
    }
}

// ============================================
// FUNCTION: Delete Student
// ============================================
function deleteStudent($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'POST request required']);
        return;
    }

    $data = json_decode(file_get_contents("php://input"), true);
    $student_id = $data['student_id'] ?? null;

    if (!$student_id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Student ID required']);
        return;
    }

    $stmt = $conn->prepare("DELETE FROM students WHERE student_id = ?");
    $stmt->bind_param("i", $student_id);

    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode([
            'status' => 'success',
            'message' => 'Student deleted successfully'
        ]);
    } else {
        $stmt->close();
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete student']);
    }
}
?>
