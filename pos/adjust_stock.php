<?php
// adjust_stock.php
// POST: product_id, qty (positive to add, negative to deduct), sugar_level (optional), size (required for drinks)
include('../includes/config.php');
header('Content-Type: application/json');

if (!isset($_POST['product_id']) || !isset($_POST['qty'])) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

$product_id = intval($_POST['product_id']);
$qty = intval($_POST['qty']);
$sugar_level = isset($_POST['sugar_level']) ? $_POST['sugar_level'] : null;
$size = isset($_POST['size']) ? $_POST['size'] : '16oz'; // Default to 16oz if not specified

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Start transaction for atomic inventory updates
    $pdo->beginTransaction();

    // Get product to find its category and category type
    $prodStmt = $pdo->prepare("SELECT p.category_id, c.type as category_type FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = :product_id");
    $prodStmt->execute([':product_id' => $product_id]);
    $product = $prodStmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode(['success' => false, 'error' => 'Product not found']);
        exit;
    }
    
    // For Food, Extra, and Extras categories, skip ingredient deduction and return success immediately
    if (in_array($product['category_type'], ['Food', 'Extra', 'Extras'])) {
        echo json_encode(['success' => true, 'message' => 'Food/Extra products do not track ingredients']);
        exit;
    }

    // Get required ingredients ONLY from category_ingredients table based on size
    $catIngStmt = $pdo->prepare("
        SELECT 
            ci.ingredient_id, 
            ci.quantity_requirement as quantity_required, 
            ci.unit,
            i.name as ingredient_name 
        FROM category_ingredients ci 
        JOIN ingredients i ON ci.ingredient_id = i.id 
        WHERE ci.category_id = :category_id 
        AND ci.size = :size
        AND ci.is_shared = 0
    ");
    $catIngStmt->execute([
        ':category_id' => $product['category_id'],
        ':size' => $size
    ]);
    $ingredients = $catIngStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get SHARED ingredients for ALL drinks (cups, lids, straws) based on size
    $sharedStmt = $pdo->prepare("
        SELECT 
            ci.ingredient_id, 
            ci.quantity_requirement as quantity_required, 
            ci.unit,
            i.name as ingredient_name 
        FROM category_ingredients ci 
        JOIN ingredients i ON ci.ingredient_id = i.id 
        WHERE ci.is_shared = 1
        AND ci.size = :size
    ");
    $sharedStmt->execute([':size' => $size]);
    $sharedIngredients = $sharedStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get linked flavors for this product and size
    $flavorStmt = $pdo->prepare("
        SELECT 
            pf.flavor_id as ingredient_id, 
            pf.quantity_required, 
            pf.unit,
            i.name as ingredient_name 
        FROM product_flavors pf 
        JOIN ingredients i ON pf.flavor_id = i.id 
        WHERE pf.product_id = :product_id 
        AND pf.size = :size
    ");
    $flavorStmt->execute([
        ':product_id' => $product_id,
        ':size' => $size
    ]);
    $flavors = $flavorStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Merge all ingredients and remove duplicates by ingredient_id
    $allIngredients = [];
    
    // Add category ingredients
    foreach ($ingredients as $ing) {
        $allIngredients[$ing['ingredient_id']] = $ing;
    }
    
    // Add shared ingredients (won't override if already exists)
    foreach ($sharedIngredients as $ing) {
        if (!isset($allIngredients[$ing['ingredient_id']])) {
            $allIngredients[$ing['ingredient_id']] = $ing;
        }
    }
    
    // Add flavors (won't override if already exists)
    foreach ($flavors as $ing) {
        if (!isset($allIngredients[$ing['ingredient_id']])) {
            $allIngredients[$ing['ingredient_id']] = $ing;
        }
    }
    
    // Convert back to indexed array
    $ingredients = array_values($allIngredients);
    
    // DEBUG: Check merged ingredients
    error_log("=== AFTER MERGE ===");
    error_log("Total merged ingredients: " . count($ingredients));
    foreach ($ingredients as $ing) {
        error_log("  - " . $ing['ingredient_name'] . " (ID: " . $ing['ingredient_id'] . ")");
    }
    
    // If no ingredients or flavors found for this size, return error
    if (empty($ingredients)) {
        echo json_encode(['success' => false, 'error' => 'No ingredients/flavors configured for this product size (' . $size . ')']);
        exit;
    }

    // Separate sweetener from other ingredients
    $sweetener_ing = null;
    $other_ingredients = [];
    
    foreach ($ingredients as $ing) {
        if (stripos($ing['ingredient_name'], 'sweetener') !== false || stripos($ing['ingredient_name'], 'sugar') !== false) {
            $sweetener_ing = $ing;
        } else {
            $other_ingredients[] = $ing;
        }
    }

    // Check stock if deducting (only for non-sweetener ingredients initially)
    if ($qty < 0) {
        foreach ($other_ingredients as $ing) {
            $check = $pdo->prepare("SELECT quantity FROM ingredients WHERE id = :id");
            $check->execute([':id' => $ing['ingredient_id']]);
            $row = $check->fetch(PDO::FETCH_ASSOC);
            $required = abs($qty) * $ing['quantity_required'];
            
            // Check if ingredient is out of stock (quantity = 0)
            if (!$row || $row['quantity'] <= 0) {
                echo json_encode(['success' => false, 'error' => 'Ingredient out of stock: ' . $ing['ingredient_name']]);
                exit;
            }
            
            // Check if there's insufficient stock
            if ($row['quantity'] < $required) {
                echo json_encode(['success' => false, 'error' => 'Insufficient stock']);
                exit;
            }
        }
    }

    // Adjust stock for non-sweetener ingredients, never allow negative
    // DEBUG: Log what we're deducting
    $debug_info = [];
    $debug_info['product_id'] = $product_id;
    $debug_info['size'] = $size;
    $debug_info['qty'] = $qty;
    $debug_info['total_ingredients'] = count($other_ingredients);
    $debug_info['ingredients_to_deduct'] = [];
    
    error_log("=== ADJUST STOCK DEBUG ===");
    error_log("Product ID: $product_id, Size: $size, Qty: $qty");
    error_log("Total ingredients to process: " . count($other_ingredients));
    foreach ($other_ingredients as $ing) {
        $deduct_amount = $qty * $ing['quantity_required'];
        error_log("Deducting: " . $ing['ingredient_name'] . " (ID: " . $ing['ingredient_id'] . ") - Qty: " . $deduct_amount);
        $debug_info['ingredients_to_deduct'][] = [
            'name' => $ing['ingredient_name'],
            'id' => $ing['ingredient_id'],
            'amount' => $deduct_amount
        ];
    }
    
    foreach ($other_ingredients as $ing) {
        // Check stock before deducting (only when qty is negative)
        if ($qty < 0) {
            $checkStock = $pdo->prepare("SELECT quantity, name FROM ingredients WHERE id = :id FOR UPDATE");
            $checkStock->execute([':id' => $ing['ingredient_id']]);
            $stockData = $checkStock->fetch(PDO::FETCH_ASSOC);
            
            $required = abs($qty * $ing['quantity_required']);
            if ($stockData['quantity'] < $required) {
                $pdo->rollBack();
                echo json_encode([
                    'success' => false, 
                    'error' => 'Insufficient stock for ' . $stockData['name'] . '. Available: ' . floor($stockData['quantity']) . ', Required: ' . $required
                ]);
                exit;
            }
        }
        
        $update = $pdo->prepare("UPDATE ingredients SET quantity = quantity + (:qty * :mult) WHERE id = :id");
        $update->execute([
            ':qty' => $qty,
            ':mult' => $ing['quantity_required'],
            ':id' => $ing['ingredient_id']
        ]);
    }

    // Handle sweetener deduction/addition based on sugar level (only for drinks with sugar_level specified)
    if ($sugar_level && $sweetener_ing) {
        $sweetener_multiplier = 0;
        
        switch ($sugar_level) {
            case 'no-sugar':
                $sweetener_multiplier = 0;
                break;
            case 'less-sugar':
                $sweetener_multiplier = 0.5;
                break;
            case 'normal-sugar':
                $sweetener_multiplier = 1;
                break;
            case 'more-sugar':
                $sweetener_multiplier = 1.5;
                break;
            default:
                $sweetener_multiplier = 1;
        }
        
        // Calculate sweetener adjustment: required_quantity * multiplier * qty
        // For negative qty: deducts sweetener
        // For positive qty: adds sweetener back (when removing items)
        $sweetener_qty = $sweetener_ing['quantity_required'] * $sweetener_multiplier * $qty;
        
        // Only adjust if multiplier is greater than 0 (skip for no-sugar)
        if ($sweetener_multiplier > 0) {
            // Check sweetener stock before deducting
            if ($qty < 0) {
                $checkSweetener = $pdo->prepare("SELECT quantity, name FROM ingredients WHERE id = :id FOR UPDATE");
                $checkSweetener->execute([':id' => $sweetener_ing['ingredient_id']]);
                $sweetenerData = $checkSweetener->fetch(PDO::FETCH_ASSOC);
                
                if ($sweetenerData['quantity'] < abs($sweetener_qty)) {
                    $pdo->rollBack();
                    echo json_encode([
                        'success' => false,
                        'error' => 'Insufficient stock for ' . $sweetenerData['name'] . '. Available: ' . floor($sweetenerData['quantity']) . ', Required: ' . abs($sweetener_qty)
                    ]);
                    exit;
                }
            }
            
            // Add or subtract sweetener based on qty sign
            $adjust_sweetener = $pdo->prepare("UPDATE ingredients SET quantity = quantity + :qty WHERE id = :id");
            $adjust_sweetener->execute([
                ':qty' => $sweetener_qty,
                ':id' => $sweetener_ing['ingredient_id']
            ]);
        }
    }
    
    // Commit transaction
    $pdo->commit();

    echo json_encode(['success' => true, 'debug' => $debug_info]);
} catch (PDOException $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("ADJUST_STOCK ERROR: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("ADJUST_STOCK GENERAL ERROR: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

