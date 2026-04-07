<?php
session_start();
include('includes/config.php');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = $_POST['id'] ?? null;

        if (isset($_POST['restorebtn'])) {
            // Restore user
            $stmt = $pdo->prepare("SELECT * FROM deleted_users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $stmt = $pdo->prepare("INSERT INTO users (id, name, username, role, password) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$user['id'], $user['name'], $user['username'], $user['role'], $user['password']]);

                $stmt = $pdo->prepare("DELETE FROM deleted_users WHERE id = ?");
                $stmt->execute([$id]);

                $_SESSION['success_message'] = "User restored successfully.";
            } else {
                $_SESSION['error_message'] = "User not found in the bin.";
            }
        } elseif (isset($_POST['deletebtn'])) {
            // Permanently delete user
            $stmt = $pdo->prepare("DELETE FROM deleted_users WHERE id = ?");
            $stmt->execute([$id]);

            $_SESSION['success_message'] = "User permanently deleted.";
        }
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Database Error: " . $e->getMessage();
}

header('Location: user_bin.php');
exit();
?>
