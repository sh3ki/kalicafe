<?php
function checkAccess($allowedRoles) {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowedRoles)) {
        // Redirect to an access denied page or login page
        header("Location: access_denied.php");
        exit();
    }
}
?>