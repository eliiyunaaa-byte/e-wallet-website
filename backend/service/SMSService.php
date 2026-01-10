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
        
        // Validate phone number
        if (empty($phone)) {
            error_log("[SMS] ❌ FAILED - No phone number provided");
            return [
                'status' => 'error',
                'message' => 'No phone number provided'
            ];
        }
        
        // Format phone number (remove spaces, dashes)
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Convert to international format if needed
        if (substr($phone, 0, 1) === '0') {
            $phone = '+63' . substr($phone, 1);
        }
        
        // Validate API key
        if (empty(NotificationConfig::SEMAPHORE_API_KEY) || NotificationConfig::SEMAPHORE_API_KEY === 'your_api_key_here') {
            error_log("[SMS] ⚠️ WARNING - Semaphore API key not configured");
            return [
                'status' => 'warning',
                'message' => 'SMS service not configured. API key missing.',
                'phone' => $phone
            ];
        }
        
        // Prepare API request
        $data = [
            'apikey' => NotificationConfig::SEMAPHORE_API_KEY,
            'number' => $phone,
            'message' => $message,
            'sendername' => NotificationConfig::SEMAPHORE_SENDER_NAME
        ];
        
        try {
            $curl = curl_init();
            
            if (!$curl) {
                throw new Exception('cURL initialization failed');
            }
            
            curl_setopt_array($curl, [
                CURLOPT_URL => NotificationConfig::SEMAPHORE_API_URL,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($data),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/x-www-form-urlencoded'
                ],
                CURLOPT_SSL_VERIFYPEER => false,  // For development only
                CURLOPT_SSL_VERIFYHOST => 0       // For development only
            ]);
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);
            curl_close($curl);
            
            if ($error) {
                error_log("[SMS] cURL Error: $error");
                return [
                    'status' => 'error',
                    'message' => 'Connection error: ' . $error,
                    'phone' => $phone
                ];
            }
            
            error_log("[SMS] API Response (" . $httpCode . "): $response");
            $result = json_decode($response, true);
            
            // Handle API response
            if ($httpCode === 200 && is_array($result) && isset($result[0]['message_id'])) {
                error_log("[SMS] ✅ SUCCESS - Message ID: {$result[0]['message_id']}");
                return [
                    'status' => 'success',
                    'message_id' => $result[0]['message_id'],
                    'phone' => $phone
                ];
            } else if ($httpCode === 200 && is_array($result) && isset($result['message'])) {
                // API returned an error message
                error_log("[SMS] ❌ FAILED - API Error: {$result['message']}");
                return [
                    'status' => 'error',
                    'message' => 'SMS API Error: ' . $result['message'],
                    'phone' => $phone
                ];
            } else if ($httpCode === 401) {
                error_log("[SMS] ❌ FAILED - Unauthorized (Invalid API Key)");
                return [
                    'status' => 'error',
                    'message' => 'SMS API Authentication failed. Check your API key.',
                    'phone' => $phone
                ];
            } else if ($httpCode === 400) {
                error_log("[SMS] ❌ FAILED - Bad Request");
                return [
                    'status' => 'error',
                    'message' => 'SMS API Bad Request. Check phone number format.',
                    'phone' => $phone,
                    'api_response' => $response
                ];
            } else {
                error_log("[SMS] ❌ FAILED - HTTP $httpCode");
                return [
                    'status' => 'error',
                    'message' => 'SMS API HTTP Error ' . $httpCode,
                    'phone' => $phone,
                    'api_response' => $response
                ];
            }
        } catch (Exception $e) {
            error_log("[SMS] Exception: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'SMS Error: ' . $e->getMessage(),
                'phone' => $phone
            ];
        }
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
