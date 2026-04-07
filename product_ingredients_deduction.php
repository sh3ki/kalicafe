<?php
session_start();
include('includes/header.php');
include('includes/navbar.php');
include('includes/config.php');
include('includes/auth.php');

checkAccess(['admin', 'cashier']);

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Database connection failed: " . $e->getMessage();
    header('Location: products.php');
    exit();
}

// Handle deduction when product is used
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deduct_ingredients_btn'])) {
    $product_id = $_POST['product_id'] ?? null;
    $quantity = (int)($_POST['quantity'] ?? 1);

    if ($product_id && $quantity > 0) {
        try {
            // Get product details
            $product_stmt = $pdo->prepare("SELECT * FROM products WHERE id = :product_id");
            $product_stmt->execute([':product_id' => $product_id]);
            $product = $product_stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                throw new Exception("Product not found");
            }

            // Get category ingredients for this product
            $cat_ing_stmt = $pdo->prepare("
                SELECT ci.*, i.id as ingredient_id, i.name as ingredient_name, i.quantity as current_quantity
                FROM category_ingredients ci
                JOIN ingredients i ON ci.ingredient_id = i.id
                WHERE ci.category_id = :category_id
            ");
            $cat_ing_stmt->execute([':category_id' => $product['category_id']]);
            $category_ingredients = $cat_ing_stmt->fetchAll(PDO::FETCH_ASSOC);

            // Deduct each ingredient
            foreach ($category_ingredients as $ing) {
                $deduct_amount = $ing['quantity_requirement'] * $quantity;
                
                $update_stmt = $pdo->prepare("
                    UPDATE ingredients 
                    SET quantity = quantity - :deduct_amount 
                    WHERE id = :ingredient_id
                ");
                $update_stmt->execute([
                    ':deduct_amount' => $deduct_amount,
                    ':ingredient_id' => $ing['ingredient_id']
                ]);
            }

            $_SESSION['success_message'] = "âœ“ Ingredients deducted successfully for " . $quantity . " unit(s) of " . htmlspecialchars($product['product_name']);
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Error: " . $e->getMessage();
        }
        header('Location: product_ingredients_deduction.php?product_id=' . $product_id);
        exit();
    }
}

// Get product ID from URL
$product_id = $_GET['product_id'] ?? null;
$product = null;
$category_ingredients = [];

if ($product_id) {
    $product_stmt = $pdo->prepare("SELECT * FROM products WHERE id = :product_id");
    $product_stmt->execute([':product_id' => $product_id]);
    $product = $product_stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        // Get category name
        $cat_stmt = $pdo->prepare("SELECT name FROM categories WHERE id = :id");
        $cat_stmt->execute([':id' => $product['category_id']]);
        $category = $cat_stmt->fetch(PDO::FETCH_ASSOC);

        // Get category ingredients
        $ing_stmt = $pdo->prepare("
            SELECT ci.id, ci.category_id, ci.ingredient_id, ci.quantity_requirement, ci.unit,
                   i.name as ingredient_name, i.quantity as current_stock
            FROM category_ingredients ci
            JOIN ingredients i ON ci.ingredient_id = i.id
            WHERE ci.category_id = :category_id
            ORDER BY i.name
        ");
        $ing_stmt->execute([':category_id' => $product['category_id']]);
        $category_ingredients = $ing_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>KUSSO - Product Ingredients & Deduction</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>

<body>
<div id="layoutSidenav_content">
    <main>
        <div class="container-fluid px-4">
            <div class="d-flex justify-content-between align-items-center mt-4">
                <h1>Product Ingredients & Deduction</h1>
                <a href="products.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Products
                </a>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show mt-3">
                    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show mt-3">
                    <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!$product): ?>
                <div class="alert alert-warning mt-4">
                    <i class="fas fa-info-circle me-2"></i> Select a product to view its ingredients and deduct stock.
                </div>

                <!-- Product Selection -->
                <div class="card shadow mt-4">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-box me-2"></i> Select Product
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php
                            $prod_stmt = $pdo->query("SELECT id, product_name, category_id FROM products WHERE status = 1 ORDER BY product_name");
                            $products = $prod_stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            foreach ($products as $prod) {
                                echo '<div class="col-md-6 col-lg-4 mb-3">';
                                echo '<a href="product_ingredients_deduction.php?product_id=' . $prod['id'] . '" class="text-decoration-none">';
                                echo '<div class="card border-2 border-primary" style="cursor: pointer;">';
                                echo '<div class="card-body">';
                                echo '<h6 class="card-title">' . htmlspecialchars($prod['product_name']) . '</h6>';
                                echo '</div>';
                                echo '</div>';
                                echo '</a>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Product Details Card -->
                <div class="card shadow mt-4">
                    <div class="card-header" style="background-color: #c67c4e; color: white;">
                        <h5 class="mb-0">
                            <i class="fas fa-box me-2"></i><?php echo htmlspecialchars($product['product_name']); ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Category:</strong> <?php echo htmlspecialchars($category['name'] ?? 'N/A'); ?></p>
                                <p><strong>Product ID:</strong> #<?php echo $product['id']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($category_ingredients)): ?>
                    <!-- Category Ingredients Required -->
                    <div class="card shadow mt-4">
                        <div class="card-header bg-success text-white">
                            <i class="fas fa-list me-2"></i> Required Ingredients (Per Unit)
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Ingredient</th>
                                            <th>Required per Unit</th>
                                            <th>Current Stock</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($category_ingredients as $ing): ?>
                                            <?php 
                                                $can_make = floor($ing['current_stock'] / $ing['quantity_requirement']);
                                                $status_color = $can_make > 0 ? 'success' : 'danger';
                                                $status_text = $can_make > 0 ? 'Available (' . $can_make . ' units possible)' : 'Low Stock!';
                                            ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($ing['ingredient_name']); ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $ing['quantity_requirement']; ?> <?php echo htmlspecialchars($ing['unit']); ?></span>
                                                </td>
                                                <td>
                                                    <?php echo $ing['current_stock']; ?> <?php echo htmlspecialchars($ing['unit']); ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $status_color; ?>">
                                                        <?php echo $status_text; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Deduction Form -->
                    <div class="card shadow mt-4">
                        <div class="card-header bg-warning text-dark">
                            <i class="fas fa-minus-circle me-2"></i> Deduct Ingredients
                        </div>
                        <div class="card-body">
                            <form action="product_ingredients_deduction.php" method="POST">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                
                                <div class="mb-3">
                                    <label for="quantity" class="form-label">
                                        <strong>Quantity of <?php echo htmlspecialchars($product['product_name']); ?> to Make:</strong>
                                    </label>
                                    <input type="number" class="form-control form-control-lg" id="quantity" name="quantity" value="1" min="1" required>
                                    <small class="text-muted">Enter how many units you're making</small>
                                </div>

                                <div class="alert alert-info">
                                    <strong>âš ï¸ Deduction Preview:</strong><br>
                                    <?php 
                                    $all_available = true;
                                    foreach ($category_ingredients as $ing): 
                                        $deduct = $ing['quantity_requirement'];
                                    ?>
                                        <div>â€¢ <strong><?php echo htmlspecialchars($ing['ingredient_name']); ?>:</strong> Will deduct <?php echo $deduct; ?> <?php echo htmlspecialchars($ing['unit']); ?> per unit</div>
                                    <?php 
                                        if ($ing['current_stock'] < $deduct) {
                                            $all_available = false;
                                        }
                                    endforeach; 
                                    ?>
                                </div>

                                <?php if (!$all_available): ?>
                                    <div class="alert alert-danger">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>Warning:</strong> Some ingredients may not have enough stock. Check the table above.
                                    </div>
                                <?php endif; ?>

                                <button type="submit" name="deduct_ingredients_btn" class="btn btn-lg btn-warning" style="min-width: 200px;">
                                    <i class="fas fa-check me-2"></i> Deduct Ingredients
                                </button>
                                <a href="inventory.php" class="btn btn-lg btn-secondary ms-2">
                                    <i class="fas fa-times me-2"></i> Cancel
                                </a>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mt-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>No linked ingredients:</strong> This category doesn't have any ingredients linked yet. 
                        Go to "Manage Category Ingredients" to add ingredients.
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

