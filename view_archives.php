<?php 
session_start();
include('includes/config.php');
include('includes/auth.php');

// Allow only admin
checkAccess(['admin']);

// Handle delete archive request
if (isset($_POST['delete_archive'])) {
    $archive_id = intval($_POST['archive_id']);
    
    try {
        // Get archive info before deleting
        $info_stmt = $conn->prepare("SELECT archive_type FROM sales_archives WHERE id = ?");
        $info_stmt->execute([$archive_id]);
        $archive_info = $info_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Delete only the archive record, not the actual sales data
        $delete_stmt = $conn->prepare("DELETE FROM sales_archives WHERE id = ?");
        $delete_stmt->execute([$archive_id]);
        
        $_SESSION['archive_message'] = 'Archive deleted successfully. Sales data remains intact.';
        $_SESSION['archive_status'] = 'success';
    } catch (Exception $e) {
        $_SESSION['archive_message'] = 'Error deleting archive: ' . $e->getMessage();
        $_SESSION['archive_status'] = 'danger';
    }
    
    header('Location: view_archives.php');
    exit;
}

// Get filter parameters
$archive_type = isset($_GET['type']) ? $_GET['type'] : 'all';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;

// Validate limit to prevent SQL injection
$limit = max(1, min(1000, $limit)); // Between 1 and 1000

// Build query based on filter
$query = "SELECT * FROM sales_archives WHERE 1=1";
$params = [];

if ($archive_type !== 'all') {
    $query .= " AND archive_type = ?";
    $params[] = $archive_type;
}

$query .= " ORDER BY period_end DESC, archived_at DESC LIMIT " . $limit;

$stmt = $conn->prepare($query);
$stmt->execute($params);
$archives = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_query = "
    SELECT 
        archive_type,
        COUNT(*) as count,
        MIN(period_start) as oldest_date,
        MAX(period_end) as newest_date
    FROM sales_archives
    GROUP BY archive_type
";
$stats_stmt = $conn->query($stats_query);
$stats = $stats_stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize stats by type
$archive_stats = [
    'daily' => ['count' => 0, 'oldest_date' => null, 'newest_date' => null],
    'weekly' => ['count' => 0, 'oldest_date' => null, 'newest_date' => null],
    'monthly' => ['count' => 0, 'oldest_date' => null, 'newest_date' => null]
];

foreach ($stats as $stat) {
    $archive_stats[$stat['archive_type']] = $stat;
}
?>

<?php include('includes/header.php'); ?>
<?php include('includes/navbar.php'); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>KUSSO - View Sales Archives</title>
    <link href="css/styles.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <link href="https://use.fontawesome.com/releases/v6.3.0/css/all.css" rel="stylesheet" />
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #f1f3f4 100%);
        }
        
        .archive-badge {
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 700;
            border-radius: 0.25rem;
        }
        .badge-daily { background: linear-gradient(135deg, #c67c4e 0%, #b8704a 100%); color: white; }
        .badge-weekly { background: linear-gradient(135deg, #8a6a4a 0%, #7a5a3a 100%); color: white; }
        .badge-monthly { background: linear-gradient(135deg, #d4925c 0%, #c67c4e 100%); color: white; }
        
        .stat-card {
            background: linear-gradient(145deg, #ffffff 0%, #fefefe 100%);
            border: 1px solid #e8ecef;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(198, 124, 78, 0.08);
            transition: all 0.3s ease;
            border-left: 4px solid;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(198, 124, 78, 0.15);
        }
        .stat-card.daily { border-left-color: #c67c4e; }
        .stat-card.weekly { border-left-color: #8a6a4a; }
        .stat-card.monthly { border-left-color: #d4925c; }
        
        .stat-card h2 {
            background: linear-gradient(135deg, #c67c4e, #d4925c);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-card h5 {
            color: #c67c4e;
            font-weight: 600;
        }
        
        .card {
            background: linear-gradient(145deg, #ffffff 0%, #fdfdfd 100%);
            border: 1px solid #e8ecef;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(198, 124, 78, 0.06);
        }
        
        .card-header {
            background: linear-gradient(135deg, #c67c4e 0%, #b8704a 100%);
            color: white;
            font-weight: 600;
            border-radius: 8px 8px 0 0 !important;
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
        
        .btn-secondary {
            background: linear-gradient(135deg, #8a6a4a 0%, #7a5a3a 100%);
            border: none;
        }
        .btn-secondary:hover {
            background: linear-gradient(135deg, #7a5a3a 0%, #6a4a2a 100%);
        }
        
        .btn-info {
            background: linear-gradient(135deg, #d4925c 0%, #c67c4e 100%);
            border: none;
            color: white;
        }
        .btn-info:hover {
            background: linear-gradient(135deg, #c67c4e 0%, #b8704a 100%);
            color: white;
        }
        
        .modal-header {
            background: linear-gradient(135deg, #c67c4e 0%, #b8704a 100%);
            color: white;
        }
        
        .modal-body table {
            font-size: 0.9rem;
        }
        
        .table thead {
            background: linear-gradient(135deg, #c67c4e 0%, #b8704a 100%);
            color: white;
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
        
        /* Adjust layout for better width utilization - FLEXBOX APPROACH */
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
        
        /* Make statistics cards more compact */
        .stat-card .card-body {
            padding: 1rem;
        }
        
        /* Ensure cards take full width */
        .card {
            width: 100%;
        }
        
        .row {
            margin-left: -0.75rem;
            margin-right: -0.75rem;
        }
        
        .row > [class*='col-'] {
            padding-left: 0.75rem;
            padding-right: 0.75rem;
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
                    <h1 class="mt-4">Sales Archives</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="sales_report.php">Sales Report</a></li>
                        <li class="breadcrumb-item active">Archives</li>
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

                    <!-- Statistics Cards -->
                    <div class="row mb-4 g-3">
                        <div class="col-xl col-lg-4 col-md-6">
                            <div class="card stat-card daily h-100">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <span class="archive-badge badge-daily">DAILY</span>
                                    </h5>
                                    <h2 class="mb-0"><?php echo $archive_stats['daily']['count']; ?></h2>
                                    <p class="text-muted small mb-0">Archives</p>
                                    <?php if ($archive_stats['daily']['count'] > 0): ?>
                                        <p class="small mb-0 mt-2">
                                            <strong>Range:</strong> 
                                            <?php echo date('M d, Y', strtotime($archive_stats['daily']['oldest_date'])); ?> - 
                                            <?php echo date('M d, Y', strtotime($archive_stats['daily']['newest_date'])); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl col-lg-4 col-md-6">
                            <div class="card stat-card weekly h-100">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <span class="archive-badge badge-weekly">WEEKLY</span>
                                    </h5>
                                    <h2 class="mb-0"><?php echo $archive_stats['weekly']['count']; ?></h2>
                                    <p class="text-muted small mb-0">Archives</p>
                                    <?php if ($archive_stats['weekly']['count'] > 0): ?>
                                        <p class="small mb-0 mt-2">
                                            <strong>Range:</strong> 
                                            <?php echo date('M d, Y', strtotime($archive_stats['weekly']['oldest_date'])); ?> - 
                                            <?php echo date('M d, Y', strtotime($archive_stats['weekly']['newest_date'])); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl col-lg-4 col-md-6">
                            <div class="card stat-card monthly h-100">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <span class="archive-badge badge-monthly">MONTHLY</span>
                                    </h5>
                                    <h2 class="mb-0"><?php echo $archive_stats['monthly']['count']; ?></h2>
                                    <p class="text-muted small mb-0">Archives</p>
                                    <?php if ($archive_stats['monthly']['count'] > 0): ?>
                                        <p class="small mb-0 mt-2">
                                            <strong>Range:</strong> 
                                            <?php echo date('M d, Y', strtotime($archive_stats['monthly']['oldest_date'])); ?> - 
                                            <?php echo date('M d, Y', strtotime($archive_stats['monthly']['newest_date'])); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Best Performance Card -->
                        <div class="col-xl col-lg-6 col-md-6">
                            <div class="card h-100" style="background: linear-gradient(145deg, #8a6a4a 0%, #7a5a3a 100%); color: white; border: none;">
                                <div class="card-body text-center">
                                    <h6 class="mb-2" style="color: white; opacity: 0.9;">
                                        <i class="fas fa-trophy me-2"></i>BEST PERIOD
                                    </h6>
                                    <?php
                                    $best_query = "SELECT archive_type, period_start, period_end, total_sales FROM sales_archives ORDER BY total_sales DESC LIMIT 1";
                                    $best_result = $conn->query($best_query);
                                    $best_period = $best_result->fetch(PDO::FETCH_ASSOC);
                                    
                                    // Format period based on type
                                    $period_label = '';
                                    if ($best_period) {
                                        if ($best_period['archive_type'] === 'monthly') {
                                            $period_label = date('F Y', strtotime($best_period['period_start']));
                                        } elseif ($best_period['archive_type'] === 'weekly') {
                                            $period_label = date('M d', strtotime($best_period['period_start'])) . ' - ' . date('M d, Y', strtotime($best_period['period_end']));
                                        } else {
                                            $period_label = date('M d, Y', strtotime($best_period['period_start']));
                                        }
                                    }
                                    ?>
                                    <h3 class="mb-2" style="color: white; font-weight: 700;">
                                        <?php echo $best_period ? strtoupper($best_period['archive_type']) : 'N/A'; ?>
                                    </h3>
                                    <p class="small mb-0" style="opacity: 0.9;">Highest Sales</p>
                                    <?php if ($period_label): ?>
                                        <p class="small mb-0 mt-1" style="opacity: 0.85; font-style: italic;">
                                            <?php echo $period_label; ?>
                                        </p>
                                    <?php endif; ?>
                                    <hr style="border-color: rgba(255,255,255,0.3); margin: 1rem 0;">
                                    <p class="small mb-0">
                                        <strong>Revenue:</strong><br>
                                        ₱<?php echo number_format($best_period['total_sales'] ?? 0, 2); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions and Info Row -->
                    <div class="row mb-4 g-3">
                        <!-- Quick Actions Card -->
                        <div class="col-xl col-lg-6 col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <i class="fas fa-bolt me-1"></i>
                                    Quick Actions
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-3">
                                        <a href="archive_sales.php" class="btn btn-primary btn-lg">
                                            <i class="fas fa-plus-circle me-2"></i> Create New Archive
                                        </a>
                                        <a href="sales_report.php" class="btn btn-secondary btn-lg">
                                            <i class="fas fa-chart-line me-2"></i> View Current Sales Report
                                        </a>
                                        <button onclick="location.href='archive_sales.php#cleanup'" class="btn btn-warning btn-lg">
                                            <i class="fas fa-broom me-2"></i> Run Cleanup Old Data
                                        </button>
                                    </div>
                                    <hr class="my-3">
                                    <div class="small text-muted">
                                        <p class="mb-2"><i class="fas fa-info-circle me-2"></i><strong>Tip:</strong> Archive data regularly to maintain system performance</p>
                                        <p class="mb-0"><i class="fas fa-calendar-check me-2"></i><strong>Last Archive:</strong> 
                                            <?php 
                                            $last_archive_query = "SELECT MAX(archived_at) as last_date FROM sales_archives";
                                            $last_archive_result = $conn->query($last_archive_query);
                                            $last_archive = $last_archive_result->fetch(PDO::FETCH_ASSOC);
                                            echo $last_archive['last_date'] ? date('M d, Y h:i A', strtotime($last_archive['last_date'])) : 'No archives yet';
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Archive Information Card -->
                        <div class="col-xl col-lg-6 col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Archive Information
                                </div>
                                <div class="card-body">
                                    <h6 class="text-muted mb-3">Data Retention Policy</h6>
                                    <div class="row mb-3">
                                        <div class="col-4 text-center">
                                            <div class="p-3 rounded" style="background: rgba(198, 124, 78, 0.1);">
                                                <h3 class="mb-1" style="color: #c67c4e;">7</h3>
                                                <small class="text-muted">Days</small>
                                                <p class="mb-0 mt-2 small"><strong>Daily</strong></p>
                                            </div>
                                        </div>
                                        <div class="col-4 text-center">
                                            <div class="p-3 rounded" style="background: rgba(138, 106, 74, 0.1);">
                                                <h3 class="mb-1" style="color: #8a6a4a;">30</h3>
                                                <small class="text-muted">Days</small>
                                                <p class="mb-0 mt-2 small"><strong>Weekly</strong></p>
                                            </div>
                                        </div>
                                        <div class="col-4 text-center">
                                            <div class="p-3 rounded" style="background: rgba(212, 146, 92, 0.1);">
                                                <h3 class="mb-1" style="color: #d4925c;">180</h3>
                                                <small class="text-muted">Days</small>
                                                <p class="mb-0 mt-2 small"><strong>Monthly</strong></p>
                                            </div>
                                        </div>
                                    </div>
                                    <hr>
                                    <h6 class="text-muted mb-3">What Gets Archived?</h6>
                                    <ul class="small mb-0">
                                        <li>Total sales and order counts</li>
                                        <li>Top-selling products and revenue</li>
                                        <li>Payment method statistics</li>
                                        <li>Daily sales breakdown</li>
                                        <li>Category performance data</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Storage & System Info Card -->
                        <div class="col-xl col-lg-6 col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <i class="fas fa-server me-1"></i>
                                    System Status
                                </div>
                                <div class="card-body">
                                    <?php
                                    // Get database size
                                    $size_query = "SELECT 
                                        COUNT(*) as total_records,
                                        SUM(LENGTH(sales_data)) as data_size
                                        FROM sales_archives";
                                    $size_result = $conn->query($size_query);
                                    $size_info = $size_result->fetch(PDO::FETCH_ASSOC);
                                    $data_size_mb = ($size_info['data_size'] ?? 0) / 1024 / 1024;
                                    ?>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="small"><i class="fas fa-database me-2 text-primary"></i>Storage Used</span>
                                            <strong><?php echo number_format($data_size_mb, 2); ?> MB</strong>
                                        </div>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar" role="progressbar" 
                                                 style="width: <?php echo min(100, ($data_size_mb / 10) * 100); ?>%; background: linear-gradient(90deg, #c67c4e, #d4925c);">
                                            </div>
                                        </div>
                                        <small class="text-muted">of ~10 MB recommended</small>
                                    </div>
                                    
                                    <hr>
                                    
                                    <div class="small">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span><i class="fas fa-folder me-2 text-warning"></i>Total Records</span>
                                            <strong><?php echo number_format($size_info['total_records'] ?? 0); ?></strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span><i class="fas fa-clock me-2 text-info"></i>Auto Cleanup</span>
                                            <strong>Enabled</strong>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span><i class="fas fa-shield-alt me-2 text-success"></i>Status</span>
                                            <strong class="text-success">Active</strong>
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    
                                    <div class="small text-muted">
                                        <p class="mb-1"><strong>Retention Policy:</strong></p>
                                        <ul class="mb-0 ps-3">
                                            <li>Daily: Auto-deleted after <strong>7 days</strong></li>
                                            <li>Weekly: Auto-deleted after <strong>30 days</strong></li>
                                            <li>Monthly: Auto-deleted after <strong>6 months</strong></li>
                                        </ul>
                                        <p class="mb-0 mt-2"><small><i class="fas fa-info-circle me-1"></i>Cleanup runs automatically when archiving</small></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Controls -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-filter me-1"></i>
                            Filter Archives
                        </div>
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-4">
                                    <label for="type" class="form-label">Archive Type</label>
                                    <select class="form-select" name="type" id="type">
                                        <option value="all" <?php echo $archive_type === 'all' ? 'selected' : ''; ?>>All Types</option>
                                        <option value="daily" <?php echo $archive_type === 'daily' ? 'selected' : ''; ?>>Daily</option>
                                        <option value="weekly" <?php echo $archive_type === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                        <option value="monthly" <?php echo $archive_type === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="limit" class="form-label">Results Limit</label>
                                    <select class="form-select" name="limit" id="limit">
                                        <option value="25" <?php echo $limit === 25 ? 'selected' : ''; ?>>25</option>
                                        <option value="50" <?php echo $limit === 50 ? 'selected' : ''; ?>>50</option>
                                        <option value="100" <?php echo $limit === 100 ? 'selected' : ''; ?>>100</option>
                                        <option value="500" <?php echo $limit === 500 ? 'selected' : ''; ?>>500</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">&nbsp;</label>
                                    <div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search me-1"></i> Apply Filter
                                        </button>
                                        <a href="view_archives.php" class="btn btn-secondary">
                                            <i class="fas fa-redo me-1"></i> Reset
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Archives Table -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-table me-1"></i>
                            Archived Sales Data
                        </div>
                        <div class="card-body">
                            <table id="archivesTable" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Period</th>
                                        <th>Total Orders</th>
                                        <th>Total Sales</th>
                                        <th>Avg Order</th>
                                        <th>Top Product</th>
                                        <th>Archived At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($archives as $archive): ?>
                                    <tr>
                                        <td>
                                            <span class="archive-badge badge-<?php echo $archive['archive_type']; ?>">
                                                <?php echo strtoupper($archive['archive_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            echo date('M d, Y', strtotime($archive['period_start']));
                                            if ($archive['period_start'] !== $archive['period_end']) {
                                                echo ' - ' . date('M d, Y', strtotime($archive['period_end']));
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo number_format($archive['total_orders']); ?></td>
                                        <td>₱<?php echo number_format($archive['total_sales'], 2); ?></td>
                                        <td>₱<?php echo number_format($archive['avg_order_value'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($archive['top_product']); ?></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($archive['archived_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info view-details" 
                                                    data-id="<?php echo $archive['id']; ?>"
                                                    data-type="<?php echo $archive['archive_type']; ?>"
                                                    data-start="<?php echo $archive['period_start']; ?>"
                                                    data-end="<?php echo $archive['period_end']; ?>"
                                                    data-sales='<?php echo htmlspecialchars($archive['sales_data']); ?>'>
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button class="btn btn-sm btn-danger delete-archive" 
                                                    data-id="<?php echo $archive['id']; ?>"
                                                    data-type="<?php echo $archive['archive_type']; ?>"
                                                    data-period="<?php echo date('M d, Y', strtotime($archive['period_start'])); ?>"
                                                    title="Delete this archive record (sales data will remain)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
            <?php include('includes/footer.php'); ?>
        </div>
    </div>

    <!-- Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailsModalLabel">Archive Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalBody">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?php include('includes/scripts.php'); ?>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#archivesTable').DataTable({
                order: [[6, 'desc']],
                pageLength: 25
            });

            // View details button - using event delegation
            $(document).on('click', '.view-details', function() {
                const salesData = JSON.parse($(this).attr('data-sales'));
                const type = $(this).attr('data-type');
                const start = $(this).attr('data-start');
                const end = $(this).attr('data-end');
                
                // Fetch expenses for this period
                $.ajax({
                    url: 'get_archive_expenses.php',
                    method: 'GET',
                    data: { start_date: start, end_date: end },
                    dataType: 'json',
                    success: function(expensesData) {
                        displayArchiveDetails(salesData, type, start, end, expensesData);
                    },
                    error: function() {
                        // If expenses fetch fails, show without expenses
                        displayArchiveDetails(salesData, type, start, end, { total: 0, list: [] });
                    }
                });
            });
            
            function displayArchiveDetails(salesData, type, start, end, expensesData) {
                const totalSales = parseFloat(salesData.summary.total_sales || 0);
                const totalExpenses = parseFloat(expensesData.total || 0);
                const netProfit = totalSales - totalExpenses;
                const profitMargin = totalSales > 0 ? (netProfit / totalSales) * 100 : 0;
                
                let html = `
                    <h6 class="mb-3">
                        <span class="archive-badge badge-${type}">${type.toUpperCase()}</span>
                        Period: ${new Date(start).toLocaleDateString()} - ${new Date(end).toLocaleDateString()}
                    </h6>
                    
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h6 class="text-muted">Total Orders</h6>
                                    <h4>${salesData.summary.total_orders || 0}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h6 class="text-muted">Total Sales</h6>
                                    <h4>₱${totalSales.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h6 class="text-muted">Total Expenses</h6>
                                    <h4 class="text-danger">₱${totalExpenses.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card" style="background: linear-gradient(145deg, #d4edda 0%, #c3e6cb 100%);">
                                <div class="card-body text-center">
                                    <h6 class="text-success">Net Profit</h6>
                                    <h4 class="text-success">₱${netProfit.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</h4>
                                    <small class="text-success">Margin: ${profitMargin.toFixed(1)}%</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    ${expensesData.list && expensesData.list.length > 0 ? `
                    <h6 class="mb-3 mt-4">
                        <i class="fas fa-money-bill-wave me-2"></i>Expenses Breakdown
                    </h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm table-striped">
                            <thead style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white;">
                                <tr>
                                    <th style="border: none; padding: 12px;">Expense Name</th>
                                    <th class="text-center" style="border: none; padding: 12px;">Date</th>
                                    <th class="text-end" style="border: none; padding: 12px;">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${expensesData.list.map((expense, index) => {
                                    const rowClass = index % 2 === 0 ? 'style="background-color: #f8f9fa;"' : '';
                                    return `
                                        <tr ${rowClass}>
                                            <td style="padding: 10px;"><strong>${expense.expense_name}</strong></td>
                                            <td class="text-center" style="padding: 10px;">${new Date(expense.expense_date).toLocaleDateString()}</td>
                                            <td class="text-end" style="padding: 10px; font-weight: 600;">₱${parseFloat(expense.amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                        </tr>
                                    `;
                                }).join('')}
                            </tbody>
                            <tfoot style="background-color: #f8f9fa; font-weight: bold;">
                                <tr>
                                    <td colspan="2" class="text-end" style="padding: 12px;">TOTAL EXPENSES:</td>
                                    <td class="text-end text-danger" style="padding: 12px;">₱${totalExpenses.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    ` : ''}
                    
                    <h6 class="mb-3">Daily Breakdown</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Orders</th>
                                    <th>Total Sales</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                if (salesData.daily_breakdown && salesData.daily_breakdown.length > 0) {
                    salesData.daily_breakdown.forEach(day => {
                        html += `
                            <tr>
                                <td>${new Date(day.sale_date).toLocaleDateString()}</td>
                                <td>${day.orders_count}</td>
                                <td>₱${parseFloat(day.daily_total).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                            </tr>
                        `;
                    });
                } else {
                    html += '<tr><td colspan="3" class="text-center">No data available</td></tr>';
                }
                
                html += `
                            </tbody>
                        </table>
                    </div>
                    
                    <h6 class="mb-3 mt-4">
                        <i class="fas fa-box me-2"></i>Products Sold
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead style="background: linear-gradient(135deg, #c67c4e 0%, #b8704a 100%); color: white;">
                                <tr>
                                    <th style="border: none; padding: 12px;">Product Name</th>
                                    <th class="text-center" style="border: none; padding: 12px;">Quantity Sold</th>
                                    <th class="text-end" style="border: none; padding: 12px;">Total Revenue</th>
                                    <th class="text-center" style="border: none; padding: 12px;">Orders</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                if (salesData.products_sold && salesData.products_sold.length > 0) {
                    salesData.products_sold.forEach((product, index) => {
                        const rowClass = index % 2 === 0 ? 'style="background-color: #f8f9fa;"' : '';
                        html += `
                            <tr ${rowClass}>
                                <td style="padding: 10px;"><strong>${product.product_name}</strong></td>
                                <td class="text-center" style="padding: 10px;">${product.total_quantity} pcs</td>
                                <td class="text-end" style="padding: 10px; font-weight: 600;">₱${parseFloat(product.total_revenue).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                <td class="text-center" style="padding: 10px;">${product.order_count}</td>
                            </tr>
                        `;
                    });
                } else {
                    html += '<tr><td colspan="4" class="text-center text-muted" style="padding: 20px;">No products sold during this period</td></tr>';
                }
                
                html += `
                            </tbody>
                        </table>
                    </div>
                `;
                
                $('#modalBody').html(html);
                $('#detailsModal').modal('show');
            }
            
            // Delete archive button - using event delegation
            $(document).on('click', '.delete-archive', function() {
                const archiveId = $(this).attr('data-id');
                const archiveType = $(this).attr('data-type');
                const archivePeriod = $(this).attr('data-period');
                
                if (confirm(`Are you sure you want to delete this ${archiveType.toUpperCase()} archive for ${archivePeriod}?\n\nNote: This will only delete the archive record. Your actual sales data will remain intact.`)) {
                    // Create a form and submit it
                    const form = $('<form>', {
                        'method': 'POST',
                        'action': 'view_archives.php'
                    });
                    
                    form.append($('<input>', {
                        'type': 'hidden',
                        'name': 'delete_archive',
                        'value': '1'
                    }));
                    
                    form.append($('<input>', {
                        'type': 'hidden',
                        'name': 'archive_id',
                        'value': archiveId
                    }));
                    
                    $('body').append(form);
                    form.submit();
                }
            });
        });
    </script>
</body>
</html>
