<?php 
    session_start();
    include ('includes/header.php');
    include('includes/navbar.php');
    include('includes/config.php');
    include('includes/auth.php');
    include('includes/invoice_helper.php');

    // Allow only admin and cashier
    checkAccess(['admin', 'cashier']);

    // Fetch orders from the database (today's orders only)
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Show only today's orders - resets daily but data remains in database
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE DATE(created_at) = CURDATE() ORDER BY created_at DESC");
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>KUSSO - Orders</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>   
<div id="layoutSidenav_content">
    <main>
        <div class="container-fluid px-4">
            <h1 class="mt-4">Orders</h1>  

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php 
                        echo $_SESSION['success_message'];
                        unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <?php 
                        echo $_SESSION['error_message'];
                        unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>

            <!-- Orders Table -->
            <div class="card shadow mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-table me-1"></i> List of Orders</span>
                    <a href="pos/index.php" class="btn btn-secondary" title="Add a new order" 
                       style="color: #ffffff; background-color:#c67c4e; border:none;">
                        <i class="fa-solid fa-plus"></i> Add Order
                    </a>
                </div>

                <div class="card-body">
                    <table id="datatablesSimple" class="table table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Invoice</th>
                                <th>Amount</th>
                                <th>Order Type</th>
                                <th>Payment Status</th>
                                <th>Order Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($orders)): ?>
                                <?php $total_orders = count($orders); ?>
                                <?php foreach ($orders as $index => $order): ?>
                                    <tr>
                                        <td><?php echo $total_orders - $index; ?></td>
                                        <td><?php echo date("M d, Y", strtotime($order['created_at'])); ?></td>
                                        <td><?php 
                                            $orderNum = htmlspecialchars($order['order_number']);
                                            echo 'INV-' . $orderNum;
                                        ?></td>
                                        <td><?php echo htmlspecialchars($order['total_amount']); ?></td>
                                        <td><?php echo htmlspecialchars($order['order_type']); ?></td>
                                        <td class="text-center">
                                            <?php if ($order['payment_status'] === 'paid'): ?>
                                                <span class="badge bg-success">Paid</span>
                                            <?php elseif ($order['payment_status'] === 'unpaid'): ?>
                                                <span class="badge bg-primary">Unpaid</span>
                                            <?php elseif ($order['payment_status'] === 'completed'): ?>
                                                <span class="badge bg-success">Completed</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars(ucfirst($order['payment_status'])); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($order['order_status'] === 'pending'): ?>
                                                <span class="badge bg-warning">Pending</span>
                                            <?php elseif ($order['order_status'] === 'preparing'): ?>
                                                <span class="badge bg-info">Preparing</span>
                                            <?php elseif ($order['order_status'] === 'ready'): ?>
                                                <span class="badge bg-primary">Ready</span>
                                            <?php elseif ($order['order_status'] === 'completed'): ?>
                                                <span class="badge bg-success">Done (Kitchen)</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars(ucfirst($order['order_status'])); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="#" class="btn btn-info btn-sm view-order" data-id="<?php echo $order['id']; ?>" title="View Order">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php // Show accept payment only if order is done (completed) in kitchen and unpaid ?>
                                            <?php if ($order['order_status'] === 'completed' && $order['payment_status'] === 'unpaid'): ?>
                                                <button class="btn btn-success btn-sm accept-payment" data-id="<?php echo $order['id']; ?>" data-amount="<?php echo $order['total_amount']; ?>" title="Accept Payment">
                                                    <i class="fas fa-credit-card"></i>
                                                </button>
                                            <?php elseif ($order['order_status'] !== 'completed' && $order['payment_status'] === 'unpaid'): ?>
                                                <a href="pos/index.php?id=<?php echo $order['id']; ?>" class="btn btn-warning btn-sm" title="Edit Order">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="delete_order.php?id=<?php echo $order['id']; ?>" class="btn btn-danger btn-sm" title="Delete Order" onclick="return confirm('Are you sure?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="btn btn-warning btn-sm disabled" style="visibility: hidden;">
                                                    <i class="fas fa-edit"></i>
                                                </span>
                                                <span class="btn btn-danger btn-sm disabled" style="visibility: hidden;">
                                                    <i class="fas fa-trash"></i>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">No orders found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div> 
        </div>
    </main>

<!-- Order Receipt Modal (Initially Empty) -->
<div class="modal fade" id="orderModal" tabindex="-1" aria-labelledby="orderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" id="orderModalContent">
            <!-- Order details will be loaded here -->
        </div>
    </div>
</div>

<!-- Accept Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #c67c4e; color: white;">
                <h5 class="modal-title" id="paymentModalLabel">Accept Payment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Order Amount:</strong> <span id="paymentAmount">PHP 0.00</span></p>
                <div class="payment-options mt-4">
                    <div class="row">
                        <div class="col-md-6">
                            <button type="button" class="btn btn-primary btn-lg w-100 payment-option-btn" data-payment-type="cash">
                                <i class="fas fa-money-bill"></i><br>Cash
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button type="button" class="btn btn-info btn-lg w-100 payment-option-btn" data-payment-type="other">
                                <i class="fas fa-credit-card"></i><br>Other Payment
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cash Payment Modal -->
<div class="modal fade" id="cashPaymentModal" tabindex="-1" role="dialog" aria-labelledby="cashPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #c67c4e; color: white;">
                <h5 class="modal-title" id="cashPaymentModalLabel"><i class="fas fa-money-bill me-2"></i>Cash Payment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>                       
            </div>
            <div class="modal-body">
                <form id="cashPaymentForm">
                    <div class="form-group mb-3">
                        <label for="totalAmount" class="form-label"><b>Total Amount</b></label>
                        <input type="text" class="form-control" id="totalAmount" readonly style="background-color: #f8f9fa;">
                    </div>
                    <div class="form-group mb-3">
                        <label for="cashReceived" class="form-label"><b>Cash Received</b></label>
                        <input type="number" class="form-control" id="cashReceived" required placeholder="Enter amount received">
                    </div>
                    <div class="form-group mb-3">
                        <label for="changeDue" class="form-label"><b>Change Due</b></label>
                        <input type="text" class="form-control" id="changeDue" readonly style="background-color: #f8f9fa;">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><b>Quick Amount</b></label>
                        <div class="d-flex flex-wrap justify-content-between">
                            <button type="button" class="btn btn-outline-primary quick-cash m-1" data-amount="100">PHP 100</button>
                            <button type="button" class="btn btn-outline-primary quick-cash m-1" data-amount="150">PHP 150</button>
                            <button type="button" class="btn btn-outline-primary quick-cash m-1" data-amount="200">PHP 200</button>
                            <button type="button" class="btn btn-outline-primary quick-cash m-1" data-amount="250">PHP 250</button>
                            <button type="button" class="btn btn-outline-primary quick-cash m-1" data-amount="500">PHP 500</button>
                            <button type="button" class="btn btn-outline-primary quick-cash m-1" data-amount="1000">PHP 1000</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" style="color: #ffffff; background-color:#3f2305; border:none;" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" style="color: #ffffff; background-color:#c67c4e; border:none;" id="processPaymentCash">Process Payment</button>
            </div>
        </div>
    </div>
</div>

<!-- PayMongo Payment Modal -->
<div class="modal fade" id="paymongoPaymentModal" tabindex="-1" role="dialog" aria-labelledby="paymongoPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content" style="border: none; border-radius: 20px; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; border: none; padding: 25px 30px;">
                <div>
                    <h5 class="modal-title mb-1" id="paymongoPaymentModalLabel" style="font-weight: 600; font-size: 1.4rem;">
                        <i class="fas fa-shield-alt me-2"></i>Secure Payment
                    </h5>
                    <p class="mb-0" style="font-size: 0.85rem; opacity: 0.9;">Powered by PayMongo</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 30px;">
                <!-- Loading State -->
                <div id="paymongo-loading" style="display: none; text-align: center; padding: 40px 20px;">
                    <div class="spinner-border" style="width: 3rem; height: 3rem; color: #667eea;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3" style="color: #6c757d; font-size: 1.1rem;">Processing your payment...</p>
                    <p class="text-muted small">Please wait, do not close this window</p>
                </div>
                
                <!-- Payment Content -->
                <div id="paymongo-content">
                    <!-- Amount Display -->
                    <div class="mb-4 p-4" style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); border-radius: 15px; text-align: center;">
                        <p class="mb-2 text-muted" style="font-size: 0.9rem; font-weight: 500;">AMOUNT TO PAY</p>
                        <h2 class="mb-0" style="font-weight: 700; color: #2d3748;">PHP <span id="paymongoTotalAmount">0.00</span></h2>
                    </div>
                    
                    <!-- Payment Method Selection -->
                    <div class="mb-4">
                        <label class="form-label" style="font-weight: 600; color: #2d3748; margin-bottom: 15px;">
                            <i class="fas fa-credit-card me-2"></i>Choose Payment Method
                        </label>
                        <div class="row g-3">
                            <!-- Card Option -->
                            <div class="col-12">
                                <input type="radio" class="btn-check" name="paymentMethod" id="paymentCard" value="card" autocomplete="off" checked>
                                <label class="btn w-100 text-start" for="paymentCard" style="border: 2px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; transition: all 0.3s; background: white;">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3" style="width: 50px; height: 50px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-credit-card" style="color: white; font-size: 1.3rem;"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div style="font-weight: 600; color: #2d3748; font-size: 1.05rem;">Credit / Debit Card</div>
                                            <div class="text-muted" style="font-size: 0.85rem;">Visa, Mastercard, JCB</div>
                                        </div>
                                        <i class="fas fa-chevron-right text-muted"></i>
                                    </div>
                                </label>
                            </div>
                            
                            <!-- GCash Option -->
                            <div class="col-12">
                                <input type="radio" class="btn-check" name="paymentMethod" id="paymentGcash" value="gcash" autocomplete="off">
                                <label class="btn w-100 text-start" for="paymentGcash" style="border: 2px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; transition: all 0.3s; background: white;">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3" style="width: 50px; height: 50px; background: #007DFF; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-mobile-alt" style="color: white; font-size: 1.3rem;"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div style="font-weight: 600; color: #2d3748; font-size: 1.05rem;">GCash</div>
                                            <div class="text-muted" style="font-size: 0.85rem;">Pay via GCash wallet</div>
                                        </div>
                                        <i class="fas fa-chevron-right text-muted"></i>
                                    </div>
                                </label>
                            </div>
                            
                            <!-- GrabPay Option -->
                            <div class="col-12">
                                <input type="radio" class="btn-check" name="paymentMethod" id="paymentGrabpay" value="grab_pay" autocomplete="off">
                                <label class="btn w-100 text-start" for="paymentGrabpay" style="border: 2px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; transition: all 0.3s; background: white;">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3" style="width: 50px; height: 50px; background: #00B14F; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-wallet" style="color: white; font-size: 1.3rem;"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div style="font-weight: 600; color: #2d3748; font-size: 1.05rem;">GrabPay</div>
                                            <div class="text-muted" style="font-size: 0.85rem;">Pay via GrabPay wallet</div>
                                        </div>
                                        <i class="fas fa-chevron-right text-muted"></i>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Card Payment Form -->
                    <div id="cardPaymentForm" class="payment-method-form">
                        <div class="card" style="border: 2px solid #e2e8f0; border-radius: 15px; padding: 25px; background: #f8fafc;">
                            <div class="mb-3">
                                <label for="cardNumber" class="form-label" style="font-weight: 600; color: #2d3748; font-size: 0.9rem;">
                                    <i class="fas fa-credit-card me-2"></i>CARD NUMBER
                                </label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="cardNumber" placeholder="1234 5678 9012 3456" maxlength="19" style="border: 1px solid #cbd5e0; border-radius: 8px; padding: 14px; font-size: 1.05rem; letter-spacing: 1px;">
                                </div>
                                <small class="text-muted mt-1 d-block">
                                    <i class="fas fa-info-circle me-1"></i>Test: 4123450131001381
                                </small>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="cardExpMonth" class="form-label" style="font-weight: 600; color: #2d3748; font-size: 0.9rem;">MONTH</label>
                                    <input type="text" class="form-control" id="cardExpMonth" placeholder="MM" maxlength="2" style="border: 1px solid #cbd5e0; border-radius: 8px; padding: 14px; font-size: 1.05rem;">
                                </div>
                                <div class="col-md-4">
                                    <label for="cardExpYear" class="form-label" style="font-weight: 600; color: #2d3748; font-size: 0.9rem;">YEAR</label>
                                    <input type="text" class="form-control" id="cardExpYear" placeholder="YY" maxlength="2" style="border: 1px solid #cbd5e0; border-radius: 8px; padding: 14px; font-size: 1.05rem;">
                                </div>
                                <div class="col-md-4">
                                    <label for="cardCvc" class="form-label" style="font-weight: 600; color: #2d3748; font-size: 0.9rem;">
                                        CVC
                                        <i class="fas fa-question-circle ms-1" data-bs-toggle="tooltip" title="3 or 4 digits on the back of your card"></i>
                                    </label>
                                    <input type="text" class="form-control" id="cardCvc" placeholder="123" maxlength="4" style="border: 1px solid #cbd5e0; border-radius: 8px; padding: 14px; font-size: 1.05rem;">
                                </div>
                            </div>
                            <div class="mt-3 p-3" style="background: #edf2f7; border-radius: 8px; border-left: 4px solid #667eea;">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-lock me-2" style="color: #667eea;"></i>
                                    <small style="color: #4a5568;">Your payment information is encrypted and secure</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- E-Wallet Payment Info -->
                    <div id="ewalletPaymentForm" class="payment-method-form" style="display: none;">
                        <div class="card" style="border: 2px solid #e2e8f0; border-radius: 15px; padding: 25px; background: #f8fafc;">
                            <div class="text-center mb-3">
                                <div class="mb-3">
                                    <i class="fas fa-mobile-screen-button" style="font-size: 3rem; color: #667eea;"></i>
                                </div>
                                <h5 style="color: #2d3748; font-weight: 600;">E-Wallet Payment</h5>
                                <p class="text-muted mb-0">You'll be redirected to complete your payment</p>
                            </div>
                            <div class="alert" style="background: #ebf8ff; border: 1px solid #bee3f8; border-radius: 10px; color: #2c5282;">
                                <div class="d-flex">
                                    <i class="fas fa-info-circle me-3 mt-1" style="font-size: 1.2rem;"></i>
                                    <div>
                                        <strong>What happens next:</strong>
                                        <ol class="mb-0 mt-2 ps-3" style="font-size: 0.9rem;">
                                            <li>A secure payment window will open</li>
                                            <li>Log in to your GCash or GrabPay account</li>
                                            <li>Confirm the payment amount</li>
                                            <li>You'll be redirected back automatically</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                            <div class="p-3" style="background: #f0fdf4; border-radius: 8px; border-left: 4px solid #10b981;">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-shield-check me-2" style="color: #10b981; font-size: 1.2rem;"></i>
                                    <small style="color: #065f46;">Secure transaction powered by PayMongo</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Error Alert -->
                <div id="paymongo-error" class="alert alert-danger mt-3" style="display: none; border-radius: 12px; border: none; background: #fee; color: #c53030;">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <span id="paymongo-error-message"></span>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #e2e8f0; padding: 20px 30px; background: #fafafa;">
                <button type="button" class="btn" style="color: #4a5568; background-color: #e2e8f0; border: none; padding: 12px 30px; border-radius: 10px; font-weight: 600;" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
                <button type="button" class="btn" style="color: #ffffff; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 12px 35px; border-radius: 10px; font-weight: 600; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);" id="processPaymongoPayment">
                    <i class="fas fa-lock me-2"></i>Pay Securely
                </button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript to Load Order Details in Modal -->
<script>
    $(document).ready(function () {
        // Listen for payment result from PayMongo redirect
        window.addEventListener('message', function(event) {
            if (event.data && event.data.type === 'paymongo_payment_result') {
                console.log('Payment result received:', event.data);
                
                if (event.data.status === 'success') {
                    // Auto-refresh payment status after successful payment
                    console.log('Refreshing payment status...');
                    
                    // Get order ID from sessionStorage (set when opening PayMongo window)
                    var orderId = sessionStorage.getItem('current_order_id');
                    
                    if (orderId) {
                        $.ajax({
                            url: 'check_payment_status.php',
                            type: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify({ order_id: orderId }),
                            dataType: 'json',
                            success: function(data) {
                                console.log('Payment status auto-refreshed:', data);
                                // Update the order row without reloading
                                updateOrderStatusInTable(orderId, data.payment_status, data.order_status);
                                showNotification('Payment received! Status updated to PAID.');
                            },
                            error: function(error) {
                                console.log('Auto-refresh error, attempting reload as fallback');
                                location.reload();
                            }
                        });
                    } else {
                        console.log('Order ID not found in sessionStorage, reloading page');
                        location.reload();
                    }
                }
            }
        });

        $('.view-order').click(function (e) {
            e.preventDefault();
            var orderId = $(this).data('id');

            $.ajax({
                url: 'view_order.php?id=' + orderId,
                type: 'GET',
                success: function (response) {
                    $('#orderModalContent').html(response);
                    $('#orderModal').modal('show');
                },
                error: function () {
                    alert('Failed to fetch order details.');
                }
            });
        });

        // Handle Accept Payment button
        $('.accept-payment').click(function (e) {
            e.preventDefault();
            var orderId = $(this).data('id');
            var amount = $(this).data('amount');
            
            $('#paymentAmount').text('PHP ' + parseFloat(amount).toFixed(2));
            $('#paymentModal').modal('show');
            $('#paymentModal').data('orderId', orderId);
            $('#paymentModal').data('amount', amount);
        });

        // Handle payment option selection
        $('.payment-option-btn').click(function () {
            var paymentType = $(this).data('payment-type');
            var orderId = $('#paymentModal').data('orderId');
            var amount = $('#paymentModal').data('amount');
            
            $('#paymentModal').modal('hide');
            
            if (paymentType === 'cash') {
                // Show cash payment modal
                $('#totalAmount').val(parseFloat(amount).toFixed(2));
                $('#cashReceived').val('');
                $('#changeDue').val('');
                $('#cashPaymentModal').modal('show');
                $('#cashPaymentModal').data('orderId', orderId);
                $('#cashPaymentModal').data('paymentType', 'cash');
            } else if (paymentType === 'other') {
                // Show PayMongo modal
                $('#paymongoTotalAmount').text(parseFloat(amount).toFixed(2));
                $('#paymongoPaymentModal').modal('show');
                $('#paymongoPaymentModal').data('orderId', orderId);
                $('#paymongoPaymentModal').data('amount', amount);
            }
        });

        // Quick cash buttons
        $('.quick-cash').click(function (e) {
            e.preventDefault();
            var amount = parseFloat($(this).data('amount'));
            $('#cashReceived').val(amount);
            calculateChangeDue();
        });

        // Cash payment modal
        $('#cashReceived').on('input', calculateChangeDue);

        function calculateChangeDue() {
            var totalAmount = parseFloat($('#totalAmount').val()) || 0;
            var cashReceived = parseFloat($('#cashReceived').val()) || 0;
            var changeDue = cashReceived - totalAmount;
            $('#changeDue').val(changeDue >= 0 ? changeDue.toFixed(2) : '0.00');
        }

        // Process Cash Payment
        $('#processPaymentCash').click(function () {
            console.log('Cash payment button clicked');
            var orderId = $('#cashPaymentModal').data('orderId');
            var totalAmount = parseFloat($('#totalAmount').val());
            var cashReceived = parseFloat($('#cashReceived').val());
            
            console.log('Cash payment data:', { orderId, totalAmount, cashReceived });
            
            if (isNaN(cashReceived) || cashReceived < totalAmount) {
                alert('Insufficient cash received.');
                return;
            }
            
            processPayment(orderId, 'cash', 'paid');
        });

        // PayMongo payment method switcher
        $('input[name="paymentMethod"]').on('change', function () {
            if (this.value === 'card') {
                $('#cardPaymentForm').show();
                $('#ewalletPaymentForm').hide();
            } else {
                $('#cardPaymentForm').hide();
                $('#ewalletPaymentForm').show();
            }
        });

        // Format card number
        $('#cardNumber').on('input', function (e) {
            var value = e.target.value.replace(/\s/g, '');
            var formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formattedValue;
        });

        // Process PayMongo Payment
        $('#processPaymongoPayment').click(function () {
            console.log('PayMongo payment button clicked');
            var orderId = $('#paymongoPaymentModal').data('orderId');
            var paymentMethod = $('input[name="paymentMethod"]:checked').val();
            var totalAmount = $('#paymongoPaymentModal').data('amount');
            
            console.log('PayMongo data:', { orderId, paymentMethod, totalAmount });
            console.log('All modal data:', $('#paymongoPaymentModal').data());
            
            // Validate orderId exists
            if (!orderId) {
                alert('Error: Order ID not found. Please try again.');
                console.error('Order ID is missing:', orderId);
                return;
            }
            
            // Validate payment method
            if (!paymentMethod) {
                $('#paymongo-error-message').text('Please select a payment method.');
                $('#paymongo-error').show();
                return;
            }
            
            // Validate card payment details
            if (paymentMethod === 'card') {
                var cardNumber = $('#cardNumber').val().replace(/\s/g, '');
                var expMonth = $('#cardExpMonth').val();
                var expYear = $('#cardExpYear').val();
                var cvc = $('#cardCvc').val();
                
                if (!cardNumber || !expMonth || !expYear || !cvc || cardNumber.length < 13) {
                    $('#paymongo-error-message').text('Please fill in all card details correctly.');
                    $('#paymongo-error').show();
                    return;
                }
            }
            
            // Show loading state
            $('#paymongo-loading').show();
            $('#paymongo-content').hide();
            $('#paymongo-error').hide();
            $(this).prop('disabled', true);
            
            // For e-wallets (GCash, GrabPay), create a source and redirect
            if (paymentMethod === 'gcash' || paymentMethod === 'grab_pay') {
                console.log('Processing e-wallet payment:', paymentMethod);
                
                // Create payment source
                fetch('pos/create_paymongo_payment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        amount: totalAmount,
                        order_id: orderId,
                        payment_type: paymentMethod,
                        order_number: 'ORD-' + Date.now()
                    })
                })
                .then(r => r.json())
                .then(data => {
                    console.log('Source creation response:', data);
                    if (data.success && data.checkout_url) {
                        console.log('E-wallet source created:', data.source_id);
                        
                        // Store the source_id in paymongo_reference for webhook matching
                        return fetch('store_payment_reference.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                order_id: orderId,
                                paymongo_reference: data.source_id,
                                payment_type: paymentMethod
                            })
                        })
                        .then(r => r.json())
                        .then(result => {
                            console.log('Reference stored:', result);
                            // Store order ID in sessionStorage for the redirect page
                            sessionStorage.setItem('current_order_id', orderId);
                            
                            // Open PayMongo authentication page in new window (non-blocking)
                            var paymentWindow = window.open(data.checkout_url, 'PayMongoAuth', 'width=800,height=600,resizable=yes,scrollbars=yes');
                            
                            // Hide loading state and close modal
                            $('#paymongo-loading').hide();
                            $('#paymongoPaymentModal').modal('hide');
                            
                            // Start polling for payment status updates every 3 seconds
                            var pollInterval = setInterval(function() {
                                console.log('Polling payment status for order:', orderId);
                                
                                fetch('check_payment_status.php?order_id=' + orderId)
                                    .then(r => r.json())
                                    .then(data => {
                                        console.log('Payment status check:', data);
                                        
                                        if (data.payment_status === 'paid') {
                                            console.log('Payment confirmed! Stopping poll and updating UI');
                                            clearInterval(pollInterval);
                                            
                                            // Close payment window if still open
                                            if (paymentWindow && !paymentWindow.closed) {
                                                paymentWindow.close();
                                            }
                                            
                                            // Update the order row in the table
                                            updateOrderRow(orderId, 'paid');
                                            
                                            // Show success message
                                            showSuccessNotification('Payment completed successfully! Order status updated to PAID.');
                                        }
                                    })
                                    .catch(err => console.error('Poll error:', err));
                            }, 3000); // Poll every 3 seconds
                            
                            // Stop polling after 10 minutes if payment not completed
                            setTimeout(function() {
                                clearInterval(pollInterval);
                                console.log('Polling timeout - stopping payment status checks');
                            }, 600000);
                        });
                    } else {
                        throw new Error(data.message || 'Failed to create payment source');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    $('#paymongo-loading').hide();
                    $('#paymongo-content').show();
                    $('#paymongo-error-message').text(error.message || 'Payment processing failed');
                    $('#paymongo-error').show();
                    $('#processPaymongoPayment').prop('disabled', false);
                });
                return;
            }
            
            // For card payment, create payment intent
            if (paymentMethod === 'card') {
                console.log('Processing card payment');
                
                fetch('pos/create_paymongo_payment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        amount: totalAmount,
                        order_id: orderId,
                        payment_type: 'card',
                        order_number: 'ORD-' + Date.now()
                    })
                })
                .then(r => r.json())
                .then(data => {
                    console.log('Payment intent response:', data);
                    if (!data.success) {
                        throw new Error(data.message || 'Failed to create payment');
                    }
                    
                    // Create card payment method
                    var cardNumber = $('#cardNumber').val().replace(/\s/g, '');
                    var expMonth = parseInt($('#cardExpMonth').val());
                    var expYear = parseInt($('#cardExpYear').val());
                    var cvc = $('#cardCvc').val();
                    
                    return createCardPaymentMethod(cardNumber, expMonth, expYear, cvc)
                        .then(pmResult => {
                            console.log('Payment method created:', pmResult);
                            if (!pmResult.success) {
                                throw new Error(pmResult.error || 'Failed to create payment method');
                            }
                            
                            // Attach payment method to payment intent
                            return attachPaymentMethod(data.payment_intent_id, pmResult.payment_method_id);
                        })
                        .then(() => {
                            console.log('Payment method attached');
                            
                            // Update payment status
                            return fetch('process_payment.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    order_id: orderId,
                                    payment_type: 'card',
                                    payment_status: 'paid'
                                })
                            });
                        })
                        .then(r => r.json())
                        .then(result => {
                            console.log('Payment completed successfully');
                            $('#paymongo-loading').hide();
                            $('#paymongoPaymentModal').modal('hide');
                            alert('Payment processed successfully! Order has been marked as PAID.');
                            setTimeout(() => location.reload(), 1500);
                        });
                })
                .catch(error => {
                    console.error('Card payment error:', error);
                    $('#paymongo-loading').hide();
                    $('#paymongo-content').show();
                    $('#paymongo-error-message').text(error.message || 'Card payment failed');
                    $('#paymongo-error').show();
                    $('#processPaymongoPayment').prop('disabled', false);
                });
            }
        });
        
        // Create card payment method
        function createCardPaymentMethod(cardNumber, expMonth, expYear, cvc) {
            return fetch('https://api.paymongo.com/v1/payment_methods', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Basic ' + btoa('pk_test_ErAW5ewZ6ynSjc2wweutsvZ1:')
                },
                body: JSON.stringify({
                    data: {
                        attributes: {
                            type: 'card',
                            details: {
                                card_number: cardNumber,
                                exp_month: expMonth,
                                exp_year: expYear,
                                cvc: cvc
                            }
                        }
                    }
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.data && data.data.id) {
                    return { success: true, payment_method_id: data.data.id };
                } else {
                    return { success: false, error: data.errors ? data.errors[0].detail : 'Failed to create payment method' };
                }
            });
        }
        
        // Attach payment method to payment intent
        function attachPaymentMethod(paymentIntentId, paymentMethodId) {
            return fetch('pos/attach_payment_method.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    payment_intent_id: paymentIntentId,
                    payment_method_id: paymentMethodId
                })
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Failed to attach payment method');
                }
                return data;
            });
        }
        
        // Update order row in table without page refresh
        window.updateOrderRow = function(orderId, newStatus) {
            // Use the new function which properly updates all cells and buttons
            updateOrderStatusInTable(orderId, newStatus, 'completed');
        };
        
        // Show success notification
        window.showNotification = function(message) {
            var notification = $('<div>')
                .addClass('alert alert-success alert-dismissible fade show')
                .attr('role', 'alert')
                .html(`
                    <strong>Success!</strong> ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `)
                .css({
                    'position': 'fixed',
                    'top': '20px',
                    'right': '20px',
                    'z-index': '9999',
                    'min-width': '400px'
                });
            
            $('body').append(notification);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                notification.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        };
        
        // Alias for backward compatibility
        window.showSuccessNotification = window.showNotification;
    });

    // Function to process payment
    function processPayment(orderId, paymentType, paymentStatus) {
        console.log('Processing payment:', { orderId, paymentType, paymentStatus });
        
        if (!orderId) {
            alert('Error: Order ID is missing. Cannot process payment.');
            console.error('Order ID is empty or undefined');
            return;
        }
        
        var requestUrl = 'process_payment.php';
        var requestData = JSON.stringify({
            order_id: orderId,
            payment_type: paymentType,
            payment_status: paymentStatus
        });
        
        console.log('Request URL:', requestUrl);
        console.log('Request Data:', requestData);
        
        $.ajax({
            url: requestUrl,
            type: 'POST',
            contentType: 'application/json',
            data: requestData,
            dataType: 'json',
            success: function (result) {
                console.log('Success response:', result);
                if (result.success) {
                    console.log('Payment processed successfully, closing modals');
                    $('#paymentModal').modal('hide');
                    $('#cashPaymentModal').modal('hide');
                    $('#paymongoPaymentModal').modal('hide');
                    
                    // Fetch updated payment status from database
                    $.ajax({
                        url: 'check_payment_status.php',
                        type: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({ order_id: orderId }),
                        dataType: 'json',
                        success: function(statusResult) {
                            console.log('Payment status refreshed from database:', statusResult);
                            
                            // Update the payment status badge in the table without reloading
                            updateOrderStatusInTable(orderId, statusResult.payment_status, statusResult.order_status);
                            
                            // Show success notification
                            showNotification('Payment accepted successfully! Payment status updated to PAID.');
                        },
                        error: function() {
                            console.log('Could not refresh status from database');
                            showNotification('Payment accepted successfully! Payment status updated to PAID.');
                        }
                    });
                } else {
                    console.error('Server returned success=false:', result.message);
                    alert('Error: ' + result.message);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', { status, error, xhr });
                console.error('Response Text:', xhr.responseText);
                console.error('XHR Status Code:', xhr.status);
                alert('Failed to process payment: ' + error + '\n' + xhr.responseText);
            }
        });
    }
    
    // Function to update order status in table without page reload
    function updateOrderStatusInTable(orderId, paymentStatus, orderStatus) {
        // Find the row with this order ID
        var $row = $('a.view-order[data-id="' + orderId + '"]').closest('tr');
        
        if ($row.length) {
            // Update payment status badge
            var paymentBadge = getPaymentStatusBadge(paymentStatus);
            var orderBadge = getOrderStatusBadge(orderStatus);
            
            // Update payment status column (5th column)
            $row.find('td').eq(5).html(paymentBadge);
            
            // Update order status column (6th column)
            $row.find('td').eq(6).html(orderBadge);
            
            // Update actions column - hide accept payment button and show appropriate actions
            updateActionButtons($row, orderId, orderStatus, paymentStatus);
            
            console.log('Order row updated successfully');
        }
    }
    
    // Helper function to get payment status badge HTML
    function getPaymentStatusBadge(status) {
        switch(status) {
            case 'paid':
                return '<span class="badge bg-success">Paid</span>';
            case 'unpaid':
                return '<span class="badge bg-primary">Unpaid</span>';
            case 'completed':
                return '<span class="badge bg-success">Completed</span>';
            default:
                return '<span class="badge bg-secondary">' + status.charAt(0).toUpperCase() + status.slice(1) + '</span>';
        }
    }
    
    // Helper function to get order status badge HTML
    function getOrderStatusBadge(status) {
        switch(status) {
            case 'pending':
                return '<span class="badge bg-warning">Pending</span>';
            case 'preparing':
                return '<span class="badge bg-info">Preparing</span>';
            case 'ready':
                return '<span class="badge bg-primary">Ready</span>';
            case 'completed':
                return '<span class="badge bg-success">Done (Kitchen)</span>';
            default:
                return '<span class="badge bg-secondary">' + status.charAt(0).toUpperCase() + status.slice(1) + '</span>';
        }
    }
    
    // Helper function to update action buttons
    function updateActionButtons($row, orderId, orderStatus, paymentStatus) {
        var $actionCell = $row.find('td').eq(7); // Actions column
        
        // Remove old buttons
        $actionCell.html('');
        
        // Add view order button
        var viewButton = '<a href="#" class="btn btn-info btn-sm view-order" data-id="' + orderId + '" title="View Order">' +
                        '<i class="fas fa-eye"></i></a>';
        $actionCell.append(viewButton);
        
        // Re-attach click handler for view-order
        $actionCell.find('.view-order').on('click', function (e) {
            e.preventDefault();
            var id = $(this).data('id');

            $.ajax({
                url: 'view_order.php?id=' + id,
                type: 'GET',
                success: function (response) {
                    $('#orderModalContent').html(response);
                    $('#orderModal').modal('show');
                },
                error: function () {
                    alert('Failed to fetch order details.');
                }
            });
        });
        
        // Show accept payment button only if order is completed and unpaid
        if (orderStatus === 'completed' && paymentStatus === 'unpaid') {
            var acceptButton = '<button class="btn btn-success btn-sm accept-payment" data-id="' + orderId + '" data-amount="0" title="Accept Payment">' +
                              '<i class="fas fa-credit-card"></i></button>';
            $actionCell.append(acceptButton);
            
            // Re-attach click handler for accept-payment
            $actionCell.find('.accept-payment').on('click', function (e) {
                e.preventDefault();
                var id = $(this).data('id');
                var amount = $(this).data('amount');
                
                $('#paymentAmount').text('PHP ' + parseFloat(amount).toFixed(2));
                $('#paymentModal').modal('show');
                $('#paymentModal').data('orderId', id);
                $('#paymentModal').data('amount', amount);
            });
        } else if (orderStatus !== 'completed' && paymentStatus === 'unpaid') {
            // Show edit and delete buttons
            var editButton = '<a href="pos/index.php?id=' + orderId + '" class="btn btn-warning btn-sm" title="Edit Order">' +
                            '<i class="fas fa-edit"></i></a>';
            var deleteButton = '<a href="delete_order.php?id=' + orderId + '" class="btn btn-danger btn-sm" title="Delete Order" onclick="return confirm(\'Are you sure?\');">' +
                              '<i class="fas fa-trash"></i></a>';
            $actionCell.append(editButton);
            $actionCell.append(deleteButton);
        }
    }
</script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Popper.js (required for Bootstrap dropdowns) -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>

    <?php include ('includes/footer.php'); ?>
    <?php include ('includes/scripts.php'); ?>
</body>
</html>

