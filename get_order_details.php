<?php
session_start();
include('includes/config.php');
include('includes/auth.php');

// Allow only admin and cashier
checkAccess(['admin', 'cashier']);

header('Content-Type: application/json');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit();
}

$orderId = $_GET['id'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch order details
    $stmt = $pdo->prepare("
        SELECT 
            id,
            order_number,
            order_type,
            payment_type,
            total_amount,
            payment_status,
            created_at
        FROM orders 
        WHERE id = :id
    ");
    $stmt->execute(['id' => $orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit();
    }
    
    // Format the date
    $order['created_at'] = date('M j, Y g:i A', strtotime($order['created_at']));
    
    // Initialize empty items array (just show transaction summary without items)
    $order['items'] = [];
    
    echo json_encode([
        'success' => true,
        'order' => $order
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
