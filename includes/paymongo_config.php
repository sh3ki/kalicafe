<?php
/**
 * PayMongo API Configuration
 * Test API Keys
 */

define('PAYMONGO_SECRET_KEY', 'sk_test_9KAKVenEGWgeAUwdrSSRPwyX');
define('PAYMONGO_PUBLIC_KEY', 'pk_test_ErAW5ewZ6ynSjc2wweutsvZ1');
define('PAYMONGO_API_URL', 'https://api.paymongo.com/v1');

/**
 * Create a PayMongo payment intent
 * @param float $amount Amount in pesos (will be converted to centavos)
 * @param string $description Payment description
 * @return array Response from PayMongo API
 */
function createPaymentIntent($amount, $description = 'Order Payment') {
    $amountInCentavos = (int)($amount * 100);
    
    $data = [
        'data' => [
            'attributes' => [
                'amount' => $amountInCentavos,
                'payment_method_allowed' => ['card', 'gcash', 'paymaya'],
                'payment_method_options' => [
                    'card' => [
                        'request_three_d_secure' => 'any'
                    ]
                ],
                'currency' => 'PHP',
                'description' => $description,
                'statement_descriptor' => 'KUSSO Payment'
            ]
        ]
    ];
    
    $ch = curl_init(PAYMONGO_API_URL . '/payment_intents');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY . ':')
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'success' => $httpCode === 200,
        'response' => json_decode($response, true),
        'http_code' => $httpCode
    ];
}

/**
 * Create a PayMongo payment method
 * @param string $type Payment method type (card, gcash, paymaya, etc.)
 * @param array $details Payment details
 * @return array Response from PayMongo API
 */
function createPaymentMethod($type, $details = []) {
    $data = [
        'data' => [
            'attributes' => [
                'type' => $type,
                'details' => $details
            ]
        ]
    ];
    
    $ch = curl_init(PAYMONGO_API_URL . '/payment_methods');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode(PAYMONGO_PUBLIC_KEY . ':')
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'success' => $httpCode === 200,
        'response' => json_decode($response, true),
        'http_code' => $httpCode
    ];
}

/**
 * Attach payment method to payment intent
 * @param string $paymentIntentId Payment intent ID
 * @param string $paymentMethodId Payment method ID
 * @return array Response from PayMongo API
 */
function attachPaymentIntent($paymentIntentId, $paymentMethodId) {
    $data = [
        'data' => [
            'attributes' => [
                'payment_method' => $paymentMethodId
            ]
        ]
    ];
    
    $ch = curl_init(PAYMONGO_API_URL . '/payment_intents/' . $paymentIntentId . '/attach');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY . ':')
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'success' => $httpCode === 200,
        'response' => json_decode($response, true),
        'http_code' => $httpCode
    ];
}

/**
 * Retrieve payment intent status
 * @param string $paymentIntentId Payment intent ID
 * @return array Response from PayMongo API
 */
function getPaymentIntent($paymentIntentId) {
    $ch = curl_init(PAYMONGO_API_URL . '/payment_intents/' . $paymentIntentId);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY . ':')
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'success' => $httpCode === 200,
        'response' => json_decode($response, true),
        'http_code' => $httpCode
    ];
}

/**
 * Create a PayMongo source for e-wallet payments (GCash, PayMaya, GrabPay)
 * @param float $amount Amount in pesos (will be converted to centavos)
 * @param string $type Source type (gcash, grab_pay, paymaya)
 * @param string $redirectUrl URL to redirect after payment
 * @param array $billing Billing information
 * @return array Response from PayMongo API
 */
function createSource($amount, $type, $redirectUrl, $billing = []) {
    $amountInCentavos = (int)($amount * 100);
    
    $data = [
        'data' => [
            'attributes' => [
                'amount' => $amountInCentavos,
                'redirect' => [
                    'success' => $redirectUrl . '?status=success',
                    'failed' => $redirectUrl . '?status=failed'
                ],
                'type' => $type,
                'currency' => 'PHP'
            ]
        ]
    ];
    
    // Add billing information if provided
    if (!empty($billing)) {
        $data['data']['attributes']['billing'] = $billing;
    }
    
    $ch = curl_init(PAYMONGO_API_URL . '/sources');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY . ':')
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'success' => $httpCode === 200,
        'response' => json_decode($response, true),
        'http_code' => $httpCode
    ];
}

/**
 * Retrieve source status
 * @param string $sourceId Source ID
 * @return array Response from PayMongo API
 */
function getSource($sourceId) {
    $ch = curl_init(PAYMONGO_API_URL . '/sources/' . $sourceId);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY . ':')
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'success' => $httpCode === 200,
        'response' => json_decode($response, true),
        'http_code' => $httpCode
    ];
}
?>
