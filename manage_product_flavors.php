<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Allow only admin
checkAccess(['admin']);

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Link Flavor to Product
    if (isset($_POST['link_flavor_btn'])) {
        $product_id = $_POST['product_id'];
        $flavor_ids = $_POST['flavor_ids'] ?? [];
        $quantities = $_POST['quantities'] ?? [];
        $units = $_POST['units'] ?? [];
        $sizes = $_POST['sizes'] ?? [];

        if (empty($flavor_ids)) {
            $_SESSION['message'] = "Please select at least one flavor.";
            $_SESSION['message_type'] = "warning";
            header('Location: inventory.php');
            exit();
        }

        $success_count = 0;
        $error_count = 0;

        foreach ($flavor_ids as $index => $flavor_id) {
            $quantity = $quantities[$index] ?? 0;
            $unit = $units[$index] ?? '';
            $size = $sizes[$index] ?? '16oz';

            if ($quantity <= 0 || empty($unit)) {
                $error_count++;
                continue;
            }

            try {
                $stmt = $pdo->prepare("
                    INSERT INTO product_flavors (product_id, flavor_id, size, quantity_required, unit)
                    VALUES (:product_id, :flavor_id, :size, :quantity_required, :unit)
                    ON DUPLICATE KEY UPDATE 
                        quantity_required = :quantity_required2,
                        unit = :unit2
                ");
                $stmt->execute([
                    'product_id' => $product_id,
                    'flavor_id' => $flavor_id,
                    'size' => $size,
                    'quantity_required' => $quantity,
                    'unit' => $unit,
                    'quantity_required2' => $quantity,
                    'unit2' => $unit
                ]);
                $success_count++;
            } catch (PDOException $e) {
                $error_count++;
            }
        }

        if ($success_count > 0) {
            $_SESSION['message'] = "Successfully linked $success_count flavor(s).";
            $_SESSION['message_type'] = "success";
        }
        if ($error_count > 0) {
            $_SESSION['message'] .= " Failed to link $error_count flavor(s).";
            $_SESSION['message_type'] = "warning";
        }

        header('Location: inventory.php');
        exit();
    }

    // Unlink Flavor from Product
    if (isset($_POST['unlink_flavor_btn'])) {
        $flavor_link_id = $_POST['flavor_link_id'];

        $stmt = $pdo->prepare("DELETE FROM product_flavors WHERE id = :id");
        $stmt->execute(['id' => $flavor_link_id]);

        $_SESSION['message'] = "Flavor unlinked successfully!";
        $_SESSION['message_type'] = "success";

        header('Location: inventory.php');
        exit();
    }

} catch (PDOException $e) {
    $_SESSION['message'] = "Error: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
    header('Location: inventory.php');
    exit();
}
?>
