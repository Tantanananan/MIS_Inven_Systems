<?php
session_start();
include '../INCLUDES/database.php';
$message = "";

// Security check: ONLY Students
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Student') {
    header("Location: login.php");
    exit();
}

$sidebar_file = '../INCLUDES/sidebarStudent.php';

// 1. Get the Student's actual ID from the students table using their full name
$student_id = "";
$stmt = $mysql->prepare("SELECT student_id FROM students WHERE full_name = ?");
$stmt->bind_param("s", $_SESSION['full_name']);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
if ($res) {
    $student_id = $res['student_id'];
}
$stmt->close();

// 2. Handle Borrow Request Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_item'])) {
    $item_id = $_POST['item_id'];
    
    // Make sure they don't already have a pending request for this exact item
    $check_req = $mysql->prepare("SELECT * FROM requests WHERE student_id = ? AND item_id = ? AND request_status = 'Pending'");
    $check_req->bind_param("si", $student_id, $item_id);
    $check_req->execute();
    
    if ($check_req->get_result()->num_rows > 0) {
        $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Already Requested', 'You already have a pending request for this item. Please wait for staff approval.', 'info'); });</script>";
    } else {
        // Insert into the requests table!
        $ins = $mysql->prepare("INSERT INTO requests (student_id, item_id, request_status) VALUES (?, ?, 'Pending')");
        $ins->bind_param("si", $student_id, $item_id);
        if ($ins->execute()) {
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Request Sent!', 'Your borrow request has been forwarded to the MIS faculty.', 'success'); });</script>";
        }
    }
}

// Fetch ONLY Available Items for them to borrow
$query = "SELECT item_id, item_name, serial_Number FROM items WHERE status = 'Available' ORDER BY item_id DESC";
$result = $mysql->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Items - EquipTrack</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { background-color: #f8f9fa; margin: 0; overflow-x: hidden; font-family: 'Source Sans Pro', sans-serif; }
        .container-fluid-custom { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin: 20px; }
        .table-hover tbody tr:hover { background-color: #f1f3f5; }
        .btn-borrow { border: 1.5px solid #3a5a40; color: #3a5a40; font-weight: 600; transition: all 0.3s; }
        .btn-borrow:hover { background-color: #3a5a40; color: white; }
        
        .wrapper { display: flex; width: 100%; min-height: 100vh; position: relative; overflow: hidden; }
        .content-wrapper { flex-grow: 1; display: flex; flex-direction: column; width: calc(100% - 250px); transition: width 0.3s ease; }
        .content-wrapper.expanded { width: calc(100% - 70px); }
        .main-header { background-color: #3a5a40; padding: 10px 20px; }
        @media (max-width: 768px) { .content-wrapper, .content-wrapper.expanded { width: 100%; } }
    </style>
</head>
<body>
<?php echo $message; ?>

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
                            <span class="nav-link font-weight-bold text-light p-0">Browse Equipment</span>
                        </li>
                    </ul>
                </div>
            </nav>

            <div class="container-fluid-custom">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-0 text-dark">Available Equipment</h2>
                        <p class="text-muted small mb-0">Select an item below to request a borrow.</p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 bg-white">
                        <thead class="table-dark">
                            <tr class="text-uppercase" style="font-size: 0.85rem;">
                                <th class="ps-4 py-3">Item ID</th>
                                <th class="py-3">Equipment Name</th>
                                <th class="py-3">Serial Number</th>
                                <th class="py-3 text-center pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold">#<?php echo htmlspecialchars($row['item_id']); ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['item_name']); ?></strong></td>
                                        <td class="text-muted small"><?php echo htmlspecialchars($row['serial_Number']); ?></td>
                                        <td class="text-center pe-4">
                                            <button type="button" class="btn btn-sm btn-borrow rounded-pill px-3 request-btn"
                                                    data-id="<?php echo htmlspecialchars($row['item_id']); ?>"
                                                    data-name="<?php echo htmlspecialchars($row['item_name']); ?>">
                                                <i class="bi bi-hand-index-thumb me-1"></i> Request to Borrow
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center py-5 text-muted">No equipment is currently available.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if(file_exists('../INCLUDES/footer.php')) include '../INCLUDES/footer.php'; ?>
        </div>
    </div>

    <form id="requestForm" method="POST" style="display: none;">
        <input type="hidden" name="item_id" id="request_item_id">
        <input type="hidden" name="request_item" value="1">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const requestButtons = document.querySelectorAll('.request-btn');
            
            requestButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const itemId = this.getAttribute('data-id');
                    const itemName = this.getAttribute('data-name');

                    Swal.fire({
                        title: 'Request Item?',
                        html: `Would you like to send a borrow request for <strong>${itemName}</strong>?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#3a5a40',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, Request it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById('request_item_id').value = itemId;
                            document.getElementById('requestForm').submit();
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>