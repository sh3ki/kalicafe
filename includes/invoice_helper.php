<?php
/**
 * Generate customized invoice number with format: YYYYMMDD-HHMM-XXX
 * Where XXX is a daily counter starting from 001 that resets every day
 * Uses device time if provided (format: "YYYY-MM-DD HH:MM:SS"), otherwise uses server time
 */
function generateInvoiceNumber($pdo, $deviceTimestamp = null) {
    // Convert device timestamp to Unix time
    if ($deviceTimestamp) {
        // Handle local datetime format: "YYYY-MM-DD HH:MM:SS"
        $date = strtotime($deviceTimestamp);
    } else {
        $date = time();
    }
    
    $dateKey = date('Ymd', $date); // Format: 20251123
    $timeKey = date('Hi', $date);  // Format: 0850 (08:50)
    $dateString = date('Y-m-d', $date); // Format: 2025-11-23
    
    try {
        // Get the next sequence number for TODAY ONLY
        // This ensures the counter resets every day at midnight
        $stmt = $pdo->prepare("
            SELECT COALESCE(MAX(CAST(SUBSTRING(order_number, -3) AS UNSIGNED)), 0) + 1 as next_num
            FROM orders 
            WHERE DATE(created_at) = :dateKey
        ");
        
        $stmt->execute([':dateKey' => $dateString]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $sequenceNum = str_pad($result['next_num'], 3, '0', STR_PAD_LEFT);
        
        // Format: YYYYMMDD-HHMM-XXX (e.g., 20251123-0850-001)
        return $dateKey . '-' . $timeKey . '-' . $sequenceNum;
    } catch (Exception $e) {
        // Fallback format if query fails - start with 001
        return $dateKey . '-' . $timeKey . '-001';
    }
}

/**
 * Format the invoice display with INV prefix
 */
function formatInvoiceDisplay($invoiceNumber) {
    return 'INV-' . $invoiceNumber;
}

/**
 * Get the daily counter for an existing invoice number
 */
function getInvoiceSequence($invoiceNumber) {
    // Extract the last 3 digits (sequence number)
    return substr($invoiceNumber, -3);
}
?>

