<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<style>
    .sb-sidenav {
        background-color: #1a1f26 !important;
        color: #fff;
    }

    .sb-sidenav .nav-link {
        color: #fff;
        font-size: 1rem;
        padding: 15px 20px;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .sb-sidenav .nav-link:hover {
        background-color: #2a3038 !important;
        color: #fff;
    }

    .sb-sidenav .sb-nav-link-icon {
        display: inline-block;
        width: 30px;
        text-align: center;
    }

    .sb-sidenav .nav-link.active {
        background-color: #c67c4e;
        color: #fff;
    }

    .sb-sidenav-footer {
        background-color: transparent;
        color: #fff;
        text-align: center;
        padding: 20px;
    }

    .sb-sidenav-footer a {
        color: #fff;
        text-decoration: none;
        font-size: 1rem;
        font-weight: bold;
        transition: color 0.3s ease;
    }

    .sb-sidenav-footer a:hover {
        color: #c67c4e;
    }

    .sb-sidenav-dark {
        background-color: #1a1f26 !important;
        color: #fff !important;
    }

    .sb-sidenav-dark .sb-sidenav-menu .nav-link {
        color: #fff !important;
    }

    .sb-sidenav-dark .sb-sidenav-menu .nav-link:hover {
        background-color: #2a3038 !important;
    }

    .sb-sidenav-dark .sb-sidenav-menu .nav-link.active {
        background-color: #c67c4e !important;
    }
</style>

<div id="layoutSidenav">
    <div id="layoutSidenav_nav">
        <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
            <div class="sb-sidenav-menu">
                <div class="nav">
                    <div class="sb-sidenav-menu-heading text-uppercase">Menu</div>

                    <!-- Admin Access -->
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
                            <div class="sb-nav-link-icon">
                                <i class="fa-solid fa-gauge-high"></i>
                            </div>
                            Dashboard
                        </a>
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>" href="orders.php">
                            <div class="sb-nav-link-icon">
                                <i class="fa-solid fa-clipboard"></i>
                            </div>
                            Orders
                        </a>
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pos/index.php' ? 'active' : ''; ?>" href="pos/index.php">
                            <div class="sb-nav-link-icon">
                                <i class="fa-solid fa-cash-register"></i>
                            </div>
                            POS
                        </a>
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>" href="categories.php">
                            <div class="sb-nav-link-icon">
                                <i class="fa-solid fa-list"></i>
                            </div>
                            Categories
                        </a>
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>" href="products.php">
                            <div class="sb-nav-link-icon">
                                <i class="fa-solid fa-cart-shopping"></i>
                            </div>
                            Products
                        </a>
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'active' : ''; ?>" href="inventory.php">
                            <div class="sb-nav-link-icon">
                                <i class="fa-solid fa-cart-flatbed"></i>
                            </div>
                            Inventory
                        </a>
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'kitchen.php' ? 'active' : ''; ?>" href="kitchen.php">
                            <div class="sb-nav-link-icon">
                                <i class="fa-solid fa-kitchen-set"></i>
                            </div>
                            Kitchen
                        </a>
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'sales_report.php' ? 'active' : ''; ?>" href="sales_report.php">
                            <div class="sb-nav-link-icon">
                                <i class="fa-solid fa-chart-bar"></i>
                            </div>
                            Sales Report
                        </a>
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_expenses.php' ? 'active' : ''; ?>" href="manage_expenses.php">
                            <div class="sb-nav-link-icon">
                                <i class="fa-solid fa-money-bill-wave"></i>
                            </div>
                            Expenses
                        </a>
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'user.php' ? 'active' : ''; ?>" href="user.php">
                            <div class="sb-nav-link-icon">
                                <i class="fa-solid fa-user"></i>
                            </div>
                            User
                        </a>
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'backup_recovery.php' ? 'active' : ''; ?>" href="backup_recovery.php">
                            <div class="sb-nav-link-icon">
                                <i class="fa-solid fa-shield-halved"></i>
                            </div>
                            Backups
                        </a>
                    <?php endif; ?>

                    <!-- Cashier Access -->
                    <?php if ($_SESSION['role'] === 'cashier'): ?>
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>" href="orders.php">
                            <div class="sb-nav-link-icon">
                                <i class="fa-solid fa-clipboard"></i>
                            </div>
                            Orders
                        </a>
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pos/index.php' ? 'active' : ''; ?>" href="pos/index.php">
                            <div class="sb-nav-link-icon">
                                <i class="fa-solid fa-cash-register"></i>
                            </div>
                            POS
                        </a>
                       
                    <?php endif; ?>

                    <!-- Barista Access -->
                    <?php if ($_SESSION['role'] === 'barista'): ?>
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'kitchen.php' ? 'active' : ''; ?>" href="kitchen.php">
                            <div class="sb-nav-link-icon">
                                <i class="fa-solid fa-kitchen-set"></i>
                            </div>
                            Kitchen
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Logout -->
            <div class="sb-sidenav-footer">
                <a href="logout.php">
                    <i class="fa-solid fa-right-from-bracket fa-2x"></i>
                    <div>Logout</div>
                </a>
            </div>
        </nav>
    </div>