<?php
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Delete users who have been in the bin for more than 30 days
    $stmt = $pdo->prepare("DELETE FROM deleted_users WHERE deleted_at < NOW() - INTERVAL 30 DAY");
    $stmt->execute();

    echo "Cleanup completed successfully.";
} catch (PDOException $e) {
    echo "Error during cleanup: " . $e->getMessage();
}