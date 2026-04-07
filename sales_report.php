<?php 
    session_start();
    
    // Set timezone to Philippines (GMT+8)
    date_default_timezone_set('Asia/Manila');
    
    include ('includes/header.php');
    include('includes/navbar.php');
    include('includes/config.php');
    include('includes/auth.php');

    // Allow only admin 
    checkAccess(['admin']);

    // Get date range from GET parameters
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;
    
    // Get period from GET parameter
    $period = isset($_GET['period']) ? $_GET['period'] : null;
    
    // Calculate dates based on period using local timezone
    if ($period && !$start_date && !$end_date) {
        $today = date('Y-m-d');
        $end_date = $today;
        
        switch($period) {
            case 'daily':
                $start_date = $today;
                break;
            case 'weekly':
                $start_date = date('Y-m-d', strtotime($today . ' -6 days'));
                break;
            case 'monthly':
                $start_date = date('Y-m-d', strtotime($today . ' -29 days'));
                break;
            default:
                $start_date = $today;
        }
    }
    
    // Set default to today's data if no period is selected
    if (!$period) {
        $period = 'daily';
        // Use local timezone for current date
        $today = date('Y-m-d');
        $start_date = $today;
        $end_date = $today;
    }

    // Only fetch data if period is set
    if ($period && $start_date && $end_date) {
        try {
        // Sales Summary Query
        $summary_query = "
            SELECT 
                COUNT(DISTINCT o.id) as total_orders,
                SUM(o.total_amount) as total_sales,
                AVG(o.total_amount) as avg_order_value,
                SUM(CASE WHEN o.payment_status = 'paid' THEN o.total_amount ELSE 0 END) as paid_sales,
                SUM(CASE WHEN o.payment_status = 'unpaid' THEN o.total_amount ELSE 0 END) as unpaid_sales
            FROM orders o 
            WHERE DATE(o.created_at) BETWEEN :start_date AND :end_date
        ";
        
        $summary_stmt = $conn->prepare($summary_query);
        $summary_stmt->bindParam(':start_date', $start_date);
        $summary_stmt->bindParam(':end_date', $end_date);
        $summary_stmt->execute();
        $summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Ensure summary values are not null
        $summary['total_orders'] = $summary['total_orders'] ?? 0;
        $summary['total_sales'] = $summary['total_sales'] ?? 0;
        $summary['avg_order_value'] = $summary['avg_order_value'] ?? 0;
        $summary['paid_sales'] = $summary['paid_sales'] ?? 0;
        $summary['unpaid_sales'] = $summary['unpaid_sales'] ?? 0;

        // Daily Sales Query
        $daily_query = "
            SELECT 
                DATE(o.created_at) as sale_date,
                COUNT(o.id) as orders_count,
                SUM(o.total_amount) as daily_total
            FROM orders o 
            WHERE DATE(o.created_at) BETWEEN :start_date AND :end_date
            GROUP BY DATE(o.created_at)
            ORDER BY DATE(o.created_at) DESC
        ";
        
        $daily_stmt = $conn->prepare($daily_query);
        $daily_stmt->bindParam(':start_date', $start_date);
        $daily_stmt->bindParam(':end_date', $end_date);
        $daily_stmt->execute();
        $daily_sales = $daily_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Top Products Query
        try {
            $products_query = "
                SELECT 
                    p.product_name,
                    oi.options as size,
                    SUM(oi.qty) as total_quantity,
                    SUM(oi.amount) as total_revenue
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                JOIN products p ON oi.product_id = p.id
                WHERE DATE(o.created_at) BETWEEN :start_date AND :end_date
                GROUP BY p.id, p.product_name, oi.options
                ORDER BY total_revenue DESC
                LIMIT 10
            ";
            
            $products_stmt = $conn->prepare($products_query);
            $products_stmt->bindParam(':start_date', $start_date);
            $products_stmt->bindParam(':end_date', $end_date);
            $products_stmt->execute();
            $top_products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // If query fails, try without size grouping
            $products_query = "
                SELECT 
                    p.product_name,
                    '' as size,
                    SUM(oi.qty) as total_quantity,
                    SUM(oi.amount) as total_revenue
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                JOIN products p ON oi.product_id = p.id
                WHERE DATE(o.created_at) BETWEEN :start_date AND :end_date
                GROUP BY p.id, p.product_name
                ORDER BY total_revenue DESC
                LIMIT 10
            ";
            
            $products_stmt = $conn->prepare($products_query);
            $products_stmt->bindParam(':start_date', $start_date);
            $products_stmt->bindParam(':end_date', $end_date);
            $products_stmt->execute();
            $top_products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Payment Method Analysis
        $payment_query = "
            SELECT 
                payment_type,
                COUNT(*) as count,
                SUM(total_amount) as total
            FROM orders o
            WHERE DATE(o.created_at) BETWEEN :start_date AND :end_date
            GROUP BY payment_type
        ";
        
        $payment_stmt = $conn->prepare($payment_query);
        $payment_stmt->bindParam(':start_date', $start_date);
        $payment_stmt->bindParam(':end_date', $end_date);
        $payment_stmt->execute();
        $payment_methods = $payment_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Categories Analysis
        try {
            $categories_query = "
                SELECT 
                    c.name as category_name,
                    SUM(oi.amount) as total_revenue,
                    COUNT(DISTINCT oi.order_id) as order_count
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                JOIN products p ON oi.product_id = p.id
                JOIN categories c ON p.category_id = c.id
                WHERE DATE(o.created_at) BETWEEN :start_date AND :end_date
                GROUP BY c.id, c.name
                ORDER BY total_revenue DESC
            ";
            
            $categories_stmt = $conn->prepare($categories_query);
            $categories_stmt->bindParam(':start_date', $start_date);
            $categories_stmt->bindParam(':end_date', $end_date);
            $categories_stmt->execute();
            $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // If categories query fails, just use empty array
            $categories = [];
        }
        } catch (PDOException $e) {
            // If any database query fails, show error
            $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
            $summary = ['total_orders' => 0, 'total_sales' => 0, 'avg_order_value' => 0, 'paid_sales' => 0, 'unpaid_sales' => 0];
            $daily_sales = [];
            $top_products = [];
            $payment_methods = [];
            $categories = [];
        }
    } else {
        // Initialize empty arrays when no period is selected
        $summary = ['total_orders' => 0, 'total_sales' => 0, 'avg_order_value' => 0, 'paid_sales' => 0, 'unpaid_sales' => 0];
        $daily_sales = [];
        $top_products = [];
        $payment_methods = [];
        $categories = [];
    }
    
    // Calculate derived values for use in the report
    $total_sales = $summary['total_sales'] ?? 0;
    $total_orders = $summary['total_orders'] ?? 0;
    $avg_order = $summary['avg_order_value'] ?? 0;
    $paid_sales = $summary['paid_sales'] ?? 0;
    $unpaid_sales = $summary['unpaid_sales'] ?? 0;
    
    // Calculate best product metrics
    $best_product = !empty($top_products) ? $top_products[0]['product_name'] : 'N/A';
    $best_product_revenue = !empty($top_products) ? ($top_products[0]['total_revenue'] ?? 0) : 0;
    $best_product_qty = !empty($top_products) ? ($top_products[0]['total_quantity'] ?? 0) : 0;
    $best_product_percentage = $total_sales > 0 ? ($best_product_revenue / $total_sales) * 100 : 0;
    
    // Calculate days count
    $days_count = 0;
    if ($start_date && $end_date) {
        $days_count = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24) + 1;
    }
    
    // Calculate daily average
    $daily_average = $days_count > 0 ? $total_sales / $days_count : 0;
    
    // Payment method analysis
    $primary_payment = 'N/A';
    $primary_payment_amount = 0;
    if (!empty($payment_methods)) {
        foreach ($payment_methods as $pm) {
            if (($pm['total'] ?? 0) > $primary_payment_amount) {
                $primary_payment_amount = $pm['total'] ?? 0;
                $primary_payment = $pm['payment_type'] ?? 'N/A';
            }
        }
    }
    $primary_payment_percentage = $total_sales > 0 ? ($primary_payment_amount / $total_sales) * 100 : 0;
    
    // Fetch expenses for the selected period
    $total_expenses = 0;
    $expenses_list = [];
    if ($period && $start_date && $end_date) {
        try {
            $expenses_query = "
                SELECT 
                    expense_name,
                    SUM(amount) as total_amount,
                    COUNT(*) as count
                FROM expenses
                WHERE expense_date BETWEEN :start_date AND :end_date
                GROUP BY expense_name
                ORDER BY total_amount DESC
            ";
            
            $expenses_stmt = $conn->prepare($expenses_query);
            $expenses_stmt->bindParam(':start_date', $start_date);
            $expenses_stmt->bindParam(':end_date', $end_date);
            $expenses_stmt->execute();
            $expenses_list = $expenses_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate total expenses
            $total_expenses_query = "
                SELECT COALESCE(SUM(amount), 0) as total
                FROM expenses
                WHERE expense_date BETWEEN :start_date AND :end_date
            ";
            
            $total_expenses_stmt = $conn->prepare($total_expenses_query);
            $total_expenses_stmt->bindParam(':start_date', $start_date);
            $total_expenses_stmt->bindParam(':end_date', $end_date);
            $total_expenses_stmt->execute();
            $total_expenses = $total_expenses_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        } catch (PDOException $e) {
            // If expenses table doesn't exist yet, just use 0
            $total_expenses = 0;
            $expenses_list = [];
        }
    }
    
    // Calculate net profit (revenue - expenses)
    $net_profit = $total_sales - $total_expenses;
    $profit_margin = $total_sales > 0 ? ($net_profit / $total_sales) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>KUSSO - Sales Report</title>
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /* Box sizing + safety */
        *, *::before, *::after { box-sizing: border-box; }

        /* Prevent horizontal overflow globally (safe) */
        html, body {
            overflow-x: hidden !important;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background: linear-gradient(135deg, #f8f9fa 0%, #f1f3f4 100%);
        }

        /* Layout containers */
        #layoutSidenav, #layoutSidenav_content {
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        main {
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            margin-top: 0; /* No extra top margin */
        }

        .container-fluid {
            padding: 15px;
            max-width: 100% !important;
            width: 100% !important;
            margin: 0 !important;
            transition: all 0.15s ease-in-out;
        }

        /* Ensure content is not hidden behind navbar */
        .sb-nav-fixed #layoutSidenav_content {
            margin-left: 0 !important;
            padding-left: 225px !important;
            padding-top: 0 !important;
            transition: all 0.15s ease-in-out;
        }

        .sb-nav-fixed main {
            margin-top: 0;
            padding-top: 0 !important;
        }

        /* When sidebar is toggled (hidden) on desktop */
        @media (min-width: 992px) {
            .sb-sidenav-toggled #layoutSidenav_content {
                padding-left: 0 !important;
            }
        }

        /* Mobile: sidebar is hidden by default */
        @media (max-width: 991px) {
            .sb-nav-fixed #layoutSidenav_content {
                padding-left: 0 !important;
                padding-top: 0 !important;
            }
            
            .sb-sidenav-toggled #layoutSidenav_content {
                padding-left: 225px !important;
            }
        }

        /* Date filter card */
        .date-filter-card {
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            border: 1px solid #e3e6ea;
            border-radius: 8px;
            padding: 18px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(198, 124, 78, 0.08);
        }

        /* Stats grid */
        .grid-container { display: grid; gap: 15px; width: 100%; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            margin-bottom: 20px;
            gap: 15px;
            align-items: stretch;
        }

        .stat-card {
            background: linear-gradient(145deg, #ffffff 0%, #fefefe 100%);
            border: 1px solid #e8ecef;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(198, 124, 78, 0.06);
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-width: 0; /* <— prevents overflow from children */
        }

        .stat-number { font-size: 2rem; font-weight: 700; margin: 8px 0; background: linear-gradient(135deg,#c67c4e,#d4925c); background-clip: text; -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
        .stat-label { font-size: 0.85rem; color:#8a6a4a; font-weight:600; text-transform:uppercase; }

        /* Charts grid */
        .charts-grid {
            display: grid;
            gap: 15px;
            grid-template-columns: 2fr 1fr 1fr;
            grid-template-rows: auto auto;
            grid-template-areas: 
                "monthly-trend product-type product-category"
                "daily-trend store-location store-location";
            width: 100%;
            min-width: 0;
        }

        .monthly-trend { grid-area: monthly-trend; }
        .daily-trend { grid-area: daily-trend; }
        .product-type { grid-area: product-type; }
        .product-category { grid-area: product-category; }
        .store-location { grid-area: store-location; }

        @media (max-width: 1400px) {
            .charts-grid {
                grid-template-columns: 1fr 1fr;
                grid-template-areas:
                    "monthly-trend product-type"
                    "daily-trend product-category"
                    "store-location store-location";
            }
        }

        @media (max-width: 992px) {
            .charts-grid {
                grid-template-columns: 1fr;
                grid-template-areas:
                    "monthly-trend"
                    "daily-trend"
                    "product-type"
                    "product-category"
                    "store-location";
            }
            .stats-grid { grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); }
        }

        .chart-card {
            background: linear-gradient(145deg, #ffffff 0%, #fdfdfd 100%);
            border: 1px solid #e8ecef;
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            min-width: 0; /* critical to prevent overflow */
            height: 100%;
        }

        .chart-header {
            background: linear-gradient(135deg, #c67c4e 0%, #b8704a 100%);
            color: white;
            padding: 12px 15px;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .chart-body {
            padding: 12px;
            height: 300px;
            position: relative;
            display: flex;
            align-items: center;
        }

        /* Make canvases fully responsive to their parent container */
        .chart-body canvas {
            width: 100% !important;
            height: 100% !important;
            max-width: 100% !important;
            display: block;
        }

        /* Ensure any images, if present, do not overflow */
        img, svg { max-width: 100%; height: auto; display: block; }

        /* Buttons */
        .btn-primary { background-color: #c67c4e; border-color: #c67c4e; }
        .btn-success { background-color: #198754; border-color: #198754; }
        .btn-secondary { background-color: #6c757d; border-color: #6c757d; }

        /* Period buttons */
        .period-btn { border-color: #c67c4e; color: #c67c4e; transition: all .15s ease; }
        .period-btn.active { background-color: #c67c4e; color: #fff; }

        /* Best Selling Products Table */
        .best-selling-section {
            background: linear-gradient(145deg, #ffffff 0%, #fdfdfd 100%);
            border: 1px solid #e8ecef;
            border-radius: 8px;
            overflow: hidden;
            margin-top: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(198, 124, 78, 0.06);
        }

        .best-selling-header {
            background: linear-gradient(135deg, #c67c4e 0%, #b8704a 100%);
            color: white;
            padding: 15px 20px;
            font-weight: 700;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .best-selling-header i {
            margin-right: 10px;
        }

        .best-selling-body {
            padding: 0;
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .products-table thead {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        .products-table thead th {
            padding: 12px 15px;
            text-align: left;
            font-size: 0.85rem;
            font-weight: 700;
            color: #495057;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #c67c4e;
        }

        .products-table thead th:first-child {
            text-align: center;
            width: 60px;
        }

        .products-table tbody tr {
            border-bottom: 1px solid #e9ecef;
            transition: background-color 0.2s ease;
        }

        .products-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .products-table tbody tr:last-child {
            border-bottom: none;
        }

        .products-table tbody td {
            padding: 14px 15px;
            font-size: 0.9rem;
            color: #495057;
        }

        .products-table .rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            font-weight: 700;
            font-size: 0.85rem;
            color: white;
        }

        .products-table .rank-1 {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #856404;
            box-shadow: 0 2px 8px rgba(255, 215, 0, 0.4);
        }

        .products-table .rank-2 {
            background: linear-gradient(135deg, #c0c0c0 0%, #d3d3d3 100%);
            color: #383d41;
            box-shadow: 0 2px 8px rgba(192, 192, 192, 0.4);
        }

        .products-table .rank-3 {
            background: linear-gradient(135deg, #cd7f32 0%, #e0a160 100%);
            color: #fff;
            box-shadow: 0 2px 8px rgba(205, 127, 50, 0.4);
        }

        .products-table .rank-other {
            background: linear-gradient(135deg, #6c757d 0%, #868e96 100%);
            color: white;
        }

        .products-table .product-name {
            font-weight: 600;
            color: #2c3e50;
        }

        .products-table .revenue-amount {
            font-weight: 700;
            color: #c67c4e;
            font-size: 0.95rem;
        }

        .products-table .quantity-sold {
            font-weight: 600;
            color: #28a745;
        }

        .products-table .avg-price {
            color: #6c757d;
            font-size: 0.85rem;
        }

        .products-table .percentage-bar {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .products-table .bar-container {
            flex: 1;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }

        .products-table .bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #c67c4e 0%, #d4925c 100%);
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .products-table .percentage-text {
            font-size: 0.8rem;
            font-weight: 600;
            color: #495057;
            min-width: 45px;
            text-align: right;
        }

        .no-data-message {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
            font-size: 0.95rem;
        }

        .no-data-message i {
            font-size: 3rem;
            color: #dee2e6;
            margin-bottom: 15px;
        }

        /* Print header styles */
        .print-header {
            display: none;
        }

        .print-report-info {
            display: none;
        }

        .executive-summary {
            display: none;
        }

        .sales-insights {
            display: none;
        }

        /* Print Styles */
        @media print {
            /* Hide navbar and navigation elements */
            .sb-topnav,
            #layoutSidenav_nav,
            nav,
            .navbar,
            .btn,
            .btn-group,
            .date-filter-card,
            .period-btn,
            .mt-4,
            .text-muted {
                display: none !important;
            }
            
            /* Remove scrollbars and fix overflow */
            html, body {
                overflow: visible !important;
                overflow-x: visible !important;
                overflow-y: visible !important;
                height: auto !important;
            }

            /* Show print header */
            .print-header {
                display: block !important;
                text-align: center;
                margin-bottom: 20px;
                padding-bottom: 15px;
                border-bottom: 3px double #c67c4e;
                background: linear-gradient(to bottom, #ffffff 0%, #fafafa 100%) !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .print-header h1 {
                font-size: 26px;
                color: #c67c4e;
                margin: 0 0 5px 0;
                font-weight: 800;
                letter-spacing: 2px;
                text-transform: uppercase;
            }

            .print-header .company-name {
                font-size: 16px;
                color: #333;
                margin: 2px 0 8px 0;
                font-weight: 700;
                letter-spacing: 3px;
                text-transform: uppercase;
            }

            .print-header .tagline {
                font-size: 10px;
                color: #666;
                margin: 5px 0 0 0;
                font-style: italic;
                font-weight: 500;
            }

            /* Print report info */
            .print-report-info {
                display: grid !important;
                grid-template-columns: 1fr 1fr;
                gap: 12px;
                margin-bottom: 20px;
                padding: 15px;
                background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                border: 2px solid #c67c4e;
                border-radius: 4px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
            }

            /* Executive Summary Section */
            .executive-summary {
                display: block !important;
                margin-bottom: 18px;
                padding: 15px 18px;
                background: #ffffff !important;
                border: 2px solid #c67c4e !important;
                border-left: 5px solid #c67c4e !important;
                border-radius: 4px;
                page-break-inside: auto;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                box-shadow: 0 1px 3px rgba(0,0,0,0.08) !important;
            }

            .executive-summary h2 {
                font-size: 14px;
                color: #c67c4e;
                margin: 0 0 12px 0;
                padding-bottom: 8px;
                border-bottom: 2px solid #c67c4e;
                font-weight: 800;
                letter-spacing: 1px;
                text-transform: uppercase;
            }

            .executive-summary p {
                font-size: 10px;
                line-height: 1.6;
                color: #333;
                margin: 0 0 8px 0;
                text-align: justify;
            }

            .executive-summary p:last-child {
                margin-bottom: 0;
            }

            .executive-summary strong {
                color: #c67c4e;
                font-weight: 700;
            }

            /* Sales Insights Section */
            .sales-insights {
                display: block !important;
                margin-bottom: 18px;
                padding: 15px 18px;
                background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%) !important;
                border: 2px solid #6c757d !important;
                border-radius: 4px;
                page-break-inside: auto;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                box-shadow: 0 1px 3px rgba(0,0,0,0.08) !important;
            }

            .sales-insights h2 {
                font-size: 14px;
                color: #333;
                margin: 0 0 12px 0;
                padding-bottom: 8px;
                border-bottom: 2px solid #6c757d;
                font-weight: 800;
                letter-spacing: 1px;
                text-transform: uppercase;
            }

            .insight-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }

            .insight-item {
                padding: 10px 12px;
                background: white !important;
                border-left: 4px solid #c67c4e;
                border: 1px solid #dee2e6;
                border-left: 4px solid #c67c4e !important;
                border-radius: 3px;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .insight-item h3 {
                font-size: 10px;
                color: #c67c4e;
                margin: 0 0 6px 0;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .insight-item p {
                font-size: 9px;
                line-height: 1.5;
                color: #555;
                margin: 0;
            }

            .insight-item ul {
                margin: 5px 0 0 0;
                padding-left: 18px;
                font-size: 9px;
                color: #555;
                line-height: 1.5;
            }

            .insight-item ul li {
                margin-bottom: 3px;
                line-height: 1.4;
            }

            .report-info-item {
                display: flex;
                align-items: center;
            }

            .report-info-label {
                font-weight: 700;
                color: #333;
                font-size: 8px;
                margin-right: 6px;
                text-transform: uppercase;
                letter-spacing: 0.3px;
            }

            .report-info-value {
                color: #666;
                font-size: 9px;
            }

            /* Adjust layout for print */
            html, body {
                margin: 0;
                padding: 0;
                background: white !important;
                overflow: visible !important;
                overflow-x: visible !important;
                overflow-y: visible !important;
                height: auto !important;
                width: 100% !important;
            }

            body {
                overflow: visible !important;
                overflow-x: visible !important;
                overflow-y: visible !important;
                color: #000 !important;
                width: 100% !important;
            }

            #layoutSidenav,
            #layoutSidenav_content,
            main,
            .container-fluid {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                padding-left: 0 !important;
                background: white !important;
                overflow: visible !important;
                height: auto !important;
            }

            .sb-nav-fixed #layoutSidenav_content {
                padding-left: 0 !important;
                padding-top: 0 !important;
                margin-left: 0 !important;
                top: 0 !important;
            }

            .sb-nav-fixed main {
                padding-top: 0 !important;
                margin-top: 0 !important;
            }

            .container-fluid {
                padding: 12px 15px !important;
            }

            /* Key Metrics Section */
            .stats-grid::before {
                content: "KEY PERFORMANCE METRICS";
                display: block;
                font-size: 13px;
                font-weight: 800;
                color: #333;
                margin-bottom: 10px;
                padding: 8px 12px;
                background: linear-gradient(135deg, #c67c4e 0%, #b8704a 100%) !important;
                color: white !important;
                border-radius: 3px;
                letter-spacing: 1px;
                text-align: center;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            /* Stats and charts styling for print */
            .stat-card {
                page-break-inside: avoid;
                margin-bottom: 10px;
                background: linear-gradient(to bottom, #ffffff 0%, #f8f9fa 100%) !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                border: 2px solid #c67c4e !important;
                padding: 14px 12px !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: center !important;
                border-radius: 4px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1) !important;
            }

            .stat-number {
                font-size: 1.6rem !important;
                color: #c67c4e !important;
                margin: 6px 0 !important;
                font-weight: 800 !important;
                background: none !important;
                background-clip: unset !important;
                -webkit-background-clip: unset !important;
                -webkit-text-fill-color: unset !important;
                text-shadow: none !important;
            }

            .stat-label {
                color: #555 !important;
                font-size: 0.7rem !important;
                font-weight: 700 !important;
                text-transform: uppercase !important;
                margin: 0 !important;
                letter-spacing: 0.5px;
            }

            /* Charts section header */
            .charts-grid::before {
                content: "VISUAL ANALYTICS & TRENDS";
                display: block;
                font-size: 13px;
                font-weight: 800;
                color: white !important;
                margin: 18px 0 10px 0;
                padding: 8px 12px;
                background: linear-gradient(135deg, #c67c4e 0%, #b8704a 100%) !important;
                border-radius: 3px;
                letter-spacing: 1px;
                text-align: center;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .chart-card {
                page-break-inside: avoid;
                margin-bottom: 12px;
                background: white !important;
                border: 2px solid #c67c4e !important;
                border-radius: 4px;
                overflow: visible !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1) !important;
                height: auto !important;
            }

            .chart-header {
                background: linear-gradient(135deg, #c67c4e 0%, #b8704a 100%) !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color: white !important;
                padding: 10px 12px !important;
                font-size: 11px !important;
                font-weight: 700 !important;
                letter-spacing: 0.5px;
                text-transform: uppercase;
            }

            .chart-body {
                padding: 12px !important;
                height: auto !important;
                min-height: 200px !important;
                max-height: none !important;
                background: white !important;
                position: relative !important;
                display: block !important;
                overflow: visible !important;
            }

            .chart-body canvas {
                max-width: 100% !important;
                height: auto !important;
                background: white !important;
            }

            /* Grid adjustments for print */
            .stats-grid {
                margin-bottom: 15px;
                grid-template-columns: repeat(4, 1fr) !important;
                gap: 10px !important;
            }

            .charts-grid {
                margin-bottom: 12px;
                grid-template-columns: 1fr 1fr !important;
                grid-template-areas: 
                    "monthly-trend product-type"
                    "daily-trend product-category"
                    "store-location store-location" !important;
                gap: 12px !important;
            }

            .charts-grid > div {
                margin-bottom: 0;
            }

            /* Footer on every page */
            @page {
                size: A4 landscape;
                margin: 0.3in 0.35in;
            }

            /* Remove browser default print headers/footers */
            @media print {
                @page { margin: 0.3in 0.35in; }
                body { margin: 0; overflow: visible !important; height: auto !important; }
            }

            body {
                font-size: 10px;
                color: #000;
                line-height: 1.4;
                overflow: visible !important;
                height: auto !important;
                width: 100% !important;
            }

            canvas {
                max-width: 100% !important;
                height: auto !important;
                display: block !important;
            }

            /* Best Selling Products Table Print Styles */
            .best-selling-section {
                page-break-inside: auto;
                margin: 15px 0;
                background: white !important;
                border: 2px solid #c67c4e !important;
                border-radius: 4px;
                overflow: visible !important;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1) !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .best-selling-header {
                background: linear-gradient(135deg, #c67c4e 0%, #b8704a 100%) !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color: white !important;
                padding: 10px 12px !important;
                font-size: 12px !important;
                font-weight: 700 !important;
                letter-spacing: 0.5px;
                text-transform: uppercase;
            }

            .best-selling-header span:last-child {
                display: none !important;
            }

            .products-table {
                font-size: 9px;
                width: 100% !important;
                table-layout: auto;
            }

            .products-table thead {
                background: linear-gradient(to bottom, #f8f9fa 0%, #e9ecef 100%) !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .products-table thead th {
                padding: 8px 6px !important;
                font-size: 9px !important;
                border-bottom: 2px solid #c67c4e !important;
                font-weight: 700 !important;
            }

            .products-table tbody td {
                padding: 7px 6px !important;
                font-size: 9px !important;
                border-bottom: 1px solid #dee2e6 !important;
            }

            .products-table tbody tr:hover {
                background-color: transparent !important;
            }

            .products-table .rank-badge {
                width: 26px !important;
                height: 26px !important;
                font-size: 10px !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .products-table .rank-1,
            .products-table .rank-2,
            .products-table .rank-3,
            .products-table .rank-other {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .products-table .bar-container {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                background: #e9ecef !important;
            }

            .products-table .bar-fill {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            /* Ensure colors print */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                overflow: visible !important;
            }
            
            /* Final overflow fixes */
            .container-fluid, main, body, html {
                overflow: visible !important;
                overflow-x: visible !important;
                overflow-y: visible !important;
                height: auto !important;
                max-height: none !important;
            }
        }
    </style>
</head>

<body class="sb-nav-fixed">
    <div id="layoutSidenav">
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <!-- Print Header (only visible when printing) -->
                    <div class="print-header">
                        <div class="company-name">KUSSO</div>
                        <h1>SALES REPORT</h1>
                        <div class="tagline">Comprehensive Sales Performance Analysis</div>
                    </div>

                    <!-- Print Report Info (only visible when printing) -->
                    <div class="print-report-info">
                        <div class="report-info-item">
                            <span class="report-info-label">Report Period:</span>
                            <span class="report-info-value"><?php 
                                if ($period) {
                                    if ($period === 'daily') {
                                        echo date('F d, Y', strtotime($start_date));
                                    } elseif ($period === 'weekly') {
                                        echo date('M d', strtotime($start_date)) . ' - ' . date('M d, Y', strtotime($end_date));
                                    } elseif ($period === 'monthly') {
                                        echo date('M d', strtotime($start_date)) . ' - ' . date('M d, Y', strtotime($end_date));
                                    } else {
                                        echo date('M d', strtotime($start_date)) . ' - ' . date('M d, Y', strtotime($end_date));
                                    }
                                } else {
                                    echo 'No period selected';
                                }
                            ?></span>
                        </div>
                        <div class="report-info-item">
                            <span class="report-info-label">Generated On:</span>
                            <span class="report-info-value"><?php echo date('F d, Y h:i A'); ?></span>
                        </div>
                        <div class="report-info-item">
                            <span class="report-info-label">Report Type:</span>
                            <span class="report-info-value"><?php 
                                if ($period === 'daily') echo 'Daily Sales Analysis';
                                elseif ($period === 'weekly') echo 'Weekly Performance Report';
                                elseif ($period === 'monthly') echo 'Monthly Overview';
                                else echo 'Custom Period Report';
                            ?></span>
                        </div>
                        <div class="report-info-item">
                            <span class="report-info-label">Total Days:</span>
                            <span class="report-info-value"><?php 
                                if ($start_date && $end_date) {
                                    $days = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24) + 1;
                                    echo ceil($days) . ' day' . ($days != 1 ? 's' : '');
                                } else {
                                    echo 'N/A';
                                }
                            ?></span>
                        </div>
                    </div>

                    <!-- Executive Summary (Only visible when printing) -->
                    <div class="executive-summary">
                        <h2>EXECUTIVE SUMMARY</h2>
                        <?php if ($period && $start_date && $end_date): 
                            $period_text = '';
                            if ($period === 'daily') {
                                $period_text = 'today (' . date('F d, Y', strtotime($start_date)) . ')';
                            } elseif ($period === 'weekly') {
                                $period_text = 'the past 7 days (' . date('M d', strtotime($start_date)) . ' - ' . date('M d, Y', strtotime($end_date)) . ')';
                            } elseif ($period === 'monthly') {
                                $period_text = 'the past 30 days (' . date('M d', strtotime($start_date)) . ' - ' . date('M d, Y', strtotime($end_date)) . ')';
                            } else {
                                $period_text = date('M d', strtotime($start_date)) . ' - ' . date('M d, Y', strtotime($end_date));
                            }
                        ?>
                        <p>
                            This comprehensive sales report analyzes the business performance for <strong><?php echo $period_text; ?></strong>. 
                            During this period, KUSSO generated a total revenue of <strong>₱<?php echo number_format($total_sales, 2); ?></strong> 
                            from <strong><?php echo number_format($total_orders); ?> transactions</strong>, resulting in an average order value of 
                            <strong>₱<?php echo number_format($avg_order, 2); ?></strong>. The daily average revenue stands at 
                            <strong>₱<?php echo number_format($daily_average, 2); ?></strong> over <?php echo ceil($days_count); ?> day<?php echo $days_count != 1 ? 's' : ''; ?>.
                        </p>
                        <p>
                            The best-selling product during this period was <strong><?php echo htmlspecialchars($best_product); ?></strong>, 
                            which contributed <strong>₱<?php echo number_format($best_product_revenue, 2); ?></strong> 
                            (<?php echo number_format($best_product_percentage, 1); ?>% of total sales) with 
                            <strong><?php echo number_format($best_product_qty); ?> units sold</strong>. 
                            <?php if (!empty($top_products) && count($top_products) >= 3): ?>
                            The top three products collectively account for 
                            <strong>₱<?php 
                                $top_three_total = ($top_products[0]['total_revenue'] ?? 0) + ($top_products[1]['total_revenue'] ?? 0) + ($top_products[2]['total_revenue'] ?? 0);
                                echo number_format($top_three_total, 2); 
                            ?></strong> 
                            (<?php echo number_format(($top_three_total / $total_sales) * 100, 1); ?>% of total revenue), 
                            demonstrating strong product concentration.
                            <?php endif; ?>
                        </p>
                        <p>
                            Payment analysis reveals that <strong><?php echo ucfirst($primary_payment); ?></strong> was the preferred payment method, 
                            accounting for <strong>₱<?php echo number_format($primary_payment_amount, 2); ?></strong> 
                            (<?php echo number_format($primary_payment_percentage, 1); ?>% of total sales). 
                            <?php 
                            $paid_percentage = $total_sales > 0 ? ($paid_sales / $total_sales) * 100 : 0;
                            ?>
                            The payment completion rate stands at <strong><?php echo number_format($paid_percentage, 1); ?>%</strong>, 
                            with <strong>₱<?php echo number_format($paid_sales, 2); ?></strong> in completed transactions 
                            and <strong>₱<?php echo number_format($unpaid_sales, 2); ?></strong> pending.
                        </p>
                        <?php else: ?>
                        <p>No data available for analysis. Please select a reporting period to generate comprehensive sales insights.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Sales Insights (Only visible when printing) -->
                    <div class="sales-insights">
                        <h2>KEY INSIGHTS & OBSERVATIONS</h2>
                        <div class="insight-grid">
                            <div class="insight-item">
                                <h3>Sales Performance</h3>
                                <?php if ($period && $total_sales > 0): ?>
                                <p>
                                    <?php 
                                    if ($daily_average >= 10000) {
                                        echo 'Strong daily sales performance exceeding ₱10,000. Business is maintaining healthy revenue generation.';
                                    } elseif ($daily_average >= 5000) {
                                        echo 'Moderate daily sales performance. Consider promotional strategies to boost revenue.';
                                    } else {
                                        echo 'Daily sales below ₱5,000. Marketing and product optimization recommended.';
                                    }
                                    ?>
                                </p>
                                <?php else: ?>
                                <p>No data available for this period.</p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="insight-item">
                                <h3>Product Portfolio</h3>
                                <?php if (!empty($top_products)): ?>
                                <p>
                                    Total of <strong><?php echo count($top_products); ?> products</strong> sold during this period. 
                                    <?php if ($best_product_percentage > 30): ?>
                                    High dependency on top product (<?php echo number_format($best_product_percentage, 1); ?>%). Consider diversifying sales.
                                    <?php else: ?>
                                    Good product mix with balanced sales distribution.
                                    <?php endif; ?>
                                </p>
                                <?php else: ?>
                                <p>No product sales recorded for this period.</p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="insight-item">
                                <h3>Transaction Analysis</h3>
                                <?php if ($total_orders > 0): ?>
                                <p>
                                    Average transaction value: <strong>₱<?php echo number_format($avg_order, 2); ?></strong>.
                                    <?php 
                                    $daily_transactions = $days_count > 0 ? $total_orders / $days_count : 0;
                                    echo ' Daily average: <strong>' . number_format($daily_transactions, 1) . ' orders</strong>.';
                                    ?>
                                </p>
                                <?php else: ?>
                                <p>No transactions recorded for this period.</p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="insight-item">
                                <h3>Recommendations</h3>
                                <ul>
                                    <?php if ($best_product_percentage > 40): ?>
                                    <li>Reduce dependency on single product through promotion of other items</li>
                                    <?php endif; ?>
                                    <?php if ($avg_order < 200): ?>
                                    <li>Implement upselling strategies to increase average order value</li>
                                    <?php endif; ?>
                                    <?php if ($unpaid_sales > ($total_sales * 0.1)): ?>
                                    <li>Focus on improving payment collection and reducing pending transactions</li>
                                    <?php endif; ?>
                                    <li>Continue monitoring top-performing products and maintain inventory levels</li>
                                    <?php if (count($payment_methods) > 1): ?>
                                    <li>Maintain diverse payment options to accommodate customer preferences</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Page Title -->
                    <h1 class="mt-4">Sales Report</h1>  
                    <p class="text-muted">Analytics and performance overview<?php 
                        if ($period) {
                            echo ' for ';
                            if ($period === 'daily') {
                                echo 'Today\'s sales';
                            } elseif ($period === 'weekly') {
                                echo 'the past 7 days';
                            } elseif ($period === 'monthly') {
                                echo 'the past 30 days';
                            } else {
                                $days = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24) + 1;
                                if ($days <= 31) echo ceil($days) . ' days';
                                else echo ceil($days/30) . ' months';
                            }
                        } else {
                            echo ' - Select a period to view data';
                        }
                    ?></p>

                    <!-- Period Filter -->
                    <div class="date-filter-card">
                        <div class="row align-items-center">
                            <div class="col-lg-4 col-md-5">
                                <h5 class="mb-1">Report Period</h5>
                                <p class="text-muted mb-0">Select the time period for your sales analysis</p>
                            </div>
                            <div class="col-lg-8 col-md-7">
                                <div class="d-flex justify-content-end align-items-center gap-3 w-100">
                                    <div class="btn-group flex-grow-1 d-flex justify-content-center" role="group" style="max-width: 400px;">
                                        <button type="button" class="btn btn-outline-primary period-btn flex-fill" data-period="daily" onclick="changePeriod('daily')">
                                            <i class="fas fa-calendar-day me-2"></i> Daily View
                                        </button>
                                        <button type="button" class="btn btn-outline-primary period-btn flex-fill" data-period="weekly" onclick="changePeriod('weekly')">
                                            <i class="fas fa-calendar-week me-2"></i> Weekly View
                                        </button>
                                        <button type="button" class="btn btn-outline-primary period-btn flex-fill" data-period="monthly" onclick="changePeriod('monthly')">
                                            <i class="fas fa-calendar-alt me-2"></i> Monthly View
                                        </button>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="view_archives.php" class="btn btn-warning px-4" title="View Past Sales Data">
                                            <i class="fas fa-archive me-2"></i> View Archives
                                        </a>
                                        <button type="button" class="btn btn-success px-4" onclick="printReport()"><i class="fas fa-print me-2"></i> Print Report</button>
                                        <button type="button" class="btn btn-info px-4" onclick="exportToExcel()"><i class="fas fa-file-excel me-2"></i> Export Excel</button>
                                        <button type="button" class="btn btn-secondary px-4" onclick="exportToCSV()"><i class="fas fa-download me-2"></i> Export CSV</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Key Statistics -->
                    <div class="grid-container stats-grid">
                        <div class="stat-card">
                            <div class="stat-number">₱<?php 
                                $total_sales = $summary['total_sales'] ?? 0;
                                echo number_format($total_sales, 2);
                            ?></div>
                            <div class="stat-label">Total Sales</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-number">₱<?php 
                                echo number_format($total_expenses, 2);
                            ?></div>
                            <div class="stat-label">Total Expenses</div>
                        </div>
                        
                        <div class="stat-card" style="background: linear-gradient(145deg, #d4edda 0%, #c3e6cb 100%);">
                            <div class="stat-number" style="background: linear-gradient(135deg,#28a745,#20c997); background-clip: text; -webkit-background-clip:text; -webkit-text-fill-color:transparent;">₱<?php 
                                echo number_format($net_profit, 2);
                            ?></div>
                            <div class="stat-label" style="color: #155724;">Net Profit</div>
                            <div style="font-size: 0.75rem; color: #155724; margin-top: 5px;">
                                Margin: <?php echo number_format($profit_margin, 1); ?>%
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-number"><?php 
                                $total_qty = 0;
                                foreach ($top_products as $product) {
                                    $total_qty += $product['total_quantity'];
                                }
                                echo number_format($total_qty);
                            ?></div>
                            <div class="stat-label">Total Quantity</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-number"><?php 
                                $total_transactions = $summary['total_orders'] ?? 0;
                                echo number_format($total_transactions);
                            ?></div>
                            <div class="stat-label">Total Transaction</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-number"><?php echo count($top_products); ?></div>
                            <div class="stat-label">Total Product</div>
                        </div>
                    </div>

                    <!-- Expenses Breakdown -->
                    <?php if (!empty($expenses_list)): ?>
                    <div class="best-selling-section" style="margin-bottom: 30px;">
                        <div class="best-selling-header" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);">
                            <div>
                                <i class="fas fa-money-bill-wave"></i>
                                <span>Expenses Breakdown</span>
                            </div>
                            <span style="font-size: 0.85rem; font-weight: 500; opacity: 0.9;">
                                Total: ₱<?php echo number_format($total_expenses, 2); ?>
                            </span>
                        </div>
                        <div class="best-selling-body">
                            <table class="products-table">
                                <thead>
                                    <tr>
                                        <th>Expense Name</th>
                                        <th>Count</th>
                                        <th>Total Amount</th>
                                        <th>% of Sales</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($expenses_list as $expense): ?>
                                        <tr>
                                            <td style="font-weight: 600;"><?php echo htmlspecialchars($expense['expense_name']); ?></td>
                                            <td><?php echo $expense['count']; ?></td>
                                            <td>₱<?php echo number_format($expense['total_amount'], 2); ?></td>
                                            <td>
                                                <?php 
                                                    $percentage = $total_sales > 0 ? ($expense['total_amount'] / $total_sales) * 100 : 0;
                                                    echo number_format($percentage, 1) . '%'; 
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr style="background-color: #f8f9fa; font-weight: bold;">
                                        <td>TOTAL</td>
                                        <td><?php echo array_sum(array_column($expenses_list, 'count')); ?></td>
                                        <td>₱<?php echo number_format($total_expenses, 2); ?></td>
                                        <td><?php echo number_format(($total_sales > 0 ? ($total_expenses / $total_sales) * 100 : 0), 1); ?>%</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Best Selling Products Table -->
                    <div class="best-selling-section">
                        <div class="best-selling-header">
                            <div>
                                <i class="fas fa-trophy"></i>
                                <span>Best Selling Products</span>
                            </div>
                            <span style="font-size: 0.85rem; font-weight: 500; opacity: 0.9;">Top performers by revenue</span>
                        </div>
                        <div class="best-selling-body">
                            <?php if (!empty($top_products)): ?>
                                <table class="products-table">
                                    <thead>
                                        <tr>
                                            <th>Rank</th>
                                            <th>Product Name</th>
                                            <th>Size</th>
                                            <th>Qty Sold</th>
                                            <th>Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $rank = 1;
                                        foreach ($top_products as $product): 
                                            // Determine rank badge class
                                            $rank_class = 'rank-other';
                                            if ($rank == 1) $rank_class = 'rank-1';
                                            elseif ($rank == 2) $rank_class = 'rank-2';
                                            elseif ($rank == 3) $rank_class = 'rank-3';
                                        ?>
                                        <tr>
                                            <td style="text-align: center;">
                                                <span class="rank-badge <?php echo $rank_class; ?>">
                                                    <?php echo $rank; ?>
                                                </span>
                                            </td>
                                            <td class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></td>
                                            <td class="size-info"><?php echo htmlspecialchars($product['size'] ?? 'N/A'); ?></td>
                                            <td class="quantity-sold"><?php echo number_format($product['total_quantity']); ?> units</td>
                                            <td class="revenue-amount">₱<?php echo number_format($product['total_revenue'], 2); ?></td>
                                        </tr>
                                        <?php 
                                            $rank++;
                                        endforeach; 
                                        ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="no-data-message">
                                    <div><i class="fas fa-box-open"></i></div>
                                    <p>No product sales data available for the selected period.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Charts Grid -->
                    <div class="grid-container charts-grid">
                        <!-- Monthly Sales Trend -->
                        <div class="chart-card monthly-trend">
                            <div class="chart-header"><i class="fas fa-chart-line me-2"></i> Monthly Sales Trend</div>
                            <div class="chart-body"><canvas id="monthlySalesChart"></canvas></div>
                        </div>
                        
                        <!-- Daily Sales Trend -->
                        <div class="chart-card daily-trend">
                            <div class="chart-header"><i class="fas fa-chart-area me-2"></i> Daily Sales Breakdown</div>
                            <div class="chart-body"><canvas id="dailySalesChart"></canvas></div>
                        </div>
                        
                        <!-- Product Performance -->
                        <div class="chart-card product-type">
                            <div class="chart-header"><i class="fas fa-chart-bar me-2"></i> Top Products</div>
                            <div class="chart-body"><canvas id="productTypeChart"></canvas></div>
                        </div>
                        
                        <!-- Product Category -->
                        <div class="chart-card product-category">
                            <div class="chart-header"><i class="fas fa-chart-pie me-2"></i> Product Categories</div>
                            <div class="chart-body"><canvas id="productCategoryChart"></canvas></div>
                        </div>
                        
                        <!-- Payment Methods -->
                        <div class="chart-card store-location">
                            <div class="chart-header"><i class="fas fa-credit-card me-2"></i> Payment Methods Distribution</div>
                            <div class="chart-body"><canvas id="storeLocationChart"></canvas></div>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <script>
        // Data from PHP
        const dailySalesData = <?php echo json_encode(array_reverse($daily_sales)); ?>;
        const topProductsData = <?php echo json_encode($top_products); ?>;
        const paymentMethodsData = <?php echo json_encode($payment_methods); ?>;
        const categoriesData = <?php echo json_encode($categories); ?>;

        // Activate selected period button
        document.addEventListener('DOMContentLoaded', function() {
            const periodParam = '<?php echo $period ?? ''; ?>';
            document.querySelectorAll('.period-btn').forEach(btn => btn.classList.remove('active'));
            if (periodParam) {
                const activeBtn = document.querySelector(`[data-period="${periodParam}"]`);
                if (activeBtn) activeBtn.classList.add('active');
            }
            
            // Clean URL for printing - remove parameters from title
            const originalTitle = document.title;
            window.addEventListener('beforeprint', function() {
                document.title = 'KUSSO - Sales Report';
            });
            window.addEventListener('afterprint', function() {
                document.title = originalTitle;
            });
        });

        // Chart color set
        const chartColors = {
            blue: '#3498db', green: '#2ecc71', red: '#e74c3c',
            purple: '#9b59b6', orange: '#f39c12', teal: '#1abc9c',
            pink: '#e91e63', indigo: '#6c5ce7',
            gradient: ['#3498db','#2ecc71','#e74c3c','#f39c12','#9b59b6','#1abc9c','#e91e63','#6c5ce7']
        };

        // Monthly Sales Chart
        new Chart(document.getElementById('monthlySalesChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: dailySalesData.slice(-30).map(item => new Date(item.sale_date).toLocaleDateString('en-US', {month: 'short', day: 'numeric'})),
                datasets: [{
                    label: 'Daily Sales',
                    data: dailySalesData.slice(-30).map(item => parseFloat(item.daily_total)),
                    borderColor: chartColors.blue,
                    backgroundColor: 'rgba(52,152,219,0.1)',
                    borderWidth: 3, fill: true, tension: 0.35,
                    pointBackgroundColor: chartColors.blue, pointBorderColor: '#fff', pointRadius: 4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: true, position: 'top' } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { callback: v => '₱' + v.toLocaleString() } },
                    x: { grid: { display: false }, ticks: { maxRotation: 45 } }
                }
            }
        });

        // Daily Sales Chart
        new Chart(document.getElementById('dailySalesChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: dailySalesData.slice(-7).map(item => new Date(item.sale_date).toLocaleDateString('en-US', {weekday: 'short'})),
                datasets: [{
                    label: 'Daily Revenue',
                    data: dailySalesData.slice(-7).map(item => parseFloat(item.daily_total)),
                    borderColor: chartColors.green, backgroundColor: 'rgba(46,204,113,0.12)',
                    borderWidth: 3, fill: true, tension: 0.3, pointRadius: 5
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: true, position: 'top' } },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: v => '₱' + v.toLocaleString() } },
                    x: { grid: { display: false }, ticks: { maxRotation: 0 } }
                }
            }
        });

        // Top Products Chart
        new Chart(document.getElementById('productTypeChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: topProductsData.slice(0,6).map(item => {
                    const productName = item.product_name;
                    const size = item.size || '';
                    const fullLabel = size ? `${productName} (${size})` : productName;
                    return fullLabel.length > 20 ? fullLabel.substring(0,20) + '...' : fullLabel;
                }),
                datasets: [{ label: 'Revenue', data: topProductsData.slice(0,6).map(item => parseFloat(item.total_revenue)), backgroundColor: chartColors.gradient.slice(0,6), borderWidth: 1 }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { callback: v => '₱' + v.toLocaleString() } } } }
        });

        // Product Category Chart
        new Chart(document.getElementById('productCategoryChart').getContext('2d'), {
            type: 'doughnut',
            data: { 
                labels: categoriesData.map(cat => cat.category_name), 
                datasets: [{ 
                    data: categoriesData.map(cat => parseFloat(cat.total_revenue)), 
                    backgroundColor: [chartColors.red, chartColors.purple, chartColors.orange, chartColors.teal, chartColors.pink, chartColors.blue, chartColors.green, chartColors.indigo], 
                    borderColor: '#fff', 
                    borderWidth: 2 
                }] 
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
        });

        // Payment Methods Chart
        new Chart(document.getElementById('storeLocationChart').getContext('2d'), {
            type: 'bar',
            data: { labels: paymentMethodsData.map(i => i.payment_type.charAt(0).toUpperCase() + i.payment_type.slice(1)), datasets: [{ label: 'Revenue by Payment', data: paymentMethodsData.map(i => parseFloat(i.total)), backgroundColor: [chartColors.indigo, chartColors.orange, chartColors.teal, chartColors.red].slice(0, paymentMethodsData.length) }] },
            options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', scales: { x: { beginAtZero: true, ticks: { callback: v => '₱' + v.toLocaleString() } } } }
        });

        // Utility functions
        function printReport() { window.print(); }
        
        function exportToExcel() {
            // Get PHP data
            const period = '<?php echo $period ?? 'custom'; ?>';
            const startDate = '<?php echo $start_date ?? ''; ?>';
            const endDate = '<?php echo $end_date ?? ''; ?>';
            const totalSales = <?php echo $total_sales; ?>;
            const totalOrders = <?php echo $total_orders; ?>;
            const avgOrder = <?php echo $avg_order; ?>;
            const totalQty = <?php echo $total_qty; ?>;
            
            // Helper function to format currency
            function formatCurrency(value) {
                return 'PHP ' + parseFloat(value).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            }
            
            // Helper function to format date
            function formatDate(dateStr) {
                const date = new Date(dateStr);
                return date.toLocaleDateString('en-US');
            }
            
            // Build HTML table with styling
            let html = `
<html xmlns:x="urn:schemas-microsoft-com:office:excel">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                    <x:Name>Sales Report</x:Name>
                    <x:WorksheetOptions>
                        <x:Print>
                            <x:ValidPrinterInfo/>
                        </x:Print>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <style>
        .header { background-color: #c67c4e; color: white; font-weight: bold; font-size: 18pt; text-align: center; padding: 10px; }
        .subheader { background-color: #c67c4e; color: white; font-size: 12pt; text-align: center; }
        .section-title { background-color: #c67c4e; color: white; font-weight: bold; font-size: 14pt; padding: 8px; }
        .label-cell { background-color: #f8f9fa; font-weight: bold; }
        .table-header { background-color: #c67c4e; color: white; font-weight: bold; text-align: center; border: 1px solid black; }
        .data-cell { border: 1px solid #ddd; padding: 5px; }
        .rank-1 { background-color: #ffd700; font-weight: bold; }
        .rank-2 { background-color: #c0c0c0; font-weight: bold; }
        .rank-3 { background-color: #cd7f32; color: white; font-weight: bold; }
        .total-revenue { color: #c67c4e; font-weight: bold; font-size: 14pt; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <table border="0" cellspacing="0" cellpadding="0" width="100%">
        <tr><td class="header" colspan="6">KUSSO SALES REPORT</td></tr>
        <tr><td class="subheader" colspan="6">Comprehensive Sales Performance Analysis</td></tr>
        <tr><td colspan="6">&nbsp;</td></tr>
        
        <tr><td class="section-title" colspan="6">REPORT INFORMATION</td></tr>
        <tr><td class="label-cell">Report Period</td><td colspan="5">${startDate} to ${endDate}</td></tr>
        <tr><td class="label-cell">Report Type</td><td colspan="5">${period.charAt(0).toUpperCase() + period.slice(1)} Overview</td></tr>
        <tr><td class="label-cell">Generated On</td><td colspan="5">${new Date().toLocaleDateString('en-US')} ${new Date().toLocaleTimeString('en-US')}</td></tr>
        <tr><td class="label-cell">Total Days</td><td colspan="5"><?php echo ceil($days_count); ?> days</td></tr>
        <tr><td colspan="6">&nbsp;</td></tr>
        
        <tr><td class="section-title" colspan="6">EXECUTIVE SUMMARY</td></tr>
        <tr><td class="label-cell">Total Revenue</td><td class="total-revenue" colspan="5">${formatCurrency(totalSales)}</td></tr>
        <tr><td class="label-cell">Total Transactions</td><td colspan="5">${totalOrders.toLocaleString()}</td></tr>
        <tr><td class="label-cell">Average Order Value</td><td colspan="5">${formatCurrency(avgOrder)}</td></tr>
        <tr><td class="label-cell">Total Items Sold</td><td colspan="5">${totalQty.toLocaleString()}</td></tr>
        <tr><td class="label-cell">Daily Average Revenue</td><td colspan="5">${formatCurrency(<?php echo $daily_average; ?>)}</td></tr>
        <tr><td colspan="6">&nbsp;</td></tr>
        
        <tr><td class="section-title" colspan="5">BEST SELLING PRODUCTS</td></tr>
        <tr>
            <td class="table-header">Rank</td>
            <td class="table-header">Product Name</td>
            <td class="table-header">Size</td>
            <td class="table-header">Quantity Sold</td>
            <td class="table-header">Revenue</td>
        </tr>`;
        
        topProductsData.forEach((product, index) => {
            let cellClass = 'data-cell';
            if (index === 0) cellClass = 'data-cell rank-1';
            else if (index === 1) cellClass = 'data-cell rank-2';
            else if (index === 2) cellClass = 'data-cell rank-3';
            
            html += `
        <tr>
            <td class="${cellClass} text-center">${index + 1}</td>
            <td class="${cellClass}">${product.product_name}</td>
            <td class="${cellClass} text-center">${product.size || 'N/A'}</td>
            <td class="${cellClass} text-right">${product.total_quantity}</td>
            <td class="${cellClass} text-right">${formatCurrency(product.total_revenue)}</td>
        </tr>`;
        });
        
        html += `
        <tr><td colspan="6">&nbsp;</td></tr>
        
        <tr><td class="section-title" colspan="6">PAYMENT METHODS DISTRIBUTION</td></tr>
        <tr>
            <td class="table-header" colspan="2">Payment Type</td>
            <td class="table-header" colspan="2">Transaction Count</td>
            <td class="table-header" colspan="2">Total Revenue</td>
        </tr>`;
        
        paymentMethodsData.forEach(pm => {
            const percentage = totalSales > 0 ? ((pm.total / totalSales) * 100).toFixed(2) : '0.00';
            html += `
        <tr>
            <td class="data-cell" colspan="2">${pm.payment_type.charAt(0).toUpperCase() + pm.payment_type.slice(1)} (${percentage}%)</td>
            <td class="data-cell text-right" colspan="2">${pm.count}</td>
            <td class="data-cell text-right" colspan="2">${formatCurrency(pm.total)}</td>
        </tr>`;
        });
        
        html += `
        <tr><td colspan="6">&nbsp;</td></tr>
        
        <tr><td class="section-title" colspan="6">DAILY SALES BREAKDOWN</td></tr>
        <tr>
            <td class="table-header" colspan="2">Date</td>
            <td class="table-header" colspan="2">Number of Orders</td>
            <td class="table-header" colspan="2">Daily Total Revenue</td>
        </tr>`;
        
        dailySalesData.forEach(item => {
            html += `
        <tr>
            <td class="data-cell" colspan="2">${formatDate(item.sale_date)}</td>
            <td class="data-cell text-right" colspan="2">${item.orders_count}</td>
            <td class="data-cell text-right" colspan="2">${formatCurrency(item.daily_total)}</td>
        </tr>`;
        });
        
        html += `
        <tr><td colspan="6">&nbsp;</td></tr>
        <tr><td class="section-title" colspan="6" style="text-align: center;">End of Report - Generated by KUSSO Sales Management System</td></tr>
    </table>
</body>
</html>`;
            
            // Create and download
            const blob = new Blob([html], {type: 'application/vnd.ms-excel'});
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `KUSSO_Sales_Report_${startDate}_to_${endDate}.xls`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
        
        function exportToCSV() {
            // Get PHP data
            const period = '<?php echo $period ?? 'custom'; ?>';
            const startDate = '<?php echo $start_date ?? ''; ?>';
            const endDate = '<?php echo $end_date ?? ''; ?>';
            const totalSales = <?php echo $total_sales; ?>;
            const totalOrders = <?php echo $total_orders; ?>;
            const avgOrder = <?php echo $avg_order; ?>;
            const totalQty = <?php echo $total_qty; ?>;
            
            // Helper function to format currency without symbol
            function formatCurrency(value) {
                return parseFloat(value).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            }
            
            // Helper function to format date properly
            function formatDate(dateStr) {
                const date = new Date(dateStr);
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                const year = date.getFullYear();
                return `${month}/${day}/${year}`;
            }
            
            // Build comprehensive CSV content
            let csvContent = '';
            
            // Header Section - Using = signs for separators
            csvContent += '==============================================================================================================\n';
            csvContent += ',,,KUSSO SALES REPORT\n';
            csvContent += ',,,Comprehensive Sales Performance Analysis\n';
            csvContent += '==============================================================================================================\n\n';
            
            // Report Information Section
            csvContent += 'REPORT INFORMATION\n';
            csvContent += '------------------------------------------------\n';
            csvContent += `Report Period:,${startDate} to ${endDate}\n`;
            csvContent += `Report Type:,${period.charAt(0).toUpperCase() + period.slice(1)} Overview\n`;
            csvContent += `Generated On:,${new Date().toLocaleDateString('en-US')} ${new Date().toLocaleTimeString('en-US')}\n`;
            csvContent += `Total Days:,<?php echo ceil($days_count); ?>\n\n`;
            
            // Executive Summary Section
            csvContent += 'EXECUTIVE SUMMARY\n';
            csvContent += '------------------------------------------------\n';
            csvContent += `Total Revenue:,PHP ${formatCurrency(totalSales)}\n`;
            csvContent += `Total Transactions:,${totalOrders.toLocaleString()}\n`;
            csvContent += `Average Order Value:,PHP ${formatCurrency(avgOrder)}\n`;
            csvContent += `Total Items Sold:,${totalQty.toLocaleString()}\n`;
            csvContent += `Daily Average Revenue:,PHP ${formatCurrency(<?php echo $daily_average; ?>)}\n\n`;
            
            // Best Selling Products Section
            csvContent += 'BEST SELLING PRODUCTS\n';
            csvContent += '------------------------------------------------\n';
            csvContent += 'Rank,Product Name,Quantity Sold,Revenue (PHP),Average Price (PHP),% of Total Sales\n';
            topProductsData.forEach((product, index) => {
                const percentage = totalSales > 0 ? ((product.total_revenue / totalSales) * 100).toFixed(2) : '0.00';
                const avgPrice = product.total_quantity > 0 ? (product.total_revenue / product.total_quantity) : 0;
                csvContent += `${index + 1},${product.product_name},${product.total_quantity},${formatCurrency(product.total_revenue)},${formatCurrency(avgPrice)},${percentage}%\n`;
            });
            csvContent += '\n';
            
            // Payment Methods Distribution Section
            csvContent += 'PAYMENT METHODS DISTRIBUTION\n';
            csvContent += '------------------------------------------------\n';
            csvContent += 'Payment Type,Transaction Count,Total Revenue (PHP),% of Total Sales\n';
            paymentMethodsData.forEach(pm => {
                const percentage = totalSales > 0 ? ((pm.total / totalSales) * 100).toFixed(2) : '0.00';
                csvContent += `${pm.payment_type.charAt(0).toUpperCase() + pm.payment_type.slice(1)},${pm.count},${formatCurrency(pm.total)},${percentage}%\n`;
            });
            csvContent += '\n';
            
            // Daily Sales Breakdown Section
            csvContent += 'DAILY SALES BREAKDOWN\n';
            csvContent += '------------------------------------------------\n';
            csvContent += 'Date,Number of Orders,Daily Total Revenue (PHP)\n';
            dailySalesData.forEach(item => {
                csvContent += `${formatDate(item.sale_date)},${item.orders_count},${formatCurrency(item.daily_total)}\n`;
            });
            csvContent += '\n';
            
            // Footer Section
            csvContent += '==============================================================================================================\n';
            csvContent += ',,,End of Report\n';
            csvContent += ',,,Generated by KUSSO Sales Management System\n';
            csvContent += '==============================================================================================================\n';
            
            // Create and download with proper UTF-8 BOM for Excel compatibility
            const BOM = '\uFEFF';
            const blob = new Blob([BOM + csvContent], {type: 'text/csv;charset=utf-8;'});
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `KUSSO_Sales_Report_${startDate}_to_${endDate}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        function changePeriod(period) {
            document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
            document.querySelector(`[data-period="${period}"]`).classList.add('active');

            // Use server date to avoid timezone issues
            let params = new URLSearchParams();
            params.append('period', period);
            
            // Let the server calculate the correct dates based on period
            window.location.href = `?${params.toString()}`;
        }

        // Handle chart resizing when sidebar toggles
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const body = document.body;
            
            // Function to resize all charts
            window.resizeAllCharts = function() {
                // Trigger window resize event to notify Chart.js
                window.dispatchEvent(new Event('resize'));
                
                // Get all canvas elements and trigger resize on their charts
                const canvases = document.querySelectorAll('canvas');
                canvases.forEach(canvas => {
                    if (canvas.chart) {
                        canvas.chart.resize();
                    }
                });
            };
            
            // Watch for sidebar toggle using MutationObserver to detect class changes
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        // Sidebar toggle happened, resize charts after animation
                        setTimeout(() => {
                            window.resizeAllCharts();
                        }, 150);
                    }
                });
            });
            
            // Observe body for class changes
            observer.observe(body, {
                attributes: true,
                attributeFilter: ['class']
            });
            
            // Also listen for window resize
            let resizeTimer;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(() => {
                    window.resizeAllCharts();
                }, 150);
            });
            
            // Initial chart resize
            setTimeout(() => {
                window.resizeAllCharts();
            }, 500);
        });
    </script>

    <?php include('includes/scripts.php'); ?>
</body>
</html>
