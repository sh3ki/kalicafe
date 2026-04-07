<?php
/**
 * PayMongo Webhook Handler
 * This script handles webhook events from PayMongo
 * Configure this URL in your PayMongo dashboard: https://yourdomain.com/paymongo_webhook.php
 */

include('includes/config.php');
include('includes/paymongo_config.php');

// Get the raw POST data
$payload = @file_get_contents('php://input');
$event = json_decode($payload, true);

// Log webhook event (for debugging)
$logFile = __DIR__ . '/paymongo_webhook_log.txt';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $payload . "\n", FILE_APPEND);

if (!$event || !isset($event['data'])) {
    http_response_code(400);
    exit('Invalid webhook data');
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $eventType = $event['data']['attributes']['type'];
    $eventData = $event['data']['attributes']['data'];
    
    // Handle payment.paid event (for card payments)
    if ($eventType === 'payment.paid') {
        $paymentIntentId = $eventData['attributes']['payment_intent_id'];
        $status = $eventData['attributes']['status'];
        $amount = $eventData['attributes']['amount'] / 100; // Convert centavos to pesos
        
        if ($status === 'paid') {
            // Find the order by payment_intent_id stored in paymongo_reference
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE paymongo_reference = :paymongo_reference");
            $stmt->execute([':paymongo_reference' => $paymentIntentId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($order) {
                // Update order status to paid
                $updateStmt = $pdo->prepare("UPDATE orders SET payment_status = 'paid', payment_type = 'paymongo' WHERE id = :order_id");
                $updateStmt->execute([':order_id' => $order['id']]);
                
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Order #{$order['order_number']} marked as paid\n", FILE_APPEND);
            } else {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Order not found for payment_intent_id: {$paymentIntentId}\n", FILE_APPEND);
            }
        }
    }
    
    // Handle payment.failed event (for card payments)
    if ($eventType === 'payment.failed') {
        $paymentIntentId = $eventData['attributes']['payment_intent_id'];
        
        // Find the order and mark as failed
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE paymongo_reference = :paymongo_reference");
        $stmt->execute([':paymongo_reference' => $paymentIntentId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            $updateStmt = $pdo->prepare("UPDATE orders SET payment_status = 'failed' WHERE id = :order_id");
            $updateStmt->execute([':order_id' => $order['id']]);
            
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Order #{$order['order_number']} payment failed\n", FILE_APPEND);
        }
    }
    
    // Handle source.chargeable event (for e-wallet payments - GCash, PayMaya)
    if ($eventType === 'source.chargeable') {
        $sourceId = $eventData['id'];
        $status = $eventData['attributes']['status'];
        $amount = $eventData['attributes']['amount'] / 100;
        
        if ($status === 'chargeable') {
            // Find the order by source_id stored in paymongo_reference
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE paymongo_reference = :paymongo_reference");
            $stmt->execute([':paymongo_reference' => $sourceId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($order) {
                // Update order status to paid
                $updateStmt = $pdo->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = :order_id");
                $updateStmt->execute([':order_id' => $order['id']]);
                
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Order #{$order['order_number']} e-wallet payment completed\n", FILE_APPEND);
            } else {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Order not found for source_id: {$sourceId}\n", FILE_APPEND);
            }
        }
    }
    
    http_response_code(200);
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Database error: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
