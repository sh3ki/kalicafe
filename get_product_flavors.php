<?php
session_start();
require_once 'includes/config.php';

header('Content-Type: application/json');

if (!isset($_GET['product_id'])) {
    echo json_encode(['error' => 'Product ID is required']);
    exit();
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $product_id = $_GET['product_id'];
    
    // Get product's category to fetch available flavors
    $stmt = $pdo->prepare("SELECT category_id FROM products WHERE id = :id");
    $stmt->execute(['id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['error' => 'Product not found']);
        exit();
    }
    
    $category_id = $product['category_id'];
    
    // Get all available ingredients as potential flavors
    // Same flavor can be linked to multiple products
    $stmt = $pdo->prepare("
        SELECT 
            i.id as ingredient_id,
            i.name as ingredient_name,
            i.unit
        FROM ingredients i
        ORDER BY i.name
    ");
    $stmt->execute();
    $flavors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get already linked flavors for this product
    $stmt = $pdo->prepare("
        SELECT 
            pf.id,
            pf.flavor_id,
            pf.size,
            pf.quantity_required,
            pf.unit,
            i.name as flavor_name
        FROM product_flavors pf
        JOIN ingredients i ON pf.flavor_id = i.id
        WHERE pf.product_id = :product_id
        ORDER BY i.name, pf.size
    ");
    $stmt->execute(['product_id' => $product_id]);
    $linked_flavors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'available_flavors' => $flavors,
        'linked_flavors' => $linked_flavors
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
