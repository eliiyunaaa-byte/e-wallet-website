<?php
/**
 * Email Service using PHPMailer
 * Send email notifications to students
 */

require_once __DIR__ . '/../dbConfiguration/NotificationConfig.php';

// Download PHPMailer from: https://github.com/PHPMailer/PHPMailer
// Or install via composer: composer require phpmailer/phpmailer
// For now, using basic PHP mail() function as fallback

class EmailService {
    
    /**
     * Send email using PHP mail() function
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
        
        // Email headers
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . NotificationConfig::EMAIL_FROM_NAME . ' <' . NotificationConfig::EMAIL_FROM_ADDRESS . '>',
            'Reply-To: ' . NotificationConfig::SUPPORT_EMAIL,
            'X-Mailer: PHP/' . phpversion()
        ];
        
        $success = mail($to, $subject, $body, implode("\r\n", $headers));
        
        if ($success) {
            error_log("[EMAIL] ✅ SUCCESS - Sent to: $to");
            return [
                'status' => 'success',
                'to' => $to
            ];
        }
        
        error_log("[EMAIL] ❌ FAILED - Could not send to: $to");
        return [
            'status' => 'error',
            'message' => 'Failed to send email'
        ];
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
