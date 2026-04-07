<?php
session_start();
include('includes/config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $order_id = intval($_POST['order_id']);
        $stmt = $pdo->prepare("UPDATE orders SET order_status = 'completed', completed_at = NOW() WHERE id = ?");
        $stmt->execute([$order_id]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
echo json_encode(['success' => false, 'error' => 'Invalid request']);
exit;
?>
