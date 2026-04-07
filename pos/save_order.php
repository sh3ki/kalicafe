<?php
session_start();
include('../includes/config.php');
include('../includes/invoice_helper.php');

// Function to auto-deduct inventory based on ordered items
function autoDeductInventory($pdo, $items) {
    // No longer needed: deduction is now handled in adjust_stock.php on add/increase only
    return;
}

// Get the JSON data from the request
$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        

    if (isset($data['order_id']) && !empty($data['order_id'])) {
            // Start transaction
            $pdo->beginTransaction();
            
            // Check if the order exists
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :order_id");
            $stmt->bindParam(':order_id', $data['order_id'], PDO::PARAM_INT);
            $stmt->execute();
            $existingOrder = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingOrder) {
                // Determine new payment status
                $payment_status = $existingOrder['payment_status'];
                if (
                    (isset($data['payment_type']) && strtolower($data['payment_type']) === 'cash')
                    || (isset($data['payment_status']) && $data['payment_status'] === 'paid')
                ) {
                    $payment_status = 'paid';
                } elseif (isset($data['payment_type']) && strtolower($data['payment_type']) === 'pay later') {
                    $payment_status = 'unpaid';
                }

                // Update the existing order
                $orderNotes = isset($data['order_notes']) ? $data['order_notes'] : null;
                $stmt = $pdo->prepare("UPDATE orders SET payment_type = :payment_type, total_amount = :total_amount, amount_tendered = :amount_tendered, payment_status = :payment_status, note = :note WHERE id = :order_id");
                $stmt->execute([
                    ':order_id' => $data['order_id'],
                    ':payment_type' => $data['payment_type'],
                    ':total_amount' => $data['total_amount'],
                    ':amount_tendered' => $data['amount_tendered'],
                    ':payment_status' => $payment_status,
                    ':note' => $orderNotes
                ]);

                // RESTORE INVENTORY from old order items before deleting
                // Fetch existing order items to restore their ingredients
                $oldItemsStmt = $pdo->prepare("
                    SELECT oi.product_id, oi.qty, oi.options, oi.note, p.category_id, c.type as category_type
                    FROM order_items oi
                    JOIN products p ON oi.product_id = p.id
                    JOIN categories c ON p.category_id = c.id
                    WHERE oi.order_id = :order_id
                ");
                $oldItemsStmt->execute([':order_id' => $data['order_id']]);
                $oldItems = $oldItemsStmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Restore inventory for each old item
                foreach ($oldItems as $oldItem) {
                    // Skip Food/Extra categories (they don't track inventory)
                    if (in_array($oldItem['category_type'], ['Food', 'Extra', 'Extras'])) {
                        continue;
                    }
                    
                    // Extract size and sugar level from options/note
                    $size = '16oz';
                    $sugar_level = null;
                    
                    if ($oldItem['options']) {
                        $optionsData = json_decode($oldItem['options'], true);
                        if (is_array($optionsData) && isset($optionsData['size'])) {
                            $size = $optionsData['size'];
                        } elseif (is_string($oldItem['options'])) {
                            // Options might just be the size string directly
                            $size = $oldItem['options'];
                        }
                    }
                    
                    if ($oldItem['note']) {
                        if (stripos($oldItem['note'], 'No Sugar') !== false) {
                            $sugar_level = 'no-sugar';
                        } elseif (stripos($oldItem['note'], 'Less Sugar') !== false) {
                            $sugar_level = 'less-sugar';
                        } elseif (stripos($oldItem['note'], 'More Sugar') !== false) {
                            $sugar_level = 'more-sugar';
                        } elseif (stripos($oldItem['note'], 'Normal Sugar') !== false) {
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
                    $catIngStmt->execute([':category_id' => $oldItem['category_id'], ':size' => $size]);
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
                            ':qty' => $oldItem['qty'],
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
                            $sweetener_qty = $sweetener_ing['quantity_requirement'] * $sweetener_multiplier * $oldItem['qty'];
                            $restoreSweetener = $pdo->prepare("UPDATE ingredients SET quantity = quantity + :qty WHERE id = :id");
                            $restoreSweetener->execute([
                                ':qty' => $sweetener_qty,
                                ':id' => $sweetener_ing['ingredient_id']
                            ]);
                        }
                    }
                }
                
                // Delete old order items and re-insert new ones
                // This is necessary because items can have same product_id with different sizes/options
                // Updating by product_id alone would only update one row and lose the others
                $deleteStmt = $pdo->prepare("DELETE FROM order_items WHERE order_id = :order_id");
                $deleteStmt->execute([':order_id' => $data['order_id']]);
                
                // Insert updated order items
                $insertStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, qty, price, amount, options, note) VALUES (:order_id, :product_id, :qty, :price, :amount, :options, :note)");
                foreach ($data['items'] as $item) {
                    // Extract actual product_id from composite key (format: "productId_size_sugar")
                    $productId = $item['id'];
                    if (strpos($productId, '_') !== false) {
                        $parts = explode('_', $productId);
                        $productId = intval($parts[0]);
                    }
                    
                    $insertStmt->execute([
                        ':order_id' => $data['order_id'],
                        ':product_id' => $productId,
                        ':qty' => $item['qty'],
                        ':price' => $item['price'],
                        ':amount' => $item['amount'],
                        ':options' => isset($item['options']) ? $item['options'] : null,
                        ':note' => isset($item['note']) ? $item['note'] : null
                    ]);
                }
                
                // NOTE: We ONLY restore old items above, we do NOT deduct new items
                // Why? Because inventory is already adjusted during cart operations:
                // - Adding items: adjust_stock.php deducts inventory
                // - Increasing qty: update_order_item.php deducts more
                // - Decreasing qty: update_order_item.php restores some
                // - Removing items: delete_order_item.php restores inventory
                // So by the time we save, the current cart already has correct inventory state
                // We only need to restore the OLD saved items to "undo" the previous save
                
                // Commit transaction
                $pdo->commit();

                echo json_encode(['success' => true, 'message' => 'Order updated to paid status.']);
            } else {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Order not found.']);
            }
        } else {

            // Insert new order - Generate invoice number with daily counter
            // Use device timestamp if provided for consistent date handling
            $deviceTimestamp = isset($data['device_timestamp']) ? $data['device_timestamp'] : null;
            $orderNumber = generateInvoiceNumber($pdo, $deviceTimestamp);
            $paymongoReference = isset($data['paymongo_reference']) ? $data['paymongo_reference'] : null;

            // Determine payment status
            $paymentStatus = 'unpaid'; // default
            
            if (isset($data['payment_type'])) {
                $paymentType = strtolower($data['payment_type']);
                if ($paymentType === 'cash') {
                    $paymentStatus = 'paid';
                } elseif ($paymentType === 'paymongo' || $paymentType === 'other') {
                    // PayMongo payments start as pending until webhook confirms
                    // Only mark as paid if explicitly confirmed
                    $paymentStatus = isset($data['payment_status']) && $data['payment_status'] === 'paid' ? 'paid' : 'pending';
                }
            } elseif (isset($data['payment_status']) && $data['payment_status'] === 'paid') {
                // Honor explicit paid status
                $paymentStatus = 'paid';
            }

            // If payment_type is cash, set payment_status to paid; if pending (pay later), set to unpaid
            // If payment_type is paymongo, set payment_status based on payment_status param or default to paid
            $orderNotes = isset($data['order_notes']) ? $data['order_notes'] : null;
            
            // Use device timestamp for created_at to ensure accurate daily reset
            $createdAt = isset($data['device_timestamp']) ? $data['device_timestamp'] : date('Y-m-d H:i:s');
            
            // Start transaction
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("INSERT INTO orders (order_number, order_type, payment_type, paymongo_reference, total_amount, amount_tendered, payment_status, note, created_at) VALUES (:order_number, :order_type, :payment_type, :paymongo_reference, :total_amount, :amount_tendered, :payment_status, :note, :created_at)");
            $stmt->execute([
                ':order_number' => $orderNumber,
                ':order_type' => $data['order_type'],
                ':payment_type' => $data['payment_type'],
                ':paymongo_reference' => $paymongoReference,
                ':total_amount' => $data['total_amount'],
                ':amount_tendered' => $data['amount_tendered'],
                ':payment_status' => $paymentStatus,
                ':note' => $orderNotes,
                ':created_at' => $createdAt
            ]);
            
            $orderId = $pdo->lastInsertId();

            // Insert order items
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, qty, price, amount, options, note) VALUES (:order_id, :product_id, :qty, :price, :amount, :options, :note)");
            foreach ($data['items'] as $item) {
                // Extract actual product_id from composite key (format: "productId_size_sugar")
                $productId = $item['id'];
                if (strpos($productId, '_') !== false) {
                    $parts = explode('_', $productId);
                    $productId = intval($parts[0]);
                }
                
                $stmt->execute([
                    ':order_id' => $orderId,
                    ':product_id' => $productId,
                    ':qty' => $item['qty'],
                    ':price' => $item['price'],
                    ':amount' => $item['amount'],
                    ':options' => isset($item['options']) ? $item['options'] : null,
                    ':note' => isset($item['note']) ? $item['note'] : null
                ]);
            }

            // No deduction here; already handled on add/increase
            
            // Commit transaction
            $pdo->commit();

            echo json_encode(['success' => true, 'message' => 'New order created.','order_number' => $orderNumber]);
        }
    } catch (PDOException $e) {
        if ($pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
}
?>