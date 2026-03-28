<?php
include '../INCLUDES/database.php';
session_start();

// Redirect to login if user isn't logged in
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Staff' )) {
    header("Location: login.php");
    exit();
}
// 1. Fetch Live Counts for the top cards
$available_count = $mysql->query("SELECT COUNT(*) FROM items WHERE status = 'Available'")->fetch_row()[0];
$borrowed_count  = $mysql->query("SELECT COUNT(*) FROM items WHERE status = 'Borrowed'")->fetch_row()[0];
$defective_count = $mysql->query("SELECT COUNT(*) FROM items WHERE status = 'Defective'")->fetch_row()[0];

// 2. Fetch Active Transactions
$query = "SELECT t.transaction_id, t.student_id, i.item_name, i.item_id, t.borrow_date 
          FROM transactions t
          JOIN items i ON t.item_id = i.item_id
          WHERE t.transaction_status = 'Active'
          ORDER BY t.borrow_date DESC";

$result = $mysql->query($query);
$transactions = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EquipTrack | Staff Dashboard</title>
    
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            background-color: #f4f6f9;
            margin: 0;
            overflow-x: hidden;
            font-family: 'Source Sans Pro', sans-serif;
        }
        
        .wrapper { 
            display: flex; 
            width: 100%; 
            min-height: 100vh; 
            position: relative; 
            overflow: hidden; 
        }
        
        .content-wrapper { 
            flex-grow: 1; 
            display: flex; 
            flex-direction: column; 
            width: calc(100% - 250px);
            transition: width 0.3s ease;
        }
        
       .content-wrapper.expanded {
            width: calc(100% - 70px);
        }
        
        .main-header { background-color: #3a5a40; padding: 10px 20px; }

        @media (max-width: 768px) {
            .content-wrapper {
                width: 100%; 
            }
            .content-wrapper.expanded {
                width: 100%;
            }
        }
    </style>
</head>
<body>

    <div class="wrapper">
        
        <?php include '../INCLUDES/sidebarStaff.php'; ?>

        <div class="content-wrapper" id="mainContent">
            
            <nav class="main-header navbar navbar-expand navbar-dark border-bottom-0 shadow-sm w-100 m-0">
                <div class="container-fluid">
                    <ul class="navbar-nav align-items-center">
                        <li class="nav-item">
                            <a class="nav-link" href="#" id="sidebarToggle" role="button"><i class="fas fa-bars"></i></a>
                        </li>
                        <li class="nav-item d-none d-sm-inline-block ms-2">
                            <span class="nav-link font-weight-bold text-light p-0">Staff Dashboard</span>
                        </li>
                    </ul>
                   
                </div>
            </nav>

            <div class="container-fluid p-4">
                
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body d-flex align-items-center p-4">
                                <div class="flex-shrink-0 bg-success-subtle p-3 rounded-3 text-success me-3">
                                    <i class="bi bi-check-circle-fill fs-2"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 text-muted text-uppercase small fw-bold">Available Items</h6>
                                    <h2 class="mb-0 fw-bold text-dark"><?php echo $available_count; ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body d-flex align-items-center p-4">
                                <div class="flex-shrink-0 bg-danger-subtle p-3 rounded-3 text-danger me-3">
                                    <i class="bi bi-cart-x-fill fs-2"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 text-muted text-uppercase small fw-bold">Borrowed Items</h6>
                                    <h2 class="mb-0 fw-bold text-dark"><?php echo $borrowed_count; ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body d-flex align-items-center p-4">
                                <div class="flex-shrink-0 bg-warning-subtle p-3 rounded-3 text-warning me-3">
                                    <i class="bi bi-exclamation-triangle-fill fs-2"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 text-muted text-uppercase small fw-bold">Defective</h6>
                                    <h2 class="mb-0 fw-bold text-dark"><?php echo $defective_count; ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="mb-0 text-dark fw-bold">
                            <i class="bi bi-plus-circle me-2"></i> Process New Borrowing
                        </h6>
                    </div>
                    <div class="card-body">
                        <form id="borrowForm" action="../ACTIONS/process_borrow.php" method="POST" class="row g-3 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label fw-bold small text-dark">Student ID</label>
                                <input type="text" name="student_id" class="form-control" placeholder="e.g. 2024-1001" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-bold small text-dark">Equipment ID</label>
                                <input type="text" name="item_id" class="form-control" placeholder="e.g. 101" required>
                            </div>
                            <div class="col-md-2 d-grid">
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="mb-0 text-dark fw-bold">
                            <i class="bi bi-list-task me-2"></i> Currently Borrowed Equipment
                        </h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 bg-white">
                            <thead class="table-light">
                                <tr class="text-uppercase" style="font-size: 0.85rem;">
                                    <th class="ps-4 py-3">Student ID</th>
                                    <th class="py-3">Equipment</th>
                                    <th class="py-3">Borrow Date</th>
                                    <th class="text-center py-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $row): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-dark"><?php echo htmlspecialchars($row['student_id']); ?></td>
                                    
                                    <td class="text-dark">
                                        <?php echo htmlspecialchars($row['item_name']) . " <span class='text-muted'>(" . htmlspecialchars($row['item_id']) . ")</span>"; ?>
                                    </td>
                                    
                                    <td class="text-muted small ps-3">
                            <i class="bi bi-clock me-1"></i> <?php echo date('M d, Y h:i A', strtotime($row['borrow_date'])); ?>
                        </td>
                                    <td class="text-center">
                                      <a href="../ACTIONS/process_return.php?tid=<?php echo $row['transaction_id']; ?>" class="btn btn-sm btn-outline-primary border-1 px-3 return-btn">One-Click Return</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if (empty($transactions)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No active borrowing transactions found.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <?php include '../INCLUDES/footer.php'; ?>

        </div> 
    </div> 
        
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Sidebar Toggle
        document.getElementById('sidebarToggle').addEventListener('click', function () {
            document.getElementById('mainContent').classList.toggle('expanded');
        });

      
    </script> 

  <?php if (isset($_GET['status'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                <?php if ($_GET['status'] == 'success'): ?>
                    Swal.fire({
                        title: 'Success!',
                        text: 'Equipment successfully borrowed.',
                        icon: 'success',
                        confirmButtonColor: '#3a5a40'
                    });
                <?php elseif ($_GET['status'] == 'unavailable'): ?>
                    Swal.fire({
                        title: 'Item Not Available',
                        text: 'This equipment is currently Borrowed, Defective, or Lost.',
                        icon: 'warning',
                        confirmButtonColor: '#f39c12'
                    });
                <?php elseif ($_GET['status'] == 'error'): ?>
                    Swal.fire({
                        title: 'Notice',
                        text: 'Item does not exist in the database.',
                        icon: 'error',
                        confirmButtonColor: '#d33'
                    });
                <?php elseif ($_GET['status'] == 'returned'): ?>
                    Swal.fire({
                        title: 'Returned!',
                        text: 'Equipment has been returned successfully.',
                        icon: 'success',
                        confirmButtonColor: '#3a5a40'
                    });
                <?php endif; ?>
                
                window.history.replaceState(null, null, window.location.pathname);
            });

            // --- SWEETALERT CONFIRMATIONS ---

    // 1. Borrowing Confirmation
    const borrowForm = document.getElementById('borrowForm');
    if (borrowForm) {
        borrowForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Stop the form from submitting instantly
            
            const studentId = this.querySelector('input[name="student_id"]').value;
            const itemId = this.querySelector('input[name="item_id"]').value;

            Swal.fire({
                title: 'Process Borrowing?',
                html: `Assign Equipment ID <strong>${itemId}</strong> to Student <strong>${studentId}</strong>?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3a5a40',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Process it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    borrowForm.submit(); // Submit the form to the backend
                }
            });
        });
    }

    // 2. Return Confirmation
    const returnButtons = document.querySelectorAll('.return-btn');
    returnButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault(); // Stop the link from redirecting instantly
            const url = this.getAttribute('href');

            Swal.fire({
                title: 'Confirm Return',
                text: 'Has this equipment been returned in good condition?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3a5a40',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Return it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url; // Proceed to the backend
                }
            });
        });
    });
        </script>
    <?php endif; ?>

</body>
</html>