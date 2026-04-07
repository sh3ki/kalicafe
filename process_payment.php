<?php
session_start();
include('includes/config.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['order_id']) || !isset($data['payment_type']) || !isset($data['payment_status'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $orderId = intval($data['order_id']);
        $paymentType = trim($data['payment_type']);
        $paymentStatus = trim($data['payment_status']);

        // Update the order with new payment information
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET payment_type = :payment_type, 
                payment_status = :payment_status 
            WHERE id = :order_id
        ");

        $stmt->execute([
            ':order_id' => $orderId,
            ':payment_type' => $paymentType,
            ':payment_status' => $paymentStatus
        ]);

        // Check if update was successful
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Payment processed successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No order found with that ID']);
        }

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>

