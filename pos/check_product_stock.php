<?php
header('Content-Type: application/json');
include('../includes/config.php');

$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$size = isset($_GET['size']) ? $_GET['size'] : null;

// DEBUG: Log incoming parameters
error_log("=== CHECK_PRODUCT_STOCK REQUEST ===");
error_log("Product ID: " . $product_id);
error_log("Size: " . ($size ? $size : 'NULL/EMPTY'));

if (!$product_id) {
    echo json_encode(['success' => false, 'error' => 'No product ID provided.']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get product category and type
    $prodStmt = $pdo->prepare("SELECT p.category_id, c.type as category_type FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = :product_id");
    $prodStmt->execute([':product_id' => $product_id]);
    $product = $prodStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['success' => false, 'error' => 'Product not found']);
        exit;
    }
    
    // For Food, Extra, and Extras categories, skip ingredient checks and return success immediately
    if (in_array($product['category_type'], ['Food', 'Extra', 'Extras'])) {
        echo json_encode(['success' => true, 'message' => 'Food/Extra products do not track ingredients']);
        exit;
    }

    // Get direct product ingredients
    $stmt = $pdo->prepare("SELECT pi.ingredient_id, pi.quantity_required, i.name, i.quantity AS stock, i.unit FROM product_ingredients pi JOIN ingredients i ON pi.ingredient_id = i.id WHERE pi.product_id = :product_id");
    $stmt->execute([':product_id' => $product_id]);
    $ingredients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get category ingredients based on size (excluding shared)
    if ($size) {
        $catStmt = $pdo->prepare("SELECT ci.ingredient_id, ci.quantity_requirement as quantity_required, ci.size, i.name, i.quantity AS stock, i.unit FROM category_ingredients ci JOIN ingredients i ON ci.ingredient_id = i.id WHERE ci.category_id = :category_id AND ci.size = :size AND ci.is_shared = 0");
        $catStmt->execute([':category_id' => $product['category_id'], ':size' => $size]);
    } else {
        $catStmt = $pdo->prepare("SELECT ci.ingredient_id, ci.quantity_requirement as quantity_required, ci.size, i.name, i.quantity AS stock, i.unit FROM category_ingredients ci JOIN ingredients i ON ci.ingredient_id = i.id WHERE ci.category_id = :category_id AND ci.is_shared = 0");
        $catStmt->execute([':category_id' => $product['category_id']]);
    }
    $catIngredients = $catStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // DEBUG: Log category ingredients
    error_log("Category ingredients found: " . count($catIngredients));
    foreach ($catIngredients as $ing) {
        error_log("  CAT: " . $ing['name'] . " (Stock: " . $ing['stock'] . ")");
    }
    
    // Get shared ingredients for ALL drinks based on size
    if ($size) {
        $sharedStmt = $pdo->prepare("SELECT ci.ingredient_id, ci.quantity_requirement as quantity_required, ci.size, i.name, i.quantity AS stock, i.unit FROM category_ingredients ci JOIN ingredients i ON ci.ingredient_id = i.id WHERE ci.is_shared = 1 AND ci.size = :size");
        $sharedStmt->execute([':size' => $size]);
        $sharedIngredients = $sharedStmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        error_log("WARNING: Size is NULL/EMPTY - shared ingredients will NOT be fetched!");
        $sharedIngredients = [];
    }
    
    // DEBUG: Log shared ingredients
    error_log("Shared ingredients found: " . count($sharedIngredients));
    foreach ($sharedIngredients as $ing) {
        error_log("  SHARED: " . $ing['name'] . " (Stock: " . $ing['stock'] . ")");
    }
    
    // Merge ingredient lists (remove duplicates by ingredient_id - use ingredient_id as key)
    $allIngredients = [];
    
    // Add product ingredients
    foreach ($ingredients as $ing) {
        $allIngredients[$ing['ingredient_id']] = $ing;
    }
    
    // Add category ingredients (won't override if already exists)
    foreach ($catIngredients as $ing) {
        if (!isset($allIngredients[$ing['ingredient_id']])) {
            $allIngredients[$ing['ingredient_id']] = $ing;
        }
    }
    
    // Add shared ingredients (won't override if already exists)
    foreach ($sharedIngredients as $ing) {
        if (!isset($allIngredients[$ing['ingredient_id']])) {
            $allIngredients[$ing['ingredient_id']] = $ing;
        }
    }
    
    $ingredients = array_values($allIngredients);
    
    // DEBUG: Log what ingredients we found after merge
    error_log("=== MERGED INGREDIENTS ===");
    error_log("Total merged ingredients: " . count($ingredients));
    foreach ($ingredients as $ing) {
        error_log("  MERGED: " . $ing['name'] . " (ID: " . $ing['ingredient_id'] . ", Stock: " . $ing['stock'] . " " . $ing['unit'] . ")");
    }

    $insufficient = [];
    $outOfStock = [];
    $availableIn16ozOnly = [];
    $lowStockWarning = []; // Can't make 10 products
    $threshold_multiplier = 10;
    
    foreach ($ingredients as $row) {
        $required = $row['quantity_required'];
        $stock = floatval($row['stock']);
        
        // Check if out of stock (quantity = 0)
        if ($stock <= 0) {
            error_log("OUT OF STOCK DETECTED: " . $row['name'] . " (Stock: " . $stock . ")");
            $outOfStock[] = [
                'name' => $row['name'],
                'required' => $required,
                'stock' => floor($row['stock']),
                'unit' => $row['unit']
            ];
            // Also add to low stock warning for product card display
            $lowStockWarning[] = [
                'name' => $row['name'],
                'required' => $required,
                'stock' => $row['stock'],
                'unit' => $row['unit'],
                'can_make' => 0
            ];
        } else if ($stock < $required) {
            $insufficient[] = [
                'name' => $row['name'],
                'required' => $required,
                'stock' => $row['stock'],
                'unit' => $row['unit']
            ];
            
            // If checking for 22oz and insufficient, check if 16oz would work
            if ($size === '22oz') {
                $check16oz = $pdo->prepare("SELECT ci.quantity_requirement FROM category_ingredients ci WHERE ci.category_id = :category_id AND ci.ingredient_id = :ingredient_id AND ci.size = '16oz'");
                $check16oz->execute([':category_id' => $product['category_id'], ':ingredient_id' => $row['ingredient_id']]);
                $result16oz = $check16oz->fetch(PDO::FETCH_ASSOC);
                
                if ($result16oz && $stock >= floatval($result16oz['quantity_requirement'])) {
                    $availableIn16ozOnly[] = [
                        'name' => $row['name'],
                        'required_22oz' => $required,
                        'required_16oz' => $result16oz['quantity_requirement'],
                        'stock' => $row['stock'],
                        'unit' => $row['unit']
                    ];
                }
            }
        } else if ($stock < ($required * $threshold_multiplier)) {
            // Low stock warning - can't make 10 products
            $canMake = floor($stock / $required);
            $lowStockWarning[] = [
                'name' => $row['name'],
                'required' => $required,
                'stock' => $row['stock'],
                'unit' => $row['unit'],
                'can_make' => $canMake
            ];
        }
    }
    
    // Return appropriately based on what we found
    if (count($outOfStock) > 0) {
        error_log("=== RESPONSE: OUT OF STOCK ===");
        error_log("Total out of stock items: " . count($outOfStock));
        foreach ($outOfStock as $item) {
            error_log("  RESPONSE: " . $item['name']);
        }
        echo json_encode(['success' => false, 'outOfStock' => $outOfStock, 'ingredients' => $ingredients, 'lowStockWarning' => $lowStockWarning]);
    } else if (count($availableIn16ozOnly) > 0) {
        echo json_encode(['success' => false, 'availableIn16ozOnly' => $availableIn16ozOnly, 'insufficient' => $insufficient, 'ingredients' => $ingredients, 'lowStockWarning' => $lowStockWarning]);
    } else if (count($insufficient) > 0) {
        echo json_encode(['success' => false, 'insufficient' => $insufficient, 'ingredients' => $ingredients, 'lowStockWarning' => $lowStockWarning]);
    } else {
        echo json_encode(['success' => true, 'ingredients' => $ingredients, 'lowStockWarning' => $lowStockWarning]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
