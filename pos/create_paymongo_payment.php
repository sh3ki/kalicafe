<?php
session_start();
include('../includes/config.php');
include('../includes/paymongo_config.php');

header('Content-Type: application/json');

// Get the JSON data from the request
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['amount'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

try {
    $amount = floatval($data['amount']);
    $orderNumber = isset($data['order_number']) ? $data['order_number'] : 'ORD-' . uniqid();
    $paymentType = isset($data['payment_type']) ? $data['payment_type'] : 'card';
    $description = 'KUSSO Order #' . $orderNumber;
    
    // For e-wallets (gcash, grab_pay), create a source instead of payment intent
    if (in_array($paymentType, ['gcash', 'grab_pay'])) {
        // Get the current domain for redirect
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $redirectUrl = $protocol . '://' . $host . '/kusso/pos/paymongo_redirect.php';
        
        // Billing information (optional but recommended)
        $billing = [
            'name' => isset($data['customer_name']) ? $data['customer_name'] : 'Customer',
            'email' => isset($data['customer_email']) ? $data['customer_email'] : 'customer@kusso.com',
            'phone' => isset($data['customer_phone']) ? $data['customer_phone'] : '09123456789'
        ];
        
        $result = createSource($amount, $paymentType, $redirectUrl, $billing);
        
        if ($result['success'] && isset($result['response']['data'])) {
            $source = $result['response']['data'];
            
            echo json_encode([
                'success' => true,
                'source_id' => $source['id'],
                'checkout_url' => $source['attributes']['redirect']['checkout_url'],
                'payment_type' => $paymentType,
                'amount' => $amount,
                'order_number' => $orderNumber
            ]);
        } else {
            $errorMessage = 'Failed to create payment source';
            if (isset($result['response']['errors'])) {
                $errors = $result['response']['errors'];
                $errorMessage = is_array($errors) ? json_encode($errors) : $errors;
            }
            
            echo json_encode([
                'success' => false,
                'message' => $errorMessage,
                'debug' => $result
            ]);
        }
    } else {
        // For card payments, create payment intent
        $result = createPaymentIntent($amount, $description);
        
        if ($result['success'] && isset($result['response']['data'])) {
            $paymentIntent = $result['response']['data'];
            
            echo json_encode([
                'success' => true,
                'client_key' => $paymentIntent['attributes']['client_key'],
                'payment_intent_id' => $paymentIntent['id'],
                'amount' => $amount,
                'order_number' => $orderNumber
            ]);
        } else {
            $errorMessage = 'Failed to create payment intent';
            if (isset($result['response']['errors'])) {
                $errors = $result['response']['errors'];
                $errorMessage = is_array($errors) ? json_encode($errors) : $errors;
            }
            
            echo json_encode([
                'success' => false,
                'message' => $errorMessage,
                'debug' => $result
            ]);
        }
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
