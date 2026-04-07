<?php
/**
 * Kitchen Completed Orders Cleanup
 * This script should run daily (e.g., via cron job at midnight)
 * to reset the completed_at field for orders older than 24 hours
 * 
 * To set up on Windows (Task Scheduler):
 * 1. Open Task Scheduler
 * 2. Create Basic Task
 * 3. Trigger: Daily at 00:00 (midnight)
 * 4. Action: Start a program
 * 5. Program: C:\path\to\php.exe
 * 6. Arguments: C:\laragon\www\kusso\cleanup_completed_orders.php
 * 
 * To set up on Linux (cron):
 * Add to crontab: 0 0 * * * /usr/bin/php /path/to/kusso/cleanup_completed_orders.php
 */

require_once 'includes/config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Reset completed_at for orders older than 24 hours
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET completed_at = NULL 
        WHERE completed_at IS NOT NULL 
        AND completed_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $stmt->execute();
    
    $affected = $stmt->rowCount();
    
    // Log the cleanup
    $logFile = 'logs/completed_orders_cleanup.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logMessage = date('Y-m-d H:i:s') . " - Cleaned up $affected completed orders older than 24 hours\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    
    echo "Success: Cleaned up $affected orders\n";
    
} catch (PDOException $e) {
    $logMessage = date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n";
    file_put_contents('logs/completed_orders_cleanup.log', $logMessage, FILE_APPEND);
    echo "Error: " . $e->getMessage() . "\n";
}
?>
