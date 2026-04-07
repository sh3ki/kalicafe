<?php
session_start();

// Set timezone to Philippines (GMT+8)
date_default_timezone_set('Asia/Manila');

include('includes/config.php');
include('includes/auth.php');

// Allow only admin
checkAccess(['admin']);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_expenses'])) {
        $expense_names = $_POST['expense_name'];
        $amounts = $_POST['amount'];
        $expense_date = $_POST['expense_date'];
        $descriptions = $_POST['description'];
        
        try {
            $conn->beginTransaction();
            $stmt = $conn->prepare("INSERT INTO expenses (expense_name, amount, expense_date, description) VALUES (?, ?, ?, ?)");
            
            $added_count = 0;
            foreach ($expense_names as $index => $expense_name) {
                if (!empty($expense_name) && !empty($amounts[$index])) {
                    $stmt->execute([
                        trim($expense_name),
                        $amounts[$index],
                        $expense_date,
                        trim($descriptions[$index] ?? '')
                    ]);
                    $added_count++;
                }
            }
            
            $conn->commit();
            $_SESSION['success_message'] = "$added_count expense(s) added successfully!";
        } catch (PDOException $e) {
            $conn->rollBack();
            $_SESSION['error_message'] = "Error adding expenses: " . $e->getMessage();
        }
        header("Location: manage_expenses.php");
        exit();
    }
    
    if (isset($_POST['update_expense'])) {
        $id = $_POST['expense_id'];
        $expense_name = $_POST['expense_name'];
        $amount = $_POST['amount'];
        $expense_date = $_POST['expense_date'];
        $description = $_POST['description'];
        
        try {
            $stmt = $conn->prepare("UPDATE expenses SET expense_name = ?, amount = ?, expense_date = ?, description = ? WHERE id = ?");
            $stmt->execute([$expense_name, $amount, $expense_date, $description, $id]);
            $_SESSION['success_message'] = "Expense updated successfully!";
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error updating expense: " . $e->getMessage();
        }
        header("Location: manage_expenses.php");
        exit();
    }
    
    if (isset($_POST['delete_expense'])) {
        $id = $_POST['expense_id'];
        
        try {
            $stmt = $conn->prepare("DELETE FROM expenses WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success_message'] = "Expense deleted successfully!";
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error deleting expense: " . $e->getMessage();
        }
        header("Location: manage_expenses.php");
        exit();
    }
}

include('includes/header.php');

// Fetch all expenses
try {
    $stmt = $conn->query("SELECT * FROM expenses ORDER BY expense_date DESC, created_at DESC");
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error fetching expenses: " . $e->getMessage();
    $expenses = [];
}

// Calculate total expenses
$total_expenses = 0;
foreach ($expenses as $expense) {
    $total_expenses += $expense['amount'];
}

// Get current date using local timezone
$current_date = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>KUSSO - Manage Expenses</title>
    <link href="css/styles.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/1.10.20/css/dataTables.bootstrap4.min.css" rel="stylesheet" crossorigin="anonymous" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/js/all.min.js" crossorigin="anonymous"></script>
    <style>
        .btn-custom {
            background-color: #c67c4e;
            color: white;
            border: none;
        }
        .btn-custom:hover {
            background-color: #a66a3e;
            color: white;
        }
        .card-header {
            background-color: #c67c4e;
            color: white;
        }
        .modal-header {
            background-color: #c67c4e;
            color: white;
        }
        .total-expenses-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="sb-nav-fixed">
    <?php include('includes/navbar.php'); ?>
    
    <!-- layoutSidenav and layoutSidenav_nav are in navbar.php -->
    <div id="layoutSidenav_content">
        <main>
            <div class="container-fluid px-4">
                <h1 class="mt-4">Manage Expenses</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Expenses</li>
                    </ol>

                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php 
                                echo $_SESSION['success_message'];
                                unset($_SESSION['success_message']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php 
                                echo $_SESSION['error_message'];
                                unset($_SESSION['error_message']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Total Expenses Card -->
                    <div class="row">
                        <div class="col-12">
                            <div class="total-expenses-card">
                                <h4>Total Expenses</h4>
                                <h2>₱<?php echo number_format($total_expenses, 2); ?></h2>
                                <p class="mb-0"><?php echo count($expenses); ?> expense records</p>
                            </div>
                        </div>
                    </div>

                    <!-- Add Multiple Expenses -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-plus-circle me-1"></i>
                            Add New Expenses
                        </div>
                        <div class="card-body">
                            <form method="POST" id="expensesForm">
                                <div class="mb-3">
                                    <label class="form-label">Date for All Expenses *</label>
                                    <input type="date" class="form-control" style="max-width: 200px;" name="expense_date" value="<?php echo $current_date; ?>" required>
                                </div>
                                
                                <div id="expenseRows">
                                    <!-- Initial row -->
                                    <div class="row expense-row mb-2">
                                        <div class="col-md-4">
                                            <input type="text" class="form-control" name="expense_name[]" placeholder="Expense name" required>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="number" step="0.01" class="form-control" name="amount[]" placeholder="Amount" required>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="text" class="form-control" name="description[]" placeholder="Description (optional)">
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-danger btn-sm remove-row" style="display: none;">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-secondary" id="addRowBtn">
                                        <i class="fas fa-plus"></i> Add Another Expense
                                    </button>
                                    <button type="submit" name="add_expenses" class="btn btn-custom">
                                        <i class="fas fa-save"></i> Save All Expenses
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Expenses List -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-table me-1"></i>
                            Expenses List
                        </div>
                        <div class="card-body">
                            <table id="expensesTable" class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Expense Name</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                        <th>Description</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($expenses as $expense): ?>
                                        <tr>
                                            <td><?php echo $expense['id']; ?></td>
                                            <td><?php echo htmlspecialchars($expense['expense_name']); ?></td>
                                            <td>₱<?php echo number_format($expense['amount'], 2); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($expense['expense_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($expense['description']); ?></td>
                                            <td><?php echo date('M d, Y H:i', strtotime($expense['created_at'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="editExpense(<?php echo htmlspecialchars(json_encode($expense)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="deleteExpense(<?php echo $expense['id']; ?>, '<?php echo htmlspecialchars($expense['expense_name']); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
            <?php include('includes/footer.php'); ?>
        </div>
    </div> <!-- Close layoutSidenav from navbar.php -->

    <!-- Edit Expense Modal -->
    <div class="modal fade" id="editExpenseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Expense</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="expense_id" id="edit_expense_id">
                        <div class="mb-3">
                            <label class="form-label">Expense Name *</label>
                            <input type="text" class="form-control" name="expense_name" id="edit_expense_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount *</label>
                            <input type="number" step="0.01" class="form-control" name="amount" id="edit_amount" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date *</label>
                            <input type="date" class="form-control" name="expense_date" id="edit_expense_date" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <input type="text" class="form-control" name="description" id="edit_description">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_expense" class="btn btn-custom">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Expense Modal -->
    <div class="modal fade" id="deleteExpenseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title text-white">Delete Expense</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="expense_id" id="delete_expense_id">
                        <p>Are you sure you want to delete this expense: <strong id="delete_expense_name"></strong>?</p>
                        <p class="text-danger">This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_expense" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.20/js/dataTables.bootstrap4.min.js"></script>
    <script src="js/scripts.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#expensesTable').DataTable({
                order: [[0, 'desc']]
            });
        });

        // Add new expense row
        document.getElementById('addRowBtn').addEventListener('click', function() {
            const rowsContainer = document.getElementById('expenseRows');
            const newRow = document.createElement('div');
            newRow.className = 'row expense-row mb-2';
            newRow.innerHTML = `
                <div class="col-md-4">
                    <input type="text" class="form-control" name="expense_name[]" placeholder="Expense name" required>
                </div>
                <div class="col-md-3">
                    <input type="number" step="0.01" class="form-control" name="amount[]" placeholder="Amount" required>
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control" name="description[]" placeholder="Description (optional)">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger btn-sm remove-row">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            rowsContainer.appendChild(newRow);
            updateRemoveButtons();
        });

        // Remove expense row
        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-row')) {
                e.target.closest('.expense-row').remove();
                updateRemoveButtons();
            }
        });

        // Show/hide remove buttons based on row count
        function updateRemoveButtons() {
            const rows = document.querySelectorAll('.expense-row');
            rows.forEach((row, index) => {
                const removeBtn = row.querySelector('.remove-row');
                if (rows.length > 1) {
                    removeBtn.style.display = 'block';
                } else {
                    removeBtn.style.display = 'none';
                }
            });
        }

        function editExpense(expense) {
            document.getElementById('edit_expense_id').value = expense.id;
            document.getElementById('edit_expense_name').value = expense.expense_name;
            document.getElementById('edit_amount').value = expense.amount;
            document.getElementById('edit_expense_date').value = expense.expense_date;
            document.getElementById('edit_description').value = expense.description || '';
            
            var modal = new bootstrap.Modal(document.getElementById('editExpenseModal'));
            modal.show();
        }

        function deleteExpense(id, name) {
            document.getElementById('delete_expense_id').value = id;
            document.getElementById('delete_expense_name').textContent = name;
            
            var modal = new bootstrap.Modal(document.getElementById('deleteExpenseModal'));
            modal.show();
        }
    </script>
</body>
</html>
