<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include 'includes/auth.php';
include 'includes/config.php';

// Check if user is admin
checkAccess(['admin']);

// Handle file download - MUST be before any HTML output
if (isset($_GET['download']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $filename = basename($_GET['download']); // Sanitize filename
    $filepath = 'backups' . DIRECTORY_SEPARATOR . $filename;
    $backupDir = realpath('backups');
    $fullPath = realpath($filepath);

    if ($fullPath && $backupDir && file_exists($filepath) && strpos($fullPath, $backupDir) === 0) {
        // Clear any existing output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Send headers for file download
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Send the file in chunks to handle large files
        $chunkSize = 8192; // 8KB chunks
        $handle = fopen($filepath, 'rb');
        if ($handle) {
            while (!feof($handle)) {
                echo fread($handle, $chunkSize);
                flush();
            }
            fclose($handle);
        }
        exit;
    }
    // Don't set error, just redirect silently
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

include 'includes/header.php';
include 'includes/navbar.php';

// Handle file deletion
if (isset($_GET['delete']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $filename = basename($_GET['delete']);
    $filepath = 'backups' . DIRECTORY_SEPARATOR . $filename;
    $backupDir = realpath('backups');
    $fullPath = realpath($filepath);

    if ($fullPath && $backupDir && file_exists($filepath) && strpos($fullPath, $backupDir) === 0) {
        if (unlink($filepath)) {
            $_SESSION['backup_success'] = "Backup file deleted successfully";
        } else {
            $_SESSION['backup_error'] = "Failed to delete backup file";
        }
    }
    // Redirect to clear GET parameters
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Get list of backups
$backupDir = 'backups';
$backups = [];
if (is_dir($backupDir)) {
    $files = scandir($backupDir, SCANDIR_SORT_DESCENDING);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'zip') {
            $backups[] = [
                'name' => $file,
                'size' => filesize($backupDir . DIRECTORY_SEPARATOR . $file),
                'date' => filemtime($backupDir . DIRECTORY_SEPARATOR . $file)
            ];
        }
    }
}

// Helper function to format bytes
function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}
?>

<style type="text/css">
    .backup-container {
        max-width: 1000px;
        margin: 20px auto;
    }
    .backup-card {
        border-left: 5px solid #c67c4e;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    .backup-item {
        padding: 15px;
        border-bottom: 1px solid #e0e0e0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .backup-item:last-child {
        border-bottom: none;
    }
    .backup-info {
        flex-grow: 1;
    }
    .backup-name {
        font-weight: 500;
        color: #333;
    }
    .backup-meta {
        font-size: 0.9rem;
        color: #666;
        margin-top: 5px;
    }
    .backup-actions {
        display: flex;
        gap: 10px;
    }
    .btn-download {
        background-color: #c67c4e;
        color: white;
        border: none;
    }
    .btn-download:hover {
        background-color: #a85a3a;
        color: white;
    }
    .btn-delete {
        background-color: #dc3545;
        color: white;
        border: none;
    }
    .btn-delete:hover {
        background-color: #c82333;
        color: white;
    }
    .alert-custom {
        border-left: 5px solid #c67c4e;
    }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 30px;
    }
    .stat-card {
        background: linear-gradient(135deg, #c67c4e, #a85a3a);
        color: white;
        padding: 20px;
        border-radius: 8px;
        text-align: center;
    }
    .stat-value {
        font-size: 2rem;
        font-weight: bold;
    }
    .stat-label {
        font-size: 0.9rem;
        opacity: 0.9;
    }
    .progress-modal-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        z-index: 9998;
        pointer-events: none;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease, visibility 0.3s ease;
    }
    .progress-modal-backdrop.active {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }
    .progress-modal {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 9999;
        pointer-events: none;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease, visibility 0.3s ease;
    }
    .progress-modal.active {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }
    body.backup-modal-shown {
        overflow: hidden;
    }
    .progress-modal-content {
        background: white;
        border-radius: 8px;
        padding: 30px;
        text-align: center;
        min-width: 400px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
    }
    .spinner {
        margin-bottom: 20px;
    }
    .spinner-border {
        width: 60px;
        height: 60px;
        color: #c67c4e;
    }
    .progress-title {
        font-size: 1.5rem;
        font-weight: bold;
        color: #333;
        margin-bottom: 15px;
    }
    .progress-bar-container {
        width: 100%;
        margin-top: 20px;
    }
    .progress-bar-wrapper {
        background-color: #e9ecef;
        border-radius: 10px;
        overflow: hidden;
        height: 8px;
    }
    .progress-bar-fill {
        background: linear-gradient(90deg, #c67c4e, #a85a3a);
        height: 100%;
        width: 0%;
        transition: width 0.3s ease;
    }
    .progress-text {
        font-size: 0.9rem;
        color: #666;
        margin-top: 15px;
    }
    .progress-step {
        font-size: 0.85rem;
        color: #999;
        margin-top: 10px;
        font-style: italic;
    }
</style>

<!-- Progress Modal Backdrop -->
<div id="progressModalBackdrop" class="progress-modal-backdrop"></div>

<!-- Progress Modal -->
<div id="progressModal" class="progress-modal">
    <div class="progress-modal-content">
        <div class="spinner">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
        <div class="progress-title">Creating Backup</div>
        <div class="progress-step" id="currentStep">Initializing...</div>
        <div class="progress-bar-container">
            <div class="progress-bar-wrapper">
                <div class="progress-bar-fill" id="progressBarFill"></div>
            </div>
        </div>
        <div class="progress-text" id="progressPercentage">0%</div>
    </div>
</div>

<div id="layoutSidenav_content">
    <main>
        <div class="container-fluid backup-container">
            <div class="row mb-4">
                <div class="col-md-12">
                    <h1 class="mb-4">
                        <i class="fa-solid fa-shield-halved"></i> Backup & Recovery
                    </h1>
                </div>
            </div>

            <!-- Alerts -->
            <?php if (isset($_SESSION['backup_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show alert-custom" role="alert">
                    <i class="fa-solid fa-circle-check"></i> <?php echo $_SESSION['backup_success']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['backup_success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['backup_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show alert-custom" role="alert">
                    <i class="fa-solid fa-circle-exclamation"></i> <?php echo $_SESSION['backup_error']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['backup_error']); ?>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($backups); ?></div>
                    <div class="stat-label">Total Backups</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">
                        <?php 
                        $totalSize = 0;
                        foreach ($backups as $backup) {
                            $totalSize += $backup['size'];
                        }
                        echo formatBytes($totalSize);
                        ?>
                    </div>
                    <div class="stat-label">Total Size</div>
                </div>
            </div>

            <!-- Create Backup Section -->
            <div class="card backup-card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fa-solid fa-plus-circle"></i> Create New Backup
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">
                        This will export the entire database and all project files into a single ZIP file for download.
                    </p>
                    <button type="button" class="btn btn-primary" onclick="createBackupAJAX();">
                        <i class="fa-solid fa-download"></i> Create Backup Now
                    </button>
                </div> 
            </div>

            <!-- Backups List Section -->
            <div class="card backup-card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fa-solid fa-list"></i> Backup History
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($backups)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="fa-solid fa-inbox fa-3x mb-3 opacity-50"></i>
                            <p>No backups found. Create your first backup to get started.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($backups as $backup): ?>
                            <div class="backup-item">
                                <div class="backup-info">
                                    <div class="backup-name">
                                        <i class="fa-solid fa-file-archive"></i> <?php echo htmlspecialchars($backup['name']); ?>
                                    </div>
                                    <div class="backup-meta">
                                        <span class="badge bg-info me-2">
                                            <?php echo formatBytes($backup['size']); ?>
                                        </span>
                                        <span class="text-muted">
                                            <?php echo date('M d, Y - H:i:s', $backup['date']); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="backup-actions">
                                    <a href="?download=<?php echo urlencode($backup['name']); ?>" class="btn btn-sm btn-download" title="Download">
                                        <i class="fa-solid fa-download"></i> Download
                                    </a>
                                    <a href="#" class="btn btn-sm btn-delete" onclick="return confirmDelete('<?php echo htmlspecialchars($backup['name'], ENT_QUOTES); ?>');" title="Delete">
                                        <i class="fa-solid fa-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Information Section -->
            <div class="card mt-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fa-solid fa-circle-info"></i> Backup Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="mb-3">What's Included:</h6>
                            <ul class="small">
                                <li><strong>Database Backup:</strong> Complete SQL dump of all tables and data</li>
                                <li><strong>Project Files:</strong> All application files and directories</li>
                                <li><strong>ZIP Archive:</strong> Easy to store and transfer</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
    </main>
</div>

<script>
    // Function to create backup via AJAX
    function createBackupAJAX() {
        if (!confirm('Create a new backup? This may take a few moments.')) {
            return;
        }
        
        // Show progress modal
        const modal = document.getElementById('progressModal');
        const backdrop = document.getElementById('progressModalBackdrop');
        const body = document.body;
        
        modal.classList.add('active');
        backdrop.classList.add('active');
        body.classList.add('backup-modal-shown');
        body.style.overflow = 'hidden';
        
        // Reset modal
        const titleEl = document.querySelector('.progress-title');
        titleEl.textContent = 'Creating Backup';
        const stepEl = document.getElementById('currentStep');
        stepEl.textContent = 'Initializing...';
        updateProgressBar(0);
        
        // Simulate progress
        let progress = 0;
        const steps = [
            'Initializing...',
            'Exporting database...',
            'Collecting project files...',
            'Creating ZIP archive...',
            'Finalizing backup...'
        ];
        
        let currentStepIndex = 0;
        const progressInterval = setInterval(() => {
            if (progress < 90) {
                progress += Math.random() * 20;
                if (progress > 90) progress = 90;
            }
            
            const stepIndex = Math.floor(progress / 20);
            if (stepIndex < steps.length && stepIndex !== currentStepIndex) {
                currentStepIndex = stepIndex;
                const el = document.getElementById('currentStep');
                if (el) el.textContent = steps[stepIndex];
            }
            
            updateProgressBar(progress);
        }, 500);
        
        // Make AJAX request
        const formData = new FormData();
        formData.append('action', 'create_backup');
        
        fetch('backup.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            clearInterval(progressInterval);
            
            if (data.success) {
                // Complete the progress bar
                updateProgressBar(100);
                const stepEl = document.getElementById('currentStep');
                stepEl.textContent = 'Backup completed successfully!';
                
                // Hide modal after 2 seconds and reload page
                setTimeout(() => {
                    hideProgressModal();
                    // Show success notification by reloading the page
                    location.reload();
                }, 2000);
            } else {
                clearInterval(progressInterval);
                hideProgressModal();
                alert('Backup failed: ' + data.message);
            }
        })
        .catch(error => {
            clearInterval(progressInterval);
            hideProgressModal();
            console.error('Error:', error);
            alert('Backup failed: ' + error.message);
        });
    }

    // Function to hide progress modal
    function hideProgressModal() {
        const modal = document.getElementById('progressModal');
        const backdrop = document.getElementById('progressModalBackdrop');
        const body = document.body;
        
        modal.classList.remove('active');
        backdrop.classList.remove('active');
        body.classList.remove('backup-modal-shown');
        body.style.overflow = 'auto';
        
        if (window.progressInterval) {
            clearInterval(window.progressInterval);
        }
    }

    function confirmDelete(backupName) {
        if (!confirm('Are you sure you want to delete this backup?\n\n' + backupName)) {
            return false;
        }
        
        // Show progress modal
        const modal = document.getElementById('progressModal');
        const backdrop = document.getElementById('progressModalBackdrop');
        const body = document.body;
        
        modal.classList.add('active');
        backdrop.classList.add('active');
        body.classList.add('backup-modal-shown');
        body.style.overflow = 'hidden';
        
        // Update modal for delete operation
        const titleEl = document.querySelector('.progress-title');
        titleEl.textContent = 'Deleting Backup';
        const stepEl = document.getElementById('currentStep');
        stepEl.textContent = 'Removing backup file...';
        updateProgressBar(0);
        
        // Simulate progress
        let progress = 0;
        const progressInterval = setInterval(() => {
            if (progress < 80) {
                progress += Math.random() * 30;
                if (progress > 80) progress = 80;
            }
            updateProgressBar(progress);
        }, 300);
        
        // Make AJAX request
        const formData = new FormData();
        formData.append('action', 'delete_backup');
        formData.append('filename', backupName);
        
        fetch('backup.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            clearInterval(progressInterval);
            
            if (data.success) {
                // Complete the progress bar
                updateProgressBar(100);
                const stepEl = document.getElementById('currentStep');
                stepEl.textContent = 'Backup deleted successfully!';
                
                // Hide modal after 2 seconds and reload page
                setTimeout(() => {
                    hideProgressModal();
                    location.reload();
                }, 1500);
            } else {
                clearInterval(progressInterval);
                hideProgressModal();
                alert('Delete failed: ' + data.message);
            }
        })
        .catch(error => {
            clearInterval(progressInterval);
            hideProgressModal();
            console.error('Error:', error);
            alert('Delete failed: ' + error.message);
        });
        
        return false;
    }
    
    function updateProgressBar(progress) {
        const fill = document.getElementById('progressBarFill');
        const percentage = document.getElementById('progressPercentage');
        if (fill && percentage) {
            fill.style.width = progress + '%';
            percentage.textContent = Math.round(progress) + '%';
        }
    }
    
    // Initialize modal on page load
    function initializeModal() {
        const modal = document.getElementById('progressModal');
        const backdrop = document.getElementById('progressModalBackdrop');
        const body = document.body;
        
        modal.classList.remove('active');
        backdrop.classList.remove('active');
        body.classList.remove('backup-modal-shown');
        body.style.overflow = 'auto';
        
        const titleEl = document.querySelector('.progress-title');
        titleEl.textContent = 'Creating Backup';
        const stepEl = document.getElementById('currentStep');
        stepEl.textContent = 'Initializing...';
        updateProgressBar(0);
    }
    
    // Initialize when page loads
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeModal);
    } else {
        initializeModal();
    }
</script>

<?php include 'includes/footer.php'; ?>
