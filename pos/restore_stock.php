<?php
include('../includes/config.php');

// Restore ingredient 2 to normal stock
$stmt = $conn->prepare("UPDATE ingredients SET quantity = 800 WHERE id = 2");
$stmt->execute();

echo "Ingredient 2 restored to 800ml";
?>
