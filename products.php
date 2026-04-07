<?php 
    session_start();
    include ('includes/header.php');
    include('includes/navbar.php');
    include('includes/config.php');
    include('includes/auth.php');

    // Allow only admin and cashier
    checkAccess(['admin', 'cashier']);
?>

<!DOCTYPE html>
<html lang="en"></html>
<head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>KUSSO-Categories</title>
        <link href="css/styles.css" rel="stylesheet" />
        <style>
        /* Reduced size for category buttons, keeping them uniform */
        .category-filter-btn, .category-filter-btn.active, .category-filter-btn:focus, .category-filter-btn:hover {
            background: #c67c4e !important;
            color: #fff !important;
            border: 2px solid #a95e2d !important;
            width: 140px !important;
            height: 44px !important;
            min-width: 140px !important;
            min-height: 44px !important;
            max-width: 140px !important;
            max-height: 44px !important;
            font-size: 1rem !important;
            font-weight: 600 !important;
            padding: 0 !important;
            margin-bottom: 10px !important;
            margin-right: 10px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            box-sizing: border-box !important;
        }
        .category-filter-btn.active, .category-filter-btn:focus, .category-filter-btn:hover {
            background: #a95e2d !important;
        }
        table th,
        table td {
            font-size: 20px;
        }
        </style>
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    </head>

    <body>
    <div id="layoutSidenav_content">
        <main>
            <div class="container-fluid px-4">
                    <!-- Page Title -->
                <h1 class="mt-4">Products</h1>

                <!-- Category Filter Buttons -->
                <div class="mb-3">
                    <form method="GET" action="products.php" class="d-flex flex-wrap align-items-center" style="gap: 0.5rem;">
                        <?php
                        $selectedCat = isset($_GET['category']) ? $_GET['category'] : 'all';
                        $isAll = ($selectedCat === 'all');
                        echo '<button type="submit" name="category" value="all" class="category-filter-btn'.($isAll ? ' active' : '').'">All</button>';
                        try {
                            $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
                            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                            $cat_stmt = $pdo->query("SELECT id, name FROM categories");
                            $categories = [];
                            $addons = null;
                            while ($cat = $cat_stmt->fetch(PDO::FETCH_ASSOC)) {
                                if (strtolower(trim($cat['name'])) === 'add ons' || strtolower(trim($cat['name'])) === 'addons') {
                                    $addons = $cat;
                                } else {
                                    $categories[] = $cat;
                                }
                            }
                            // Sort categories alphabetically by name
                            usort($categories, function($a, $b) {
                                return strcasecmp($a['name'], $b['name']);
                            });
                            // Output all categories except Add ons
                            foreach ($categories as $cat) {
                                $isActive = ($selectedCat == $cat['id']);
                                echo '<button type="submit" name="category" value="' . htmlspecialchars($cat['id']) . '" class="category-filter-btn'.($isActive ? ' active' : '').'">' . htmlspecialchars($cat['name']) . '</button>';
                            }
                            // Output Add ons last if it exists
                            if ($addons) {
                                $isActive = ($selectedCat == $addons['id']);
                                echo '<button type="submit" name="category" value="' . htmlspecialchars($addons['id']) . '" class="category-filter-btn'.($isActive ? ' active' : '').'">' . htmlspecialchars($addons['name']) . '</button>';
                            }
                        } catch (PDOException $e) {
                            echo '<span class="text-danger">Error loading categories</span>';
                        }
                        ?>
                    </form>
                </div>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success">
                        <?php 
                            echo $_SESSION['success_message'];
                            unset($_SESSION['success_message']); // Clear the message after displaying
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger">
                        <?php 
                            echo $_SESSION['error_message'];
                            unset($_SESSION['error_message']); // Clear the message after displaying
                        ?>
                    </div>
                <?php endif; ?>
                
                 <!-- Modal -->
            <div class="modal fade" id="add_products" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="exampleModalLabel">Add Products</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="manage_products.php" method="POST" enctype="multipart/form-data">
                            <div class="modal-body">
                                <!-- Product Name -->
                                <div class="mb-3">
                                    <label for="product_name" class="form-label">Product Name</label>
                                    <input type="text" class="form-control" id="product_name" name="product_name" placeholder="Enter Product Name" required>
                                </div>
                                
                                <!-- Category Dropdown -->
                                <div class="mb-3">
                                    <label for="category_id" class="form-label">Category</label>
                                    <select class="form-select" id="category_id" name="category_id" required>
                                        <option value="">Select Category</option>
                                        <?php
                                        $categoryTypes = [];
                                        $categoryNames = [];
                                        try {
                                            $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
                                            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                                            $stmt = $pdo->query("SELECT id, name, type FROM categories");
                                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                $categoryTypes[$row['id']] = $row['type'];
                                                $categoryNames[$row['id']] = $row['name'];
                                                echo "<option value='" . htmlspecialchars($row['id']) . "' data-type='" . htmlspecialchars($row['type']) . "' data-name='" . htmlspecialchars($row['name']) . "'>" . htmlspecialchars($row['name']) . "</option>";
                                            }
                                        } catch (PDOException $e) {
                                            echo "<option value=''>Error Loading Categories</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <!-- Options Dropdown (for Food only) -->
                                <div class="mb-3" id="options_group">
                                    <label for="options" class="form-label">Options</label>
                                    <select class="form-select" id="options" name="options" onchange="handleDropdownLogic()">
                                        <option value="none">None</option>
                                        <option value="16oz">16oz</option>
                                        <option value="22oz">22oz</option>
                                    </select>
                                </div>

                                <!-- Dynamic Price Fields for Food -->
                                <div class="mb-3 d-none" id="price_none">
                                    <label for="price_none" class="form-label">Price</label>
                                    <input type="number" step="0.01" class="form-control" id="price_none" name="price_none" placeholder="Enter Price">
                                </div>
                                <div class="mb-3 d-none" id="price_16oz">
                                    <label for="price_16oz" class="form-label">Price (16oz)</label>
                                    <input type="number" step="0.01" class="form-control" id="price_16oz" name="price_16oz" placeholder="Enter 16oz Price">
                                </div>
                                <div class="mb-3 d-none" id="price_22oz">
                                    <label for="price_22oz" class="form-label">Price (22oz)</label>
                                    <input type="number" step="0.01" class="form-control" id="price_22oz" name="price_22oz" placeholder="Enter 22oz Price">
                                </div>

                                <!-- Price fields for Drinks only -->
                                <div class="mb-3 d-none" id="drinks_prices">
                                    <label class="form-label">Drinks Prices</label>
                                    <div class="row g-2">
                                        <div class="col">
                                            <input type="number" step="0.01" class="form-control" id="price_drink_16oz" name="price_drink_16oz" placeholder="16oz Price">
                                        </div>
                                        <div class="col">
                                            <input type="number" step="0.01" class="form-control" id="price_drink_22oz" name="price_drink_22oz" placeholder="22oz Price">
                                        </div>
                                        <div class="col" id="hot_price_col">
                                            <input type="number" step="0.01" class="form-control" id="price_drink_hot" name="price_drink_hot" placeholder="Hot Price">
                                        </div>
                                    </div>
                                </div>
                                                    

                                                <!-- Status Dropdown -->
                                                        <div class="mb-3">
                                                            <label for="status" class="form-label">Status</label>
                                                            <div class="form-check form-switch">
                                                                <input type="hidden" name="status" value="0">
                                                                <input class="form-check-input" type="checkbox" id="status" name="status" value="1" checked>
                                                                <label class="form-check-label" for="status">Available</label>
                                                            </div>
                                                        </div>

                                                        <!-- Image Upload -->
                                                        <div class="mb-3">
                                                            <label for="product_image" class="form-label">Product Image</label>
                                                            <input type="file" class="form-control" id="product_image" name="product_image" accept="image/*">
                                                            <small class="form-text text-muted">Leave blank to use the default image.</small>
                                                        </div>
                                                </div>

                                                    

                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" name="add_product_btn" class="btn btn-primary">Add</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

<script>
// Store category types in JS
const categoryTypes = <?php echo json_encode($categoryTypes ?? []); ?>;
const categoryNames = <?php echo json_encode($categoryNames ?? []); ?>;
const hotCategories = ['Coffee', 'Coffee Blended']; // Categories that should show hot price

function handleCategoryTypeLogic() {
    const categorySelect = document.getElementById('category_id');
    const selectedCatId = categorySelect.value;
    const type = categoryTypes[selectedCatId];
    const categoryName = categoryNames[selectedCatId];

    const optionsGroup = document.getElementById('options_group');
    const priceNone = document.getElementById('price_none');
    const price16oz = document.getElementById('price_16oz');
    const price22oz = document.getElementById('price_22oz');
    const drinksPrices = document.getElementById('drinks_prices');
    const hotPriceCol = document.getElementById('hot_price_col');

    // Hide all price fields and remove required
    [priceNone, price16oz, price22oz].forEach(price => {
        price.classList.add('d-none');
        price.querySelector('input').removeAttribute('required');
    });
    drinksPrices.classList.add('d-none');
    drinksPrices.querySelectorAll('input').forEach(input => input.removeAttribute('required'));

    // Always hide options for all types
    optionsGroup.classList.add('d-none');
    if (type === 'Drinks') {
        drinksPrices.classList.remove('d-none');
        // Set required on 16oz and 22oz
        document.getElementById('price_drink_16oz').setAttribute('required', 'true');
        document.getElementById('price_drink_22oz').setAttribute('required', 'true');
        // Show hot price only for specific categories
        if (hotCategories.includes(categoryName)) {
            hotPriceCol.classList.remove('d-none');
            document.getElementById('price_drink_hot').setAttribute('required', 'true');
        } else {
            hotPriceCol.classList.add('d-none');
            document.getElementById('price_drink_hot').removeAttribute('required');
        }
    } else {
        handleDropdownLogic();
    }
}

function handleDropdownLogic() {
    const selectedOption = document.getElementById('options').value;
    const priceNone = document.getElementById('price_none');
    const price16oz = document.getElementById('price_16oz');
    const price22oz = document.getElementById('price_22oz');
    // Hide all prices and remove required
    [priceNone, price16oz, price22oz].forEach(price => {
        price.classList.add('d-none');
        price.querySelector('input').removeAttribute('required');
    });
    if (selectedOption === 'none') {
        priceNone.classList.remove('d-none');
        priceNone.querySelector('input').setAttribute('required', 'true');
    } else if (selectedOption === '16oz') {
        price16oz.classList.remove('d-none');
        price16oz.querySelector('input').setAttribute('required', 'true');
    } else if (selectedOption === '22oz') {
        price22oz.classList.remove('d-none');
        price22oz.querySelector('input').setAttribute('required', 'true');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('category_id').addEventListener('change', handleCategoryTypeLogic);
    document.getElementById('options').addEventListener('change', handleDropdownLogic);
    handleCategoryTypeLogic();
    // Always hide options on page load
    document.getElementById('options_group').classList.add('d-none');
});
</script>


                    <div class="card shadow mb-4">
                        <!-- Card Header -->
                       <div class="card-header d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-table me-1"></i> Manage Products</span>
                                    <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#add_products" title="Add a new product" style="color: #ffffff; background-color:#c67c4e; border:none;">
                                    <i class="fa-solid fa-plus" ></i>
                                    Add Product 
                                    </button>
                        </div>

                            <!-- Card Body -->
                            <div class="card-body">
                                <!-- Table -->
                                <table id="datatablesSimple" class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th scope="col">Product Image</th>
                                            <th scope="col">Product Name</th>
                                            <th scope="col">Category Name</th>
                                            <th scope="col">Type</th>
                                            <th scope="col">Price</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                            try {
                                                // Connect to the database
                                                $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
                                                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                                                // Filter by category if set
                                                $categoryFilter = '';
                                                if (isset($_GET['category']) && $_GET['category'] !== 'all') {
                                                    $cat_id = intval($_GET['category']);
                                                    $categoryFilter = ' WHERE p.category_id = ' . $cat_id . ' ';
                                                }
                                                // Query to fetch products with category names
                                                $sql = "
                                                    SELECT 
                                                        p.id,
                                                        p.product_name, 
                                                        c.name AS category_name, 
                                                        c.type AS category_type,
                                                        p.options, 
                                                        p.price, 
                                                        p.status,
                                                        p.image
                                                    FROM 
                                                        products p
                                                    JOIN 
                                                        categories c 
                                                    ON 
                                                        p.category_id = c.id
                                                    $categoryFilter
                                                ";
                                                $stmt = $pdo->query($sql);
                                                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                                // Loop through the products and display them
                                                foreach ($products as $product) {
                                                    echo '<tr>';
                                                    echo '<td><img src="' . htmlspecialchars($product['image']) . '" alt="Product Image" style="width:50px; height:50px;"></td>';
                                                    echo '<td>' . htmlspecialchars($product['product_name']);
                                                    if (!empty($product['options']) && strtolower($product['options']) !== 'none' && strtolower($product['options']) !== 'drinks') {
                                                        echo ' (' . htmlspecialchars($product['options']) . ')';
                                                    }
                                                    echo '</td>';
                                                    echo '<td>' . htmlspecialchars($product['category_name']) . '</td>';
                                                    echo '<td>' . (isset($product['category_type']) ? htmlspecialchars($product['category_type']) : '-') . '</td>';
                                                    // Price display logic
                                                    echo '<td>';
                                                    if (isset($product['category_type']) && $product['category_type'] === 'Drinks') {
                                                        $prices = json_decode($product['price'], true);
                                                        if (is_array($prices)) {
                                                            $parts = [];
                                                            if (isset($prices['16oz'])) $parts[] = '16oz: PHP ' . number_format($prices['16oz'], 2);
                                                            if (isset($prices['22oz'])) $parts[] = '22oz: PHP ' . number_format($prices['22oz'], 2);
                                                            if (isset($prices['hot'])) $parts[] = 'Hot: PHP ' . number_format($prices['hot'], 2);
                                                            echo implode('<br>', $parts);
                                                        } else {
                                                            echo '-';
                                                        }
                                                    } else {
                                                        echo 'PHP ' . number_format($product['price'], 2);
                                                    }
                                                    echo '</td>';
                                                    // Status
                                                    echo '<td>';
                                                    if ($product['status'] == 1) {
                                                        echo '<span style="color: green;"> Available</span>';
                                                    } else {
                                                        echo '<span style="color: red;"> Unavailable</span>';
                                                    }
                                                    echo '</td>';
                                                    // Actions
                                                    echo '<td>';
                                                    // Edit Button
                                                    echo '<form action="edit_product.php" method="GET" style="display:inline;">';
                                                    echo '<input type="hidden" name="id" value="' . htmlspecialchars($product['id']) . '">';
                                                    echo '<button type="submit" class="btn btn-primary" title="Edit"><i class="fa fa-edit"></i></button>';
                                                    echo '</form>';
                                                    // Delete Button
                                                    echo '<form action="manage_products.php" method="POST" style="display:inline;" onsubmit="return confirm(\'Are you sure you want to delete this product?\');">';
                                                    echo '<input type="hidden" name="id" value="' . htmlspecialchars($product['id']) . '">';
                                                    echo '<button type="submit" name="deleteproduct_btn" class="btn btn-danger" title="Delete"><i class="fa fa-trash"></i></button>';
                                                    echo '</form>';
                                                    echo '</td>';
                                                    echo '</tr>';
                                                }
                                            } catch (PDOException $e) {
                                                echo "<tr><td colspan='5'>Error fetching data: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                                            }
                                        ?>
                                    </tbody>
                            </table>  
                        </div>                       
        </main>

    <?php 
        include ('includes/footer.php');
        include ('includes/scripts.php');
     ?>         
  </body>  
</html>              
