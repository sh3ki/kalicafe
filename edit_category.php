<?php 
session_start();
include('includes/header.php');
include('includes/navbar.php');
include('includes/config.php');

// Fetch category details
    if (isset($_GET['id'])) {
        $categoryID = $_GET['id'];
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = :id");
            $stmt->execute(['id' => $categoryID]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$category) {
                $_SESSION['error_message'] = "Category not found.";
                header('Location: categories.php'); // Redirect to the category list
                exit();
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error fetching category: " . $e->getMessage();
            header('Location: categories.php');
            exit();
        }
    } else {
        header('Location: categories.php'); // Redirect if no ID is provided
        exit();
    }
    ?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <title>KUSSO - Edit Category</title>
        <link href="css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    </head>

    <body>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <!-- Title Section -->
                    <h1 class="mt-4">Edit Category</h1>

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

                    <!-- Edit Form -->
                    <form action="manage_categories.php" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="edit_id" value="<?php echo htmlspecialchars($category['id']); ?>">

                            <div class="mb-3">
                                <label for="edit_category" class="form-label">Name</label>
                                <input type="text" 
                                    class="form-control" 
                                    id="edit_category" 
                                    name="edit_category" 
                                    value="<?php echo htmlspecialchars($category['name']); ?>" 
                                    placeholder="Enter category name" 
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_category_type" class="form-label">Category Type</label>
                                <select class="form-select" id="edit_category_type" name="edit_category_type" required>
                                    <option value="">Select Type</option>
                                    <option value="Food" <?php if(isset($category['type']) && $category['type']==='Food') echo 'selected'; ?>>Food</option>
                                    <option value="Drinks" <?php if(isset($category['type']) && $category['type']==='Drinks') echo 'selected'; ?>>Drinks</option>
                                    <option value="Extras" <?php if(isset($category['type']) && $category['type']==='Extras') echo 'selected'; ?>>Extras</option>
                                </select>
                            </div>

                            <!-- Cancel and Save Changes Buttons -->
                            <a href="categories.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" name="updatecat_btn" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </main>
        </div>

        <?php
        include('includes/footer.php');
        include('includes/scripts.php');
        ?>
    </body>

    </html>

