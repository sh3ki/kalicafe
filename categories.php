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
                <h1 class="mt-4">Categories</h1>

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
                    <div class="modal fade" id="add_category" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5" id="exampleModalLabel">Add Category</h1>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <!-- Error Message Placeholder -->
                                <div id="error-message" class="alert alert-danger d-none" role="alert"></div>
                                <!-- Form for submission -->
                                <form action="manage_categories.php" method="POST">
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Category Name</label>
                                            <input type="text" class="form-control" id="category_name" name="category_name" placeholder="Enter Category name" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="category_type" class="form-label">Category Type</label>
                                            <select class="form-select" id="category_type" name="category_type" required>
                                                <option value="">Select Type</option>
                                                <option value="Food">Food</option>
                                                <option value="Drinks">Drinks</option>
                                                <option value="Extras">Extras</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" name="addcat_btn" class="btn btn-primary">Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>


                    <div class="card shadow mb-4">
                        <!-- Card Header -->
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-table me-1"></i> Manage Categories</span>
                                <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#add_category" title="Add a new category" style="color: #ffffff; background-color:#c67c4e; border:none;">
                                <i class="fa-solid fa-plus" ></i>
                                Add Category 
                                </button>
                        </div>

                        <!-- Card Body -->
                        <div class="card-body">
                            <!-- Table -->
                            <table id="datatablesSimple" class="table table-bordered">
                                <thead>
                                    <tr>
                                        <!-- <th scope="col">ID</th> -->
                                        <th scope="col">Name</th>
                                        <th scope="col">Actions</th>
                                
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                        try {
                                            // Connect to the database
                                            $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
                                            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                                            // Query to fetch categories (excluding specific categories)
                                            $stmt = $pdo->query("
                                                SELECT id, name FROM categories 
                                                WHERE name NOT IN ('Add Ons', 'Burgers', 'Combo', 'Pasta', 'Ricemeal', 'Sandwiches', 'Snacks', 'Wings')
                                            ");
                                            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                            // Loop through the users and display them
                                            foreach ($categories as $category) {
                                                echo '<tr>
                                                       
                                                        <td>' . htmlspecialchars($category['name']) . '</td>
                                                
                                                         <td>
                                                            <!-- Edit Button -->
                                                            <form action="edit_category.php" method="GET" style="display:inline;">
                                                                <input type="hidden" name="id" value="' . htmlspecialchars($category['id']) . '">
                                                                <button type="submit" class="btn btn-primary" title="Edit">
                                                                    <i class="fa fa-edit"></i>
                                                                </button>
                                                            </form>
                                                            
                                                            <!-- Delete Button -->
                                                            <form action="manage_categories.php" method="POST" style="display:inline;" onsubmit="return confirm(\'Are you sure you want to delete this user?\');">
                                                                <input type="hidden" name="id" value="' . htmlspecialchars($category['id']) . '">
                                                                <button type="submit" name= "deletecat_btn" class="btn btn-danger" title="Delete">
                                                                    <i class="fa fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </td>                                                  
                                                    </tr>';

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
