<?php 
    session_start();
    include ('includes/header.php');
    include('includes/navbar.php');
    include('includes/config.php');
?>

<!DOCTYPE html>
<html lang="en"></html>
<head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>KUSSO-Users</title>
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
        <h1 class="mt-4">Users</h1>  
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
                    <div class="modal fade" id="add_user" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5" id="exampleModalLabel">Add User</h1>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <!-- Error Message Placeholder -->
                                <div id="error-message" class="alert alert-danger d-none" role="alert"></div>
                                <!-- Form for submission -->
                                <form action="manage_user.php" method="POST">
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Full Name</label>
                                            <input type="text" class="form-control" id="name" name="name" placeholder="Enter your Name" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="username" class="form-label">Username</label>
                                            <input type="text" class="form-control" id="username" name="username" placeholder="Enter your Username" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="role" class="form-label">Select Role</label>
                                            <select class="form-select" id="role" name="role" required>
                                                <option value="admin">Admin</option>
                                                <option value="barista">Barista</option>
                                                <option value="cashier">Cashier</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="password" class="form-label">Password</label>
                                            <input type="password" class="form-control" id="password" name="password" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="confirmpassword" class="form-label">Confirm Password</label>
                                            <input type="password" class="form-control" id="confirmpassword" name="confirmpassword" required>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" name="registerbtn" class="btn btn-primary">Save changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                  <!-- Card -->
                    <div class="card shadow mb-4">
                        <!-- Card Header -->
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-table me-1"></i> Manage Users</span>
                            <div class="d-flex">
                                <!-- Add User Button -->
                                <button type="button" class="btn btn-secondary me-2" data-bs-toggle="modal" data-bs-target="#add_user" title="Add a new user" style="color: #ffffff; background-color:#c67c4e; border:none;">
                                    <i class="fa-solid fa-plus"></i> Add User
                                </button>    

                                <!-- Recycle Bin Button -->
                                <a href="user_bin.php" class="btn btn-danger me-2" title="View Deleted Users" style="color: #ffffff;  border:none;">
                                    <i class="fa-solid fa-trash-can"></i> User Bin
                                </a>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="card-body">
                            <!-- Table -->
                            <table id="datatablesSimple" class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th scope="col">ID</th>
                                        <th scope="col">Name</th>
                                        <th scope="col">Position</th>
                                        <th scope="col">Username</th>
                                        <!-- <th scope="col">Password</th> -->
                                        <th scope="col">Actions</th>

                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                        try {
                                            // Connect to the database
                                            $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
                                            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                                            // Query to fetch users
                                            $stmt = $pdo->query("SELECT id, name, role, username, password FROM users");
                                            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                            // Loop through the users and display them
                                            foreach ($users as $user) {
                                                echo '<tr>
                                                        <th scope="row">' . htmlspecialchars($user['id']) . '</th>
                                                        <td>' . htmlspecialchars($user['name']) . '</td>
                                                        <td>' . htmlspecialchars($user['role']) . '</td>
                                                        <td>' . htmlspecialchars($user['username']) . '</td>
                                                        
                                                         <td>
                                                            <!-- Edit Button -->
                                                            <form action="edit_user.php" method="GET" style="display:inline;">
                                                                <input type="hidden" name="id" value="' . htmlspecialchars($user['id']) . '">
                                                                <button type="submit" class="btn btn-primary" title="Edit">
                                                                    <i class="fa fa-edit"></i>
                                                                </button>
                                                            </form>
                                                            
                                                            <!-- Delete Button -->
                                                            <form action="manage_user.php" method="POST" style="display:inline;" onsubmit="return confirm(\'Are you sure you want to delete this user?\');">
                                                                <input type="hidden" name="id" value="' . htmlspecialchars($user['id']) . '">
                                                                <button type="submit" name= "deletebtn" class="btn btn-danger" title="Delete">
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
                            <!-- End Table -->
                        </div>
                    </div>                
                  <!-- End Card -->
                  </div>
                </main>
                
      <?php include ('includes/footer.php');
            include ('includes/scripts.php');
     
        ?>         
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirmpassword');
            const errorMessage = document.getElementById('error-message');

            passwordInput.addEventListener('input', function () {
                const password = passwordInput.value;
                const strengthMessage = checkPasswordStrength(password);

                if (strengthMessage) {
                    errorMessage.textContent = strengthMessage;
                    errorMessage.classList.remove('d-none');
                } else {
                    errorMessage.textContent = '';
                    errorMessage.classList.add('d-none');
                }
            });

            confirmPasswordInput.addEventListener('input', function () {
                if (passwordInput.value !== confirmPasswordInput.value) {
                    errorMessage.textContent = "Passwords do not match.";
                    errorMessage.classList.remove('d-none');
                } else {
                    errorMessage.textContent = '';
                    errorMessage.classList.add('d-none');
                }
            });

            function checkPasswordStrength(password) {
                if (password.length < 8) {
                    return "Password must be at least 8 characters long.";
                }
                if (!/[A-Z]/.test(password)) {
                    return "Password must contain at least one uppercase letter.";
                }
                if (!/[a-z]/.test(password)) {
                    return "Password must contain at least one lowercase letter.";
                }
                if (!/[0-9]/.test(password)) {
                    return "Password must contain at least one number.";
                }
                if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
                    return "Password must contain at least one special character.";
                }
                return null;
            }
        });
    </script>
</body>  
</html>              
