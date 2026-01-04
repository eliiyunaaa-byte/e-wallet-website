<?php
/**
 * Email Service using SMTP (Gmail)
 * Send email notifications to students
 */

require_once __DIR__ . '/../dbConfiguration/NotificationConfig.php';

class EmailService {
    
    /**
     * Send email using SMTP
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @return array Result
     */
    public static function send($to, $subject, $body) {
        // Log Email attempt
        error_log("[EMAIL] Attempting to send to: $to");
        error_log("[EMAIL] Subject: $subject");
        
        if (!NotificationConfig::ENABLE_EMAIL) {
            error_log("[EMAIL] DISABLED - Email notifications are turned off");
            return [
                'status' => 'disabled',
                'message' => 'Email notifications are disabled'
            ];
        }
        
        // Validate email format
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            error_log("[EMAIL] ❌ FAILED - Invalid email format: $to");
            return [
                'status' => 'error',
                'message' => 'Invalid email format'
            ];
        }
        
        try {
            // Use fsockopen for SMTP connection (no external libraries needed)
            $host = NotificationConfig::EMAIL_SMTP_HOST;
            $port = NotificationConfig::EMAIL_SMTP_PORT;
            $username = NotificationConfig::EMAIL_FROM_ADDRESS;
            $password = NotificationConfig::EMAIL_PASSWORD;
            
            $smtp = fsockopen($host, $port, $errno, $errstr, 10);
            
            if (!$smtp) {
                throw new Exception("SMTP connection failed: $errstr ($errno)");
            }
            
            // Helper function to read SMTP responses
            $read_response = function($stream) {
                $response = '';
                while ($line = fgets($stream, 1024)) {
                    $response .= $line;
                    if (substr($line, 3, 1) === ' ') break;
                }
                return $response;
            };
            
            // Read initial SMTP response
            $response = $read_response($smtp);
            if (strpos($response, '220') === false) {
                throw new Exception("SMTP server error: $response");
            }
            
            // Send EHLO
            fputs($smtp, "EHLO " . gethostname() . "\r\n");
            $response = $read_response($smtp);
            
            // Start TLS
            fputs($smtp, "STARTTLS\r\n");
            $response = $read_response($smtp);
            if (strpos($response, '220') === false) {
                throw new Exception("STARTTLS failed: $response");
            }
            
            // Enable crypto
            if (!stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception("Failed to enable TLS encryption");
            }
            
            // Send EHLO again after TLS
            fputs($smtp, "EHLO " . gethostname() . "\r\n");
            $response = $read_response($smtp);
            
            // Authenticate
            fputs($smtp, "AUTH LOGIN\r\n");
            $response = $read_response($smtp);
            
            fputs($smtp, base64_encode($username) . "\r\n");
            $response = $read_response($smtp);
            
            fputs($smtp, base64_encode($password) . "\r\n");
            $response = $read_response($smtp);
            if (strpos($response, '235') === false) {
                throw new Exception("Authentication failed");
            }
            
            // Prepare message
            $message = "From: " . NotificationConfig::EMAIL_FROM_NAME . " <" . $username . ">\r\n";
            $message .= "To: $to\r\n";
            $message .= "Subject: $subject\r\n";
            $message .= "MIME-Version: 1.0\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
            $message .= $body . "\r\n";
            
            // Send message
            fputs($smtp, "MAIL FROM: <$username>\r\n");
            $response = $read_response($smtp);
            
            fputs($smtp, "RCPT TO: <$to>\r\n");
            $response = $read_response($smtp);
            
            fputs($smtp, "DATA\r\n");
            $response = $read_response($smtp);
            
            fputs($smtp, $message . "\r\n.\r\n");
            $response = $read_response($smtp);
            if (strpos($response, '250') === false) {
                throw new Exception("Message send failed: $response");
            }
            
            // Close connection
            fputs($smtp, "QUIT\r\n");
            fclose($smtp);
            
            error_log("[EMAIL] ✅ SUCCESS - Sent to: $to");
            return [
                'status' => 'success',
                'to' => $to
            ];
            
        } catch (Exception $e) {
            error_log("[EMAIL] ❌ FAILED - " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to send email: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Send cash-in confirmation email
     * @param string $email Student email
     * @param string $name Student name
     * @param float $amount Amount added
     * @param float $newBalance New balance
     * @param string $reference Transaction reference
     * @return array Result
     */
    public static function sendCashInNotification($email, $name, $amount, $newBalance, $reference) {
        $subject = 'E-Wallet Cash-In Successful - Siena College';
        
        $body = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #8B0000, #DC8B6B); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .amount { font-size: 32px; font-weight: bold; color: #16a34a; text-align: center; margin: 20px 0; }
                .balance { font-size: 24px; color: #2563eb; text-align: center; margin: 20px 0; }
                .details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
                .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>✅ Cash-In Successful!</h1>
                </div>
                <div class="content">
                    <p>Hi <strong>' . htmlspecialchars($name) . '</strong>,</p>
                    <p>Your e-wallet has been successfully credited.</p>
                    
                    <div class="amount">
                        + ₱' . number_format($amount, 2) . '
                    </div>
                    
                    <div class="balance">
                        New Balance: ₱' . number_format($newBalance, 2) . '
                    </div>
                    
                    <div class="details">
                        <div class="detail-row">
                            <span><strong>Transaction Date:</strong></span>
                            <span>' . date('F d, Y') . '</span>
                        </div>
                        <div class="detail-row">
                            <span><strong>Transaction Time:</strong></span>
                            <span>' . date('h:i A') . '</span>
                        </div>
                        <div class="detail-row">
                            <span><strong>Reference Number:</strong></span>
                            <span>' . htmlspecialchars($reference) . '</span>
                        </div>
                        <div class="detail-row">
                            <span><strong>Payment Method:</strong></span>
                            <span>PayMongo (GCash/Maya/Card)</span>
                        </div>
                    </div>
                    
                    <p style="text-align: center; margin-top: 30px;">
                        <em>Thank you for using ' . NotificationConfig::SCHOOL_NAME . ' E-Wallet!</em>
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
        
        return self::send($email, $subject, $body);
    }
    
    /**
     * Send purchase receipt email
     * @param string $email Student email
     * @param string $name Student name
     * @param float $amount Amount spent
     * @param string $item Item purchased
     * @param string $location Purchase location
     * @param float $newBalance New balance
     * @return array Result
     */
    public static function sendPurchaseReceipt($email, $name, $amount, $item, $location, $newBalance) {
        $subject = 'Purchase Receipt - Siena College E-Wallet';
        
        $body = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #8B0000, #DC8B6B); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .amount { font-size: 32px; font-weight: bold; color: #dc2626; text-align: center; margin: 20px 0; }
                .balance { font-size: 20px; color: #2563eb; text-align: center; margin: 20px 0; }
                .details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
                .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🧾 Purchase Receipt</h1>
                </div>
                <div class="content">
                    <p>Hi <strong>' . htmlspecialchars($name) . '</strong>,</p>
                    <p>Thank you for your purchase!</p>
                    
                    <div class="amount">
                        - ₱' . number_format($amount, 2) . '
                    </div>
                    
                    <div class="details">
                        <div class="detail-row">
                            <span><strong>Item:</strong></span>
                            <span>' . htmlspecialchars($item) . '</span>
                        </div>
                        <div class="detail-row">
                            <span><strong>Location:</strong></span>
                            <span>' . htmlspecialchars($location) . '</span>
                        </div>
                        <div class="detail-row">
                            <span><strong>Date:</strong></span>
                            <span>' . date('F d, Y') . '</span>
                        </div>
                        <div class="detail-row">
                            <span><strong>Time:</strong></span>
                            <span>' . date('h:i A') . '</span>
                        </div>
                    </div>
                    
                    <div class="balance">
                        Remaining Balance: ₱' . number_format($newBalance, 2) . '
                    </div>
                    
                    <p style="text-align: center; margin-top: 30px;">
                        <em>Keep this email as your receipt.</em>
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
        
        return self::send($email, $subject, $body);
    }
}
?>
