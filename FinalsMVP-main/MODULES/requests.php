<?php
include '../INCLUDES/database.php';
session_start();

// Redirect to login if user isn't logged in
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Staff' && $_SESSION['role'] !== 'Admin')) {
    header("Location: ../PAGES/login.php");
    exit();
}

// Fetch Pending Requests (Updated to match your exact table columns)
$query = "SELECT r.request_id, r.student_id, s.full_name, i.item_name, i.item_id, r.request_date 
          FROM requests r
          JOIN students s ON r.student_id = s.student_id
          JOIN items i ON r.item_id = i.item_id
          WHERE r.request_status = 'Pending'
          ORDER BY r.request_date ASC";

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
        body {
            background-color: #f4f6f9;
            margin: 0;
            overflow-x: hidden;
            font-family: 'Source Sans Pro', sans-serif;
        }
        
        /* Layout Wrappers */
        .wrapper { display: flex; width: 100%; min-height: 100vh; position: relative; overflow: hidden; }
        
        /* Main Content Area */
        .content-wrapper { 
            flex-grow: 1; display: flex; flex-direction: column; width: calc(100% - 250px);
            transition: width 0.3s ease;
        }
        
        /* Class added via JS to expand content when sidebar hides */
        .content-wrapper.expanded { width: calc(100% - 70px); }
        
        .main-header { background-color: #3a5a40; padding: 10px 20px; }

        /* Mobile Responsive adjustments */
        @media (max-width: 768px) {
            .content-wrapper { width: 100%; }
            .content-wrapper.expanded { width: 100%; }
        }
    </style>
</head>
<body>

    <div class="wrapper">
        
        <?php 
            if ($_SESSION['role'] === 'Admin') {
                include '../INCLUDES/sidebarAdmin.php'; 
            } else {
                include '../INCLUDES/sidebarStaff.php'; 
            }
        ?>

        <div class="content-wrapper" id="mainContent">
            
            <nav class="main-header navbar navbar-expand navbar-dark border-bottom-0 shadow-sm w-100 m-0">
                <div class="container-fluid">
                    <ul class="navbar-nav align-items-center">
                        <li class="nav-item">
                            <a class="nav-link" href="#" id="sidebarToggle" role="button"><i class="fas fa-bars"></i></a>
                        </li>
                        <li class="nav-item d-none d-sm-inline-block ms-2">
                            <span class="nav-link font-weight-bold text-light p-0">Manage Requests</span>
                        </li>
                    </ul>
                  
                </div>
            </nav>

            <div class="container-fluid p-4">
                
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 text-dark fw-bold">
                            <i class="bi bi-inbox me-2"></i> Pending Student Requests
                        </h6>
                        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill"><?php echo count($requests); ?> Pending</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 bg-white">
                            <thead class="table-light">
                                <tr class="text-uppercase" style="font-size: 0.85rem;">
                                    <th class="ps-4 py-3">Date & Time</th>
                                    <th class="py-3">Student</th>
                                    <th class="py-3">Equipment Requested</th>
                                    <th class="text-center py-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $row): ?>
                                <tr>
                                    <td class="ps-4 text-muted small">
                                        <?php echo date('M d, Y h:i A', strtotime($row['request_date'])); ?>
                                    </td>
                                    <td class="text-dark">
                                        <div class="fw-bold"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($row['student_id']); ?></small>
                                    </td>
                                    <td class="text-dark">
                                        <?php echo htmlspecialchars($row['item_name']) . " <span class='text-muted'>(" . htmlspecialchars($row['item_id']) . ")</span>"; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <a href="ACTIONS\process_request.php?action=approve&id=<?php echo $row['request_id']; ?>" class="btn btn-sm btn-success px-3">
                                                <i class="bi bi-check-lg"></i> Approve
                                            </a>
                                            <a href="ACTIONS\process_request.php?action=reject&id=<?php echo $row['request_id']; ?>" class="btn btn-sm btn-danger px-3">
                                                <i class="bi bi-x-lg"></i> Reject
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if (empty($requests)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2 text-light-muted"></i>
                                        No pending requests at the moment.
                                    </td>
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
        document.getElementById('sidebarToggle').addEventListener('click', function () {
            document.getElementById('mainContent').classList.toggle('expanded');
        });
    </script> 

    <?php if (isset($_GET['status'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                <?php if ($_GET['status'] == 'approved'): ?>
                    Swal.fire({
                        title: 'Approved!',
                        text: 'The request has been approved and moved to active transactions.',
                        icon: 'success',
                        confirmButtonColor: '#3a5a40'
                    });
                <?php elseif ($_GET['status'] == 'rejected'): ?>
                    Swal.fire({
                        title: 'Rejected',
                        text: 'The student\'s request has been rejected.',
                        icon: 'info',
                        confirmButtonColor: '#6c757d'
                    });
                <?php elseif ($_GET['status'] == 'unavailable'): ?>
                    Swal.fire({
                        title: 'Cannot Approve',
                        text: 'This item is currently out of stock or defective.',
                        icon: 'error',
                        confirmButtonColor: '#d33'
                    });
                <?php endif; ?>
                
                window.history.replaceState(null, null, window.location.pathname);
            });
        </script>
    <?php endif; ?>

</body>
</html>