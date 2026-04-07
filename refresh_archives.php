<?php
/**
 * Refresh/Update Existing Archives with Products Data
 * This updates old archives to include products_sold information
 */

session_start();
include('includes/config.php');
include('includes/auth.php');

checkAccess(['admin']);

if (isset($_POST['refresh'])) {
    $archive_id = intval($_POST['archive_id']);
    
    try {
        // Get the archive
        $stmt = $conn->prepare("SELECT * FROM sales_archives WHERE id = ?");
        $stmt->execute([$archive_id]);
        $archive = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($archive) {
            // Get all products sold with quantities for this period
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
            
            $products_stmt = $conn->prepare($all_products_query);
            $products_stmt->execute([$archive['period_start'], $archive['period_end']]);
            $all_products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get existing sales data and update it
            $sales_data = json_decode($archive['sales_data'], true);
            $sales_data['products_sold'] = $all_products;
            
            // Update the archive
            $update_stmt = $conn->prepare("UPDATE sales_archives SET sales_data = ? WHERE id = ?");
            $update_stmt->execute([json_encode($sales_data), $archive_id]);
            
            $_SESSION['message'] = "Archive #{$archive_id} updated successfully with " . count($all_products) . " products!";
            $_SESSION['message_type'] = 'success';
        }
    } catch (Exception $e) {
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }
    
    header('Location: refresh_archives.php');
    exit;
}

// Get all archives
$stmt = $conn->query("SELECT * FROM sales_archives ORDER BY period_end DESC");
$archives = $stmt->fetchAll(PDO::FETCH_ASSOC);

include('includes/header.php');
include('includes/navbar.php');
?>

<!DOCTYPE html>
<html>
<head>
    <title>Refresh Archives - KUSSO</title>
    <link href="css/styles.css" rel="stylesheet" />
    <style>
        .needs-update { background-color: #fff3cd; }
        .updated { background-color: #d1e7dd; }
    </style>
</head>
<body class="sb-nav-fixed">
    <div id="layoutSidenav">
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Refresh Archives with Products Data</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="view_archives.php">Archives</a></li>
                        <li class="breadcrumb-item active">Refresh</li>
                    </ol>

                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show">
                            <?php 
                            echo $_SESSION['message']; 
                            unset($_SESSION['message']);
                            unset($_SESSION['message_type']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-sync me-1"></i>
                            Archives Status
                        </div>
                        <div class="card-body">
                            <p>Click "Refresh" to update archives with products sold data.</p>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Type</th>
                                            <th>Period</th>
                                            <th>Total Sales</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($archives as $archive): 
                                            $sales_data = json_decode($archive['sales_data'], true);
                                            $has_products = isset($sales_data['products_sold']) && !empty($sales_data['products_sold']);
                                        ?>
                                        <tr class="<?php echo $has_products ? 'updated' : 'needs-update'; ?>">
                                            <td><?php echo $archive['id']; ?></td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo strtoupper($archive['archive_type']); ?></span>
                                            </td>
                                            <td>
                                                <?php 
                                                echo date('M d, Y', strtotime($archive['period_start']));
                                                if ($archive['period_start'] !== $archive['period_end']) {
                                                    echo ' - ' . date('M d, Y', strtotime($archive['period_end']));
                                                }
                                                ?>
                                            </td>
                                            <td>₱<?php echo number_format($archive['total_sales'], 2); ?></td>
                                            <td>
                                                <?php if ($has_products): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check"></i> Has Products (<?php echo count($sales_data['products_sold']); ?>)
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-exclamation-triangle"></i> Missing Products
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="archive_id" value="<?php echo $archive['id']; ?>">
                                                    <button type="submit" name="refresh" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-sync"></i> Refresh
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> This will update archives to include detailed product sales information. 
                        Archives that already have product data are shown in green.
                    </div>
                </div>
            </main>
            <?php include('includes/footer.php'); ?>
        </div>
    </div>
    <?php include('includes/scripts.php'); ?>
</body>
</html>
