<?php
session_start();
include('includes/config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("SELECT * FROM orders WHERE amount_tendered > 0 AND order_status != 'completed' ORDER BY created_at DESC LIMIT 1 OFFSET :offset");
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            // Fetch order items
            $stmtItems = $pdo->prepare("
                SELECT oi.qty, p.product_name 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = ?
            ");
            $stmtItems->execute([$order['id']]);
            $orderItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
            $item_count = array_sum(array_column($orderItems, 'qty'));

            ob_start();
            ?>
            <div class="col-md-4">
                <div class="card mb-4 shadow-sm">
                    <div class="card-header" style="background-color: #c67c4e; color: #ffffff;">
                        <strong>Order #<?php echo htmlspecialchars($order['order_number']); ?></strong>
                    </div>
                    <div class="card-body">
                        <p><strong>Date:</strong> <?php echo date("M d, Y", strtotime($order['created_at'])); ?></p>
                        <p><strong>Amount:</strong> <?php echo htmlspecialchars($order['total_amount']); ?></p>
                        <p><strong>Order Type:</strong> <?php echo htmlspecialchars($order['order_type']); ?></p>
                        <p><strong>Items:</strong> <span class="badge" style="background-color: #c67c4e; color: #ffffff;"><?php echo $item_count; ?></span></p>
                        <?php if (!empty($orderItems)): ?>
                            <div class="mt-3">
                                <p><strong>Order Details:</strong></p>
                                <div class="card card-body p-2 border-light">
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($orderItems as $item): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center p-2">
                                                <?php echo htmlspecialchars($item['product_name']); ?>
                                                <span class="badge bg-secondary rounded-pill"><?php echo htmlspecialchars($item['qty']); ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endif; ?>
                        <p class="mt-3"><strong>Status:</strong> <span class="badge bg-success">Paid</span></p>
                        <div class="d-grid gap-2 mt-3">
                            <button class="btn mark-done-btn" style="background-color: #c67c4e; color: #ffffff;" data-order-id="<?php echo $order['id']; ?>">
                                <i class="fas fa-check me-1"></i> Mark as Done
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            $html = ob_get_clean();
            echo json_encode(['success' => true, 'html' => $html]);
        } else {
            echo json_encode(['success' => false]);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
echo json_encode(['success' => false, 'error' => 'Invalid request']);
exit;
?>
