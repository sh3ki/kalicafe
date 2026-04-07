<?php
// Start the session
session_start();

// Include the database configuration
include('includes/config.php');

try {
    // Establish database connection
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Handle database connection failure
    $_SESSION['error_message'] = "Database connection failed: " . $e->getMessage();
    header('Location: products.php');
    exit();
}

// Ensure the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Invalid request method.";
    header('Location: products.php');
    exit();
}

// Handle the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Handle Product Update
    if (isset($_POST['updateproduct_btn'])) {
        handleProductUpdate($pdo);
    } 
    // Handle Adding New Product
    elseif (isset($_POST['add_product_btn'])) {
        handleAddProduct($pdo);
    } 
    // Handle Product Deletion
    elseif (isset($_POST['deleteproduct_btn'])) {
        handleDeleteProduct($pdo);
    } 
    // Handle Image Deletion
    elseif (isset($_POST['delete_image_btn'])) {
        handleImageDelete($pdo);
    } 
    // Handle Invalid Form Submission
    else {
        $_SESSION['error_message'] = "Invalid form submission.";
        header('Location: products.php');
        exit();
    }

} else {
    $_SESSION['error_message'] = "Invalid request method.";
    header('Location: products.php');
    exit();
}


function handleProductUpdate($pdo) {
    $product_id = $_POST['edit_product_id'] ?? '';
    $product_name = trim($_POST['edit_product_name'] ?? '');
    $category_id = $_POST['edit_category'] ?? '';
    $options = $_POST['edit_options'] ?? '';
    $status = $_POST['status'] ?? 0;

    // Get category type and name
    $cat_stmt = $pdo->prepare("SELECT type, name FROM categories WHERE id = :id");
    $cat_stmt->execute([':id' => $category_id]);
    $cat = $cat_stmt->fetch(PDO::FETCH_ASSOC);
    $category_type = $cat ? $cat['type'] : null;
    $category_name = $cat ? $cat['name'] : null;
    $hotCategories = ['Coffee', 'Coffee Blended'];

    $price = null;
    $optionsToStore = $options;
    if ($category_type === 'Drinks') {
        // For drinks, ignore options, require all prices
        $price_16oz = isset($_POST['edit_price_drink_16oz']) ? floatval($_POST['edit_price_drink_16oz']) : null;
        $price_22oz = isset($_POST['edit_price_drink_22oz']) ? floatval($_POST['edit_price_drink_22oz']) : null;
        // Only include hot price if category is in hot categories
        $price_hot = (in_array($category_name, $hotCategories) && isset($_POST['edit_price_drink_hot'])) ? floatval($_POST['edit_price_drink_hot']) : null;
        $price = json_encode(['16oz' => $price_16oz, '22oz' => $price_22oz, 'hot' => $price_hot]);
        $optionsToStore = 'drinks';
    } else {
        // For food, use options and one price
        switch ($options) {
            case 'none':
                $price = isset($_POST['edit_price_none']) ? floatval($_POST['edit_price_none']) : 0.0;
                break;
            case '16oz':
                $price = isset($_POST['edit_price_16oz']) ? floatval($_POST['edit_price_16oz']) : 0.0;
                break;
            case '22oz':
                $price = isset($_POST['edit_price_22oz']) ? floatval($_POST['edit_price_22oz']) : 0.0;
                break;
            default:
                $price = 0.0;
        }
    }

    // Handle file upload
    $target_file = null;
    if (isset($_FILES["product_image"]) && $_FILES["product_image"]["error"] == 0) {
        $target_dir = "assets/img/";
        $target_file = $target_dir . basename($_FILES["product_image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $check = getimagesize($_FILES["product_image"]["tmp_name"]);
        if ($check === false) {
            $_SESSION['error_message'] = "File is not an image.";
            header("Location: edit_product.php?id=$product_id");
            exit();
        }

        if ($_FILES["product_image"]["size"] > 5000000) {
            $_SESSION['error_message'] = "Sorry, your file is too large.";
            header("Location: edit_product.php?id=$product_id");
            exit();
        }

        if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            $_SESSION['error_message'] = "Only JPG, JPEG, PNG, and GIF files are allowed.";
            header("Location: edit_product.php?id=$product_id");
            exit();
        }

        if (!move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
            $_SESSION['error_message'] = "Error uploading your file.";
            header("Location: edit_product.php?id=$product_id");
            exit();
        }
    } else {
        $stmt = $pdo->prepare("SELECT image FROM products WHERE id = :id");
        $stmt->execute([':id' => $product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        $target_file = $product['image'] ?: "assets/img/kalicafe_logo.jpg"; // Use existing or default image
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE products 
            SET product_name = :name, category_id = :category_id, options = :options, price = :price, status = :status, image = :image
            WHERE id = :id
        ");
        $stmt->execute([
            ':name' => $product_name,
            ':category_id' => $category_id,
            ':options' => $optionsToStore,
            ':price' => $price,
            ':status' => $status,
            ':image' => $target_file,
            ':id' => $product_id
        ]);

        $_SESSION['success_message'] = "Product updated successfully.";
        header('Location: products.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error updating product: " . $e->getMessage();
        header("Location: edit_product.php?id=$product_id");
        exit();
    }
}

/**
 * Handle Adding New Product Logic
 */

function handleAddProduct($pdo) {
    $product_name = isset($_POST['product_name']) ? trim($_POST['product_name']) : null;
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $status = isset($_POST['status']) ? 1 : 0;
    $option = isset($_POST['options']) ? $_POST['options'] : null;

    // Get category type and name
    $cat_stmt = $pdo->prepare("SELECT type, name FROM categories WHERE id = :id");
    $cat_stmt->execute([':id' => $category_id]);
    $cat = $cat_stmt->fetch(PDO::FETCH_ASSOC);
    $category_type = $cat ? $cat['type'] : null;
    $category_name = $cat ? $cat['name'] : null;
    $hotCategories = ['Coffee', 'Coffee Blended'];

    $price = null;
    $optionsToStore = $option;
    if ($category_type === 'Drinks') {
        // For drinks, ignore options, require all 3 prices
        $price_16oz = isset($_POST['price_drink_16oz']) ? floatval($_POST['price_drink_16oz']) : null;
        $price_22oz = isset($_POST['price_drink_22oz']) ? floatval($_POST['price_drink_22oz']) : null;
        // Only require hot price if category is in hot categories
        $price_hot = (in_array($category_name, $hotCategories) && isset($_POST['price_drink_hot'])) ? floatval($_POST['price_drink_hot']) : null;
        if (!$product_name || !$category_id || $price_16oz === null || $price_22oz === null) {
            $_SESSION['error_message'] = "Please fill all required fields for drinks.";
            $_SESSION['old_input'] = $_POST;
            header('Location: products.php');
            exit();
        }
        // For hot categories, require hot price
        if (in_array($category_name, $hotCategories) && $price_hot === null) {
            $_SESSION['error_message'] = "Please fill the hot price for this category.";
            $_SESSION['old_input'] = $_POST;
            header('Location: products.php');
            exit();
        }
        $price = json_encode(['16oz' => $price_16oz, '22oz' => $price_22oz, 'hot' => $price_hot]);
        $optionsToStore = 'drinks';
    } else {
        // For food, use options and one price
        switch ($option) {
            case 'none':
                $price = isset($_POST['price_none']) ? floatval($_POST['price_none']) : 0.0;
                break;
            case '16oz':
                $price = isset($_POST['price_16oz']) ? floatval($_POST['price_16oz']) : 0.0;
                break;
            case '22oz':
                $price = isset($_POST['price_22oz']) ? floatval($_POST['price_22oz']) : 0.0;
                break;
            default:
                $_SESSION['error_message'] = "Invalid option selected.";
                header('Location: products.php');
                exit();
        }
        if (!$product_name || !$category_id || !$option) {
            $_SESSION['error_message'] = "Please fill all required fields.";
            $_SESSION['old_input'] = $_POST;
            header('Location: products.php');
            exit();
        }
    }

    // Handle file upload or default image
    if (isset($_FILES["product_image"]) && $_FILES["product_image"]["error"] == 0) {
        $target_dir = "assets/img/";
        $target_file = $target_dir . basename($_FILES["product_image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES["product_image"]["tmp_name"]);
        if ($check === false) {
            $_SESSION['error_message'] = "File is not an image.";
            header('Location: products.php');
            exit();
        }

        // Check file size
        if ($_FILES["product_image"]["size"] > 5000000) {
            $_SESSION['error_message'] = "Sorry, your file is too large.";
            header('Location: products.php');
            exit();
        }

        // Allow certain file formats
        if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            $_SESSION['error_message'] = "Only JPG, JPEG, PNG, and GIF files are allowed.";
            header('Location: products.php');
            exit();
        }

        // Try to upload file
        if (!move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
            $_SESSION['error_message'] = "Error uploading your file.";
            header('Location: products.php');
            exit();
        }
    } else {
        // Default image if no file is uploaded
        $target_file = "assets/img/kalicafe_logo.jpg";
    }
    try {
        $stmt = $pdo->prepare("
            INSERT INTO products (product_name, category_id, options, price, status, image)
            VALUES (:product_name, :category_id, :options, :price, :status, :image)
        ");
        $stmt->execute([
            ':product_name' => $product_name,
            ':category_id' => $category_id,
            ':options' => $optionsToStore,
            ':price' => $price,
            ':status' => $status,
            ':image' => $target_file
        ]);

        $_SESSION['success_message'] = "Product added successfully.";
        header('Location: products.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Failed to save product: " . $e->getMessage();
        header('Location: products.php');
        exit();
    }
}

function handleImageDelete($pdo) {
    $product_id = $_POST['edit_product_id'] ?? '';

    // Validate product ID
    if (empty($product_id)) {
        $_SESSION['error_message'] = "Invalid product ID.";
        header("Location: edit_product.php?id=$product_id");
        exit();
    }

    try {
        // Fetch the current image path
        $stmt = $pdo->prepare("SELECT image FROM products WHERE id = :id");
        $stmt->execute([':id' => $product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product && $product['image']) {
            // Delete the image file from the server
            if (file_exists($product['image'])) {
                unlink($product['image']);
            }

            // Update the database to remove the image path
            $stmt = $pdo->prepare("UPDATE products SET image = NULL WHERE id = :id");
            $stmt->execute([':id' => $product_id]);

            $_SESSION['success_message'] = "Product image deleted successfully.";
        } else {
            $_SESSION['error_message'] = "Product image not found.";
        }

        header("Location: edit_product.php?id=$product_id");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error deleting product image: " . $e->getMessage();
        header("Location: edit_product.php?id=$product_id");
        exit();
    }
}

/**
 * Handle Product Deletion Logic
 */
function handleDeleteProduct($pdo) {
    $product_id = $_POST['id'] ?? null;

    if (!$product_id) {
        $_SESSION['error_message'] = "Invalid request.";
        header('Location: products.php');
        exit();
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
        $stmt->execute([':id' => $product_id]);

        $_SESSION['success_message'] = "Product deleted successfully.";
        header('Location: products.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error deleting product: " . $e->getMessage();
        header('Location: products.php');
        exit();
    }
}
?>

