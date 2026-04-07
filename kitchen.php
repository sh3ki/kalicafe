<?php 
    session_start();
    include ('includes/header.php');
    include('includes/navbar.php');
    include('includes/config.php');

    // Pagination settings
    $orders_per_page = 3;
    $current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    if ($current_page < 1) $current_page = 1;
    $offset = ($current_page - 1) * $orders_per_page;

    // Fetch paid orders from the database with pagination
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Count total orders for pagination (exclude ALL completed orders)
        $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE amount_tendered > 0 AND order_status != 'completed'");
        $count_stmt->execute();
        $total_orders = $count_stmt->fetchColumn();
        $total_pages = ceil($total_orders / $orders_per_page);
        
        // Fetch orders for current page (exclude ALL completed orders)
       $stmt = $pdo->prepare("SELECT * FROM orders WHERE amount_tendered > 0 AND order_status != 'completed' ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $orders_per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>KUSSO - Kitchen</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

    <body>   
        <div id="layoutSidenav_content">
            <main>
                <!-- Improved Form Styling -->
                <div class="container-fluid px-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h1 class="mt-4">Kitchen Orders</h1>
                        <div class="mt-4 d-flex align-items-center gap-3">
                            <span id="realTimeStatus" class="badge bg-success">
                                <i class="fas fa-circle-notch fa-spin me-1"></i> Real-time Updates Active
                            </span>
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#completedOrdersModal">
                                <i class="fas fa-check-circle me-1"></i> View Completed Orders Today
                            </button>
                        </div>
                    </div>

                    <!-- Orders Cards -->
                    <div class="row">
                        <?php if (!empty($orders)): ?>
                            <?php foreach ($orders as $order): ?>
                                <?php
                                    // Fetch the items for this order with quantities and names
                                    try {
                                            $stmt = $pdo->prepare("
                                                SELECT oi.qty, p.product_name, oi.options, oi.note 
                                            FROM order_items oi 
                                            JOIN products p ON oi.product_id = p.id 
                                            WHERE oi.order_id = ?
                                        ");
                                        $stmt->execute([$order['id']]);
                                        $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        
                                        // Get the total count of items
                                        $item_count = array_sum(array_column($orderItems, 'qty'));
                                    } catch (PDOException $e) {
                                        $orderItems = [];
                                        $item_count = 0;
                                    }
                                ?>
                                <div class="col-md-4" data-order-id="<?php echo $order['id']; ?>">
                                    <div class="card mb-4 shadow-sm">
                                        <div class="card-header" style="background-color: #c67c4e; color: #ffffff;">
                                            <strong>Order #<?php echo htmlspecialchars($order['order_number']); ?></strong>
                                        </div>
                                        <div class="card-body">
                                            <p><strong>Date:</strong> <?php echo date("M d, Y", strtotime($order['created_at'])); ?></p>
                                            <p><strong>Amount:</strong> <?php echo htmlspecialchars($order['total_amount']); ?></p>
                                            <p><strong>Order Type:</strong> <?php echo htmlspecialchars($order['order_type']); ?></p>
                                            <p><strong>Items:</strong> <span class="badge" style="background-color: #c67c4e; color: #ffffff;"><?php echo $item_count; ?></span></p>
                                            
                                            <?php if (!empty($order['note'])): ?>
                                                <div class="alert alert-warning p-2 mb-2">
                                                    <strong><i class="fas fa-sticky-note me-1"></i> Order Notes:</strong><br>
                                                    <?php echo htmlspecialchars($order['note']); ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <!-- Display order items directly without dropdown/collapse -->
                                            <?php if (!empty($orderItems)): ?>
                                                <div class="mt-3">
                                                    <p><strong>Order Details:</strong></p>
                                                    <div class="card card-body p-2 border-light">
                                                        <ul class="list-group list-group-flush">
                                                                <?php foreach ($orderItems as $item): ?>
                                                                    <li class="list-group-item d-flex justify-content-between align-items-center p-2">
                                                                        <div>
                                                                            <?php echo htmlspecialchars($item['product_name']); ?>
                                                                            <?php if (!empty($item['options'])): ?>
                                                                                <span class="badge bg-info text-dark ms-2"><?php echo htmlspecialchars($item['options']); ?></span>
                                                                            <?php endif; ?>
                                                                            <?php if (!empty($item['note'])): ?>
                                                                                <div style="font-size:0.95em;color:#7a5c2e;">Note: <?php echo htmlspecialchars($item['note']); ?></div>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                        <span class="badge bg-secondary rounded-pill"><?php echo htmlspecialchars($item['qty']); ?></span>
                                                                    </li>
                                                                <?php endforeach; ?>
                                                        </ul>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <p class="mt-3"><strong>Status:</strong> 
                                                <span class="badge <?php echo ($order['payment_status'] === 'paid' ? 'bg-success' : 'bg-warning'); ?>">
                                                    <?php echo htmlspecialchars(ucfirst($order['payment_status'])); ?>
                                                </span>
                                            </p>
                                            
                                            <!-- Mark as Done button -->
                                            <div class="d-grid gap-2 mt-3">
                                                <button class="btn mark-done-btn" style="background-color: #c67c4e; color: #ffffff;" data-order-id="<?php echo $order['id']; ?>">
                                                    <i class="fas fa-check me-1"></i> Mark as Done
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="alert alert-info text-center">No paid orders found.</div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Kitchen orders pagination">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $current_page - 1; ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo ($current_page == $i) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $current_page + 1; ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
    </main>

<!-- Completed Orders Modal -->
<div class="modal fade" id="completedOrdersModal" tabindex="-1" aria-labelledby="completedOrdersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #28a745; color: #ffffff;">
                <h5 class="modal-title" id="completedOrdersModalLabel"><i class="fas fa-check-circle me-2"></i>Completed Orders Today</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="completedOrdersContent">
                    <div class="text-center p-4">
                        <div class="spinner-border text-success" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include ('includes/footer.php'); ?>
<?php include ('includes/scripts.php'); ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JavaScript for toggling button text -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Load completed orders when modal is shown
        $('#completedOrdersModal').on('show.bs.modal', function() {
            $('#completedOrdersContent').html('<div class="text-center p-4"><div class="spinner-border text-success" role="status"><span class="visually-hidden">Loading...</span></div></div>');
            
            $.ajax({
                url: 'get_completed_orders.php',
                type: 'GET',
                success: function(response) {
                    $('#completedOrdersContent').html(response);
                },
                error: function() {
                    $('#completedOrdersContent').html('<div class="alert alert-danger">Failed to load completed orders.</div>');
                }
            });
        });

        // Get all collapse buttons
        const collapseButtons = document.querySelectorAll('[data-bs-toggle="collapse"]');
        
        // Add click event listener to each button
        collapseButtons.forEach(button => {
            // Get the target collapse element
            const targetId = button.getAttribute('data-bs-target');
            const collapseElement = document.querySelector(targetId);
            
            // Listen for bootstrap collapse events
            collapseElement.addEventListener('show.bs.collapse', function() {
                // Change button text when expanding
                button.innerHTML = '<i class="fas fa-times me-1"></i> Hide Order Details';
                button.classList.remove('btn-outline-primary');
                button.classList.add('btn-outline-secondary');
            });
            
            collapseElement.addEventListener('hide.bs.collapse', function() {
                // Change button text when collapsing
                button.innerHTML = '<i class="fas fa-list-ul me-1"></i> Show Order Details';
                button.classList.remove('btn-outline-secondary');
                button.classList.add('btn-outline-primary');
            });
        });
    });

        $(document).ready(function() {
        var totalOrders = <?php echo $total_orders; ?>;
        var ordersPerPage = <?php echo $orders_per_page; ?>;
        var currentPage = <?php echo $current_page; ?>;
        var currentOrderIds = [];
        var pollingInterval = null;
        
        // Initialize current order IDs
        $('.col-md-4[data-order-id]').each(function() {
            currentOrderIds.push($(this).data('order-id'));
        });
        
        function updatePaginationDisplay() {
            var totalPages = Math.ceil(totalOrders / ordersPerPage);
            
            // If current page exceeds total pages, redirect to last valid page
            if (currentPage > totalPages && totalPages > 0) {
                window.location.href = '?page=' + totalPages;
                return;
            }
            
            // If no orders left, reload to show empty message
            if (totalOrders === 0) {
                window.location.reload();
                return;
            }
            
            // Rebuild pagination HTML
            var paginationHtml = '<ul class="pagination justify-content-center">';
            
            // Previous button
            paginationHtml += '<li class="page-item ' + (currentPage <= 1 ? 'disabled' : '') + '">';
            paginationHtml += '<a class="page-link" href="?page=' + (currentPage - 1) + '" aria-label="Previous">';
            paginationHtml += '<span aria-hidden="true">&laquo;</span></a></li>';
            
            // Page numbers
            for (var i = 1; i <= totalPages; i++) {
                paginationHtml += '<li class="page-item ' + (currentPage == i ? 'active' : '') + '">';
                paginationHtml += '<a class="page-link" href="?page=' + i + '">' + i + '</a></li>';
            }
            
            // Next button
            paginationHtml += '<li class="page-item ' + (currentPage >= totalPages ? 'disabled' : '') + '">';
            paginationHtml += '<a class="page-link" href="?page=' + (currentPage + 1) + '" aria-label="Next">';
            paginationHtml += '<span aria-hidden="true">&raquo;</span></a></li>';
            
            paginationHtml += '</ul>';
            
            // Update or hide pagination
            if (totalPages <= 1) {
                $('nav[aria-label="Kitchen orders pagination"]').hide();
            } else {
                $('nav[aria-label="Kitchen orders pagination"]').html(paginationHtml).show();
            }
        }
        
        function updatePagination() {
            updatePaginationDisplay();
        }
        
        // Real-time polling function to check for new orders
        function pollForNewOrders() {
            $.ajax({
                url: 'get_kitchen_orders.php',
                type: 'GET',
                data: { page: currentPage },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Check if there are new orders or changes
                        var newOrderIds = response.order_ids || [];
                        var hasChanges = false;
                        
                        // Check if order count changed
                        if (totalOrders !== response.total_orders) {
                            hasChanges = true;
                            totalOrders = response.total_orders;
                        }
                        
                        // Check if order IDs differ (new orders or order removed)
                        if (JSON.stringify(currentOrderIds.sort()) !== JSON.stringify(newOrderIds.sort())) {
                            hasChanges = true;
                        }
                        
                        if (hasChanges) {
                            // Update the orders display
                            if (response.has_orders) {
                                $('.row').html(response.html);
                                currentOrderIds = newOrderIds;
                                bindMarkDone();
                                
                                // Show notification for new orders
                                if (newOrderIds.length > currentOrderIds.length || currentOrderIds.some(id => !newOrderIds.includes(id))) {
                                    showNotification('New order received!');
                                }
                            } else {
                                $('.row').html('<div class="col-12"><div class="alert alert-info text-center">No paid orders found.</div></div>');
                                currentOrderIds = [];
                            }
                            
                            // Update pagination
                            updatePaginationDisplay();
                        }
                    }
                },
                error: function() {
                    console.log('Failed to poll for new orders');
                }
            });
        }
        
        // Show notification
        function showNotification(message) {
            // Create notification element
            var notification = $('<div class="alert alert-success alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 250px;" role="alert">' +
                '<i class="fas fa-bell me-2"></i>' + message +
                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                '</div>');
            
            $('body').append(notification);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                notification.fadeOut(500, function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Play notification sound (optional)
            try {
                var audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBjGH0fPTgjMGHm7A7OWhUhELTKXh8bllHAU2jdXzzn0vBSp+zPLaizsIGGS57OmiUBEKS6Lf8rxrJAU2jdXzzn0vBSp+zPLaizsIGGS57OmiUBEKS6Lf8rxrJAU2jdXzzn0vBSp+zPLaizsIGGS57OmiUBEKS6Lf8rxrJAU2jdXzzn0vBSp+zPLaizsIGGS57OmiUBEKS6Lf8rxrJAU2jdXzzn0vBSp+zPLaizsIGGS57OmiUBEKS6Lf8rxrJAU2jdXzzn0vBSp+zPLaizsIGGS57OmiUBEKS6Lf8rxrJAU2jdXzzn0vBSp+zPLaizsIGGS57OmiUBEKS6Lf8rxrJAU2jdXzzn0vBSp+zPLaizsIGGS57OmiUBEKS6Lf8rxrJAU2jdXzzn0vBSp+zPLaizsIGGS57OmiUBEKS6Lf8rxrJAU2jdXzzn0vBSp+zPLaizsIGGS57OmiUBEKS6Lf8rxrJAU2jdXzzn0vBSp+zPLaizsIGGS57OmiUBEKS6Lf8rxrJAU2jdXzzn0vBSp+zPLaizsIGGS57OmiUBEKS6Lf8rxrJAU2jdXzzn0vBSp+zPLaizsIGGS57OmiUBEKS6Lf8rxrJAU2jdXzzn0vBSp+zPLaizsIGGS57OmiUBEKS6Lf8rxrJAU2jdXzzn0vBSp+zPLaizsIGGS57OmiUBEKS6Lf8rxrJAU2jdXzzn0vBQ==');
                audio.play();
            } catch(e) {
                // Ignore audio errors
            }
        }
        
        // Start polling every 3 seconds
        pollingInterval = setInterval(pollForNewOrders, 3000);
        
        // Stop polling when user leaves the page
        $(window).on('beforeunload', function() {
            if (pollingInterval) {
                clearInterval(pollingInterval);
            }
        });
        
        function bindMarkDone() {
            $('.mark-done-btn').off('click').on('click', function() {
                var btn = $(this);
                var orderId = btn.data('order-id');
                var card = btn.closest('.col-md-4');
                if (confirm('Mark this order as completed?')) {
                    $.ajax({
                        url: 'kitchen_mark_done.php',
                        type: 'POST',
                        data: { order_id: orderId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                // Decrement total orders count
                                totalOrders--;
                                
                                // Update pagination immediately after decrementing
                                updatePaginationDisplay();
                                
                                // Remove the card from the UI
                                card.fadeOut(500, function() { 
                                    $(this).remove();

                                    // After removal, check how many cards are left
                                    var visibleCards = $('.row > .col-md-4').length;

                                    // Only fetch next if there are more orders in DB
                                    if (visibleCards < 3 && visibleCards > 0) {
                                        var nextOffset = (currentPage - 1) * ordersPerPage + visibleCards;
                                        $.ajax({
                                            url: 'kitchen_next_order.php',
                                            type: 'POST',
                                            data: { offset: nextOffset },
                                            dataType: 'json',
                                            success: function(res) {
                                                if (res.success && res.html) {
                                                    $('.row').append(res.html);
                                                    bindMarkDone(); // Re-bind for new button
                                                } else {
                                                    // No more orders, update pagination again
                                                    updatePaginationDisplay();
                                                }
                                            }
                                        });
                                    } else if (visibleCards === 0) {
                                        // Page is empty, update pagination
                                        updatePaginationDisplay();
                                    }
                                });
                            } else {
                                alert('Failed to update order: ' + (response.error || 'Unknown error'));
                            }
                        },
                        error: function() {
                            alert('AJAX error. Please try again.');
                        }
                    });
                }
            });
        }
        bindMarkDone();
    });
</script>
</body>
</html>
cdn.jsdelivr.net
