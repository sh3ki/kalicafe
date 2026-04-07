<?php
session_start();
include('includes/config.php');

header('Content-Type: application/json');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $category_id = $_GET['category_id'] ?? null;
    $size = $_GET['size'] ?? '16oz';
    
    if (!$category_id) {
        echo json_encode(['ingredients' => []]);
        exit();
    }
    
    if ($size === '22oz') {
        // For 22oz, get ingredients that are assigned to 16oz but not yet assigned to 22oz
        $stmt = $pdo->prepare("
            SELECT DISTINCT i.id, i.name 
            FROM ingredients i
            INNER JOIN category_ingredients ci ON i.id = ci.ingredient_id
            WHERE ci.category_id = :category_id 
            AND ci.size = '16oz'
            AND i.id NOT IN (
                SELECT ingredient_id 
                FROM category_ingredients 
                WHERE category_id = :category_id AND size = '22oz'
            )
            ORDER BY i.name
        ");
    } else {
        // For 16oz, get all ingredients in the category that are not yet assigned to 16oz
        $stmt = $pdo->prepare("
            SELECT i.id, i.name 
            FROM ingredients i
            WHERE i.category_id = :category_id
            AND i.id NOT IN (
                SELECT ingredient_id 
                FROM category_ingredients 
                WHERE category_id = :category_id AND size = '16oz'
            )
            ORDER BY i.name
        ");
    }
    
    $stmt->execute([':category_id' => $category_id]);
    $ingredients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['ingredients' => $ingredients]);
    
} catch (PDOException $e) {
    echo json_encode(['ingredients' => [], 'error' => $e->getMessage()]);
}
?>
