<?php
session_start();

date_default_timezone_set('Asia/Manila'); // Set timezone to Philippine Time
include('../includes/invoice_helper.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderData = json_decode(file_get_contents('php://input'), true);
    echo generateReceipt($orderData);
    exit;
}


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
            body {
                font-family: Arial, sans-serif;
                font-size: 14px;
                line-height: 1.6;
                margin: 0;
                padding: 0;
            }
            .container {
                width: 300px;
                margin: 0 auto;
                text-align: left;
            }
            .header, .footer {
                text-align: center;
                font-weight: bold;
            }
            .line {
                border-top: 1px dashed #000;
                margin: 10px 0;
            }
            .details, .items, .totals {
                margin-bottom: 10px;
            }
            .items .item {
                display: grid;
                grid-template-columns: 2fr 1fr 1fr 1fr;
                gap: 5px;
                margin-bottom: 5px;
            }
            .items .item span {
                word-wrap: break-word;
            }
            .items .item-header {
                font-weight: bold;
                border-bottom: 1px solid #000;
                padding-bottom: 5px;
            }
            .totals div {
                display: flex;
                justify-content: space-between;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
            <img src="/kusso/assets/img/kalicafe_logo.jpg" alt="Kali Cafe Logo" style="display: block; margin: 0 auto; max-width: 100px; height: auto;">
            <br>KALI CAFE<br>
                Lamot 2 4012 Calauan<br>
                Laguna Philippines
            </div>
            <div class="line"></div>
            <div class="details">
                <div>Employee: <b><?php echo htmlspecialchars($orderData['username'] ?? 'N/A'); ?></b></div>
            </div>
            <div class="line"></div>
            <div class="details">
                <div><?php echo $orderData['order_type'] === 'dine-in' ? 'Dine in' : 'Take out'; ?></div>
            </div>
            <div class="line"></div>
            <div class="items">
                <div class="item item-header">
                    <span>Item Name</span>
                    <span>Price</span>
                    <span>Qty</span>
                    <span>Total</span>
                </div>
                <?php foreach ($orderData['items'] as $item): ?>
                    <div class="item">
                        <span>
                            <?php 
                                echo htmlspecialchars($item['name']); 
                                if (!empty($item['options'])) {
                                    echo ' (' . htmlspecialchars($item['options']) . ')';
                                }
                            ?>
                        </span>
                        <span>₱<?php echo number_format($item['price'], 2); ?></span>
                        <span><?php echo htmlspecialchars($item['qty']); ?></span>
                        <span>₱<?php echo number_format($item['amount'], 2); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="line"></div>
            <div class="totals">
                <div><span>Total</span><span>₱<?php echo number_format($orderData['total_amount'], 2); ?></span></div>
                <div><span>Cash</span><span>₱<?php echo number_format($orderData['amount_tendered'], 2); ?></span></div>
                <div><span>Change</span><span>₱<?php echo number_format($orderData['change_due'], 2); ?></span></div>
            </div>
            <div class="line"></div>
            <div class="footer">
                Thank you<br>
                <div style="display: flex; justify-content: space-between; align-items: center; font-weight: normal; font-size: 12px; margin-top: 5px;">
                    <span><?php echo date('m/d/y h:i A'); ?></span>
                    <span style="text-align: right;">
                        <?php echo formatInvoiceDisplay($orderData['order_number']); ?>
                    </span>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
   
    return ob_get_clean();
}


?>