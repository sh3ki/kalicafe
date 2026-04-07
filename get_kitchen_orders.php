<?php
session_start();
include('includes/config.php');

header('Content-Type: application/json');

$orders_per_page = 3;
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $orders_per_page;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Count total orders
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE amount_tendered > 0 AND order_status != 'completed'");
    $count_stmt->execute();
    $total_orders = $count_stmt->fetchColumn();
    
    // Fetch orders for current page
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE amount_tendered > 0 AND order_status != 'completed' ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $orders_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $ordersHtml = '';
    
    if (!empty($orders)) {
        foreach ($orders as $order) {
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

            ob_start();
            ?>
            <div class="col-md-4" data-order-id="<?php echo $order['id']; ?>">
                <div class="card mb-4 shadow-sm">
                    <div class="card-header" style="background-color: #c67c4e; color: #ffffff;">
                        <strong>Order #<?php echo htmlspecialchars($order['order_number']); ?></strong>
                    </div>
                    <div class="card-body">
                        <p><strong>Date:</strong> <?php echo date("M d, Y", strtotime($order['created_at'])); ?></p>
                        <p><strong>Amount:</strong> <?php echo htmlspecialchars($order['total_amount']); ?></p>
                        <p><strong>Order Type:</strong> <?php echo htmlspecialchars($order['order_type']); ?></p>
                        <p><strong>Items:</strong> <span class="badge" style="background-color: #c67c4e; color: #ffffff;"><?php echo $item_count; ?></span></p>
                        
                        <?php if (!empty($order['note'])): ?>
                            <div class="alert alert-warning p-2 mb-2">
                                <strong><i class="fas fa-sticky-note me-1"></i> Order Notes:</strong><br>
                                <?php echo htmlspecialchars($order['note']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($orderItems)): ?>
                            <div class="mt-3">
                                <p><strong>Order Details:</strong></p>
                                <div class="card card-body p-2 border-light">
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($orderItems as $item): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center p-2">
                                                <div>
                                                    <?php echo htmlspecialchars($item['product_name']); ?>
                                                    <?php if (!empty($item['options'])): ?>
                                                        <span class="badge bg-info text-dark ms-2"><?php echo htmlspecialchars($item['options']); ?></span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($item['note'])): ?>
                                                        <div style="font-size:0.95em;color:#7a5c2e;">Note: <?php echo htmlspecialchars($item['note']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                                <span class="badge bg-secondary rounded-pill"><?php echo htmlspecialchars($item['qty']); ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <p class="mt-3"><strong>Status:</strong> 
                            <span class="badge <?php echo ($order['payment_status'] === 'paid' ? 'bg-success' : 'bg-warning'); ?>">
                                <?php echo htmlspecialchars(ucfirst($order['payment_status'])); ?>
                            </span>
                        </p>
                        
                        <div class="d-grid gap-2 mt-3">
                            <button class="btn mark-done-btn" style="background-color: #c67c4e; color: #ffffff;" data-order-id="<?php echo $order['id']; ?>">
                                <i class="fas fa-check me-1"></i> Mark as Done
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            $ordersHtml .= ob_get_clean();
        }
    }

    // Get list of order IDs
    $orderIds = array_column($orders, 'id');

    echo json_encode([
        'success' => true,
        'html' => $ordersHtml,
        'total_orders' => $total_orders,
        'total_pages' => ceil($total_orders / $orders_per_page),
        'current_page' => $current_page,
        'order_ids' => $orderIds,
        'has_orders' => !empty($orders)
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

