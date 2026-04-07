<?php 
    session_start();
    include ('includes/header.php');
    include('includes/navbar.php');
    include('includes/config.php');
    

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Recycle Bin - Users</title>
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>

<body>
    <div id="layoutSidenav_content">
        <main>
            <div class="container-fluid px-4">
                <h1 class="mt-4">Recycle Bin - Deleted Users</h1>

                <!-- Display Success or Error Messages -->
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

                <!-- Table for Recycle Bin -->
                <div class="card shadow mb-4">
                  <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-table me-1"></i> Deleted Users</span>
                            <div class="d-flex">
                                <!-- User Button -->
                                <a href="user.php" class="btn btn-danger me-2" title="View Deleted Users" style="color: #ffffff; background-color:#c67c4e;  border:none;">
                                    <i class="fa-solid fa-user"></i> Users
                                </a>
                            </div>
                        </div>
                    <div class="card-body">
                    <table id="datatablesSimple" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th scope="col">ID</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Position</th>
                                    <th scope="col">Username</th>
                                    <th scope="col">Deleted At</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    try {
                                        // Connect to the database
                                        $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
                                        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                                        // Query to fetch deleted users
                                        $stmt = $pdo->query("SELECT id, name, role, username, deleted_at FROM deleted_users");
                                        $deleted_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                        // Loop through the deleted users and display them
                                        foreach ($deleted_users as $deleted_user) {
                                            echo '<tr>
                                                    <th scope="row">' . htmlspecialchars($deleted_user['id']) . '</th>
                                                    <td>' . htmlspecialchars($deleted_user['name']) . '</td>
                                                    <td>' . htmlspecialchars($deleted_user['role']) . '</td>
                                                    <td>' . htmlspecialchars($deleted_user['username']) . '</td>
                                                    <td>' . htmlspecialchars($deleted_user['deleted_at']) . '</td>
                                                    <td>
                                                    <!-- Restore Button -->
                                                    <form action="manage_user_bin.php" method="POST" style="display:inline;">
                                                        <input type="hidden" name="id" value="' . htmlspecialchars($deleted_user['id']) . '">
                                                        <button type="submit" name="restorebtn" class="btn btn-success btn-sm" title="Restore">
                                                            <i class="fa fa-undo"></i> Restore
                                                        </button>
                                                    </form>
                                                    <!-- Permanently Delete Button -->
                                                    <form action="manage_user_bin.php" method="POST" style="display:inline;">
                                                        <input type="hidden" name="id" value="' . htmlspecialchars($deleted_user['id']) . '">
                                                        <button type="submit" name="deletebtn" class="btn btn-danger btn-sm" title="Delete Permanently">
                                                            <i class="fa fa-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                </td>
                                                </tr>';
                                        }
                                    } catch (PDOException $e) {
                                        echo "<tr><td colspan='6'>Error fetching data: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                                    }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>


    <?php include ('includes/footer.php'); 
        include('cleanup_deleted_users.php');
     include ('includes/scripts.php'); ?>
</body>
</html>

