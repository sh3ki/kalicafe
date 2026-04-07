<?php
include('includes/config.php');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check what product "Almond" belongs to
    echo "<h3>Product: Almond</h3>";
    $stmt = $pdo->query("SELECT p.id, p.product_name, p.category_id, c.name as category_name 
                         FROM products p 
                         LEFT JOIN categories c ON p.category_id = c.id 
                         WHERE p.product_name LIKE '%Almond%'");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($products);
    echo "</pre>";
    
    if (!empty($products)) {
        $product = $products[0];
        $category_id = $product['category_id'];
        
        echo "<h3>Category Ingredients for category_id = $category_id, size = 22oz (is_shared = 0)</h3>";
        $stmt = $pdo->prepare("
            SELECT ci.*, i.name as ingredient_name 
            FROM category_ingredients ci
            JOIN ingredients i ON ci.ingredient_id = i.id
            WHERE ci.category_id = :category_id 
            AND ci.size = '22oz'
            AND ci.is_shared = 0
        ");
        $stmt->execute([':category_id' => $category_id]);
        $catIngredients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($catIngredients);
        echo "</pre>";
        
        echo "<h3>Shared Ingredients for size = 22oz (is_shared = 1)</h3>";
        $stmt = $pdo->query("
            SELECT ci.*, i.name as ingredient_name 
            FROM category_ingredients ci
            JOIN ingredients i ON ci.ingredient_id = i.id
            WHERE ci.is_shared = 1
            AND ci.size = '22oz'
        ");
        $sharedIngredients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($sharedIngredients);
        echo "</pre>";
        
        echo "<h3>Product Flavors for product_id = {$product['id']}, size = 22oz</h3>";
        $stmt = $pdo->prepare("
            SELECT pf.*, i.name as ingredient_name 
            FROM product_flavors pf
            JOIN ingredients i ON pf.flavor_id = i.id
            WHERE pf.product_id = :product_id
            AND pf.size = '22oz'
        ");
        $stmt->execute([':product_id' => $product['id']]);
        $flavors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($flavors);
        echo "</pre>";
    }
    
    echo "<h3>ALL Category Ingredients with Cups</h3>";
    $stmt = $pdo->query("
        SELECT ci.*, i.name as ingredient_name, c.name as category_name
        FROM category_ingredients ci
        JOIN ingredients i ON ci.ingredient_id = i.id
        LEFT JOIN categories c ON ci.category_id = c.id
        WHERE i.name LIKE '%Cup%'
        ORDER BY ci.is_shared DESC, ci.category_id, ci.size
    ");
    $allCups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($allCups);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
