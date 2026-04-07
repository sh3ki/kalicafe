<?php
session_start();

// Handle AJAX form submission BEFORE any includes that output HTML
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['assign_btn']) || isset($_POST['remove_btn']))) {
    include('includes/config.php');
    include('includes/auth.php');
    checkAccess(['admin']);
    
    header('Content-Type: application/json');
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        if (isset($_POST['assign_btn'])) {
            $ingredient_id = $_POST['ingredient_id'] ?? null;
            $category_id = $_POST['category_id'] ?? null;

            if ($ingredient_id && $category_id) {
                // Check if already linked
                $check_stmt = $pdo->prepare("SELECT id FROM category_ingredients WHERE ingredient_id = :ingredient_id AND category_id = :category_id");
                $check_stmt->execute([
                    ':ingredient_id' => $ingredient_id,
                    ':category_id' => $category_id
                ]);
                
                if ($check_stmt->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'Already assigned to this category']);
                } else {
                    // Add new category_ingredients link
                    $insert_stmt = $pdo->prepare("
                        INSERT INTO category_ingredients (ingredient_id, category_id, quantity_requirement, unit) 
                        VALUES (:ingredient_id, :category_id, 0, 'unit')
                    ");
                    $insert_stmt->execute([
                        ':ingredient_id' => $ingredient_id,
                        ':category_id' => $category_id
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Ingredient assigned to category successfully! ✓']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid data']);
            }
        } elseif (isset($_POST['remove_btn'])) {
            $link_id = $_POST['link_id'] ?? null;

            if ($link_id) {
                $delete_stmt = $pdo->prepare("DELETE FROM category_ingredients WHERE id = :link_id");
                $delete_stmt->execute([':link_id' => $link_id]);
                echo json_encode(['success' => true, 'message' => 'Category assignment removed! ✓']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid data']);
            }
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit();
}

// Now include the HTML headers AFTER AJAX handling
include('includes/header.php');
include('includes/navbar.php');
include('includes/config.php');
include('includes/auth.php');

checkAccess(['admin']);

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}


// Get all ingredients
$ingredients_stmt = $pdo->query("SELECT * FROM ingredients ORDER BY name");
$all_ingredients = $ingredients_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all categories (excluding specific categories)
$categories_stmt = $pdo->query("
    SELECT * FROM categories 
    WHERE name NOT IN ('Add Ons', 'Burgers', 'Combo', 'Pasta', 'Ricemeal', 'Sandwiches', 'Snacks', 'Wings')
    ORDER BY name
");
$all_categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all category_ingredients links
$links_stmt = $pdo->query("
    SELECT ci.id, ci.ingredient_id, ci.category_id, ci.size, i.name as ingredient_name, c.name as category_name
    FROM category_ingredients ci
    JOIN ingredients i ON ci.ingredient_id = i.id
    JOIN categories c ON ci.category_id = c.id
    ORDER BY c.name, i.name, ci.size
");
$all_links = $links_stmt->fetchAll(PDO::FETCH_ASSOC);

// Create map of ingredient IDs to their assigned categories
$ingredient_categories = [];
foreach ($all_links as $link) {
    if (!isset($ingredient_categories[$link['ingredient_id']])) {
        $ingredient_categories[$link['ingredient_id']] = [];
    }
    $ingredient_categories[$link['ingredient_id']][] = [
        'link_id' => $link['id'],
        'category_id' => $link['category_id'],
        'category_name' => $link['category_name']
    ];
}

// Get unassigned ingredients (ALL ingredients should be available for assignment to multiple categories)
// Don't filter out already assigned ones - they should appear in left panel to assign to more categories
$unassigned_ingredients = $all_ingredients;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>KUSSO - Assign Ingredients to Categories</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>

<body>
<div id="layoutSidenav_content">
    <main>
        <div class="container-fluid px-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mt-4">Assign Ingredients to Categories</h1>
                    <p class="text-muted">Match each ingredient to its product category</p>
                </div>
                <div class="mt-4">
                    <a href="inventory.php" class="btn" style="color: #ffffff; background-color:#6c757d; border:none;">
                        <i class="fas fa-arrow-left"></i> Back to Inventory
                    </a>
                </div>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['info_message'])): ?>
                <div class="alert alert-info alert-dismissible fade show">
                    <?php echo $_SESSION['info_message']; unset($_SESSION['info_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Unassigned Ingredients -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header text-white" style="background-color: #3f2305;">
                            <i class="fas fa-exclamation-circle me-2"></i>Add Ingredients to Categories (<?php echo count($unassigned_ingredients); ?>)
                        </div>
                        <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                            <?php if (!empty($unassigned_ingredients)): ?>
                                <?php foreach ($unassigned_ingredients as $ing): ?>
                                    <div class="card mb-3" style="border-color: #c67c4e;">
                                        <div class="card-body">
                                            <h6 class="card-title"><?php echo htmlspecialchars($ing['name']); ?></h6>
                                            <form class="d-flex gap-2 assign-form" onsubmit="handleAssign(event, this)">
                                                <input type="hidden" name="ingredient_id" value="<?php echo $ing['id']; ?>">
                                                <select name="category_id" class="form-select form-select-sm" required>
                                                    <option value="">-- Select Category --</option>
                                                    <?php foreach ($all_categories as $cat): ?>
                                                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" class="btn btn-sm" style="white-space: nowrap; color: #ffffff; background-color:#c67c4e; border:none;">
                                                    <i class="fas fa-check me-1"></i>Add
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="alert alert-success">✓ All ingredients have been added to categories!</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Assigned Ingredients by Category -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header text-white" style="background-color: #3f2305;">
                            <i class="fas fa-check-circle me-2"></i>Assigned Ingredients by Category
                        </div>
                        <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                            <?php foreach ($all_categories as $cat): ?>
                                <?php 
                                    // Get ingredients for this category grouped by ingredient name
                                    $cat_links = array_filter($all_links, function($link) use ($cat) {
                                        return $link['category_id'] == $cat['id'];
                                    });
                                    
                                    // Group by ingredient name
                                    $grouped = [];
                                    foreach ($cat_links as $link) {
                                        $ingName = $link['ingredient_name'];
                                        if (!isset($grouped[$ingName])) {
                                            $grouped[$ingName] = [];
                                        }
                                        $grouped[$ingName][] = $link;
                                    }
                                    
                                    $cat_count = count($grouped);
                                ?>
                                <div class="mb-3 pb-3" style="border-bottom: 1px solid #ddd;">
                                    <h6 class="mb-2">
                                        <span class="badge" style="background-color: #c67c4e;"><?php echo $cat_count; ?></span>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </h6>
                                    <?php if ($cat_count > 0): ?>
                                        <ul style="padding-left: 20px; margin: 0; list-style: none;">
                                            <?php foreach ($grouped as $ingName => $links): ?>
                                                <li style="margin-bottom: 8px; padding: 8px; background: #f8f9fa; border-radius: 3px;">
                                                    <div style="font-weight: 600; margin-bottom: 5px;"><?php echo htmlspecialchars($ingName); ?></div>
                                                    <?php foreach ($links as $link): ?>
                                                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 3px 0 3px 15px;">
                                                            <span style="font-size: 13px;">
                                                                <span class="badge" style="background-color: <?php echo ($link['size'] ?? '16oz') === '16oz' ? '#2196F3' : '#4CAF50'; ?>; font-size: 10px;">
                                                                    <?php echo htmlspecialchars($link['size'] ?? '16oz'); ?>
                                                                </span>
                                                            </span>
                                                            <form class="remove-form" onsubmit="handleRemove(event, this)" style="display: inline;">
                                                                <input type="hidden" name="link_id" value="<?php echo $link['id']; ?>">
                                                                <button type="submit" class="btn btn-sm btn-danger" style="padding: 4px 12px; font-size: 11px;" title="Remove <?php echo htmlspecialchars($link['size'] ?? '16oz'); ?> from category">
                                                                    <i class="fas fa-trash-alt me-1"></i>Remove
                                                                </button>
                                                            </form>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <span style="color: #999; font-style: italic;">No ingredients assigned</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Instructions -->
            <div class="card shadow mt-4">
                <div class="card-header text-white" style="background-color: #6c757d;">
                    <i class="fas fa-info-circle me-2"></i>Instructions
                </div>
                <div class="card-body">
                    <ol>
                        <li>On the <strong>left side</strong>, select a category for each ingredient</li>
                        <li>Click <strong>Add</strong> to assign the ingredient to that category ✓</li>
                        <li>An ingredient can be assigned to <strong>multiple categories</strong></li>
                        <li>On the <strong>right side</strong>, see all assignments by category</li>
                        <li>Click the <strong>Remove button</strong> to remove an ingredient from a category</li>
                        <li>Once set up, go to "Manage Category Ingredients" to set quantity requirements</li>
                    </ol>
                </div>
            </div>
        </div>
    </main>
</div>

<?php 
    include('includes/footer.php');
    include('includes/scripts.php');
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Function to show alerts dynamically
function showAlert(message, type) {
    const alertContainer = document.querySelector('main .container-fluid');
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insert after title
    const title = alertContainer.querySelector('h1');
    title.parentNode.insertBefore(alertDiv, title.nextSibling);
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Function to reload the assignments panel
function reloadAssignments() {
    location.reload();
}

// Handle assign form submission
function handleAssign(event, form) {
    event.preventDefault();
    
    const formData = new FormData(form);
    formData.append('assign_btn', '1');
    
    fetch('assign_ingredients_to_categories.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            form.reset();
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showAlert(data.message, 'info');
            form.reset();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error processing request: ' + error.message, 'danger');
    });
}

// Handle remove form submission
function handleRemove(event, form) {
    event.preventDefault();
    
    const formData = new FormData(form);
    formData.append('remove_btn', '1');
    
    fetch('assign_ingredients_to_categories.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error processing request: ' + error.message, 'danger');
    });
}
</script>

</body>
</html>
