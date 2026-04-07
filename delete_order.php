<?php
session_start();
include('includes/config.php');

if (isset($_GET['id'])) {
    $order_id = $_GET['id'];

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Start transaction
        $pdo->beginTransaction();

        // RESTORE INVENTORY BEFORE DELETING
        // Fetch all order items to restore their ingredients
        $itemsStmt = $pdo->prepare("
            SELECT oi.product_id, oi.qty, oi.options, oi.note, p.category_id, c.type as category_type
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN categories c ON p.category_id = c.id
            WHERE oi.order_id = :order_id
        ");
        $itemsStmt->execute([':order_id' => $order_id]);
        $orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($orderItems as $item) {
            // Skip Food/Extra categories (they don't track inventory)
            if (in_array($item['category_type'], ['Food', 'Extra', 'Extras'])) {
                continue;
            }
            
            // Extract size and sugar level from options/note
            $size = '16oz'; // default
            $sugar_level = null;
            
            if ($item['options']) {
                $options = json_decode($item['options'], true);
                if (isset($options['size'])) {
                    $size = $options['size'];
                }
            }
            
            if ($item['note']) {
                if (stripos($item['note'], 'No Sugar') !== false) {
                    $sugar_level = 'no-sugar';
                } elseif (stripos($item['note'], 'Less Sugar') !== false) {
                    $sugar_level = 'less-sugar';
                } elseif (stripos($item['note'], 'More Sugar') !== false) {
                    $sugar_level = 'more-sugar';
                } elseif (stripos($item['note'], 'Normal Sugar') !== false) {
                    $sugar_level = 'normal-sugar';
                }
            }
            
            // Get category ingredients (non-shared)
            $catIngStmt = $pdo->prepare("
                SELECT ci.ingredient_id, ci.quantity_requirement, i.name as ingredient_name
                FROM category_ingredients ci
                JOIN ingredients i ON ci.ingredient_id = i.id
                WHERE ci.category_id = :category_id AND ci.size = :size AND ci.is_shared = 0
            ");
            $catIngStmt->execute([':category_id' => $item['category_id'], ':size' => $size]);
            $ingredients = $catIngStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get shared ingredients
            $sharedStmt = $pdo->prepare("
                SELECT ci.ingredient_id, ci.quantity_requirement, i.name as ingredient_name
                FROM category_ingredients ci
                JOIN ingredients i ON ci.ingredient_id = i.id
                WHERE ci.is_shared = 1 AND ci.size = :size
            ");
            $sharedStmt->execute([':size' => $size]);
            $sharedIngredients = $sharedStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Merge and deduplicate
            $allIngredients = [];
            foreach ($ingredients as $ing) {
                $allIngredients[$ing['ingredient_id']] = $ing;
            }
            foreach ($sharedIngredients as $ing) {
                if (!isset($allIngredients[$ing['ingredient_id']])) {
                    $allIngredients[$ing['ingredient_id']] = $ing;
                }
            }
            
            // Separate sweetener from others
            $sweetener_ing = null;
            $other_ingredients = [];
            foreach ($allIngredients as $ing) {
                if (stripos($ing['ingredient_name'], 'sweetener') !== false || stripos($ing['ingredient_name'], 'sugar') !== false) {
                    $sweetener_ing = $ing;
                } else {
                    $other_ingredients[] = $ing;
                }
            }
            
            // Restore non-sweetener ingredients
            foreach ($other_ingredients as $ing) {
                $restoreStmt = $pdo->prepare("UPDATE ingredients SET quantity = quantity + (:qty * :mult) WHERE id = :id");
                $restoreStmt->execute([
                    ':qty' => $item['qty'],
                    ':mult' => $ing['quantity_requirement'],
                    ':id' => $ing['ingredient_id']
                ]);
            }
            
            // Restore sweetener based on sugar level
            if ($sugar_level && $sweetener_ing) {
                $sweetener_multiplier = match($sugar_level) {
                    'no-sugar' => 0,
                    'less-sugar' => 0.5,
                    'normal-sugar' => 1,
                    'more-sugar' => 1.5,
                    default => 1
                };
                
                if ($sweetener_multiplier > 0) {
                    $sweetener_qty = $sweetener_ing['quantity_requirement'] * $sweetener_multiplier * $item['qty'];
                    $restoreSweetener = $pdo->prepare("UPDATE ingredients SET quantity = quantity + :qty WHERE id = :id");
                    $restoreSweetener->execute([
                        ':qty' => $sweetener_qty,
                        ':id' => $sweetener_ing['ingredient_id']
                    ]);
                }
            }
        }

        // Now delete all related order_items
        $stmt_items = $pdo->prepare("DELETE FROM order_items WHERE order_id = :id");
        $stmt_items->bindParam(':id', $order_id, PDO::PARAM_INT);
        $stmt_items->execute();

        // Then, delete the order itself
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = :id");
        $stmt->bindParam(':id', $order_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $pdo->commit();
            $_SESSION['success_message'] = "Order deleted successfully and inventory restored.";
        } else {
            $pdo->rollBack();
            $_SESSION['error_message'] = "Failed to delete the order.";
        }
    } catch (PDOException $e) {
        if ($pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
} else {
    $_SESSION['error_message'] = "Invalid order ID.";
}

header("Location: orders.php");
exit();
?>
