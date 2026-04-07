<?php
session_start();
include('includes/config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if (isset($_POST['add_ingredient_btn'])) {
            // Add a new ingredient
            $name = $_POST['name'];
            $quantity = $_POST['quantity'];
            $unit = $_POST['unit'];

            $stmt = $pdo->prepare("INSERT INTO ingredients (name, quantity, unit) VALUES (:name, :quantity, :unit)");
            $stmt->execute([
                ':name' => $name,
                ':quantity' => $quantity,
                ':unit' => $unit
            ]);

            $_SESSION['success_message'] = "Ingredient added successfully!";
        } elseif (isset($_POST['edit_ingredient_btn'])) {
            // Edit an ingredient - add to existing quantity
            $id = $_POST['id'];
            $name = $_POST['name'];
            $add_quantity = $_POST['add_quantity'];
            $unit = $_POST['unit'];

            // Get current quantity
            $stmt = $pdo->prepare("SELECT quantity FROM ingredients WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $current = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Add the new quantity to existing quantity
            $new_quantity = $current['quantity'] + $add_quantity;

            $stmt = $pdo->prepare("UPDATE ingredients SET name = :name, quantity = :quantity, unit = :unit WHERE id = :id");
            $stmt->execute([
                ':name' => $name,
                ':quantity' => $new_quantity,
                ':unit' => $unit,
                ':id' => $id
            ]);

            // Log the activity only if quantity was added
            if ($add_quantity > 0) {
                // Clean up old logs (older than 3 months)
                $cleanupStmt = $pdo->prepare("DELETE FROM inventory_activity_log WHERE action_date < DATE_SUB(NOW(), INTERVAL 3 MONTH)");
                $cleanupStmt->execute();
                
                $logStmt = $pdo->prepare("INSERT INTO inventory_activity_log (ingredient_id, ingredient_name, quantity_added, previous_quantity, unit) VALUES (:ingredient_id, :ingredient_name, :quantity_added, :previous_quantity, :unit)");
                $logStmt->execute([
                    ':ingredient_id' => $id,
                    ':ingredient_name' => $name,
                    ':quantity_added' => $add_quantity,
                    ':previous_quantity' => $current['quantity'],
                    ':unit' => $unit
                ]);
            }

            $_SESSION['success_message'] = "Ingredient updated successfully! Added " . $add_quantity . " " . $unit;
        } elseif (isset($_POST['delete_ingredient_btn'])) {
            // Delete an ingredient
            $id = $_POST['id'];

            $stmt = $pdo->prepare("DELETE FROM ingredients WHERE id = :id");
            $stmt->execute([':id' => $id]);

            $_SESSION['success_message'] = "Ingredient deleted successfully!";
        } elseif (isset($_POST['link_ingredient_btn'])) {
            // Link an ingredient to a product
            $product_id = $_POST['product_id'];
            $ingredient_id = $_POST['ingredient_id'];
            $quantity_required = $_POST['quantity_required'];

            $stmt = $pdo->prepare("INSERT INTO product_ingredients (product_id, ingredient_id, quantity_required) VALUES (:product_id, :ingredient_id, :quantity_required)");
            $stmt->execute([
                ':product_id' => $product_id,
                ':ingredient_id' => $ingredient_id,
                ':quantity_required' => $quantity_required
            ]);

            $_SESSION['success_message'] = "Ingredient linked to product successfully!";
        } elseif (isset($_POST['unlink_ingredient_btn'])) {
            // Unlink an ingredient from a product
            $id = $_POST['id'];

            $stmt = $pdo->prepare("DELETE FROM product_ingredients WHERE id = :id");
            $stmt->execute([':id' => $id]);

            $_SESSION['success_message'] = "Ingredient unlinked from product successfully!";
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }

    header("Location: inventory.php");
    exit();
}
?>
