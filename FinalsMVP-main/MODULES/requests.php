<?php
include '../INCLUDES/database.php';
session_start();

// Redirect to login if user isn't logged in
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Staff' && $_SESSION['role'] !== 'Admin')) {
    header("Location: login.php");
    exit();
}

$sidebar_file = ($_SESSION['role'] === 'Admin') ? '../INCLUDES/sidebarAdmin.php' : '../INCLUDES/sidebarStaff.php';

// --- PAGINATION LOGIC START ---
$records_per_page = 10; // Change this number to adjust rows per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Count total pending requests for pagination
$count_query = "SELECT COUNT(*) FROM requests WHERE request_status = 'Pending'";
$total_rows = $mysql->query($count_query)->fetch_row()[0];
$total_pages = ceil($total_rows / $records_per_page);
// --- PAGINATION LOGIC END ---

// Fetch Pending Requests (With LIMIT and OFFSET added)
$query = "SELECT r.request_id, r.student_id, s.full_name, i.item_name, i.item_id, r.request_date 
          FROM requests r
          JOIN students s ON r.student_id = s.student_id
          JOIN items i ON r.item_id = i.item_id
          WHERE r.request_status = 'Pending'
          ORDER BY r.request_date ASC
          LIMIT $records_per_page OFFSET $offset";

$result = $mysql->query($query);
$requests = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EquipTrack | Manage Requests</title>
    
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { background-color: #f8f9fa; margin: 0; overflow-x: hidden; font-family: 'Source Sans Pro', sans-serif; }
        .container-fluid-custom { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin: 20px; }
        .table-hover tbody tr:hover { background-color: #f1f3f5; }
        .wrapper { display: flex; width: 100%; min-height: 100vh; position: relative; overflow: hidden; }
        .content-wrapper { flex-grow: 1; display: flex; flex-direction: column; width: calc(100% - 250px); transition: width 0.3s ease; }
        .content-wrapper.expanded { width: calc(100% - 70px); }
        .main-header { background-color: #3a5a40; padding: 10px 20px; }
        
        /* Pagination custom colors */
        .pagination .page-item.active .page-link { background-color: #3a5a40; border-color: #3a5a40; color: white; }
        .pagination .page-link { color: #3a5a40; }

        @media (max-width: 768px) { .content-wrapper, .content-wrapper.expanded { width: 100%; } }
    </style>
</head>
<body>

    <div class="wrapper">
        <?php include $sidebar_file; ?>

        <div class="content-wrapper" id="mainContent">
            
            <nav class="main-header navbar navbar-expand navbar-dark border-bottom-0 shadow-sm w-100 m-0">
                <div class="container-fluid">
                    <ul class="navbar-nav align-items-center">
                        <li class="nav-item">
                            <a class="nav-link" href="#" id="sidebarToggle" role="button"><i class="fas fa-bars"></i></a>
                        </li>
                        <li class="nav-item d-none d-sm-inline-block ms-2">
                            <span class="nav-link font-weight-bold text-light p-0">Pending Requests</span>
                        </li>
                    </ul>
                </div>
            </nav>

            <div class="container-fluid-custom">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-0 text-dark">Equipment Requests</h2>
                        <p class="text-muted small mb-0">Review and approve student borrowing requests.</p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date Requested</th>
                                <th>Student No.</th>
                                <th>Student Name</th>
                                <th>Equipment</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($requests)): ?>
                                <?php foreach ($requests as $row): ?>
                                    <tr>
                                        <td class="text-muted small"><?php echo date('M d, Y h:i A', strtotime($row['request_date'])); ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['student_id']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                                        
                                        <td class="text-center">
                                            <a href="../ACTIONS/process_request.php?action=approve&id=<?php echo $row['request_id']; ?>" 
                                               class="btn btn-sm btn-success px-3 fw-bold approve-btn"
                                               data-student="<?php echo htmlspecialchars($row['full_name']); ?>"
                                               data-item="<?php echo htmlspecialchars($row['item_name']); ?>">
                                                <i class="bi bi-check-circle me-1"></i> Approve
                                            </a>
                                            
                                            <a href="../ACTIONS/process_request.php?action=reject&id=<?php echo $row['request_id']; ?>" 
                                               class="btn btn-sm btn-outline-danger px-3 ms-2 fw-bold reject-btn"
                                               data-student="<?php echo htmlspecialchars($row['full_name']); ?>"
                                               data-item="<?php echo htmlspecialchars($row['item_name']); ?>">
                                                <i class="bi bi-x-circle me-1"></i> Reject
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted">No pending requests at the moment.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                <div class="d-flex justify-content-end mt-3">
                    <nav aria-label="Requests page navigation">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                            </li>
                            
                            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
                </div>
            <?php if(file_exists('../INCLUDES/footer.php')) include '../INCLUDES/footer.php'; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar Toggle
            document.getElementById('sidebarToggle').addEventListener('click', function (e) {
                e.preventDefault();
                document.getElementById('mainContent').classList.toggle('expanded');
            });

            // --- APPROVE LOGIC ---
            const approveButtons = document.querySelectorAll('.approve-btn');
            approveButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault(); // Stop instant redirect
                    const url = this.getAttribute('href');
                    const student = this.getAttribute('data-student');
                    const item = this.getAttribute('data-item');

                    Swal.fire({
                        title: 'Approve Request?',
                        html: `Allow <strong>${student}</strong> to borrow the <strong>${item}</strong>?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#198754', // Success Green
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, Approve'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = url; // Proceed to backend
                        }
                    });
                });
            });

            // --- REJECT LOGIC ---
            const rejectButtons = document.querySelectorAll('.reject-btn');
            rejectButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault(); // Stop instant redirect
                    const url = this.getAttribute('href');
                    const student = this.getAttribute('data-student');
                    const item = this.getAttribute('data-item');

                    Swal.fire({
                        title: 'Reject Request?',
                        html: `Are you sure you want to decline the request from <strong>${student}</strong> for the <strong>${item}</strong>?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545', // Danger Red
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, Reject'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = url; // Proceed to backend
                        }
                    });
                });
            });
        });
    </script>

    <?php if (isset($_GET['status'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                <?php if ($_GET['status'] == 'approved'): ?>
                    Swal.fire('Approved!', 'The request has been approved and moved to active transactions.', 'success');
                <?php elseif ($_GET['status'] == 'rejected'): ?>
                    Swal.fire('Rejected', 'The student\'s request has been rejected.', 'info');
                <?php elseif ($_GET['status'] == 'unavailable'): ?>
                    Swal.fire('Cannot Approve', 'This item is currently out of stock or defective.', 'error');
                <?php endif; ?>
                
                // Clear the status from the URL but preserve the page number
                const url = new URL(window.location.href);
                url.searchParams.delete('status');
                window.history.replaceState(null, null, url.toString() || window.location.pathname);
            });
        </script>
    <?php endif; ?>

</body>
</html>