<?php
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <!-- Custom Styles -->
    <link href="css/styles.css" rel="stylesheet" />
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css" rel="stylesheet">
    <style>
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .navbar-brand img {
            max-height: 40px;
            width: auto;
        }

        .navbar-text {
            font-size: 1rem;
            font-weight: bold;
        }

        .dropdown-menu {
            background-color: #3f2305;
            color: #c67c4e;
        }

        .dropdown-menu a {
            color: #c67c4e;
            transition: color 0.3s ease;
        }

        .dropdown-menu a:hover {
            color: #3f2305;
        }
    </style>
</head>
<body class="sb-nav-fixed">
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <!-- Navbar Brand-->
        <a class="navbar-brand ps-5" href="index.php">
            <img src="assets/img/kalicafe_logo.jpg" alt="Kali Cafe Logo">
            <h4 class="d-inline text-white">Kali Cafe</h4>
        </a>
        <!-- Sidebar Toggle-->
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!">
            <i class="fas fa-bars fa-2x"></i>
        </button>
        <!-- Navbar-->
        <ul class="navbar-nav ms-auto me-3 me-lg-4">
            <li class="nav-item">
                <span class="navbar-text text-white me-3">Welcome, <?php echo htmlspecialchars($username); ?></span>
            </li>
        </ul>
    </nav>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>