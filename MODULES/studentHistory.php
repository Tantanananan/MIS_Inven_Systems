<?php
session_start();
include '../INCLUDES/database.php';

// Security check: ONLY Students
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Student') {
    header("Location: ../PAGES/login.php");
    exit();
}

$sidebar_file = '../INCLUDES/sidebarStudent.php';

// Get the Student's actual ID
$student_id = "";
$stmt = $mysql->prepare("SELECT student_id FROM students WHERE full_name = ?");
$stmt->bind_param("s", $_SESSION['full_name']);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
if ($res) {
    $student_id = $res['student_id'];
}
$stmt->close();

// Fetch their Actual Borrowing Transactions (Active/Completed)
$trans_query = "SELECT t.borrow_date, i.item_name, t.transaction_status 
                FROM transactions t 
                JOIN items i ON t.item_id = i.item_id 
                WHERE t.student_id = ? ORDER BY t.borrow_date DESC";
$trans_stmt = $mysql->prepare($trans_query);
$trans_stmt->bind_param("s", $student_id);
$trans_stmt->execute();
$transactions = $trans_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History - EquipTrack</title>
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
                            <span class="nav-link font-weight-bold text-light p-0">Transaction History</span>
                        </li>
                    </ul>
                </div>
            </nav>

            <div class="container-fluid-custom">
                <h2 class="mb-1 text-dark">My Borrowing History</h2>
                <p class="text-muted small mb-4">A complete record of equipment you have borrowed and returned.</p>

                <div class="table-responsive mb-5">
                    <table class="table table-hover table-bordered align-middle bg-white">
                        <thead class="table-dark">
                            <tr style="font-size: 0.85rem;">
                                <th>Date Borrowed</th>
                                <th>Equipment</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($transactions && $transactions->num_rows > 0): ?>
                                <?php while ($row = $transactions->fetch_assoc()): ?>
                                    <tr>
                                        <td class="text-muted small"><?php echo date('M d, Y h:i A', strtotime($row['borrow_date'])); ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['item_name']); ?></strong></td>
                                        <td class="text-center">
                                            <?php if ($row['transaction_status'] === 'Active'): ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1">Returned</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center py-4 text-muted">You have no borrowing history.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if(file_exists('../INCLUDES/footer.php')) include '../INCLUDES/footer.php'; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>