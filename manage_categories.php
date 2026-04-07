    <?php
    session_start();
    include('includes/config.php');

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Database connection failed: " . $e->getMessage();
        header('Location: categories.php');
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $_SESSION['error_message'] = "Invalid request method.";
        header('Location: categories.php');
        exit();
    }

    // Handle Add Request
    if (isset($_POST['addcat_btn'])) {
        $name = trim($_POST['category_name'] ?? '');
        $type = trim($_POST['category_type'] ?? '');

        if (empty($name) || empty($type)) {
            $_SESSION['error_message'] = "Category name and type cannot be empty.";
            header('Location: categories.php');
            exit();
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, type) VALUES (:name, :type)");
            $stmt->execute([':name' => $name, ':type' => $type]);

            $_SESSION['success_message'] = "Category added successfully.";
            header('Location: categories.php');
            exit();
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Database error: " . $e->getMessage();
            header('Location: categories.php');
            exit();
        }
    }

    // Handle Update Request
    if (isset($_POST['updatecat_btn'])) {
        $edit_id = $_POST['edit_id'] ?? null;
        $edit_name = trim($_POST['edit_category'] ?? '');

        if (empty($edit_id) || empty($edit_name)) {
            $_SESSION['error_message'] = "Invalid request. Missing required data.";
            header('Location: categories.php');
            exit();
        }

        $edit_type = trim($_POST['edit_category_type'] ?? '');
        try {
            $stmt = $pdo->prepare("UPDATE categories SET name = :name, type = :type WHERE id = :id");
            $stmt->execute(['name' => $edit_name, 'type' => $edit_type, 'id' => $edit_id]);

            $_SESSION['success_message'] = "Category updated successfully.";
            header('Location: categories.php');
            exit();
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error updating category: " . $e->getMessage();
            header("Location: edit_category.php?id=$edit_id");
            exit();
        }
    }

    // Handle Delete Request
    if (isset($_POST['deletecat_btn'])) {
        $delete_id = $_POST['id'] ?? null;

        if (empty($delete_id)) {
            $_SESSION['error_message'] = "Invalid request. Missing required data.";
            header('Location: categories.php');
            exit();
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = :id");
            $stmt->execute(['id' => $delete_id]);

            $_SESSION['success_message'] = "Category deleted successfully.";
            header('Location: categories.php');
            exit();
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error deleting category: " . $e->getMessage();
            header('Location: categories.php');
            exit();
        }
    }

    // Default Invalid Request
    $_SESSION['error_message'] = "Invalid request.";
    header('Location: categories.php');
    exit();
    ?>

