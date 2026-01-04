<?php
/**
 * Authentication Service
 * Handles login, registration, password reset, and session management
 */

class AuthService {
    private $conn;

    public function __construct($connection) {
        $this->conn = $connection;
    }

    /**
     * Login student with school ID and password
     */
    public function login($school_id, $password) {
        try {
            // Validate inputs
            if (empty($school_id) || empty($password)) {
                return [
                    'status' => 'error',
                    'message' => 'School ID and password are required'
                ];
            }

            // Query student by school ID
            $stmt = $this->conn->prepare("SELECT student_id, school_id, first_name, last_name, password_hash, balance, grade_section, is_active FROM students WHERE school_id = ?");
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }

            $stmt->bind_param("s", $school_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                return [
                    'status' => 'error',
                    'message' => 'School ID or password is incorrect'
                ];
            }

            $student = $result->fetch_assoc();
            $stmt->close();

            // Check if account is active
            if (!$student['is_active']) {
                return [
                    'status' => 'error',
                    'message' => 'Your account has been deactivated'
                ];
            }

            // Verify password (PLAIN TEXT - FOR DEBUGGING ONLY)
            if ($password !== $student['password_hash']) {
                return [
                    'status' => 'error',
                    'message' => 'School ID or password is incorrect'
                ];
            }

            // Password is correct, return student data
            return [
                'status' => 'success',
                'message' => 'Login successful',
                'data' => [
                    'student_id' => $student['student_id'],
                    'school_id' => $student['school_id'],
                    'name' => $student['first_name'] . ' ' . $student['last_name'],
                    'balance' => (float)$student['balance'],
                    'grade_section' => $student['grade_section']
                ]
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'An error occurred during login: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Register new student
     */
    public function register($school_id, $first_name, $last_name, $email, $grade_section, $password) {
        try {
            // Validate inputs
            if (empty($school_id) || empty($first_name) || empty($last_name) || empty($password)) {
                return [
                    'status' => 'error',
                    'message' => 'All fields are required'
                ];
            }

            // Check if school ID already exists
            $check_stmt = $this->conn->prepare("SELECT student_id FROM students WHERE school_id = ?");
            $check_stmt->bind_param("s", $school_id);
            $check_stmt->execute();
            
            if ($check_stmt->get_result()->num_rows > 0) {
                $check_stmt->close();
                return [
                    'status' => 'error',
                    'message' => 'School ID already registered'
                ];
            }
            $check_stmt->close();

            // Store password as plain text (FOR DEBUGGING ONLY - NOT FOR PRODUCTION)
            $password_hash = $password;

            // Insert new student
            $stmt = $this->conn->prepare("INSERT INTO students (school_id, first_name, last_name, email, grade_section, password_hash, full_name) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            $full_name = $first_name . ' ' . $last_name;
            $stmt->bind_param("sssssss", $school_id, $first_name, $last_name, $email, $grade_section, $password_hash, $full_name);

            if ($stmt->execute()) {
                $stmt->close();
                return [
                    'status' => 'success',
                    'message' => 'Registration successful. You can now login.'
                ];
            } else {
                throw new Exception("Registration failed: " . $stmt->error);
            }

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'An error occurred during registration: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Request password reset - generates OTP
     */
    public function requestPasswordReset($school_id) {
        try {
            // Find student
            $stmt = $this->conn->prepare("SELECT student_id FROM students WHERE school_id = ?");
            $stmt->bind_param("s", $school_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $stmt->close();
                return [
                    'status' => 'error',
                    'message' => 'School ID not found'
                ];
            }

            $student = $result->fetch_assoc();
            $student_id = $student['student_id'];
            $stmt->close();

            // Generate OTP
            $otp = rand(100000, 999999);
            $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            // Insert reset request
            $stmt = $this->conn->prepare("INSERT INTO password_resets (student_id, otp_code, expires_at) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $student_id, $otp, $expires_at);

            if ($stmt->execute()) {
                $stmt->close();
                // In production, send OTP via email
                return [
                    'status' => 'success',
                    'message' => 'OTP sent. Check your email.',
                    'otp_debug' => $otp  // Remove in production
                ];
            } else {
                throw new Exception("Reset request failed");
            }

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify OTP and get reset token
     */
    public function verifyOTP($school_id, $otp) {
        try {
            // Find student
            $stmt = $this->conn->prepare("SELECT student_id FROM students WHERE school_id = ?");
            $stmt->bind_param("s", $school_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $stmt->close();
                return ['status' => 'error', 'message' => 'School ID not found'];
            }

            $student = $result->fetch_assoc();
            $student_id = $student['student_id'];
            $stmt->close();

            // Verify OTP
            $stmt = $this->conn->prepare("SELECT reset_id FROM password_resets WHERE student_id = ? AND otp_code = ? AND is_used = 0 AND expires_at > NOW()");
            $stmt->bind_param("is", $student_id, $otp);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $stmt->close();
                return ['status' => 'error', 'message' => 'Invalid or expired OTP'];
            }

            $stmt->close();
            return ['status' => 'success', 'message' => 'OTP verified'];

        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()];
        }
    }
}
?>
