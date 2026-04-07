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
    
    // Get ingredients for this category and size from category_ingredients table
    $stmt = $pdo->prepare("
        SELECT 
            i.id,
            i.name,
            ci.quantity_requirement,
            ci.unit,
            ci.size
        FROM category_ingredients ci
        INNER JOIN ingredients i ON ci.ingredient_id = i.id
        WHERE ci.category_id = :category_id 
        AND ci.size = :size
        ORDER BY i.name
    ");
    
    $stmt->execute([
        ':category_id' => $category_id,
        ':size' => $size
    ]);
    
    $ingredients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['ingredients' => $ingredients]);
    
} catch (PDOException $e) {
    echo json_encode(['ingredients' => [], 'error' => $e->getMessage()]);
}
?>
