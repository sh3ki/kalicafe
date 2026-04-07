<?php
include('includes/config.php');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Updating product_flavors unique constraint...<br><br>";
    
    // Drop the old unique constraint
    try {
        $pdo->exec("ALTER TABLE product_flavors DROP INDEX unique_product_flavor_size");
        echo "✓ Dropped old constraint 'unique_product_flavor_size'<br>";
    } catch (PDOException $e) {
        echo "Note: Old constraint may not exist: " . $e->getMessage() . "<br>";
    }
    
    // Add new unique constraint that allows same flavor in multiple products
    // but prevents duplicate flavor+size for the same product
    $pdo->exec("ALTER TABLE product_flavors ADD UNIQUE KEY unique_product_flavor_size (product_id, flavor_id, size)");
    echo "✓ Added new constraint (product_id, flavor_id, size)<br>";
    
    echo "<br><strong>Done!</strong> Now you can link the same flavor to multiple products.";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
