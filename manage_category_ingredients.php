<?php
session_start();
include('includes/header.php');
include('includes/navbar.php');
include('includes/config.php');
include('includes/auth.php');

// Allow only admin
checkAccess(['admin']);

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Database connection failed: " . $e->getMessage();
    header('Location: inventory.php');
    exit();
}

// Get all categories (excluding specific categories)
$categories_stmt = $pdo->query("
    SELECT * FROM categories 
    WHERE name NOT IN ('Add Ons', 'Burgers', 'Combo', 'Pasta', 'Ricemeal', 'Sandwiches', 'Snacks', 'Wings')
    ORDER BY name
");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>KUSSO - Manage Category Ingredients</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>

<body>
<div id="layoutSidenav_content">
    <main>
        <div class="container-fluid px-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mt-4">Manage Category Ingredients</h1>
                    <p class="text-muted">Link ingredients to categories and set quantity requirements</p>
                </div>
                <div class="mt-4">
                    <a href="inventory.php" class="btn" style="color: #ffffff; background-color:#6c757d; border:none;">
                        <i class="fas fa-arrow-left"></i> Back to Inventory
                    </a>
                </div>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                        echo $_SESSION['success_message'];
                        unset($_SESSION['success_message']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                        echo $_SESSION['error_message'];
                        unset($_SESSION['error_message']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Categories Tabs -->
            <div class="card shadow mb-4">
                <div class="card-header text-white" style="background-color: #3f2305;">
                    <i class="fas fa-layer-group me-1"></i> Select Category
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" id="categoryTabs" role="tablist">
                        <?php foreach ($categories as $idx => $category): ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?php echo $idx === 0 ? 'active' : ''; ?>" 
                                    id="category-<?php echo $category['id']; ?>-tab" 
                                    data-bs-toggle="tab" 
                                    data-bs-target="#category-<?php echo $category['id']; ?>-content" 
                                    type="button" role="tab">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="tab-content" id="categoryTabsContent">
                        <?php foreach ($categories as $idx => $category): ?>
                            <div class="tab-pane fade <?php echo $idx === 0 ? 'show active' : ''; ?>" 
                                id="category-<?php echo $category['id']; ?>-content" role="tabpanel">
                                
                                <div class="mt-4">
                                    <!-- Add Ingredient to Category Button -->
                                    <button type="button" class="btn mb-3" 
                                        style="color: #ffffff; background-color:#c67c4e; border:none;" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#addCategoryIngredientModal<?php echo $category['id']; ?>">
                                        <i class="fa-solid fa-plus"></i> Add Ingredient to <?php echo htmlspecialchars($category['name']); ?>
                                    </button>

                                    <!-- 16oz Size Table -->
                                    <h5 class="mt-4 mb-3">
                                        <span class="badge bg-primary">16oz</span> Size Ingredients
                                    </h5>
                                    <div class="table-responsive mb-4">
                                        <table class="table table-bordered table-hover">
                                            <thead class="text-white" style="background-color: #3f2305;">
                                                <tr>
                                                    <th>Ingredient Name</th>
                                                    <th>Quantity Requirement</th>
                                                    <th>Unit</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                try {
                                                    $ing_stmt = $pdo->prepare("
                                                        SELECT ci.*, i.name as ingredient_name 
                                                        FROM category_ingredients ci
                                                        JOIN ingredients i ON ci.ingredient_id = i.id
                                                        WHERE ci.category_id = :category_id AND ci.size = '16oz'
                                                        ORDER BY i.name
                                                    ");
                                                    $ing_stmt->execute([':category_id' => $category['id']]);
                                                    $ingredients = $ing_stmt->fetchAll(PDO::FETCH_ASSOC);
                                                    
                                                    if (count($ingredients) > 0) {
                                                        foreach ($ingredients as $ing) {
                                                            echo "<tr>
                                                                <td>" . htmlspecialchars($ing['ingredient_name']) . "</td>
                                                                <td>" . number_format($ing['quantity_requirement'], 0) . "</td>
                                                                <td>" . htmlspecialchars($ing['unit']) . "</td>
                                                                <td>
                                                                    <button type='button' class='btn btn-sm btn-primary' 
                                                                        data-bs-toggle='modal' 
                                                                        data-bs-target='#editCategoryIngredientModal" . $ing['id'] . "'>
                                                                        <i class='fa fa-edit'></i>
                                                                    </button>
                                                                    <form action='manage_category_ingredients_handler.php' method='POST' style='display:inline;' 
                                                                        onsubmit='return confirm(\"Remove this ingredient from \" + \"" . addslashes(htmlspecialchars($category['name'])) . "\" + \" (16oz)?\");'>
                                                                        <input type='hidden' name='delete_category_ingredient_btn' value='1'>
                                                                        <input type='hidden' name='category_ingredient_id' value='" . $ing['id'] . "'>
                                                                        <button type='submit' class='btn btn-sm btn-danger'>
                                                                            <i class='fa fa-trash'></i>
                                                                        </button>
                                                                    </form>
                                                                </td>
                                                            </tr>";
                                                        }
                                                    } else {
                                                        echo "<tr><td colspan='4' class='text-center text-muted'>No ingredients linked to this category yet (16oz)</td></tr>";
                                                    }
                                                } catch (PDOException $e) {
                                                    echo "<tr><td colspan='4' class='text-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- 22oz Size Table -->
                                    <h5 class="mt-4 mb-3">
                                        <span class="badge bg-success">22oz</span> Size Ingredients
                                    </h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover">
                                            <thead class="text-white" style="background-color: #3f2305;">
                                                <tr>
                                                    <th>Ingredient Name</th>
                                                    <th>Quantity Requirement</th>
                                                    <th>Unit</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                try {
                                                    $ing_stmt = $pdo->prepare("
                                                        SELECT ci.*, i.name as ingredient_name 
                                                        FROM category_ingredients ci
                                                        JOIN ingredients i ON ci.ingredient_id = i.id
                                                        WHERE ci.category_id = :category_id AND ci.size = '22oz'
                                                        ORDER BY i.name
                                                    ");
                                                    $ing_stmt->execute([':category_id' => $category['id']]);
                                                    $ingredients = $ing_stmt->fetchAll(PDO::FETCH_ASSOC);
                                                    
                                                    if (count($ingredients) > 0) {
                                                        foreach ($ingredients as $ing) {
                                                            echo "<tr>
                                                                <td>" . htmlspecialchars($ing['ingredient_name']) . "</td>
                                                                <td>" . number_format($ing['quantity_requirement'], 0) . "</td>
                                                                <td>" . htmlspecialchars($ing['unit']) . "</td>
                                                                <td>
                                                                    <button type='button' class='btn btn-sm btn-primary' 
                                                                        data-bs-toggle='modal' 
                                                                        data-bs-target='#editCategoryIngredientModal" . $ing['id'] . "'>
                                                                        <i class='fa fa-edit'></i>
                                                                    </button>
                                                                    <form action='manage_category_ingredients_handler.php' method='POST' style='display:inline;' 
                                                                        onsubmit='return confirm(\"Remove this ingredient from \" + \"" . addslashes(htmlspecialchars($category['name'])) . "\" + \" (22oz)?\");'>
                                                                        <input type='hidden' name='delete_category_ingredient_btn' value='1'>
                                                                        <input type='hidden' name='category_ingredient_id' value='" . $ing['id'] . "'>
                                                                        <button type='submit' class='btn btn-sm btn-danger'>
                                                                            <i class='fa fa-trash'></i>
                                                                        </button>
                                                                    </form>
                                                                </td>
                                                            </tr>";
                                                        }
                                                    } else {
                                                        echo "<tr><td colspan='4' class='text-center text-muted'>No ingredients linked to this category yet (22oz)</td></tr>";
                                                    }
                                                } catch (PDOException $e) {
                                                    echo "<tr><td colspan='4' class='text-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Add Ingredient Modal for this Category -->
                                <div class="modal fade" id="addCategoryIngredientModal<?php echo $category['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="manage_category_ingredients_handler.php" method="POST">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Add Ingredient to <?php echo htmlspecialchars($category['name']); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                    
                                                    <!-- Currently Selected Ingredients -->
                                                    <div class="mb-3 p-3" style="background-color: #f8f9fa; border-left: 3px solid #c67c4e; border-radius: 4px;">
                                                        <label class="form-label" style="font-weight: 600; color: #333;">
                                                            <i class="fas fa-check-circle me-2" style="color: #c67c4e;"></i>Current Ingredients in <?php echo htmlspecialchars($category['name']); ?>
                                                        </label>
                                                        <div id="current_ingredients_<?php echo $category['id']; ?>">
                                                            <?php
                                                            try {
                                                                $curr_stmt = $pdo->prepare("
                                                                    SELECT ci.*, i.name as ingredient_name 
                                                                    FROM category_ingredients ci
                                                                    JOIN ingredients i ON ci.ingredient_id = i.id
                                                                    WHERE ci.category_id = :category_id
                                                                    ORDER BY ci.size, i.name
                                                                ");
                                                                $curr_stmt->execute([':category_id' => $category['id']]);
                                                                $current = $curr_stmt->fetchAll(PDO::FETCH_ASSOC);
                                                                
                                                                if (count($current) > 0) {
                                                                    // Group by size
                                                                    $bySize = ['16oz' => [], '22oz' => []];
                                                                    foreach ($current as $curr) {
                                                                        $size = $curr['size'] ?? '16oz';
                                                                        if (!isset($bySize[$size])) $bySize[$size] = [];
                                                                        $bySize[$size][] = $curr;
                                                                    }
                                                                    
                                                                    echo "<div style='display: flex; gap: 15px;'>";
                                                                    
                                                                    // 16oz Table
                                                                    echo "<div style='flex: 1;'>";
                                                                    echo "<div style='background: #e3f2fd; padding: 8px; border-radius: 4px; margin-bottom: 8px;'>";
                                                                    echo "<strong style='color: #1976d2;'>ðŸ“‹ 16oz Assigned (" . count($bySize['16oz']) . ")</strong>";
                                                                    echo "</div>";
                                                                    if (count($bySize['16oz']) > 0) {
                                                                        echo "<ul style='margin-bottom: 0; padding-left: 20px; font-size: 13px;'>";
                                                                        foreach ($bySize['16oz'] as $curr) {
                                                                            echo "<li style='margin-bottom: 5px;'><strong>" . htmlspecialchars($curr['ingredient_name']) . "</strong> - " . htmlspecialchars($curr['quantity_requirement']) . " " . htmlspecialchars($curr['unit']) . "</li>";
                                                                        }
                                                                        echo "</ul>";
                                                                    } else {
                                                                        echo "<span style='color: #999; font-style: italic; font-size: 12px;'>None</span>";
                                                                    }
                                                                    echo "</div>";
                                                                    
                                                                    // 22oz Table
                                                                    echo "<div style='flex: 1;'>";
                                                                    echo "<div style='background: #e8f5e9; padding: 8px; border-radius: 4px; margin-bottom: 8px;'>";
                                                                    echo "<strong style='color: #388e3c;'>ðŸ“‹ 22oz Assigned (" . count($bySize['22oz']) . ")</strong>";
                                                                    echo "</div>";
                                                                    if (count($bySize['22oz']) > 0) {
                                                                        echo "<ul style='margin-bottom: 0; padding-left: 20px; font-size: 13px;'>";
                                                                        foreach ($bySize['22oz'] as $curr) {
                                                                            echo "<li style='margin-bottom: 5px;'><strong>" . htmlspecialchars($curr['ingredient_name']) . "</strong> - " . htmlspecialchars($curr['quantity_requirement']) . " " . htmlspecialchars($curr['unit']) . "</li>";
                                                                        }
                                                                        echo "</ul>";
                                                                    } else {
                                                                        echo "<span style='color: #999; font-style: italic; font-size: 12px;'>None</span>";
                                                                    }
                                                                    echo "</div>";
                                                                    
                                                                    echo "</div>";
                                                                } else {
                                                                    echo "<span style='color: #999; font-style: italic;'>No ingredients linked yet</span>";
                                                                }
                                                            } catch (PDOException $e) {
                                                                echo "<span style='color: red;'>Error loading current ingredients</span>";
                                                            }
                                                            ?>
                                                        </div>
                                                    </div>
                                                    
                                                    <hr>
                                                    
                                                    <div class="mb-3">
                                                        <label for="size_<?php echo $category['id']; ?>" class="form-label"><strong>Size</strong></label>
                                                        <select class="form-control" id="size_<?php echo $category['id']; ?>" name="size" required>
                                                            <option value="16oz">16oz</option>
                                                            <option value="22oz">22oz</option>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="ingredient_id_<?php echo $category['id']; ?>" class="form-label"><strong>Select Ingredient to Add</strong></label>
                                                        <select class="form-control ingredient-select" id="ingredient_id_<?php echo $category['id']; ?>" name="ingredient_id" data-category-id="<?php echo $category['id']; ?>" required>
                                                            <option value="">-- Choose Ingredient --</option>
                                                            <?php
                                                            try {
                                                                // Get ingredients that are not already linked to this category for the selected size
                                                                $avail_stmt = $pdo->prepare("
                                                                    SELECT i.* FROM ingredients i
                                                                    WHERE i.category_id = :category_id
                                                                    ORDER BY i.name
                                                                ");
                                                                $avail_stmt->execute([':category_id' => $category['id']]);
                                                                $available = $avail_stmt->fetchAll(PDO::FETCH_ASSOC);
                                                                
                                                                if (count($available) > 0) {
                                                                    foreach ($available as $avail) {
                                                                        echo "<option value='" . $avail['id'] . "'>" . htmlspecialchars($avail['name']) . "</option>";
                                                                    }
                                                                } else {
                                                                    echo "<option value='' disabled>No ingredients available for this category</option>";
                                                                }
                                                            } catch (PDOException $e) {
                                                                echo "<option value='' disabled>Error loading ingredients</option>";
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="quantity_<?php echo $category['id']; ?>" class="form-label">Quantity Requirement</label>
                                                        <input type="number" step="1" class="form-control" 
                                                            id="quantity_<?php echo $category['id']; ?>" 
                                                            name="quantity_requirement" required>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="unit_<?php echo $category['id']; ?>" class="form-label">Unit</label>
                                                        <input type="text" class="form-control" 
                                                            id="unit_<?php echo $category['id']; ?>" 
                                                            name="unit" placeholder="e.g., ml, g, oz" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" name="add_category_ingredient_btn" class="btn" style="color: #ffffff; background-color:#c67c4e; border:none;">
                                                        Add Ingredient
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Edit Modals for each ingredient in this category -->
                                <?php
                                try {
                                    $edit_stmt = $pdo->prepare("
                                        SELECT ci.*, i.name as ingredient_name 
                                        FROM category_ingredients ci
                                        JOIN ingredients i ON ci.ingredient_id = i.id
                                        WHERE ci.category_id = :category_id
                                    ");
                                    $edit_stmt->execute([':category_id' => $category['id']]);
                                    $edit_ings = $edit_stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    foreach ($edit_ings as $edit_ing) {
                                        ?>
                                        <div class="modal fade" id="editCategoryIngredientModal<?php echo $edit_ing['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form action="manage_category_ingredients_handler.php" method="POST">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Edit <?php echo htmlspecialchars($edit_ing['ingredient_name']); ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="category_ingredient_id" value="<?php echo $edit_ing['id']; ?>">
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Ingredient</label>
                                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($edit_ing['ingredient_name']); ?>" disabled>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label for="edit_size_<?php echo $edit_ing['id']; ?>" class="form-label">Size</label>
                                                                <select class="form-control" id="edit_size_<?php echo $edit_ing['id']; ?>" name="size" required>
                                                                    <option value="16oz" <?php echo (($edit_ing['size'] ?? '16oz') === '16oz') ? 'selected' : ''; ?>>16oz</option>
                                                                    <option value="22oz" <?php echo (($edit_ing['size'] ?? '16oz') === '22oz') ? 'selected' : ''; ?>>22oz</option>
                                                                </select>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label for="edit_quantity_<?php echo $edit_ing['id']; ?>" class="form-label">Quantity Requirement</label>
                                                                <input type="number" step="1" class="form-control" 
                                                                    id="edit_quantity_<?php echo $edit_ing['id']; ?>" 
                                                                    name="quantity_requirement" 
                                                                    value="<?php echo htmlspecialchars($edit_ing['quantity_requirement']); ?>" required>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label for="edit_unit_<?php echo $edit_ing['id']; ?>" class="form-label">Unit</label>
                                                                <input type="text" class="form-control" 
                                                                    id="edit_unit_<?php echo $edit_ing['id']; ?>" 
                                                                    name="unit" 
                                                                    value="<?php echo htmlspecialchars($edit_ing['unit']); ?>" required>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            <button type="submit" name="edit_category_ingredient_btn" class="btn" style="color: #ffffff; background-color:#c67c4e; border:none;">
                                                                Update Ingredient
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <?php
                                    }
                                } catch (PDOException $e) {
                                    // Error silently
                                }
                                ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Reference Section -->
            <div class="card shadow">
                <div class="card-header text-white" style="background-color: #6c757d;">
                    <i class="fas fa-info-circle me-2"></i> How to Use
                </div>
                <div class="card-body">
                    <ul>
                        <li>Select a category from the tabs above</li>
                        <li>Click <strong>"Add Ingredient to [Category]"</strong> to link an ingredient to that category</li>
                        <li>Set the quantity requirement (how much of that ingredient is needed per serving/product)</li>
                        <li>Edit or remove ingredients as needed</li>
                        <li>When creating products, you'll only see ingredients from that product's category</li>
                    </ul>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js"></script>
<script src="js/scripts.js"></script>
<script>
// Filter ingredients based on selected size
document.addEventListener('DOMContentLoaded', function() {
    // Get all size selects
    const sizeSelects = document.querySelectorAll('select[name="size"]');
    
    sizeSelects.forEach(sizeSelect => {
        const modal = sizeSelect.closest('.modal');
        const categoryId = sizeSelect.id.replace('size_', '');
        const ingredientSelect = document.getElementById('ingredient_id_' + categoryId);
        
        if (ingredientSelect) {
            // Store all original options
            const allOptions = Array.from(ingredientSelect.options).map(opt => ({
                value: opt.value,
                text: opt.text
            }));
            
            sizeSelect.addEventListener('change', function() {
                const selectedSize = this.value;
                
                // Fetch ingredients based on size
                fetch('get_available_ingredients.php?category_id=' + categoryId + '&size=' + selectedSize)
                    .then(response => response.json())
                    .then(data => {
                        // Clear current options
                        ingredientSelect.innerHTML = '<option value="">-- Choose Ingredient --</option>';
                        
                        if (data.ingredients && data.ingredients.length > 0) {
                            data.ingredients.forEach(ing => {
                                const option = document.createElement('option');
                                option.value = ing.id;
                                option.textContent = ing.name;
                                ingredientSelect.appendChild(option);
                            });
                        } else {
                            const option = document.createElement('option');
                            option.value = '';
                            option.disabled = true;
                            option.textContent = selectedSize === '22oz' ? 
                                'Please add ingredients to 16oz first' : 
                                'No ingredients available';
                            ingredientSelect.appendChild(option);
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching ingredients:', error);
                    });
            });
            
            // Trigger change on page load to set initial state
            sizeSelect.dispatchEvent(new Event('change'));
        }
    });
});
</script>
</body>
</html>

