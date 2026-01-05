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
            // Find student with email
            $stmt = $this->conn->prepare("SELECT student_id, full_name, email FROM students WHERE school_id = ?");
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
            $student_name = $student['full_name'];
            $student_email = $student['email'];
            $stmt->close();

            if (!$student_email) {
                return [
                    'status' => 'error',
                    'message' => 'No email registered for this account'
                ];
            }

            // Generate OTP and unique token
            $otp = rand(100000, 999999);
            $reset_token = bin2hex(random_bytes(32)); // Unique token
            
            // Use MySQL NOW() function to avoid timezone issues
            $expires_in_minutes = 15;

            // Insert reset request with unique token and MySQL-based expiration
            $stmt = $this->conn->prepare("INSERT INTO password_resets (student_id, reset_token, otp_code, expires_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))");
            $stmt->bind_param("issi", $student_id, $reset_token, $otp, $expires_in_minutes);

            if ($stmt->execute()) {
                $stmt->close();
                
                // Send OTP via email
                require_once __DIR__ . '/EmailService.php';
                $emailResult = $this->sendOTPEmail($student_email, $student_name, $otp);
                
                return [
                    'status' => 'success',
                    'message' => 'Verification code sent to your email. Please check your inbox.',
                    'email_status' => $emailResult['status'] ?? 'unknown'
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

    /**
     * Reset password after OTP verification
     */
    public function resetPassword($school_id, $new_password) {
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

            // Check if there's a valid verified reset request
            $stmt = $this->conn->prepare("SELECT reset_id FROM password_resets WHERE student_id = ? AND is_used = 0 AND expires_at > NOW() LIMIT 1");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $stmt->close();
                return ['status' => 'error', 'message' => 'No valid reset request found. Please request a new OTP.'];
            }

            $reset = $result->fetch_assoc();
            $reset_id = $reset['reset_id'];
            $stmt->close();

            // Hash the new password (plain text for debugging - change in production)
            // $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
            $password_hash = $new_password; // Plain text for debugging

            // Update password
            $stmt = $this->conn->prepare("UPDATE students SET password_hash = ? WHERE student_id = ?");
            $stmt->bind_param("si", $password_hash, $student_id);
            
            if (!$stmt->execute()) {
                $stmt->close();
                throw new Exception("Failed to update password");
            }
            $stmt->close();

            // Mark reset request as used
            $stmt = $this->conn->prepare("UPDATE password_resets SET is_used = 1 WHERE reset_id = ?");
            $stmt->bind_param("i", $reset_id);
            $stmt->execute();
            $stmt->close();

            return [
                'status' => 'success',
                'message' => 'Password reset successful'
            ];

        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()];
        }
    }

    /**
     * Send OTP via email
     */
    private function sendOTPEmail($email, $name, $otp) {
        require_once __DIR__ . '/../dbConfiguration/NotificationConfig.php';
        
        $subject = 'Password Reset - Siena College E-Wallet';
        
        $body = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #8B0000, #DC8B6B); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .otp-box { background: white; border: 2px dashed #8B0000; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px; }
                .otp-code { font-size: 36px; font-weight: bold; color: #8B0000; letter-spacing: 8px; margin: 10px 0; }
                .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; margin: 20px 0; }
                .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🔐 Password Reset Request</h1>
                </div>
                <div class="content">
                    <p>Hi <strong>' . htmlspecialchars($name) . '</strong>,</p>
                    <p>We received a request to reset your password. Use the verification code below to continue:</p>
                    
                    <div class="otp-box">
                        <p style="margin: 0; color: #666; font-size: 14px;">Your verification code is:</p>
                        <div class="otp-code">' . $otp . '</div>
                        <p style="margin: 0; color: #666; font-size: 12px;">Valid for 15 minutes</p>
                    </div>
                    
                    <div class="warning">
                        ⚠️ <strong>Security Notice:</strong> If you did not request a password reset, please ignore this email and contact support immediately.
                    </div>
                    
                    <p style="text-align: center; margin-top: 30px;">
                        <em>This is an automated message from ' . NotificationConfig::SCHOOL_NAME . ' E-Wallet</em>
                    </p>
                </div>
                <div class="footer">
                    <p>This is an automated message. Please do not reply.</p>
                    <p>For support, contact: ' . NotificationConfig::SUPPORT_EMAIL . '</p>
                    <p>&copy; ' . date('Y') . ' ' . NotificationConfig::SCHOOL_NAME . '</p>
                </div>
            </div>
        </body>
        </html>
        ';
        
        return EmailService::send($email, $subject, $body);
    }
}
?>
