<?php
include('includes/config.php');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Creating product_flavors table...<br><br>";
    
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'product_flavors'");
    if ($stmt->rowCount() > 0) {
        echo "Table 'product_flavors' already exists!<br>";
    } else {
        // Create product_flavors table
        $pdo->exec("
            CREATE TABLE product_flavors (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_id INT NOT NULL,
                flavor_id INT NOT NULL,
                size VARCHAR(20) DEFAULT '16oz',
                quantity_required DECIMAL(10,2) NOT NULL,
                unit VARCHAR(50) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_product_flavor_size (product_id, flavor_id, size),
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                FOREIGN KEY (flavor_id) REFERENCES ingredients(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "✓ Table 'product_flavors' created successfully!<br>";
    }
    
    echo "<br><strong>Done!</strong> You can now link flavors to products with size-specific quantities.";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
