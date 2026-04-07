<?php
session_start();
include('includes/config.php');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Database connection failed: " . $e->getMessage();
    header('Location: manage_category_ingredients.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Add ingredient to category
        if (isset($_POST['add_category_ingredient_btn'])) {
            $category_id = $_POST['category_id'] ?? null;
            $ingredient_id = $_POST['ingredient_id'] ?? null;
            $quantity_requirement = $_POST['quantity_requirement'] ?? null;
            $unit = $_POST['unit'] ?? null;
            $size = $_POST['size'] ?? '16oz';
            $is_shared = isset($_POST['is_shared']) && $_POST['is_shared'] == '1' ? 1 : 0;

            if (!$ingredient_id || !$quantity_requirement || !$unit) {
                throw new Exception("All fields are required");
            }
            
            // For shared ingredients, category_id can be 0 or NULL
            if (!$is_shared && !$category_id) {
                throw new Exception("Category is required for non-shared ingredients");
            }

            // Check if already linked with same size
            if ($is_shared) {
                $check_stmt = $pdo->prepare("
                    SELECT id FROM category_ingredients 
                    WHERE is_shared = 1 AND ingredient_id = :ingredient_id AND size = :size
                ");
                $check_stmt->execute([
                    ':ingredient_id' => $ingredient_id,
                    ':size' => $size
                ]);
            } else {
                $check_stmt = $pdo->prepare("
                    SELECT id FROM category_ingredients 
                    WHERE category_id = :category_id AND ingredient_id = :ingredient_id AND size = :size
                ");
                $check_stmt->execute([
                    ':category_id' => $category_id,
                    ':ingredient_id' => $ingredient_id,
                    ':size' => $size
                ]);
            }

            if ($check_stmt->fetch()) {
                throw new Exception("This ingredient is already linked" . ($is_shared ? " as shared" : " to this category") . " for size " . $size);
            }

            $stmt = $pdo->prepare("
                INSERT INTO category_ingredients (category_id, ingredient_id, quantity_requirement, unit, size, is_shared)
                VALUES (:category_id, :ingredient_id, :quantity_requirement, :unit, :size, :is_shared)
            ");
            $stmt->execute([
                ':category_id' => $is_shared ? null : $category_id,
                ':ingredient_id' => $ingredient_id,
                ':quantity_requirement' => $quantity_requirement,
                ':unit' => $unit,
                ':size' => $size,
                ':is_shared' => $is_shared
            ]);

            $_SESSION['success_message'] = $is_shared ? "Shared ingredient added successfully!" : "Ingredient linked to category successfully!";
        }
        // Edit ingredient in category
        elseif (isset($_POST['edit_category_ingredient_btn'])) {
            $category_ingredient_id = $_POST['category_ingredient_id'] ?? null;
            $quantity_requirement = $_POST['quantity_requirement'] ?? null;
            $unit = $_POST['unit'] ?? null;
            $size = $_POST['size'] ?? '16oz';

            if (!$category_ingredient_id || !$quantity_requirement || !$unit) {
                throw new Exception("All fields are required");
            }

            $stmt = $pdo->prepare("
                UPDATE category_ingredients 
                SET quantity_requirement = :quantity_requirement, unit = :unit, size = :size
                WHERE id = :id
            ");
            $stmt->execute([
                ':quantity_requirement' => $quantity_requirement,
                ':unit' => $unit,
                ':size' => $size,
                ':id' => $category_ingredient_id
            ]);

            $_SESSION['success_message'] = "Ingredient requirement updated successfully!";
        }
        // Delete ingredient from category
        elseif (isset($_POST['delete_category_ingredient_btn'])) {
            $category_ingredient_id = $_POST['category_ingredient_id'] ?? null;

            if (!$category_ingredient_id) {
                throw new Exception("Invalid request");
            }

            $stmt = $pdo->prepare("DELETE FROM category_ingredients WHERE id = :id");
            $stmt->execute([':id' => $category_ingredient_id]);

            $_SESSION['success_message'] = "Ingredient removed from category successfully!";
        }
        else {
            throw new Exception("Invalid action");
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
}

header('Location: manage_category_ingredients.php');
exit();

