<?php
/**
 * SMS Service using Semaphore API
 * Send SMS notifications to students
 */

require_once __DIR__ . '/../dbConfiguration/NotificationConfig.php';

class SMSService {
    
    /**
     * Send SMS via Semaphore
     * @param string $phone Phone number (e.g., 09123456789 or +639123456789)
     * @param string $message SMS content
     * @return array Result with status
     */
    public static function send($phone, $message) {
        // Log SMS attempt
        error_log("[SMS] Attempting to send to: $phone");
        error_log("[SMS] Message: $message");
        
        if (!NotificationConfig::ENABLE_SMS) {
            error_log("[SMS] DISABLED - SMS notifications are turned off");
            return [
                'status' => 'disabled',
                'message' => 'SMS notifications are disabled'
            ];
        }
        
        // Format phone number (remove spaces, dashes)
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Convert to international format if needed
        if (substr($phone, 0, 1) === '0') {
            $phone = '+63' . substr($phone, 1);
        }
        
        // Prepare API request
        $data = [
            'apikey' => NotificationConfig::SEMAPHORE_API_KEY,
            'number' => $phone,
            'message' => $message,
            'sendername' => NotificationConfig::SEMAPHORE_SENDER_NAME
        ];
        
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => NotificationConfig::SEMAPHORE_API_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded'
            ],
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        
        if ($error) {
            error_log("[SMS] cURL Error: $error");
            return [
                'status' => 'error',
                'message' => 'cURL Error: ' . $error
            ];
        }
        
        error_log("[SMS] API Response: $response");
        $result = json_decode($response, true);
        
        if ($httpCode === 200 && isset($result[0]['message_id'])) {
            error_log("[SMS] ✅ SUCCESS - Message ID: {$result[0]['message_id']}");
            return [
                'status' => 'success',
                'message_id' => $result[0]['message_id'],
                'phone' => $phone
            ];
        }
        
        error_log("[SMS] ❌ FAILED - HTTP $httpCode: " . ($result['message'] ?? 'Unknown error'));
        return [
            'status' => 'error',
            'message' => $result['message'] ?? 'Failed to send SMS',
            'response' => $response
        ];
    }
    
    /**
     * Send cash-in confirmation SMS
     * @param string $phone Student phone number
     * @param string $name Student name
     * @param float $amount Amount added
     * @param float $newBalance New balance
     * @return array Result
     */
    public static function sendCashInNotification($phone, $name, $amount, $newBalance) {
        $message = sprintf(
            "Hi %s! Your Siena College e-wallet has been credited with P%.2f. New balance: P%.2f. Thank you!",
            $name,
            $amount,
            $newBalance
        );
        
        return self::send($phone, $message);
    }
    
    /**
     * Send purchase notification SMS
     * @param string $phone Student phone number
     * @param string $name Student name
     * @param float $amount Amount spent
     * @param string $item Item purchased
     * @param float $newBalance New balance
     * @return array Result
     */
    public static function sendPurchaseNotification($phone, $name, $amount, $item, $newBalance) {
        $message = sprintf(
            "Hi %s! You purchased %s for P%.2f. Remaining balance: P%.2f.",
            $name,
            $item,
            $amount,
            $newBalance
        );
        
        return self::send($phone, $message);
    }
}
?>
