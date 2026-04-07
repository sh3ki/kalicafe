<?php
session_start();
include('includes/config.php');
include('includes/paymongo_config.php');

header('Content-Type: application/json');

// Accept both GET and POST requests
$orderId = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $orderId = isset($data['order_id']) ? intval($data['order_id']) : null;
} elseif (isset($_GET['order_id'])) {
    $orderId = intval($_GET['order_id']);
}

if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'Order ID not provided']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch the current payment status
    $stmt = $pdo->prepare("SELECT id, payment_status, order_status, paymongo_reference, payment_type FROM orders WHERE id = :order_id");
    $stmt->execute([':order_id' => $orderId]);
    
    if ($stmt->rowCount() > 0) {
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If not yet paid and has PayMongo reference, check PayMongo API
        if ($order['payment_status'] !== 'paid' && $order['paymongo_reference']) {
            $reference = $order['paymongo_reference'];
            
            // Check if it's a source (e-wallet) or payment intent (card)
            if (strpos($reference, 'src_') === 0) {
                // It's a source ID - check source status
                $url = "https://api.paymongo.com/v1/sources/{$reference}";
                $auth = base64_encode(PAYMONGO_SECRET_KEY . ':');
                
                $context = stream_context_create([
                    'http' => [
                        'header' => "Authorization: Basic {$auth}\r\n",
                        'method' => 'GET'
                    ]
                ]);
                
                $response = @file_get_contents($url, false, $context);
                if ($response) {
                    $sourceData = json_decode($response, true);
                    if (isset($sourceData['data']['attributes']['status']) && $sourceData['data']['attributes']['status'] === 'chargeable') {
                        // Source is ready - update order to paid
                        $updateStmt = $pdo->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = :order_id");
                        $updateStmt->execute([':order_id' => $orderId]);
                        
                        $order['payment_status'] = 'paid';
                    }
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'payment_status' => $order['payment_status'],
            'order_status' => $order['order_status'],
            'order_id' => $orderId
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>

