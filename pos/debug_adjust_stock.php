<?php
// Debug version of adjust_stock to see what's happening
include('../includes/config.php');
header('Content-Type: application/json');

if (!isset($_POST['product_id']) || !isset($_POST['qty'])) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

$product_id = intval($_POST['product_id']);
$qty = intval($_POST['qty']);
$sugar_level = isset($_POST['sugar_level']) ? $_POST['sugar_level'] : null;
$size = isset($_POST['size']) ? $_POST['size'] : '16oz';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get product
    $prodStmt = $pdo->prepare("SELECT p.category_id, c.type as category_type FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = :product_id");
    $prodStmt->execute([':product_id' => $product_id]);
    $product = $prodStmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode(['success' => false, 'error' => 'Product not found']);
        exit;
    }
    
    // Get category ingredients
    $catIngStmt = $pdo->prepare("
        SELECT ci.ingredient_id, ci.quantity_requirement as quantity_required, ci.unit, i.name as ingredient_name 
        FROM category_ingredients ci 
        JOIN ingredients i ON ci.ingredient_id = i.id 
        WHERE ci.category_id = :category_id AND ci.size = :size AND ci.is_shared = 0
    ");
    $catIngStmt->execute([':category_id' => $product['category_id'], ':size' => $size]);
    $catIngredients = $catIngStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get shared ingredients
    $sharedStmt = $pdo->prepare("
        SELECT ci.ingredient_id, ci.quantity_requirement as quantity_required, ci.unit, i.name as ingredient_name 
        FROM category_ingredients ci 
        JOIN ingredients i ON ci.ingredient_id = i.id 
        WHERE ci.is_shared = 1 AND ci.size = :size
    ");
    $sharedStmt->execute([':size' => $size]);
    $sharedIngredients = $sharedStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get flavors
    $flavorStmt = $pdo->prepare("
        SELECT pf.flavor_id as ingredient_id, pf.quantity_required, pf.unit, i.name as ingredient_name 
        FROM product_flavors pf 
        JOIN ingredients i ON pf.flavor_id = i.id 
        WHERE pf.product_id = :product_id AND pf.size = :size
    ");
    $flavorStmt->execute([':product_id' => $product_id, ':size' => $size]);
    $flavors = $flavorStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'debug' => true,
        'product_id' => $product_id,
        'category_id' => $product['category_id'],
        'size' => $size,
        'qty' => $qty,
        'category_ingredients' => $catIngredients,
        'shared_ingredients' => $sharedIngredients,
        'flavors' => $flavors
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
