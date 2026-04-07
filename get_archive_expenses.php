<?php
session_start();

// Set timezone to Philippines (GMT+8)
date_default_timezone_set('Asia/Manila');

include('includes/config.php');
include('includes/auth.php');

// Allow only admin
checkAccess(['admin']);

header('Content-Type: application/json');

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

if (!$start_date || !$end_date) {
    echo json_encode(['total' => 0, 'list' => []]);
    exit;
}

try {
    // Get total expenses for the period
    $total_query = "
        SELECT COALESCE(SUM(amount), 0) as total
        FROM expenses
        WHERE expense_date BETWEEN ? AND ?
    ";
    
    $total_stmt = $conn->prepare($total_query);
    $total_stmt->execute([$start_date, $end_date]);
    $total_expenses = $total_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get list of expenses for the period
    $list_query = "
        SELECT 
            id,
            expense_name,
            amount,
            expense_date,
            description
        FROM expenses
        WHERE expense_date BETWEEN ? AND ?
        ORDER BY expense_date DESC, created_at DESC
    ";
    
    $list_stmt = $conn->prepare($list_query);
    $list_stmt->execute([$start_date, $end_date]);
    $expenses_list = $list_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'total' => floatval($total_expenses),
        'list' => $expenses_list
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['total' => 0, 'list' => [], 'error' => $e->getMessage()]);
}
?>
