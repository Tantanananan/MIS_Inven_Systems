<?php
session_start();
include '../INCLUDES/database.php';
$message = "";

// Security check: ONLY Super Admins can access this page!
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Super Admin') {
    header("Location: login.php");
    exit();
}

$sidebar_file = '../INCLUDES/sidebarSuperAdmin.php';

// --- 1. HANDLE RESTORE ADMIN ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['restore_admin'])) {
    $user_id = $_POST['user_id'];
    $sql = "UPDATE user SET status = 1 WHERE user_id = ? AND role = 'Admin'";
    if ($stmt = $mysql->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Restored!', 'Admin account reactivated successfully.', 'success'); });</script>";
        }
        $stmt->close();
    }
}

// --- 2. HANDLE PERMANENT DELETE ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_admin'])) {
    $user_id = $_POST['user_id'];
    $sql = "DELETE FROM user WHERE user_id = ? AND role = 'Admin'";
    if ($stmt = $mysql->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Deleted!', 'Admin account permanently erased.', 'success'); });</script>";
        }
        $stmt->close();
    }
}

// Fetch Archived Admins (status = 0)
$query = "SELECT user_id, full_name, username, status FROM user WHERE role = 'Admin' AND status = 0 ORDER BY user_id ASC";
$result = $mysql->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Admins - EquipTrack</title>
    
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
                            <span class="nav-link font-weight-bold text-light p-0">Admin Archives</span>
                        </li>
                    </ul>
                </div>
            </nav>

            <div class="container-fluid-custom">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-0 text-dark">Archived Administrators</h2>
                        <p class="text-muted small mb-0">Review suspended Admin accounts or permanently remove them.</p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle bg-white">
                        <thead class="table-light">
                            <tr class="text-uppercase" style="font-size: 0.85rem;">
                                <th class="py-3 ps-3">Full Name</th>
                                <th class="py-3">Username</th>
                                <th class="text-center py-3">Status</th>
                                <th class="text-center py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-dark"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                        <td class="text-muted"><?php echo htmlspecialchars($row['username']); ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1">Archived</span>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-success px-3 restore-btn" 
                                                    data-id="<?= $row['user_id'] ?>" 
                                                    data-name="<?= htmlspecialchars($row['full_name']) ?>">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i> Restore
                                            </button>

                                            <button class="btn btn-sm btn-outline-danger px-3 ms-1 delete-btn" 
                                                    data-id="<?= $row['user_id'] ?>" 
                                                    data-name="<?= htmlspecialchars($row['full_name']) ?>">
                                                <i class="bi bi-trash3 me-1"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center text-muted py-5"><i class="bi bi-archive fs-2 d-block mb-2 text-light-muted"></i>No archived Admin accounts found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php if(file_exists('../INCLUDES/footer.php')) include '../INCLUDES/footer.php'; ?>
        </div>
    </div>

<form id="restoreForm" method="POST" style="display: none;">
    <input type="hidden" name="user_id" id="restore_user_id">
    <input type="hidden" name="restore_admin" value="1">
</form>

<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="user_id" id="delete_user_id">
    <input type="hidden" name="delete_admin" value="1">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- RESTORE LOGIC ---
        document.querySelectorAll('.restore-btn').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-id');
                const fullName = this.getAttribute('data-name');
                Swal.fire({
                    title: 'Restore Admin?',
                    html: `Are you sure you want to reactivate access for <strong>${fullName}</strong>?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#198754',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, restore access'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('restore_user_id').value = userId;
                        document.getElementById('restoreForm').submit();
                    }
                });
            });
        });

        // --- DELETE LOGIC ---
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-id');
                const fullName = this.getAttribute('data-name');

                Swal.fire({
                    title: 'Permanent Deletion',
                    html: `You are about to permanently erase <strong>${fullName}</strong> from the database.<br>This action cannot be undone.`,
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Erase Account'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('delete_user_id').value = userId;
                        document.getElementById('deleteForm').submit();
                    }
                });
            });
        });
    });
</script>
</body>
</html>