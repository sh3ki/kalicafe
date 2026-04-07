<?php 
    session_start();
    include('../includes/config.php');
    include('../includes/auth.php');

    // Allow only admin and cashier
    checkAccess(['admin', 'cashier']);
?>

<!DOCTYPE html>
<html lang="en"></html>
<head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>KUSSO-POS</title>
        <link href="css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>

        <?php include('../pos/topbar.php'); ?>

    </head>

    <style>


    </style>
    <body>

    <div class="page"></div>
    <div id="layoutSidenav_content">
        <main>
        <?php $page = isset($_GET['page']) ? $_GET['page'] :'home'; ?>
        <?php include $page.'.php' ?>
        </main>

    <?php 
        include ('../includes/scripts.php');
     ?>         

  </body>  
</html>              