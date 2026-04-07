<?php
session_start();
include('includes/config.php');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch orders completed today
    $stmt = $pdo->prepare("
        SELECT * FROM orders 
        WHERE order_status = 'completed' 
        AND DATE(completed_at) = CURDATE()
        ORDER BY completed_at DESC
    ");
    $stmt->execute();
    $completedOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($completedOrders)) {
        echo '<div class="alert alert-info text-center">No orders completed today yet.</div>';
    } else {
        echo '<div class="row">';
        foreach ($completedOrders as $order) {
            // Fetch the items for this order
            $stmt = $pdo->prepare("
                SELECT oi.qty, p.product_name, oi.options, oi.note 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$order['id']]);
            $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $item_count = array_sum(array_column($orderItems, 'qty'));

            ?>
            <div class="col-md-6 mb-3">
                <div class="card border-success">
                    <div class="card-header" style="background-color: #28a745; color: #ffffff;">
                        <strong>Order #<?php echo htmlspecialchars($order['order_number']); ?></strong>
                        <span class="float-end">
                            <i class="fas fa-check-circle"></i> Completed at <?php echo date("h:i A", strtotime($order['completed_at'])); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Date:</strong> <?php echo date("M d, Y", strtotime($order['created_at'])); ?></p>
                                <p class="mb-1"><strong>Amount:</strong> PHP <?php echo number_format($order['total_amount'], 2); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Order Type:</strong> <?php echo htmlspecialchars($order['order_type']); ?></p>
                                <p class="mb-1"><strong>Items:</strong> <span class="badge bg-success"><?php echo $item_count; ?></span></p>
                            </div>
                        </div>

                        <?php if (!empty($order['note'])): ?>
                            <div class="alert alert-warning p-2 mt-2 mb-2">
                                <strong><i class="fas fa-sticky-note me-1"></i> Order Notes:</strong><br>
                                <?php echo htmlspecialchars($order['note']); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($orderItems)): ?>
                            <div class="mt-2">
                                <strong>Order Details:</strong>
                                <ul class="list-group list-group-flush mt-2">
                                    <?php foreach ($orderItems as $item): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center p-2">
                                            <div>
                                                <?php echo htmlspecialchars($item['product_name']); ?>
                                                <?php if (!empty($item['options'])): ?>
                                                    <span class="badge bg-info text-dark ms-2"><?php echo htmlspecialchars($item['options']); ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($item['note'])): ?>
                                                    <div style="font-size:0.9em;color:#666;">Note: <?php echo htmlspecialchars($item['note']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <span class="badge bg-success rounded-pill"><?php echo htmlspecialchars($item['qty']); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php
        }
        echo '</div>';
    }
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>

