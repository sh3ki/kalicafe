<?php
session_start();
include('../includes/config.php');
include('../includes/paymongo_config.php');

header('Content-Type: application/json');

// Get the JSON data from the request
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['payment_intent_id']) || !isset($data['payment_method_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

try {
    $paymentIntentId = $data['payment_intent_id'];
    $paymentMethodId = $data['payment_method_id'];
    
    // Attach payment method to payment intent
    $result = attachPaymentIntent($paymentIntentId, $paymentMethodId);
    
    if ($result['success'] && isset($result['response']['data'])) {
        $paymentIntent = $result['response']['data'];
        $status = $paymentIntent['attributes']['status'];
        
        echo json_encode([
            'success' => true,
            'status' => $status,
            'payment_intent' => $paymentIntent
        ]);
    } else {
        $errorMessage = 'Failed to attach payment method';
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
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
