<?php
session_start();
include('includes/config.php');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid order ID.");
}

$orderId = $_GET['id'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch total number of orders
    $stmtTotalOrders = $pdo->prepare("SELECT COUNT(*) AS total_orders FROM orders");
    $stmtTotalOrders->execute();
    $totalOrders = $stmtTotalOrders->fetch(PDO::FETCH_ASSOC)['total_orders'];

    // Fetch order details and items together
    $stmt = $pdo->prepare("
        SELECT 
            o.order_number, o.created_at, o.total_amount, o.amount_tendered, o.order_type,
            p.product_name AS item_name, p.options, oi.qty, oi.price
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE o.id = ?
    ");
    $stmt->execute([$orderId]);
    $orderDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($orderDetails)) {
        die("Order not found.");
    }

    // Extract order information
    $order = $orderDetails[0];
    $orderData = [
        'invoice_number' => $order['order_number'], // Use the order_number from the database as the invoice number
        'order_id' => $totalOrders, // Use the total number of orders as the order number
        'date' => date('Y-m-d', strtotime($order['created_at'])),
        'order_type' => $order['order_type'],
        'items' => [],
        'total_amount' => $order['total_amount'],
        'amount_tendered' => $order['amount_tendered'],
        'change_due' => $order['amount_tendered'] - $order['total_amount'],
    ];

    // Extract items information
    foreach ($orderDetails as $item) {
        $orderData['items'][] = [
            'qty' => $item['qty'],
            'name' => $item['item_name'] . ' (' . $item['options'] . ')',
            'amount' => $item['price'] * $item['qty']
        ];
    }

} catch (PDOException $e) {
    die("Database error: " . htmlspecialchars($e->getMessage()));
}

// Function to generate receipt
function generateReceipt($orderData) {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Receipt</title>
        <style>
            .flex { display: inline-flex; width: 100%; }
            .w-50 { width: 50%; }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
            table.wborder { width: 100%; border-collapse: collapse; }
            table.wborder > tbody > tr, table.wborder > tbody > tr > td { border: 1px solid; }
            p { margin: unset; }
            .modal-footer { display: flex; justify-content: flex-end; }
        </style>
    </head>
    <body>
        <div class="container-fluid">
            <p class="text-center"><b>Receipt</b></p>
            <hr>
            <div class="flex">
                <div class="w-100">
                    <p>Invoice Number: <b id="receipt-invoice-number"><?php echo htmlspecialchars($orderData['invoice_number']); ?></b></p>
                    <p>Date: <b id="receipt-date"><?php echo htmlspecialchars($orderData['date']); ?></b></p>
                    <p>Order Type: <b id="receipt-order-type"><?php echo htmlspecialchars($orderData['order_type']); ?></b></p>
                </div>
            </div>
            <hr>
            <p><b>Order List</b></p>
            <table width="100%">
                <thead>
                    <tr>
                        <td><b>QTY</b></td>
                        <td><b>Order</b></td>
                        <td class="text-right"><b>Amount</b></td>
                    </tr>
                </thead>
                <tbody id="receipt-items">
                    <?php foreach ($orderData['items'] as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['qty']); ?></td>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td class="text-right"><?php echo number_format($item['amount'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <hr>
            <table width="100%">
                <tbody>
                    <tr>
                        <td><b>Total Amount</b></td>
                        <td class="text-right"><b id="receipt-total-amount"><?php echo number_format($orderData['total_amount'], 2); ?></b></td>
                    </tr>
                    <tr>
                        <td><b>Amount Tendered</b></td>
                        <td class="text-right"><b id="receipt-amount-tendered"><?php echo number_format($orderData['amount_tendered'], 2); ?></b></td>
                    </tr>
                    <tr>
                        <td><b>Change</b></td>
                        <td class="text-right"><b id="receipt-change-due"><?php echo number_format($orderData['change_due'], 2); ?></b></td>
                    </tr>
                </tbody>
            </table>
            <hr>
            <p class="text-center"><b>Order No.</b></p>
            <h4 class="text-center"><b id="receipt-order-number"><?php echo htmlspecialchars($orderData['order_id']); ?></b></h4>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" style="color: #ffffff; background-color:#c67c4e; border:none;" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

// Display the receipt
echo generateReceipt($orderData);
?>
