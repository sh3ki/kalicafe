<?php
session_start();
include('includes/header.php');
include('includes/navbar.php');
include('includes/config.php');
include('includes/auth.php');

// Allow only admin 
 checkAccess(['admin']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>KUSSO-Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
        #datatablesSimple th,
        #datatablesSimple td,
        #linkedFlavorsTable th,
        #linkedFlavorsTable td {
            font-size: 20px;
        }
    </style>
</head>

<body class="sb-nav-fixed">
    <div id="layoutSidenav_content">
        <main>
            <div class="container-fluid px-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="mt-4">Inventory</h1>
                    <div class="mt-4 d-flex" style="gap: 10px;">
                        <a href="assign_ingredients_to_categories.php" class="btn" style="color: #ffffff; background-color:#6c757d; border:none;">
                            <i class="fas fa-tags"></i> Assign to Categories
                        </a>
                        <a href="manage_category_ingredients.php" class="btn" style="color: #ffffff; background-color:#c67c4e; border:none;">
                            <i class="fas fa-layer-group"></i> Manage Category Ingredients
                        </a>
                    </div>
                </div>

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

            <!-- Activity Log Button -->
            <div class="mb-3">
                <button type="button" class="btn" style="color: #ffffff; background-color:#c67c4e; border:none;" data-bs-toggle="modal" data-bs-target="#activityLogModal">
                    <i class="fas fa-history"></i> View Activity Log
                </button>
            </div>

            <!-- Display low stock ingredients alert at the very top -->
            <?php
            try {
                // Use the same logic as check_low_stock.php - check if can make 10 products
                $threshold_multiplier = 10;
                
                $query = "
                    SELECT 
                        i.id,
                        i.name,
                        i.quantity,
                        i.unit,
                        MAX(GREATEST(
                            COALESCE(pi_max.max_qty, 0),
                            COALESCE(ci_max.max_qty, 0)
                        )) AS max_quantity_required
                    FROM ingredients i
                    LEFT JOIN (
                        SELECT ingredient_id, MAX(quantity_required) as max_qty
                        FROM product_ingredients
                        GROUP BY ingredient_id
                    ) pi_max ON i.id = pi_max.ingredient_id
                    LEFT JOIN (
                        SELECT ingredient_id, MAX(quantity_requirement) as max_qty
                        FROM category_ingredients
                        GROUP BY ingredient_id
                    ) ci_max ON i.id = ci_max.ingredient_id
                    WHERE (pi_max.max_qty IS NOT NULL OR ci_max.max_qty IS NOT NULL)
                    GROUP BY i.id, i.name, i.quantity, i.unit
                    HAVING i.quantity < (MAX(GREATEST(
                        COALESCE(pi_max.max_qty, 0),
                        COALESCE(ci_max.max_qty, 0)
                    )) * :threshold)
                    ORDER BY i.name
                ";
                
                $stmt = $conn->prepare($query);
                $stmt->execute(['threshold' => $threshold_multiplier]);
                $lowStock = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($lowStock) > 0) {
                    echo '<div class="alert alert-danger mt-4"><b>Low Stock Ingredients (Cannot make 10 products):</b><ul style="margin-bottom:0;">';
                    foreach ($lowStock as $item) {
                        $canMake = floor($item['quantity'] / $item['max_quantity_required']);
                        echo '<li><b>' . htmlspecialchars($item['name']) . '</b>: ' . htmlspecialchars($item['quantity']) . ' ' . htmlspecialchars($item['unit']) . ' <span class="text-muted">(can make ' . $canMake . ' products)</span></li>';
                    }
                    echo '</ul></div>';
                }
            } catch (PDOException $e) {
                echo '<div class="alert alert-danger">Error fetching low stock: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            ?>


            <!-- Add Ingredient Modal -->
            <div class="modal fade" id="addIngredientModal" tabindex="-1" aria-labelledby="addIngredientModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="manage_inventory.php" method="POST">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addIngredientModalLabel">Add Ingredient</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Ingredient Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="quantity" class="form-label">Quantity</label>
                                    <input type="number" step="0.01" class="form-control" id="quantity" name="quantity" required>
                                </div>
                                <div class="mb-3">
                                    <label for="unit" class="form-label">Unit</label>
                                    <select class="form-control" id="unit" name="unit" required>
                                        <option value="">Select unit...</option>
                                        <option value="kg">kg</option>
                                        <option value="ml">ml</option>
                                        <option value="pcs">pcs</option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" name="add_ingredient_btn" class="btn" style="color: #ffffff; background-color:#c67c4e; border:none;">Add Ingredient</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Ingredients Table -->
            <div class="card shadow mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-table me-1"></i> Manage Ingredients</span>

                     <!-- Add Ingredient Button -->
                      <div class="d-flex">
                          <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#addIngredientModal" title="Add a new ingredient" style="color: #ffffff; background-color:#c67c4e; border:none;">
                              <i class="fa-solid fa-plus"></i> Add Ingredient
                          </button>
                      </div>
                </div>
                <div class="card-body">
                    <table id="datatablesSimple" class="table table-bordered">
                        <thead>
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col">Quantity</th>
                                <th scope="col">Unit</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                // Get threshold for low stock calculation (can make 10 products)
                                $threshold_multiplier = 10;
                                
                                // Query to get all ingredients with low stock indicator
                                $query = "
                                    SELECT 
                                        i.*,
                                        CASE 
                                            WHEN i.quantity < (COALESCE(MAX(GREATEST(
                                                COALESCE(pi_max.max_qty, 0),
                                                COALESCE(ci_max.max_qty, 0)
                                            )), 0) * :threshold) 
                                            THEN 1 
                                            ELSE 0 
                                        END AS is_low_stock
                                    FROM ingredients i
                                    LEFT JOIN (
                                        SELECT ingredient_id, MAX(quantity_required) as max_qty
                                        FROM product_ingredients
                                        GROUP BY ingredient_id
                                    ) pi_max ON i.id = pi_max.ingredient_id
                                    LEFT JOIN (
                                        SELECT ingredient_id, MAX(quantity_requirement) as max_qty
                                        FROM category_ingredients
                                        GROUP BY ingredient_id
                                    ) ci_max ON i.id = ci_max.ingredient_id
                                    GROUP BY i.id, i.name, i.quantity, i.unit
                                    ORDER BY is_low_stock DESC, i.name ASC
                                ";
                                
                                $stmt = $conn->prepare($query);
                                $stmt->execute(['threshold' => $threshold_multiplier]);
                                
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    // Add visual indicator for low stock rows
                                    $rowClass = $row['is_low_stock'] ? "table-danger" : "";
                                    $lowStockBadge = $row['is_low_stock'] ? "<span class='badge bg-danger ms-2'>Low Stock</span>" : "";
                                    echo "<tr class='" . $rowClass . "'>
                                        <td>" . htmlspecialchars($row['name']) . $lowStockBadge . "</td>
                                        <td>" . number_format($row['quantity'], 0) . "</td>
                                        <td>" . htmlspecialchars($row['unit']) . "</td>
                                        <td>
                                            <!-- Edit Button -->
                                            <button type='button' class='btn btn-primary' data-bs-toggle='modal' data-bs-target='#editIngredientModal" . htmlspecialchars($row['id']) . "'>
                                                <i class='fa fa-edit'></i>
                                            </button>

                                            <!-- Delete Button -->
                                            <form action='manage_inventory.php' method='POST' style='display:inline;' onsubmit='return confirm(\"Are you sure you want to delete this ingredient?\");'>
                                                <input type='hidden' name='id' value='" . htmlspecialchars($row['id']) . "'>
                                                <button type='submit' name='delete_ingredient_btn' class='btn btn-danger'>
                                                    <i class='fa fa-trash'></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>";

                                    // Edit Ingredient Modal
                                    echo "
                                    <div class='modal fade' id='editIngredientModal" . htmlspecialchars($row['id']) . "' tabindex='-1' aria-labelledby='editIngredientModalLabel" . htmlspecialchars($row['id']) . "' aria-hidden='true'>
                                        <div class='modal-dialog'>
                                            <div class='modal-content'>
                                                <form action='manage_inventory.php' method='POST'>
                                                    <div class='modal-header'>
                                                        <h5 class='modal-title' id='editIngredientModalLabel" . htmlspecialchars($row['id']) . "'>Edit Ingredient</h5>
                                                        <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                                                    </div>
                                                    <div class='modal-body'>
                                                        <input type='hidden' name='id' value='" . htmlspecialchars($row['id']) . "'>
                                                        <div class='mb-3'>
                                                            <label for='name" . htmlspecialchars($row['id']) . "' class='form-label'>Ingredient Name</label>
                                                            <input type='text' class='form-control' id='name" . htmlspecialchars($row['id']) . "' name='name' value='" . htmlspecialchars($row['name']) . "' required>
                                                        </div>
                                                        <div class='mb-3'>
                                                            <label for='current_quantity" . htmlspecialchars($row['id']) . "' class='form-label'>Current Quantity</label>
                                                            <input type='number' step='1' class='form-control' id='current_quantity" . htmlspecialchars($row['id']) . "' value='" . number_format($row['quantity'], 0) . "' readonly>
                                                        </div>
                                                        <div class='mb-3'>
                                                            <label for='add_quantity" . htmlspecialchars($row['id']) . "' class='form-label'>Add Quantity</label>
                                                            <input type='number' step='0.01' class='form-control' id='add_quantity" . htmlspecialchars($row['id']) . "' name='add_quantity' min='0' value='0' required>
                                                            <small class='form-text text-muted'>Enter the amount to add to current quantity</small>
                                                        </div>
                                                        <div class='mb-3'>
                                                            <label for='unit" . htmlspecialchars($row['id']) . "' class='form-label'>Unit</label>
                                                            <input type='text' class='form-control' id='unit" . htmlspecialchars($row['id']) . "' name='unit' value='" . htmlspecialchars($row['unit']) . "' required>
                                                        </div>
                                                    </div>
                                                    <div class='modal-footer'>
                                                        <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
                                                        <button type='submit' name='edit_ingredient_btn' class='btn' style='color: #ffffff; background-color: #c67c4e; border: none;'>Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>";
                                }
                            } catch (PDOException $e) {
                                echo "<tr><td colspan='4'>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>


        <!-- Linked Flavors Table -->
        <div class="card shadow mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-link me-1"></i> Link Flavors to Products</span>

                 <!-- Link Flavor Button -->
                 <div class="d-flex">
                    <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#linkFlavorModal" title="Link a flavor to a product" style="color: #ffffff; background-color:#c67c4e; border:none;">
                        <i class="fa-solid fa-link"></i> Link Flavor
                    </button>
                </div>
            </div>


            <div class="card-body">
                <table id="linkedFlavorsTable" class="table table-bordered">
                    <thead>
                        <tr>
                            <th scope="col">Product Name</th>
                            <th scope="col">Flavor Name</th>
                            <th scope="col">Size</th>
                            <th scope="col">Quantity Required</th>
                            <th scope="col">Unit</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                       <?php
                            try {
                                // Query to get product flavors with size information
                                $stmt = $conn->query("
                                    SELECT 
                                        pf.id AS link_id, 
                                        p.product_name AS product_name, 
                                        p.options AS product_option, 
                                        i.name AS flavor_name,
                                        pf.size,
                                        pf.quantity_required,
                                        pf.unit
                                    FROM product_flavors pf
                                    JOIN products p ON pf.product_id = p.id
                                    JOIN ingredients i ON pf.flavor_id = i.id
                                    ORDER BY p.product_name, pf.size
                                ");
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    // Combine product name and option
                                    $productDisplay = htmlspecialchars($row['product_name']);
                                    if (!empty($row['product_option'])) {
                                        $productDisplay .= " (" . htmlspecialchars($row['product_option']) . ")";
                                    }
                                    $sizeBadge = ($row['size'] ?? '16oz') === '16oz' ? 
                                        "<span class='badge bg-primary'>16oz</span>" : 
                                        "<span class='badge bg-success'>22oz</span>";
                                    echo "<tr>
                                        <td>" . $productDisplay . "</td>
                                        <td>" . htmlspecialchars($row['flavor_name']) . "</td>
                                        <td>" . $sizeBadge . "</td>
                                        <td>" . number_format($row['quantity_required'], 0) . "</td>
                                        <td>" . htmlspecialchars($row['unit']) . "</td>
                                        <td>
                                            <form action='manage_product_flavors.php' method='POST' style='display:inline;' onsubmit='return confirm(\"Are you sure you want to unlink this flavor?\");'>
                                                <input type='hidden' name='flavor_link_id' value='" . htmlspecialchars($row['link_id']) . "'>
                                                <button type='submit' name='unlink_flavor_btn' class='btn btn-danger'>
                                                    <i class='fa fa-unlink'></i> Unlink
                                                </button>
                                            </form>
                                        </td>
                                    </tr>";
                                }
                            } catch (PDOException $e) {
                                echo "<tr><td colspan='6'>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                            }
                            ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Link Flavor Modal -->
        <div class="modal fade" id="linkFlavorModal" tabindex="-1" aria-labelledby="linkFlavorModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form action="manage_product_flavors.php" method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title" id="linkFlavorModalLabel">Link Flavors to Product</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="category_filter" class="form-label">Category</label>
                                <select class="form-control" id="category_filter" onchange="filterProductsByCategory()">
                                    <option value="">All Categories</option>
                                    <?php
                                    try {
                                        $stmt = $conn->query("SELECT id, name FROM categories WHERE type = 'Drinks' ORDER BY name");
                                        while ($category = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                            echo "<option value='" . htmlspecialchars($category['id']) . "'>" . htmlspecialchars($category['name']) . "</option>";
                                        }
                                    } catch (PDOException $e) {
                                        echo "<option>Error: " . htmlspecialchars($e->getMessage()) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="product_id" class="form-label">Product</label>
                                <select class="form-control" id="product_id" name="product_id" required onchange="loadProductIngredients(this.value)">
                                    <option value="">Select a product...</option>
                                    <?php
                                    try {
                                        $stmt = $conn->query("SELECT p.id, p.product_name, p.options, p.category_id, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE c.type = 'Drinks' ORDER BY p.product_name");
                                        while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                            $optionText = (!empty($product['options']) && strtolower($product['options']) !== 'drinks') ? " (" . htmlspecialchars($product['options']) . ")" : "";
                                            $categoryText = !empty($product['category_name']) ? " [" . htmlspecialchars($product['category_name']) . "]" : "";
                                            echo "<option value='" . htmlspecialchars($product['id']) . "' data-category-id='" . htmlspecialchars($product['category_id']) . "'>" . htmlspecialchars($product['product_name']) . $optionText . $categoryText . "</option>";
                                        }
                                    } catch (PDOException $e) {
                                        echo "<option>Error: " . htmlspecialchars($e->getMessage()) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="product_size" class="form-label">Size</label>
                                <select class="form-control" id="product_size" name="product_size" required onchange="loadProductIngredients(document.getElementById('product_id').value)">
                                    <option value="">Select size...</option>
                                    <option value="16oz">16oz</option>
                                    <option value="22oz">22oz</option>
                                </select>
                            </div>
                            
                            <hr>
                            <label class="form-label"><strong>Select Flavors</strong></label>
                            <div id="flavors-container" class="mb-3" style="max-height: 400px; overflow-y: auto; border: 1px solid #dee2e6; padding: 15px; border-radius: 0.375rem;">
                                <p class='text-muted'>Please select a product and size first.</p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" name="link_flavor_btn" class="btn" style="color: #ffffff; background-color: #c67c4e; border: none;">Link Flavors</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const linkFlavorModal = document.getElementById('linkFlavorModal');
            
            // Reset form when modal is shown
            if (linkFlavorModal) {
                linkFlavorModal.addEventListener('show.bs.modal', function() {
                    // Reset category, product and size selects
                    document.getElementById('category_filter').value = '';
                    document.getElementById('product_id').value = '';
                    document.getElementById('product_size').value = '';
                    
                    // Show all products
                    filterProductsByCategory();
                    
                    // Clear container
                    document.getElementById('flavors-container').innerHTML = '<p class="text-muted">Please select a product and size first.</p>';
                });
            }
        });
        
        // Filter products by selected category
        function filterProductsByCategory() {
            const categoryId = document.getElementById('category_filter').value;
            const productSelect = document.getElementById('product_id');
            const options = productSelect.getElementsByTagName('option');
            
            // Reset product selection
            productSelect.value = '';
            document.getElementById('flavors-container').innerHTML = '<p class="text-muted">Please select a product and size first.</p>';
            
            // Show/hide options based on category
            for (let i = 1; i < options.length; i++) {
                const option = options[i];
                const optionCategoryId = option.getAttribute('data-category-id');
                
                if (!categoryId || optionCategoryId === categoryId) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            }
        }
        
        // Load flavors based on selected product and size
        function loadProductIngredients(productId) {
            const size = document.getElementById('product_size').value;
            const container = document.getElementById('flavors-container');
            
            if (!productId || !size) {
                container.innerHTML = '<p class="text-muted">Please select a product and size first.</p>';
                return;
            }
            
            container.innerHTML = '<p class="text-muted">Loading flavors...</p>';
            
            fetch('get_product_flavors.php?product_id=' + productId)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        container.innerHTML = '<p class="text-danger">' + data.error + '</p>';
                        return;
                    }
                    
                    if (data.available_flavors && data.available_flavors.length > 0) {
                        let html = '';
                        
                        // Create a map of already linked flavors for this product and size
                        const linkedMap = {};
                        if (data.linked_flavors && data.linked_flavors.length > 0) {
                            data.linked_flavors.forEach(lf => {
                                if (lf.size === size) {
                                    linkedMap[lf.flavor_id] = {
                                        quantity: lf.quantity_required,
                                        unit: lf.unit
                                    };
                                }
                            });
                        }
                        
                        data.available_flavors.forEach(flavor => {
                            const isLinked = linkedMap[flavor.ingredient_id];
                            const checked = isLinked ? 'checked' : '';
                            const quantity = isLinked ? isLinked.quantity : '';
                            const selectedUnit = isLinked ? isLinked.unit : flavor.unit;
                            const disabled = isLinked ? '' : 'disabled';
                            const linkedBadge = isLinked ? '<span class="badge bg-success ms-2">Linked</span>' : '';
                            
                            html += `
                            <div class='row mb-3 align-items-end'>
                                <div class='col-md-1'>
                                    <input type='checkbox' class='form-check-input flavor-checkbox' name='flavor_ids[]' value='${flavor.ingredient_id}' id='flavor_${flavor.ingredient_id}_${size}' ${checked}>
                                </div>
                                <div class='col-md-5'>
                                    <label for='flavor_${flavor.ingredient_id}_${size}' class='form-check-label'>${flavor.ingredient_name}${linkedBadge}</label>
                                </div>
                                <div class='col-md-3'>
                                    <input type='number' step='0.01' class='form-control flavor-quantity' name='quantities[]' placeholder='Enter quantity' min='0' value='${quantity}' ${disabled}>
                                </div>
                                <div class='col-md-3'>
                                    <select class='form-select flavor-unit' name='units[]' ${disabled}>
                                        <option value='grams' ${selectedUnit === 'grams' ? 'selected' : ''}>grams</option>
                                        <option value='ml' ${selectedUnit === 'ml' ? 'selected' : ''}>ml</option>
                                        <option value='pcs' ${selectedUnit === 'pcs' ? 'selected' : ''}>pcs</option>
                                    </select>
                                    <input type='hidden' name='sizes[]' value='${size}'>
                                </div>
                            </div>`;
                        });
                        container.innerHTML = html;
                            
                            // Enable/disable inputs based on checkbox
                            document.querySelectorAll('.flavor-checkbox').forEach(checkbox => {
                                checkbox.addEventListener('change', function() {
                                    const row = this.closest('.row');
                                    const quantityInput = row.querySelector('.flavor-quantity');
                                    const unitSelect = row.querySelector('.flavor-unit');
                                    
                                    if (this.checked) {
                                        quantityInput.disabled = false;
                                        unitSelect.disabled = false;
                                        quantityInput.focus();
                                    } else {
                                        quantityInput.disabled = true;
                                        unitSelect.disabled = true;
                                        quantityInput.value = '';
                                    }
                                });
                            });
                    } else {
                        container.innerHTML = '<p class="text-muted">No flavors available. Add ingredients first in the Inventory menu.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    container.innerHTML = '<p class="text-danger">Error loading flavors.</p>';
                });
        }

        function toggleActivityLog() {
            const content = document.getElementById('activityLogContent');
            const icon = document.getElementById('toggleIcon');
            const text = document.getElementById('toggleText');
            
            if (content.style.display === 'none') {
                content.style.display = 'block';
                icon.className = 'fas fa-eye-slash';
                text.textContent = 'Hide Log';
            } else {
                content.style.display = 'none';
                icon.className = 'fas fa-eye';
                text.textContent = 'Show Log';
            }
        }
        </script>
        </main>
        <?php include('includes/footer.php'); ?>
    </div>
</div>
<?php include('includes/scripts.php'); ?>
</body>
</html>        </script>

        <!-- Activity Log Modal -->
        <div class="modal fade" id="activityLogModal" tabindex="-1" aria-labelledby="activityLogModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="activityLogModalLabel"><i class="fas fa-history"></i> Inventory Activity Log</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Ingredient</th>
                                    <th>Quantity Added</th>
                                    <th>Unit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    // Fetch recent activity logs (last 50 records)
                                    $stmt = $conn->query("
                                        SELECT 
                                            ingredient_name,
                                            quantity_added,
                                            previous_quantity,
                                            unit,
                                            action_date
                                        FROM inventory_activity_log
                                        ORDER BY action_date DESC
                                        LIMIT 50
                                    ");
                                    
                                    if ($stmt->rowCount() > 0) {
                                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                            $quantityDisplay = isset($row['previous_quantity']) 
                                                ? htmlspecialchars(number_format($row['previous_quantity'], 0)) . "+" . htmlspecialchars(number_format($row['quantity_added'], 0))
                                                : "+" . htmlspecialchars(number_format($row['quantity_added'], 0));
                                            
                                            echo "<tr>
                                                <td>" . htmlspecialchars(date('M d, Y', strtotime($row['action_date']))) . "</td>
                                                <td>" . htmlspecialchars(date('h:i A', strtotime($row['action_date']))) . "</td>
                                                <td>" . htmlspecialchars($row['ingredient_name']) . "</td>
                                                <td class='text-success'>" . $quantityDisplay . "</td>
                                                <td>" . htmlspecialchars($row['unit']) . "</td>
                                            </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='5' class='text-center text-muted'>No activity recorded yet</td></tr>";
                                    }
                                } catch (PDOException $e) {
                                    echo "<tr><td colspan='5' class='text-danger'>Error loading activity log: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
