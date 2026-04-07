<?php
session_start();
include('includes/config.php');

if (isset($_POST['link_ingredient_btn'])) {

    $product_id = $_POST['product_id'];
    $product_size = $_POST['product_size'] ?? '16oz';
    $selected = $_POST['selected_ingredients'] ?? [];

    if (empty($selected)) {
        $_SESSION['error_message'] = "No ingredients selected!";
        header("Location: inventory.php");
        exit();
    }

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $count = 0;
        foreach ($selected as $ingredient_id) {

            // Get quantity & unit for this ingredient
            $qty = $_POST['ingredient_quantity_' . $ingredient_id] ?? 0;
            $unit = $_POST['ingredient_unit_' . $ingredient_id] ?? 'grams';

            // Basic validation
            if ($qty <= 0) continue;

            // Check if already exists
            $check = $pdo->prepare("SELECT id FROM product_ingredients WHERE product_id = ? AND ingredient_id = ?");
            $check->execute([$product_id, $ingredient_id]);
            
            if ($check->fetch()) {
                // Update existing
                $stmt = $pdo->prepare("
                    UPDATE product_ingredients 
                    SET quantity_required = :qty, unit = :unit 
                    WHERE product_id = :pid AND ingredient_id = :iid
                ");
            } else {
                // Insert new
                $stmt = $pdo->prepare("
                    INSERT INTO product_ingredients (product_id, ingredient_id, quantity_required, unit)
                    VALUES (:pid, :iid, :qty, :unit)
                ");
            }

            $stmt->execute([
                'pid'  => $product_id,
                'iid'  => $ingredient_id,
                'qty'  => $qty,
                'unit' => $unit
            ]);
            $count++;
        }

        if ($count > 0) {
            $_SESSION['success_message'] = "Successfully linked $count ingredient(s) to the product ($product_size)!";
        } else {
            $_SESSION['error_message'] = "No ingredients added. Please enter quantities for selected ingredients!";
        }
        
        header("Location: inventory.php");
        exit();

    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        header("Location: inventory.php");
        exit();
    }
}

if (isset($_POST['unlink_ingredient_btn'])) {
    $id = $_POST['id'] ?? 0;

    if (!$id) {
        $_SESSION['error_message'] = "Invalid ingredient link!";
        header("Location: inventory.php");
        exit();
    }

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("DELETE FROM product_ingredients WHERE id = ?");
        $stmt->execute([$id]);

        $_SESSION['success_message'] = "Ingredient unlinked from product successfully!";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }

    header("Location: inventory.php");
    exit();
}
?>
