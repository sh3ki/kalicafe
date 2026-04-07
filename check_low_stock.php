<?php
header('Content-Type: application/json');
include('includes/config.php');

$threshold_multiplier = 10; // Low stock alert when you can't make 10 products

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check both product_ingredients and category_ingredients
    // An ingredient is low stock if you can't make 10 products with it
    $query = "
        SELECT 
            i.id,
            i.name,
            i.quantity,
            i.unit,
            MAX(GREATEST(
                COALESCE(pi_max.max_qty, 0),
                COALESCE(ci_max.max_qty, 0)
            )) AS max_quantity_required,
            (MAX(GREATEST(
                COALESCE(pi_max.max_qty, 0),
                COALESCE(ci_max.max_qty, 0)
            )) * :threshold) AS low_stock_threshold,
            FLOOR(i.quantity / MAX(GREATEST(
                COALESCE(pi_max.max_qty, 0),
                COALESCE(ci_max.max_qty, 0)
            ))) AS can_make_products
        FROM ingredients i
        LEFT JOIN (
            SELECT ingredient_id, MAX(quantity_required) as max_qty
            FROM product_ingredients
            GROUP BY ingredient_id
        ) pi_max ON i.id = pi_max.ingredient_id
        LEFT JOIN (
            SELECT ingredient_id, MAX(quantity_requirement) as max_qty
            FROM category_ingredients
            GROUP BY ingredient_id
        ) ci_max ON i.id = ci_max.ingredient_id
        WHERE (pi_max.max_qty IS NOT NULL OR ci_max.max_qty IS NOT NULL)
        GROUP BY i.id, i.name, i.quantity, i.unit
        HAVING i.quantity < (MAX(GREATEST(
            COALESCE(pi_max.max_qty, 0),
            COALESCE(ci_max.max_qty, 0)
        )) * :threshold)
        ORDER BY i.name
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute(['threshold' => $threshold_multiplier]);
    $lowStock = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'low_stock' => $lowStock]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
