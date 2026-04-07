<?php
// Set timezone to Asia/Manila (change if needed)
date_default_timezone_set('Asia/Manila');
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include('includes/header.php');
include('includes/navbar.php');
include('includes/auth.php');
checkAccess(['admin']);
include('includes/config.php');

// Always define variables to avoid warnings
$num_transactions = $total_profit = $total_orders = $num_items = $num_categories = $total_discounts = $total_refunds = 0;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Today's date
        $todayDate = date('Y-m-d');

        // No. of Transactions (paid or completed orders) for today
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE payment_status IN ('paid','completed') AND DATE(created_at) = :today");
        $stmt->execute(['today' => $todayDate]);
        $num_transactions = $stmt->fetchColumn();

        // Total Profit (assume profit = total_amount for now) for today
        $stmt = $pdo->prepare("SELECT SUM(total_amount) FROM orders WHERE payment_status IN ('paid','completed') AND DATE(created_at) = :today");
        $stmt->execute(['today' => $todayDate]);
        $total_profit = $stmt->fetchColumn();
        if ($total_profit === null) $total_profit = 0;

        // Total Orders for today
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = :today");
        $stmt->execute(['today' => $todayDate]);
        $total_orders = $stmt->fetchColumn();

    // No. of Items
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    $num_items = $stmt->fetchColumn();

    // No. of Categories
    $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
    $num_categories = $stmt->fetchColumn();

    // Total Discounts (if discount column exists)
    $discount_col = $pdo->query("SHOW COLUMNS FROM orders LIKE 'discount'")->fetch();
    if ($discount_col) {
        $stmt = $pdo->query("SELECT SUM(discount) FROM orders WHERE payment_status IN ('paid','completed')");
        $total_discounts = $stmt->fetchColumn();
        if ($total_discounts === null) $total_discounts = 0;
    }

    // Total Refunds (if refund column exists)
    $refund_col = $pdo->query("SHOW COLUMNS FROM orders LIKE 'refund'")->fetch();
    if ($refund_col) {
        $stmt = $pdo->query("SELECT SUM(refund) FROM orders WHERE payment_status IN ('paid','completed')");
        $total_refunds = $stmt->fetchColumn();
        if ($total_refunds === null) $total_refunds = 0;
    }
} catch (PDOException $e) {
    // Use default zero values
}

// Prepare daily and monthly sales data for charts
$daily_labels = [];
$daily_sales = [];
$monthly_labels = [];
$monthly_sales = [];

// Daily sales for the last 7 days
$stmt = $pdo->query("SELECT DATE(created_at) as day, SUM(total_amount) as sales FROM orders WHERE payment_status IN ('paid','completed') GROUP BY day ORDER BY day DESC LIMIT 7");
$daily = $stmt->fetchAll(PDO::FETCH_ASSOC);
$daily = array_reverse($daily); // oldest first
foreach ($daily as $row) {
    $daily_labels[] = date('M j', strtotime($row['day']));
    $daily_sales[] = (float)$row['sales'];
}
// Monthly sales for the last 6 months
$stmt = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total_amount) as sales FROM orders WHERE payment_status IN ('paid','completed') GROUP BY month ORDER BY month DESC LIMIT 6");
$monthly = $stmt->fetchAll(PDO::FETCH_ASSOC);
$monthly = array_reverse($monthly);
foreach ($monthly as $row) {
    $monthly_labels[] = date('M Y', strtotime($row['month'].'-01'));
    $monthly_sales[] = (float)$row['sales'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>KUSSO-Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>
<body>
<div id="layoutSidenav_content">
    <main>
        <div class="container-fluid px-4">
            <h1 class="mt-4">Dashboard</h1>
            <div class="alert alert-primary text-center mb-4" style="font-size:2rem; font-weight:600; border-radius:1rem; background:#f5f7fa; color:#c67c4e; border:none;">
                <?php
                    $adminName = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin';
                    $today = date('l, F j, Y');
                    echo "Welcome, <span style='color:#a95e2d;'>$adminName</span>! Today is <span style='color:#a95e2d;'>$today</span>.";
                ?>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-4 col-xl-3">
                    <div class="card dashboard-metric">
                        <div class="card-body text-center">
                            <div class="card-title">Transactions</div>
                            <div class="card-text"><?php echo $num_transactions; ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-3">
                    <div class="card dashboard-metric">
                        <div class="card-body text-center">
                            <div class="card-title">Profit</div>
                            <div class="card-text">PHP <?php echo number_format($total_profit,2); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-3">
                    <div class="card dashboard-metric">
                        <div class="card-body text-center">
                            <div class="card-title">Orders</div>
                            <div class="card-text"><?php echo $total_orders; ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-3">
                    <div class="card dashboard-metric">
                        <div class="card-body text-center">
                            <div class="card-title">Items</div>
                            <div class="card-text"><?php echo $num_items; ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-3">
                    <div class="card dashboard-metric">
                        <div class="card-body text-center">
                            <div class="card-title">Categories</div>
                            <div class="card-text"><?php echo $num_categories; ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-3">
                    <div class="card dashboard-metric">
                        <div class="card-body text-center">
                            <div class="card-title">Discounts</div>
                            <div class="card-text">PHP <?php echo number_format($total_discounts,2); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-3">
                    <div class="card dashboard-metric">
                        <div class="card-body text-center">
                            <div class="card-title">Refunds</div>
                            <div class="card-text">PHP <?php echo number_format($total_refunds,2); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-6">
                    <div class="card mb-4 h-100">
                        <div class="card-header">
                            <i class="fas fa-chart-area me-1"></i>
                            Daily Sales
                        </div>
                        <div class="card-body"><canvas id="myAreaChart" style="width:100%;height:260px;min-height:180px;"></canvas></div>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="card mb-4 h-100">
                        <div class="card-header">
                            <i class="fas fa-chart-bar me-1"></i>
                            Monthly Sales
                        </div>
                        <div class="card-body"><canvas id="myBarChart" style="width:100%;height:260px;min-height:180px;"></canvas></div>
                    </div>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-table me-1"></i>
                    Recent Transactions
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="datatablesSimple" class="table table-bordered table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Payment Type</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    // Fetch recent transactions (orders)
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
                                        ORDER BY created_at DESC 
                                        LIMIT 100
                                    ");
                                    $stmt->execute();
                                    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    if (count($transactions) > 0) {
                                        foreach ($transactions as $transaction) {
                                            $orderId = htmlspecialchars($transaction['id']);
                                            $orderNumber = htmlspecialchars($transaction['order_number']);
                                            $orderType = htmlspecialchars($transaction['order_type']);
                                            $paymentType = htmlspecialchars($transaction['payment_type']);
                                            $totalAmount = number_format($transaction['total_amount'], 2);
                                            $paymentStatus = htmlspecialchars($transaction['payment_status']);
                                            $createdAt = date('M j, Y g:i A', strtotime($transaction['created_at']));
                                            
                                            // Status badge color
                                            $statusBadge = '';
                                            switch ($paymentStatus) {
                                                case 'paid':
                                                case 'completed':
                                                    $statusBadge = '<span class="badge bg-success">Paid</span>';
                                                    break;
                                                case 'pending':
                                                    $statusBadge = '<span class="badge bg-warning text-dark">Pending</span>';
                                                    break;
                                                case 'failed':
                                                    $statusBadge = '<span class="badge bg-danger">Failed</span>';
                                                    break;
                                                default:
                                                    $statusBadge = '<span class="badge bg-secondary">' . ucfirst($paymentStatus) . '</span>';
                                            }
                                            
                                            echo "<tr>";
                                            echo "<td>{$orderNumber}</td>";
                                            echo "<td>{$createdAt}</td>";
                                            echo "<td>" . ucfirst($orderType) . "</td>";
                                            echo "<td>" . ucfirst($paymentType) . "</td>";
                                            echo "<td>PHP {$totalAmount}</td>";
                                            echo "<td>{$statusBadge}</td>";
                                            echo "<td><button class='btn btn-sm btn-primary view-order-btn' data-order-id='{$orderId}'><i class='fas fa-eye'></i> View</button></td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='7' class='text-center'>No transactions found</td></tr>";
                                    }
                                } catch (PDOException $e) {
                                    echo "<tr><td colspan='7' class='text-center text-danger'>Error loading transactions: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Details Modal -->
        <div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header" style="background-color: #c67c4e; color: white;">
                        <h5 class="modal-title" id="orderDetailsModalLabel">
                            <i class="fas fa-receipt me-2"></i>Order Details
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="orderDetailsContent">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading order details...</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php include ('includes/footer.php');
    include ('includes/scripts.php'); ?>
</div>
<style>
.dashboard-metric {
    background: linear-gradient(135deg, #f8fafc 60%, #e9ecef 100%);
    border-radius: 1rem;
    box-shadow: 0 2px 12px 0 rgba(44,62,80,0.08);
    border: none;
    transition: transform 0.15s;
}
.dashboard-metric:hover {
    transform: translateY(-4px) scale(1.03);
    box-shadow: 0 6px 24px 0 rgba(220,53,69,0.12);
}
.dashboard-metric .card-title {
    color: #b1001a;
    font-weight: 600;
    font-size: 1.1rem;
    margin-bottom: 0.5rem;
}
.dashboard-metric .card-text {
    color: #222;
    font-size: 2.1rem;
    font-weight: 700;
    letter-spacing: 1px;
}
@media (max-width: 991.98px) {
    .dashboard-metric .card-text { font-size: 1.1rem; }
    .dashboard-metric { margin-bottom: 1rem; }
}
@media (max-width: 767.98px) {
    .dashboard-metric { min-width: 100%; }
    #myAreaChart, #myBarChart {
        min-height: 120px !important;
        height: 120px !important;
        width: 100% !important;
    }
    .card.mb-4.h-100 { min-height: 180px; }
}
.table-responsive { overflow-x: auto; }
</style>
<script>
window.addEventListener('resize', function() {
    var area = document.getElementById('myAreaChart');
    var bar = document.getElementById('myBarChart');
    if (window.innerWidth < 768) {
        if (area) { area.style.height = '120px'; area.style.minHeight = '120px'; }
        if (bar) { bar.style.height = '120px'; bar.style.minHeight = '120px'; }
    } else {
        if (area) { area.style.height = '260px'; area.style.minHeight = '180px'; }
        if (bar) { bar.style.height = '260px'; bar.style.minHeight = '180px'; }
    }
});

// Daily Sales Chart
if (document.getElementById('myAreaChart')) {
    var ctx = document.getElementById('myAreaChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($daily_labels); ?>,
            datasets: [{
                label: 'Sales',
                lineTension: 0.3,
                backgroundColor: 'rgba(2,117,216,0.2)',
                borderColor: 'rgba(2,117,216,1)',
                pointRadius: 5,
                pointBackgroundColor: 'rgba(2,117,216,1)',
                pointBorderColor: 'rgba(255,255,255,0.8)',
                pointHoverRadius: 5,
                pointHoverBackgroundColor: 'rgba(2,117,216,1)',
                pointHitRadius: 50,
                pointBorderWidth: 2,
                data: <?php echo json_encode($daily_sales); ?>,
            }],
        },
        options: {
            scales: {
                xAxes: [{
                    gridLines: { display: false },
                    ticks: { maxTicksLimit: 7 }
                }],
                yAxes: [{
                    ticks: { min: 0, maxTicksLimit: 5 },
                    gridLines: { color: 'rgba(0, 0, 0, .125)' }
                }],
            },
            legend: { display: false }
        }
    });
}
// Monthly Sales Chart
if (document.getElementById('myBarChart')) {
    var ctx = document.getElementById('myBarChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($monthly_labels); ?>,
            datasets: [{
                label: 'Revenue',
                backgroundColor: 'rgba(2,117,216,1)',
                borderColor: 'rgba(2,117,216,1)',
                data: <?php echo json_encode($monthly_sales); ?>,
            }],
        },
        options: {
            scales: {
                xAxes: [{
                    gridLines: { display: false },
                    ticks: { maxTicksLimit: 6 }
                }],
                yAxes: [{
                    ticks: { min: 0, maxTicksLimit: 5 },
                    gridLines: { display: true }
                }],
            },
            legend: { display: false }
        }
    });
}

// Remove demo chart scripts from the page
window.addEventListener('DOMContentLoaded', function() {
    // Remove demo chart scripts if loaded
    var scripts = document.querySelectorAll('script[src*="assets/demo/chart-area-demo.js"], script[src*="assets/demo/chart-bar-demo.js"]');
    scripts.forEach(function(s) { s.remove(); });
});

// Order Details Modal Functionality
document.addEventListener('click', function(e) {
    if (e.target.closest('.view-order-btn')) {
        const button = e.target.closest('.view-order-btn');
        const orderId = button.getAttribute('data-order-id');
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
        modal.show();
        
        // Reset content to loading state
        document.getElementById('orderDetailsContent').innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading order details...</p>
            </div>
        `;
        
        // Fetch order details
        fetch('get_order_details.php?id=' + orderId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayOrderDetails(data.order);
                } else {
                    document.getElementById('orderDetailsContent').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Error: ${data.message || 'Failed to load order details'}
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('orderDetailsContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Error loading order details. Please try again.
                    </div>
                `;
            });
    }
});

function displayOrderDetails(order) {
    const statusBadge = getStatusBadge(order.payment_status);
    
    const html = `
        <div class="card border-0">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="text-muted small">Order Number</label>
                            <div class="fw-bold fs-5">${order.order_number}</div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small">Order Type</label>
                            <div class="fw-semibold">${order.order_type ? order.order_type.charAt(0).toUpperCase() + order.order_type.slice(1) : 'N/A'}</div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small">Payment Type</label>
                            <div class="fw-semibold">${order.payment_type ? order.payment_type.charAt(0).toUpperCase() + order.payment_type.slice(1) : 'N/A'}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="text-muted small">Transaction Date</label>
                            <div class="fw-semibold">${order.created_at}</div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small">Payment Status</label>
                            <div>${statusBadge}</div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small">Total Amount</label>
                            <div class="fw-bold fs-4 text-success">PHP ${parseFloat(order.total_amount).toFixed(2)}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('orderDetailsContent').innerHTML = html;
}

function getStatusBadge(status) {
    switch (status) {
        case 'paid':
        case 'completed':
            return '<span class="badge bg-success">Paid</span>';
        case 'pending':
            return '<span class="badge bg-warning text-dark">Pending</span>';
        case 'failed':
            return '<span class="badge bg-danger">Failed</span>';
        default:
            return '<span class="badge bg-secondary">' + status.charAt(0).toUpperCase() + status.slice(1) + '</span>';
    }
}
</script>
</body>
</html>
