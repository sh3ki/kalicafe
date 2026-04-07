<?php
$host = "sql100.infinityfree.com";
$db_name = "if0_41603891_kusso";
$username = "if0_41603891";
$password = "KaliCafe123";

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
