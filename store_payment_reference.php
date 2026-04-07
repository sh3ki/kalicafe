<?php
session_start();
include('includes/config.php');

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['order_id']) || !isset($data['paymongo_reference'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $orderId = intval($data['order_id']);
    $paymongoReference = trim($data['paymongo_reference']);
    $paymentType = isset($data['payment_type']) ? trim($data['payment_type']) : 'gcash';

    // Store the source_id in paymongo_reference for webhook matching
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET paymongo_reference = :paymongo_reference,
            payment_type = :payment_type
        WHERE id = :order_id
    ");

    $stmt->execute([
        ':order_id' => $orderId,
        ':paymongo_reference' => $paymongoReference,
        ':payment_type' => $paymentType
    ]);

    echo json_encode(['success' => true, 'message' => 'Payment reference stored']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>

