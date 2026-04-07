<?php 
    session_start();
    include ('includes/header.php');
    include('includes/navbar.php');
    include('includes/config.php');


    // Fetch user details
    if (isset($_GET['id'])) {
      $userId = $_GET['id'];
      try {
          $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
          $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

          $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
          $stmt->execute(['id' => $userId]);
          $user = $stmt->fetch(PDO::FETCH_ASSOC);

          if (!$user) {
              $_SESSION['error_message'] = "User not found.";
              header('Location: edit_user.php');
              exit();
          }
      } catch (PDOException $e) {
          $_SESSION['error_message'] = "Error fetching user: " . $e->getMessage();
          header('Location: edit_user.php');
          exit();
      }
    } else {
      header('Location: users.php');
      exit();
    }

?>

<!DOCTYPE html>
<html lang="en"></html>
<head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>KUSSO- Edit Users</title>
        <link href="css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    </head>

    <body>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <!-- Title Section -->
                    <h1 class="mt-4">Edit User</h1>
                    <!-- Edit Form -->
                    <form action="manage_user.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="edit_id" value="<?php echo htmlspecialchars($user['id']);?>">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="edit_name" name="edit_name" value="<?php echo htmlspecialchars($user['name']); ?>" placeholder="Enter your Name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="edit_username" name="edit_username" value="<?php echo htmlspecialchars($user['username']); ?>" placeholder="Enter your Username" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_role" class="form-label">Select Role</label>
                            <select class="form-select" id="edit_role" name="edit_role" required>
                                <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="barista" <?php echo $user['role'] == 'barista' ? 'selected' : ''; ?>>Barista</option>
                                <option value="cashier" <?php echo $user['role'] == 'cashier' ? 'selected' : ''; ?>>Cashier</option>
                            </select>
                        </div>

                         <!-- Current Password Section -->
                         <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" placeholder="Enter current password">
                        </div>

                         <!-- New Password Section -->
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Enter new password (leave blank to keep current password)">
                        </div>

                        <div class="mb-3">
                            <label for="confirm_new_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password" placeholder="Confirm new password">
                        </div>

                                <!-- Cancel and Save Changes Buttons -->
                                <a href="user.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" name="updatebtn" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </main>

    <?php
    include('includes/footer.php');
    include('includes/scripts.php');
    ?>
</body>

</html>
