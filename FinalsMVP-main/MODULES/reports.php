<?php
include '../INCLUDES/database.php';
session_start();

// Security check
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Staff' && $_SESSION['role'] !== 'Admin')) {
    header("Location: ../PAGES/login.php");
    exit();
}

// DYNAMIC SIDEBAR LOGIC
if ($_SESSION['role'] === 'Admin') {
    $sidebar_file = '../INCLUDES/sidebarAdmin.php';
} else {
    $sidebar_file = '../INCLUDES/sidebarStaff.php';
}

// 1. Fetch Inventory Health Summary
$total_items = $mysql->query("SELECT COUNT(*) FROM items")->fetch_row()[0];
$available = $mysql->query("SELECT COUNT(*) FROM items WHERE status = 'Available'")->fetch_row()[0];
$borrowed = $mysql->query("SELECT COUNT(*) FROM items WHERE status = 'Borrowed'")->fetch_row()[0];
$defective = $mysql->query("SELECT COUNT(*) FROM items WHERE status = 'Defective'")->fetch_row()[0];

// --- SORTING LOGIC ---
$sort_filter = isset($_GET['range']) ? $_GET['range'] : 'all';
$where_clause = "";

switch ($sort_filter) {
    case 'day':
        $where_clause = "WHERE t.borrow_date >= CURDATE()";
        break;
    case 'week':
        $where_clause = "WHERE t.borrow_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $where_clause = "WHERE t.borrow_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        break;
    default:
        $where_clause = ""; // Show all
        break;
}

// --- PAGINATION LOGIC START ---
$records_per_page = 10; // Adjust this to show more/less rows per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Count total transactions matching the filter to calculate total pages
$count_query = "SELECT COUNT(*) FROM transactions t $where_clause";
$total_rows = $mysql->query($count_query)->fetch_row()[0];
$total_pages = ceil($total_rows / $records_per_page);
// --- PAGINATION LOGIC END ---

// 2. Fetch Transaction History with Filter and Pagination
$query = "SELECT t.transaction_id, t.student_id, s.full_name, i.item_name, i.item_id, t.borrow_date, t.transaction_status 
          FROM transactions t
          JOIN items i ON t.item_id = i.item_id
          JOIN students s ON t.student_id = s.student_id
          $where_clause
          ORDER BY t.borrow_date DESC
          LIMIT $records_per_page OFFSET $offset";

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
    <title>EquipTrack | Reports</title>
    
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body { background-color: #f4f6f9; margin: 0; overflow-x: hidden; font-family: 'Source Sans Pro', sans-serif; }
        .wrapper { display: flex; width: 100%; min-height: 100vh; position: relative; overflow: hidden; }
        .content-wrapper { flex-grow: 1; display: flex; flex-direction: column; width: calc(100% - 250px); transition: width 0.3s ease; }
        .content-wrapper.expanded { width: calc(100% - 70px); }
        .main-header { background-color: #3a5a40; padding: 10px 20px; }
        .pagination .page-item.active .page-link { background-color: #3a5a40; border-color: #3a5a40; color: white; }
        .pagination .page-link { color: #3a5a40; }

        @media (max-width: 768px) {
            .content-wrapper { width: 100%; }
            .content-wrapper.expanded { width: 100%; }
        }

        /* Print Specific Styling */
        @media print {
            .sidebar, .main-header, .btn-print, .filter-section, .pagination-container, footer { display: none !important; }
            .content-wrapper { margin: 0 !important; padding: 0 !important; width: 100% !important; background-color: white !important; }
            body { background-color: white !important; }
            .card { border: 1px solid #dee2e6 !important; box-shadow: none !important; margin-bottom: 20px !important;}
            .container-fluid { padding: 0 !important; }
            .badge { border: 1px solid #000 !important; color: #000 !important; background: transparent !important; }
        }
    </style>
</head>
<body>

    <div class="wrapper">
        <?php include $sidebar_file; ?>

        <div class="content-wrapper" id="mainContent">
            <nav class="main-header navbar navbar-expand navbar-dark border-bottom-0 shadow-sm w-100 m-0">
                <div class="container-fluid">
                    <ul class="navbar-nav align-items-center">
                        <li class="nav-item"><a class="nav-link" href="#" id="sidebarToggle" role="button"><i class="fas fa-bars"></i></a></li>
                        <li class="nav-item d-none d-sm-inline-block ms-2"><span class="nav-link font-weight-bold text-light p-0">System Reports</span></li>
                    </ul>
                </div>
            </nav>

            <div class="container-fluid p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold text-dark m-0">Inventory & Transaction Report</h4>
                    
                    <div class="d-flex gap-2 filter-section">
                        <form method="GET" class="d-flex gap-2">
                            <select name="range" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="all" <?php echo $sort_filter == 'all' ? 'selected' : ''; ?>>All History</option>
                                <option value="day" <?php echo $sort_filter == 'day' ? 'selected' : ''; ?>>Today</option>
                                <option value="week" <?php echo $sort_filter == 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                                <option value="month" <?php echo $sort_filter == 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
                            </select>
                        </form>

                        <button onclick="window.print()" class="btn btn-primary btn-print" style="background-color: #3a5a40; border-color: #3a5a40;">
                            <i class="bi bi-file-earmark-pdf me-2"></i> Export PDF / Print
                        </button>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm text-center p-3">
                            <h6 class="text-muted text-uppercase small fw-bold">Total Assets</h6>
                            <h3 class="mb-0 fw-bold text-dark"><?php echo $total_items; ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm text-center p-3">
                            <h6 class="text-success text-uppercase small fw-bold">Available</h6>
                            <h3 class="mb-0 fw-bold text-success"><?php echo $available; ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm text-center p-3">
                            <h6 class="text-danger text-uppercase small fw-bold">Currently Borrowed</h6>
                            <h3 class="mb-0 fw-bold text-danger"><?php echo $borrowed; ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm text-center p-3">
                            <h6 class="text-warning text-uppercase small fw-bold">Defective</h6>
                            <h3 class="mb-0 fw-bold text-warning"><?php echo $defective; ?></h3>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-5">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="mb-0 text-dark fw-bold">
                            <i class="bi bi-clock-history me-2"></i> Transaction Masterlist 
                            <span class="text-muted small fw-normal">(Showing: <?php echo ucfirst($sort_filter); ?>)</span>
                        </h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 bg-white">
                            <thead class="table-light">
                                <tr class="text-uppercase" style="font-size: 0.85rem;">
                                    <th class="ps-4 py-3">Student Name</th>
                                    <th class="py-3">Equipment</th>
                                    <th class="py-3">Date Borrowed</th>
                                    <th class="text-center py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $row): ?>
                                <tr>   
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                        <div class="small text-muted"><?php echo htmlspecialchars($row['student_id']); ?></div>
                                    </td>
                                    <td class="text-dark">
                                        <?php echo htmlspecialchars($row['item_name']) . " <span class='text-muted'>(" . htmlspecialchars($row['item_id']) . ")</span>"; ?>
                                    </td>
                                    <td class="text-muted"><?php echo date('M d, Y - h:i A', strtotime($row['borrow_date'])); ?></td>
                                    <td class="text-center">
                                        <?php if ($row['transaction_status'] === 'Active'): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded-1">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-1">Completed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($transactions)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No transaction history found for this period.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($total_pages > 1): ?>
                    <div class="card-footer bg-white border-top py-3 d-flex justify-content-end pagination-container">
                        <nav aria-label="Transaction page navigation">
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?range=<?php echo $sort_filter; ?>&page=<?php echo $page - 1; ?>">Previous</a>
                                </li>
                                
                                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?range=<?php echo $sort_filter; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?range=<?php echo $sort_filter; ?>&page=<?php echo $page + 1; ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                    </div>
            </div>
            <?php if(file_exists('../INCLUDES/footer.php')) include '../INCLUDES/footer.php'; ?>  
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('sidebarToggle').addEventListener('click', function (e) {
            e.preventDefault();
            document.getElementById('mainContent').classList.toggle('expanded');
        });
    </script> 
</body>
</html>