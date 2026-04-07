<?php
/**
 * Archive Statistics Widget
 * This can be included in the dashboard to show archive status
 */

// Include this file in your dashboard/index page
// require_once('archive_stats_widget.php');

if (!isset($conn)) {
    include('includes/config.php');
}

try {
    // Get archive statistics
    $stats_query = "
        SELECT 
            archive_type,
            COUNT(*) as count,
            MAX(period_end) as latest_archive,
            SUM(total_sales) as total_archived_sales
        FROM sales_archives
        GROUP BY archive_type
    ";
    $stats_stmt = $conn->query($stats_query);
    $archive_stats = $stats_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count
    $total_query = "SELECT COUNT(*) as total FROM sales_archives";
    $total_stmt = $conn->query($total_query);
    $total_archives = $total_stmt->fetchColumn();
    
    $recent_logs = [];
    
} catch (Exception $e) {
    $archive_stats = [];
    $total_archives = 0;
    $recent_logs = [];
}
?>

<div class="row">
    <div class="col-xl-12">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-archive me-1"></i>
                Sales Archive Status
                <a href="view_archives.php" class="btn btn-sm btn-warning float-end">
                    <i class="fas fa-eye me-1"></i> View All Archives
                </a>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center p-3 border rounded">
                            <h2 class="text-primary"><?php echo $total_archives; ?></h2>
                            <p class="text-muted mb-0">Total Archives</p>
                        </div>
                    </div>
                    
                    <?php 
                    $archive_types = ['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'];
                    $archive_data = [];
                    foreach ($archive_stats as $stat) {
                        $archive_data[$stat['archive_type']] = $stat;
                    }
                    
                    foreach ($archive_types as $type => $label):
                        $data = isset($archive_data[$type]) ? $archive_data[$type] : null;
                    ?>
                    <div class="col-md-3">
                        <div class="text-center p-3 border rounded">
                            <h2 class="text-success"><?php echo $data ? $data['count'] : 0; ?></h2>
                            <p class="text-muted mb-1"><?php echo $label; ?></p>
                            <?php if ($data && $data['latest_archive']): ?>
                                <small class="text-muted">
                                    Latest: <?php echo date('M d, Y', strtotime($data['latest_archive'])); ?>
                                </small>
                            <?php else: ?>
                                <small class="text-muted">No archives</small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (!empty($recent_logs)): ?>
                <div class="mt-3">
                    <h6 class="text-muted mb-2">Recent Activity</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Operation</th>
                                    <th>Records</th>
                                    <th>Message</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_logs as $log): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $log['archive_type'] === 'daily' ? 'primary' : 
                                                ($log['archive_type'] === 'weekly' ? 'success' : 'danger'); 
                                        ?>">
                                            <?php echo strtoupper($log['archive_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo ucfirst($log['operation']); ?></td>
                                    <td><?php echo $log['records_affected']; ?></td>
                                    <td class="small"><?php echo htmlspecialchars(substr($log['message'], 0, 50)); ?></td>
                                    <td class="small"><?php echo date('M d, H:i', strtotime($log['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="mt-3 d-flex gap-2">
                    <a href="archive_sales.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-1"></i> Create Archive
                    </a>
                    <a href="view_archives.php" class="btn btn-sm btn-info">
                        <i class="fas fa-list me-1"></i> View Archives
                    </a>
                    <form method="POST" action="archive_sales.php" class="d-inline">
                        <button type="submit" name="cleanup" class="btn btn-sm btn-warning">
                            <i class="fas fa-broom me-1"></i> Run Cleanup
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.border.rounded {
    transition: all 0.3s ease;
}
.border.rounded:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}
</style>
