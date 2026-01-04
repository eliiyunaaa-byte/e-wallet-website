<?php
// Quick API Key Test
require_once __DIR__ . '/PayMongoConfig.php';

echo "Testing PayMongo API Keys...\n\n";

echo "Public Key: " . PayMongoConfig::PUBLIC_KEY . "\n";
echo "Secret Key: " . substr(PayMongoConfig::SECRET_KEY, 0, 12) . "***************\n\n";

// Test API connection with a simple payment intent
$curl = curl_init();

$testData = [
    'data' => [
        'attributes' => [
            'amount' => 10000, // ₱100 in centavos
            'currency' => 'PHP',
            'description' => 'API Test'
        ]
    ]
];

curl_setopt_array($curl, array(
    CURLOPT_URL => PayMongoConfig::API_URL . '/payment_intents',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($testData),
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        PayMongoConfig::getAuthHeader()
    ),
));

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$curlError = curl_error($curl);
curl_close($curl);

echo "HTTP Response Code: $httpCode\n";

if ($curlError) {
    echo "cURL Error: $curlError\n";
}

echo "Response: " . substr($response, 0, 200) . "...\n\n";

if ($httpCode === 200 || $httpCode === 201) {
    echo "✅ SUCCESS! API keys are VALID and working!\n";
    echo "You can now use PayMongo payment integration.\n";
} elseif ($httpCode === 401) {
    echo "❌ ERROR! Invalid API keys\n";
    echo "Please check your keys at https://dashboard.paymongo.com/developers/api_keys\n";
} else {
    echo "⚠️  Unexpected response (code: $httpCode)\n";
    $decoded = json_decode($response, true);
    if (isset($decoded['errors'])) {
        echo "Error: " . $decoded['errors'][0]['detail'] . "\n";
    }
}
?>
