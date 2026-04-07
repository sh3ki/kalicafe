<?php
/**
 * Archive Sales Data Script
 * This script archives sales data in daily, weekly, and monthly periods
 * and automatically cleans up old archives based on retention policies:
 * - Daily archives: Keep for 7 days
 * - Weekly archives: Keep for 1 month (30 days)
 * - Monthly archives: Keep for 6 months (180 days)
 */

session_start();
include('includes/config.php');
include('includes/auth.php');

// Only allow admin to run this
checkAccess(['admin']);

// Get operation type from GET parameter
$operation = isset($_GET['operation']) ? $_GET['operation'] : 'manual';

/**
 * Archive data for a specific period
 */
function archiveData($conn, $archive_type, $period_start, $period_end) {
    try {
        // Check if archive already exists
        $check_query = "SELECT id FROM sales_archives WHERE archive_type = ? AND period_start = ? AND period_end = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->execute([$archive_type, $period_start, $period_end]);
        
        if ($check_stmt->fetch()) {
            return ['success' => false, 'message' => "Archive already exists for this period"];
        }
        
        // Get sales summary
        $summary_query = "
            SELECT 
                COUNT(DISTINCT o.id) as total_orders,
                SUM(o.total_amount) as total_sales,
                AVG(o.total_amount) as avg_order_value,
                SUM(CASE WHEN o.payment_status = 'paid' THEN o.total_amount ELSE 0 END) as paid_sales,
                SUM(CASE WHEN o.payment_status = 'unpaid' THEN o.total_amount ELSE 0 END) as unpaid_sales
            FROM orders o 
            WHERE DATE(o.created_at) BETWEEN ? AND ?
        ";
        
        $summary_stmt = $conn->prepare($summary_query);
        $summary_stmt->execute([$period_start, $period_end]);
        $summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get top product
        $products_query = "
            SELECT 
                p.product_name,
                SUM(oi.amount) as total_revenue
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN products p ON oi.product_id = p.id
            WHERE DATE(o.created_at) BETWEEN ? AND ?
            GROUP BY p.id, p.product_name
            ORDER BY total_revenue DESC
            LIMIT 1
        ";
        
        $products_stmt = $conn->prepare($products_query);
        $products_stmt->execute([$period_start, $period_end]);
        $top_product = $products_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get all products sold with quantities
        $all_products_query = "
            SELECT 
                p.product_name,
                SUM(oi.qty) as total_quantity,
                SUM(oi.amount) as total_revenue,
                COUNT(DISTINCT o.id) as order_count
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN products p ON oi.product_id = p.id
            WHERE DATE(o.created_at) BETWEEN ? AND ?
            GROUP BY p.id, p.product_name
            ORDER BY total_quantity DESC
        ";
        
        $all_products_stmt = $conn->prepare($all_products_query);
        $all_products_stmt->execute([$period_start, $period_end]);
        $all_products = $all_products_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get primary payment method
        $payment_query = "
            SELECT 
                payment_type,
                SUM(total_amount) as total
            FROM orders o
            WHERE DATE(o.created_at) BETWEEN ? AND ?
            GROUP BY payment_type
            ORDER BY total DESC
            LIMIT 1
        ";
        
        $payment_stmt = $conn->prepare($payment_query);
        $payment_stmt->execute([$period_start, $period_end]);
        $payment_method = $payment_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get detailed sales data
        $detailed_query = "
            SELECT 
                DATE(o.created_at) as sale_date,
                COUNT(o.id) as orders_count,
                SUM(o.total_amount) as daily_total
            FROM orders o 
            WHERE DATE(o.created_at) BETWEEN ? AND ?
            GROUP BY DATE(o.created_at)
            ORDER BY DATE(o.created_at) ASC
        ";
        
        $detailed_stmt = $conn->prepare($detailed_query);
        $detailed_stmt->execute([$period_start, $period_end]);
        $daily_sales = $detailed_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Compile all data
        $sales_data = [
            'daily_breakdown' => $daily_sales,
            'summary' => $summary,
            'top_product_details' => $top_product,
            'payment_method_details' => $payment_method,
            'products_sold' => $all_products
        ];
        
        // Insert archive
        $insert_query = "
            INSERT INTO sales_archives (
                archive_type, period_start, period_end, 
                total_orders, total_sales, avg_order_value, 
                paid_sales, unpaid_sales, 
                top_product, top_product_revenue, 
                primary_payment_method, sales_data
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->execute([
            $archive_type,
            $period_start,
            $period_end,
            $summary['total_orders'] ?? 0,
            $summary['total_sales'] ?? 0.00,
            $summary['avg_order_value'] ?? 0.00,
            $summary['paid_sales'] ?? 0.00,
            $summary['unpaid_sales'] ?? 0.00,
            $top_product['product_name'] ?? 'N/A',
            $top_product['total_revenue'] ?? 0.00,
            $payment_method['payment_type'] ?? 'N/A',
            json_encode($sales_data)
        ]);
        
        return ['success' => true, 'message' => "Successfully archived {$archive_type} data"];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => "Error: " . $e->getMessage()];
    }
}

/**
 * Clean up old archives based on retention policy
 */
function cleanupOldArchives($conn) {
    $results = [];
    
    try {
        // Clean daily archives older than 7 days
        $daily_cutoff = date('Y-m-d', strtotime('-7 days'));
        $daily_query = "DELETE FROM sales_archives WHERE archive_type = 'daily' AND period_end < ?";
        $daily_stmt = $conn->prepare($daily_query);
        $daily_stmt->execute([$daily_cutoff]);
        $daily_deleted = $daily_stmt->rowCount();
        
        if ($daily_deleted > 0) {
            $results[] = "Deleted {$daily_deleted} daily archive(s) older than 7 days";
        }
        
        // Clean weekly archives older than 30 days
        $weekly_cutoff = date('Y-m-d', strtotime('-30 days'));
        $weekly_query = "DELETE FROM sales_archives WHERE archive_type = 'weekly' AND period_end < ?";
        $weekly_stmt = $conn->prepare($weekly_query);
        $weekly_stmt->execute([$weekly_cutoff]);
        $weekly_deleted = $weekly_stmt->rowCount();
        
        if ($weekly_deleted > 0) {
            $results[] = "Deleted {$weekly_deleted} weekly archive(s) older than 30 days";
        }
        
        // Clean monthly archives older than 180 days (6 months)
        $monthly_cutoff = date('Y-m-d', strtotime('-180 days'));
        $monthly_query = "DELETE FROM sales_archives WHERE archive_type = 'monthly' AND period_end < ?";
        $monthly_stmt = $conn->prepare($monthly_query);
        $monthly_stmt->execute([$monthly_cutoff]);
        $monthly_deleted = $monthly_stmt->rowCount();
        
        if ($monthly_deleted > 0) {
            $results[] = "Deleted {$monthly_deleted} monthly archive(s) older than 180 days";
        }
        
        if (empty($results)) {
            $results[] = "No old archives to clean up";
        }
        
        return ['success' => true, 'messages' => $results];
        
    } catch (Exception $e) {
        return ['success' => false, 'messages' => ["Error during cleanup: " . $e->getMessage()]];
    }
}

// Handle manual archiving
if (isset($_POST['archive_type']) && isset($_POST['start_date']) && isset($_POST['end_date'])) {
    $archive_type = $_POST['archive_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    $result = archiveData($conn, $archive_type, $start_date, $end_date);
    
    $_SESSION['archive_message'] = $result['message'];
    $_SESSION['archive_status'] = $result['success'] ? 'success' : 'danger';
    
    header('Location: archive_sales.php');
    exit;
}

// Handle automatic archiving (can be called via cron job)
if ($operation === 'auto') {
    $messages = [];
    
    // Archive yesterday's data as daily
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $result = archiveData($conn, 'daily', $yesterday, $yesterday);
    $messages[] = $result['message'];
    
    // Archive last week's data if it's Monday
    if (date('N') == 1) {
        $last_week_start = date('Y-m-d', strtotime('last monday -7 days'));
        $last_week_end = date('Y-m-d', strtotime('last sunday'));
        $result = archiveData($conn, 'weekly', $last_week_start, $last_week_end);
        $messages[] = $result['message'];
    }
    
    // Archive last month's data if it's the 1st of the month
    if (date('j') == 1) {
        $last_month_start = date('Y-m-01', strtotime('first day of last month'));
        $last_month_end = date('Y-m-t', strtotime('last day of last month'));
        $result = archiveData($conn, 'monthly', $last_month_start, $last_month_end);
        $messages[] = $result['message'];
    }
    
    // Run cleanup
    $cleanup_result = cleanupOldArchives($conn);
    $messages = array_merge($messages, $cleanup_result['messages']);
    
    echo json_encode(['success' => true, 'messages' => $messages]);
    exit;
}

// Handle cleanup request
if (isset($_POST['cleanup'])) {
    $result = cleanupOldArchives($conn);
    $_SESSION['archive_message'] = implode('<br>', $result['messages']);
    $_SESSION['archive_status'] = $result['success'] ? 'success' : 'danger';
    
    header('Location: archive_sales.php');
    exit;
}

include('includes/header.php');
include('includes/navbar.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>KUSSO - Archive Sales Data</title>
    <link href="css/styles.css" rel="stylesheet" />
    <link href="https://use.fontawesome.com/releases/v6.3.0/css/all.css" rel="stylesheet" />
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #f1f3f4 100%);
        }
        
        .card {
            background: linear-gradient(145deg, #ffffff 0%, #fdfdfd 100%);
            border: 1px solid #e8ecef;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(198, 124, 78, 0.06);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: 0 5px 15px rgba(198, 124, 78, 0.12);
        }
        
        .card-header {
            background: linear-gradient(135deg, #c67c4e 0%, #b8704a 100%);
            color: white;
            font-weight: 600;
            border-radius: 8px 8px 0 0 !important;
            padding: 15px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #c67c4e 0%, #b8704a 100%);
            border: none;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #b8704a 0%, #a86040 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(198, 124, 78, 0.3);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #d4925c 0%, #c67c4e 100%);
            border: none;
            color: white;
        }
        .btn-warning:hover {
            background: linear-gradient(135deg, #c67c4e 0%, #b8704a 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(212, 146, 92, 0.3);
        }
        
        .alert-success {
            background: linear-gradient(145deg, #e8f5e9 0%, #f1f8e9 100%);
            border-left: 4px solid #8a6a4a;
            color: #6a4a2a;
        }
        
        .alert-danger {
            background: linear-gradient(145deg, #ffebee 0%, #fce4ec 100%);
            border-left: 4px solid #c67c4e;
            color: #8a6a4a;
        }
        
        .breadcrumb-item.active {
            color: #c67c4e;
        }
        
        .breadcrumb-item a {
            color: #8a6a4a;
            text-decoration: none;
        }
        .breadcrumb-item a:hover {
            color: #c67c4e;
        }
        
        h1, h2, h3, h4, h5, h6 {
            color: #8a6a4a;
        }
        
        .form-label {
            color: #8a6a4a;
            font-weight: 600;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #c67c4e;
            box-shadow: 0 0 0 0.2rem rgba(198, 124, 78, 0.25);
        }
        
        code {
            background: #f5f5f5;
            color: #c67c4e;
            padding: 2px 6px;
            border-radius: 4px;
        }
        
        /* Full width layout - FLEXBOX APPROACH */
        #layoutSidenav {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }
        
        /* Sidebar (fixed width) */
        #layoutSidenav_nav {
            width: 225px;
            flex-shrink: 0;
        }
        
        /* Content fills remaining space */
        #layoutSidenav_content {
            flex-grow: 1;
            width: auto;
            margin-left: 0;
        }
        
        /* Fixed sidebar mode */
        .sb-nav-fixed #layoutSidenav_content {
            margin-left: 225px;
            width: calc(100% - 225px);
        }
        
        /* Mobile */
        @media (max-width: 991px) {
            .sb-nav-fixed #layoutSidenav_content {
                margin-left: 0;
                width: 100%;
            }
        }
        
        /* Container stretches naturally */
        .container-fluid {
            max-width: 100%;
            padding-left: 1rem;
            padding-right: 1rem;
        }
        
        /* Wider padding for large screens */
        @media (min-width: 1400px) {
            .container-fluid {
                padding-left: 1.5rem;
                padding-right: 1.5rem;
            }
        }
        
        /* Override any container constraints */
        html, body {
            overflow-x: hidden;
            width: 100%;
            margin: 0;
            padding: 0;
        }
    </style>
</head>
<body class="sb-nav-fixed">
    <div id="layoutSidenav">
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
                        <h1 class="mb-0">Archive Sales Data</h1>
                        <a href="view_archives.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to Archives
                        </a>
                    </div>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="sales_report.php">Sales Report</a></li>
                        <li class="breadcrumb-item active">Archive</li>
                    </ol>

                    <?php if (isset($_SESSION['archive_message'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['archive_status']; ?> alert-dismissible fade show" role="alert">
                            <?php 
                            echo $_SESSION['archive_message']; 
                            unset($_SESSION['archive_message']);
                            unset($_SESSION['archive_status']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-xl-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <i class="fas fa-archive me-1"></i>
                                    Manual Archive
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="mb-3">
                                            <label for="archive_type" class="form-label">Archive Type</label>
                                            <select class="form-select" name="archive_type" id="archive_type" required>
                                                <option value="">Select Type</option>
                                                <option value="daily">Daily</option>
                                                <option value="weekly">Weekly</option>
                                                <option value="monthly">Monthly</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="start_date" class="form-label">Start Date</label>
                                            <input type="date" class="form-control" name="start_date" id="start_date" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="end_date" class="form-label">End Date</label>
                                            <input type="date" class="form-control" name="end_date" id="end_date" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i> Archive Data
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <i class="fas fa-trash me-1"></i>
                                    Cleanup Old Archives
                                </div>
                                <div class="card-body">
                                    <p>Remove old archives based on retention policies:</p>
                                    <ul>
                                        <li><strong>Daily archives:</strong> Deleted after 7 days</li>
                                        <li><strong>Weekly archives:</strong> Deleted after 30 days</li>
                                        <li><strong>Monthly archives:</strong> Deleted after 180 days (6 months)</li>
                                    </ul>
                                    <form method="POST">
                                        <button type="submit" name="cleanup" class="btn btn-warning">
                                            <i class="fas fa-broom me-1"></i> Run Cleanup
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="card mb-4">
                                <div class="card-header">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Information
                                </div>
                                <div class="card-body">
                                    <p><strong>What Gets Archived:</strong></p>
                                    <ul class="small mb-0">
                                        <li>Total sales and order statistics</li>
                                        <li><strong>All products sold</strong> with quantities</li>
                                        <li>Top-selling products and revenue</li>
                                        <li>Payment method statistics</li>
                                        <li>Daily sales breakdown</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <?php include('includes/footer.php'); ?>
        </div>
    </div>
    <?php include('includes/scripts.php'); ?>
</body>
</html>
