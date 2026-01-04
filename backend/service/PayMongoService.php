<?php
require_once __DIR__ . '/../dbConfiguration/PayMongoConfig.php';

class PayMongoService {
    
    /**
     * Create a payment source (QR Code)
     * @param float $amount Amount in PHP
     * @param string $student_id Student ID for reference
     * @return array Response with payment link and QR code details
     */
    public static function createPaymentLink($amount, $student_id) {
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
            CURLOPT_URL => PayMongoConfig::API_URL . '/checkout_sessions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                'data' => [
                    'attributes' => [
                        'line_items' => [
                            [
                                'currency' => 'PHP',
                                'amount' => intval($amount * 100), // Convert to centavos
                                'description' => 'E-Wallet Cash In',
                                'name' => 'Siena College E-Wallet',
                                'quantity' => 1
                            ]
                        ],
                        'payment_method_types' => ['gcash'],
                        'success_url' => 'http://localhost/e-wallet-website/e-wallet-website/frontend/components/dashboard.php?payment=success',
                        'cancel_url' => 'http://localhost/e-wallet-website/e-wallet-website/frontend/components/cashin.php?payment=cancelled',
                        'description' => 'Student ID: ' . $student_id . ' | Amount: ₱' . number_format($amount, 2),
                        'metadata' => [
                            'student_id' => $student_id,
                            'amount' => $amount
                        ]
                    ]
                ]
            ]),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                PayMongoConfig::getAuthHeader()
            ),
        ));
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        
        if ($err) {
            return [
                'status' => 'error',
                'message' => 'cURL Error: ' . $err
            ];
        }
        
        $decoded = json_decode($response, true);
        
        if (isset($decoded['data'])) {
            return [
                'status' => 'success',
                'checkout_url' => $decoded['data']['attributes']['checkout_url'],
                'session_id' => $decoded['data']['id'],
                'amount' => $amount
            ];
        }
        
        return [
            'status' => 'error',
            'message' => $decoded['errors'][0]['detail'] ?? 'Failed to create payment link'
        ];
    }
    
    /**
     * Verify payment status
     * @param string $payment_id Payment ID from PayMongo
     * @return array Payment status
     */
    public static function getPaymentStatus($payment_id) {
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
            CURLOPT_URL => PayMongoConfig::API_URL . '/payments/' . $payment_id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                PayMongoConfig::getAuthHeader()
            ),
        ));
        
        $response = curl_exec($curl);
        curl_close($curl);
        
        $decoded = json_decode($response, true);
        
        if (isset($decoded['data'])) {
            return [
                'status' => $decoded['data']['attributes']['status'],
                'amount' => $decoded['data']['attributes']['amount'] / 100, // Convert from centavos
                'paid' => ($decoded['data']['attributes']['status'] === 'paid')
            ];
        }
        
        return ['status' => 'error'];
    }
}
?>
