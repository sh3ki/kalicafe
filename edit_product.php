<?php 
session_start();
include('includes/header.php');
include('includes/navbar.php');
include('includes/config.php');

// Fetch product details
if (isset($_GET['id'])) {
    $productID = $_GET['id'];
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Fetch product details
        $stmt = $pdo->prepare("
            SELECT 
                p.*, 
                c.name AS category_name 
            FROM 
                products p
            JOIN 
                categories c 
            ON 
                p.category_id = c.id 
            WHERE 
                p.id = :id
        ");
        $stmt->execute(['id' => $productID]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $_SESSION['error_message'] = "Product not found.";
            header('Location: products.php'); // Redirect to the product list
            exit();
        }

        // Fetch all categories for the dropdown, including type
        $categoriesStmt = $pdo->query("SELECT id, name, type FROM categories");
        $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

        // Build a PHP array for category types for JS
        $categoryTypes = [];
        foreach ($categories as $cat) {
            $categoryTypes[$cat['id']] = $cat['type'];
        }

        // Determine current category type for this product
        $currentCategoryType = null;
        $currentCategoryName = null;
        foreach ($categories as $cat) {
            if ($cat['id'] == $product['category_id']) {
                $currentCategoryType = $cat['type'];
                $currentCategoryName = $cat['name'];
                break;
            }
        }

        // If product is drinks, decode price JSON
        $drinkPrices = ['16oz' => '', '22oz' => '', 'hot' => ''];
        if ($currentCategoryType === 'Drinks' && !empty($product['price'])) {
            $decoded = json_decode($product['price'], true);
            if (is_array($decoded)) {
                $drinkPrices['16oz'] = isset($decoded['16oz']) ? $decoded['16oz'] : '';
                $drinkPrices['22oz'] = isset($decoded['22oz']) ? $decoded['22oz'] : '';
                $drinkPrices['hot'] = isset($decoded['hot']) ? $decoded['hot'] : '';
            }
        }

    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error fetching product: " . $e->getMessage();
        header('Location: products.php');
        exit();
    }
} else {
    header('Location: products.php'); // Redirect if no ID is provided
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>KUSSO - Edit Product</title>
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>

<body>
    <div id="layoutSidenav_content">
        <main>
            <div class="container-fluid px-4">
                <!-- Title Section -->
                <h1 class="mt-4">Edit Product</h1>

                <!-- Display Error/Success Messages -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success">
                        <?php 
                            echo $_SESSION['success_message'];
                            unset($_SESSION['success_message']);
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger">
                        <?php 
                            echo $_SESSION['error_message'];
                            unset($_SESSION['error_message']);
                        ?>
                    </div>
                <?php endif; ?>

                <form action="manage_products.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="edit_product_id" value="<?php echo htmlspecialchars($product['id']); ?>">

                        <div class="mb-3">
                            <label for="edit_product_name" class="form-label">Product Name</label>
                            <input type="text" 
                                class="form-control" 
                                id="edit_product_name" 
                                name="edit_product_name" 
                                value="<?php echo htmlspecialchars($product['product_name']); ?>" 
                                placeholder="Enter product name" 
                                required>
                        </div>

                        <div class="mb-3">
                            <label for="edit_category" class="form-label">Category</label>
                            <select class="form-select" id="edit_category" name="edit_category" required>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                        <?php echo $category['id'] == $product['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>


                        <!-- Options Dropdown (for Food only) -->
                        <div class="mb-3" id="options_group">
                            <label for="edit_options" class="form-label">Options</label>
                            <select class="form-select" id="edit_options" name="edit_options">
                                <option value="none" <?php echo $product['options'] === 'none' ? 'selected' : ''; ?>>None</option>
                                <option value="16oz" <?php echo $product['options'] === '16oz' ? 'selected' : ''; ?>>16oz</option>
                                <option value="22oz" <?php echo $product['options'] === '22oz' ? 'selected' : ''; ?>>22oz</option>
                            </select>
                        </div>

                        <!-- Price fields for Food only -->
                        <div class="mb-3 d-none" id="price_none_group">
                            <label for="edit_price_none" class="form-label">Price</label>
                            <input type="number" step="0.01" class="form-control" id="edit_price_none" name="edit_price_none" value="<?php echo ($product['options'] === 'none' && $currentCategoryType !== 'Drinks') ? htmlspecialchars($product['price']) : ''; ?>" placeholder="Enter Price">
                        </div>
                        <div class="mb-3 d-none" id="price_16oz_group">
                            <label for="edit_price_16oz" class="form-label">Price (16oz)</label>
                            <input type="number" step="0.01" class="form-control" id="edit_price_16oz" name="edit_price_16oz" value="<?php echo ($product['options'] === '16oz' && $currentCategoryType !== 'Drinks') ? htmlspecialchars($product['price']) : ''; ?>" placeholder="Enter 16oz Price">
                        </div>
                        <div class="mb-3 d-none" id="price_22oz_group">
                            <label for="edit_price_22oz" class="form-label">Price (22oz)</label>
                            <input type="number" step="0.01" class="form-control" id="edit_price_22oz" name="edit_price_22oz" value="<?php echo ($product['options'] === '22oz' && $currentCategoryType !== 'Drinks') ? htmlspecialchars($product['price']) : ''; ?>" placeholder="Enter 22oz Price">
                        </div>

                        <!-- Price fields for Drinks only -->
                        <div class="mb-3 d-none" id="drinks_prices_group">
                            <label class="form-label">Drinks Prices</label>
                            <div class="row g-2">
                                <div class="col">
                                    <input type="number" step="0.01" class="form-control" id="edit_price_drink_16oz" name="edit_price_drink_16oz" placeholder="16oz Price" value="<?php echo htmlspecialchars($drinkPrices['16oz']); ?>">
                                </div>
                                <div class="col">
                                    <input type="number" step="0.01" class="form-control" id="edit_price_drink_22oz" name="edit_price_drink_22oz" placeholder="22oz Price" value="<?php echo htmlspecialchars($drinkPrices['22oz']); ?>">
                                </div>
                                <div class="col" id="hot_price_col">
                                    <input type="number" step="0.01" class="form-control" id="edit_price_drink_hot" name="edit_price_drink_hot" placeholder="Hot Price" value="<?php echo htmlspecialchars($drinkPrices['hot']); ?>">
                                </div>
                            </div>
                        </div>

                         <!-- Status Dropdown -->
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <div class="form-check form-switch">
                                <input type="hidden" name="status" value="0">
                                <input class="form-check-input" 
                                    type="checkbox" 
                                    id="status" 
                                    name="status" 
                                    value="1" 
                                    <?php echo $product['status'] == 1 ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="status">Available</label>
                            </div>
                        </div>

                         <!-- Image Upload -->
                        <div class="mb-3">
                            <label for="product_image" class="form-label">Product Image</label>
                            <input type="file" class="form-control" id="product_image" name="product_image" accept="image/*">
                            <?php if ($product['image']): ?>
                                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="Product Image" style="width: 100px; height: 100px;">
                                <button type="submit" name="delete_image_btn" class="btn btn-danger mt-2"> <i class="fas fa-trash"></i></button>
                            <?php endif; ?>
                        </div>

                        <!-- Cancel and Save Changes Buttons -->
                        <a href="products.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" name="updateproduct_btn" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>

            </div>
        </main>

    <script>
    // Category types from PHP
    const categoryTypes = <?php echo json_encode($categoryTypes ?? []); ?>;
    // Category names from database
    const categoryNames = {};
    const categoriesData = <?php echo json_encode($categories ?? []); ?>;
    categoriesData.forEach(cat => {
        categoryNames[cat.id] = cat.name;
    });
    // Current product's category type
    let currentCategoryType = '<?php echo $currentCategoryType; ?>';
    const hotCategories = ['Coffee', 'Coffee Blended']; // Categories that should show hot price

    function handleCategoryTypeLogic() {
        const categorySelect = document.getElementById('edit_category');
        const selectedCatId = categorySelect.value;
        const type = categoryTypes[selectedCatId];
        const categoryName = categoryNames[selectedCatId];

        const optionsGroup = document.getElementById('options_group');
        const priceNoneGroup = document.getElementById('price_none_group');
        const price16ozGroup = document.getElementById('price_16oz_group');
        const price22ozGroup = document.getElementById('price_22oz_group');
        const drinksPricesGroup = document.getElementById('drinks_prices_group');
        const hotPriceCol = document.getElementById('hot_price_col');

        // Hide all price fields
        [priceNoneGroup, price16ozGroup, price22ozGroup].forEach(g => g.classList.add('d-none'));
        drinksPricesGroup.classList.add('d-none');

        // Always hide options for all types
        optionsGroup.classList.add('d-none');
        if (type === 'Drinks') {
            drinksPricesGroup.classList.remove('d-none');
            // Show hot price only for specific categories
            if (hotCategories.includes(categoryName)) {
                hotPriceCol.classList.remove('d-none');
            } else {
                hotPriceCol.classList.add('d-none');
            }
        } else {
            handleDropdownLogic();
        }
    }

    function handleDropdownLogic() {
        const selectedOption = document.getElementById('edit_options').value;
        const priceNoneGroup = document.getElementById('price_none_group');
        const price16ozGroup = document.getElementById('price_16oz_group');
        const price22ozGroup = document.getElementById('price_22oz_group');
        // Hide all
        [priceNoneGroup, price16ozGroup, price22ozGroup].forEach(g => g.classList.add('d-none'));
        if (selectedOption === 'none') {
            priceNoneGroup.classList.remove('d-none');
        } else if (selectedOption === '16oz') {
            price16ozGroup.classList.remove('d-none');
        } else if (selectedOption === '22oz') {
            price22ozGroup.classList.remove('d-none');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('edit_category').addEventListener('change', handleCategoryTypeLogic);
        document.getElementById('edit_options').addEventListener('change', handleDropdownLogic);
    handleCategoryTypeLogic();
    // Always hide options on page load
    document.getElementById('options_group').classList.add('d-none');
    });
    </script>

    <?php
    include('includes/footer.php');
    include('includes/scripts.php');
    ?>
</body>

</html>

