<?php
session_start();
include('includes/config.php');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Database connection failed: " . $e->getMessage();
    header('Location: user.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Invalid request method.";
    header('Location: user.php');
    exit();
}

// Function to validate password strength
function validatePasswordStrength($password) {
    if (strlen($password) < 8) {
        return "Password must be at least 8 characters long.";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return "Password must contain at least one uppercase letter.";
    }
    if (!preg_match('/[a-z]/', $password)) {
        return "Password must contain at least one lowercase letter.";
    }
    if (!preg_match('/[0-9]/', $password)) {
        return "Password must contain at least one number.";
    }
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        return "Password must contain at least one special character.";
    }
    return null;
}

// Handle Add Request
if (isset($_POST['registerbtn'])) {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $role = $_POST['role'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmpassword = $_POST['confirmpassword'] ?? '';

    if (empty($name) || empty($username) || empty($role) || empty($password) || empty($confirmpassword)) {
        $_SESSION['error_message'] = "All fields are required.";
        header('Location: user.php');
        exit();
    }

    if ($password !== $confirmpassword) {
        $_SESSION['error_message'] = "Passwords do not match.";
        header('Location: user.php');
        exit();
    }

    $passwordError = validatePasswordStrength($password);
    if ($passwordError) {
        $_SESSION['error_message'] = $passwordError;
        header('Location: user.php');
        exit();
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (name, username, role, password) VALUES (:name, :username, :role, :password)");
        $stmt->execute([
            ':name' => $name,
            ':username' => $username,
            ':role' => $role,
            ':password' => $hashedPassword,
        ]);

        $_SESSION['success_message'] = "User added successfully.";
        header('Location: user.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error saving user: " . $e->getMessage();
        header('Location: user.php');
        exit();
    }
}

// Handle Update Request
if (isset($_POST['updatebtn'])) {
    $id = $_POST['edit_id'] ?? null;
    $name = trim($_POST['edit_name'] ?? '');
    $username = trim($_POST['edit_username'] ?? '');
    $role = $_POST['edit_role'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';
    $current_password = $_POST['current_password'] ?? '';  // Retrieve the current password

    if (empty($id) || empty($name) || empty($username) || empty($role)) {
        $_SESSION['error_message'] = "Required fields are missing.";
        header('Location: user.php');
        exit();
    }

    try {
        // Check if the current password is provided and valid
        if (!empty($new_password) || !empty($confirm_new_password)) {
            // Check if current password is provided
            if (empty($current_password)) {
                throw new Exception("Current password is required.");
            }

            // Retrieve the user's current password from the database
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verify if the entered current password matches the stored password
            if ($user && !password_verify($current_password, $user['password'])) {
                throw new Exception("Current password is incorrect.");
            }

            // Validate new password
            if ($new_password !== $confirm_new_password) {
                throw new Exception("Passwords do not match.");
            }

            $passwordError = validatePasswordStrength($new_password);
            if ($passwordError) {
                throw new Exception($passwordError);
            }

            // Hash new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update user information and password
            $stmt = $pdo->prepare("UPDATE users SET name = ?, username = ?, role = ?, password = ? WHERE id = ?");
            $stmt->execute([$name, $username, $role, $hashed_password, $id]);

            $_SESSION['success_message'] = "User information and password updated successfully.";
        } else {
            // Update user information without changing password
            $stmt = $pdo->prepare("UPDATE users SET name = ?, username = ?, role = ? WHERE id = ?");
            $stmt->execute([$name, $username, $role, $id]);

            $_SESSION['success_message'] = "User information updated successfully.";
        }

        header('Location: user.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Database Error: " . $e->getMessage();
        header('Location: user.php');
        exit();
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        header('Location: user.php');
        exit();
    }
}


// Handle Delete Request
if (isset($_POST['deletebtn'])) {
    $id = $_POST['id'] ?? null;

    $deleted_by = $_SESSION['username']; // Username of the admin who is deleting the user
    $deleted_for = "User deleted"; // Reason for deletion or any other information

    // Check if the user ID is empty
    if (empty($id)) {
        $_SESSION['error_message'] = "User ID is missing.";
        header('Location: user.php');
        exit();
    }

    try {
        // Step 1: Fetch the user data before deleting
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$user) {
            $_SESSION['error_message'] = "User not found.";
            header('Location: user.php');
            exit();
        }
    
        // Step 2: Insert the user record into the deleted_users table with deleted_at timestamp
        $stmt = $pdo->prepare("INSERT INTO deleted_users (id, name, username, password, role, deleted_at, deleted_by) VALUES (?, ?, ?, ?, ?, NOW(), ?)");
        $stmt->execute([$user['id'], $user['name'], $user['username'], $user['password'], $user['role'], $deleted_by]);
    
        // Step 3: Delete the user from the users table
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
    
        $_SESSION['success_message'] = "User has been moved to the bin.";
        header('Location: user.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Database Error: " . $e->getMessage();
        header('Location: user.php');
        exit();
    }
}


// Catch-All for Invalid Actions
$_SESSION['error_message'] = "Invalid request.";
header('Location: user.php');
exit();
?>

