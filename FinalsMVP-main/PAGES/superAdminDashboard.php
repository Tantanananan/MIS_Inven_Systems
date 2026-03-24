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

// --- 1. HANDLE ADD ADMIN ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_admin'])) {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $password_raw = $_POST['password'];
    $role = 'Admin'; // Strictly locked to Admin

    if (strlen($username) < 8 || strlen($username) > 16) {
        $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Error', 'Username must be 8-16 characters.', 'error'); });</script>";
    } else {
        $password = password_hash($password_raw, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO user (full_name, username, password, role, status) VALUES (?, ?, ?, ?, 1)";
        if ($stmt = $mysql->prepare($sql)) {
            $stmt->bind_param("ssss", $full_name, $username, $password, $role);
            if ($stmt->execute()) {
                $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Success!', 'New Admin account created successfully.', 'success'); });</script>";
            } else {
                $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Error', 'Username may already exist.', 'error'); });</script>";
            }
            $stmt->close();
        }
    }
}

// --- 2. HANDLE EDIT ADMIN (Optional Password Update) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_admin'])) {
    $user_id = $_POST['user_id'];
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $new_password = $_POST['password']; 

    if (strlen($username) < 8 || strlen($username) > 16) {
        $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Error', 'Username must be 8-16 characters.', 'error'); });</script>";
    } else {
        if (!empty($new_password)) {
            // Update everything including a new hashed password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql = "UPDATE user SET full_name = ?, username = ?, password = ? WHERE user_id = ? AND role = 'Admin'";
            if ($stmt = $mysql->prepare($sql)) {
                $stmt->bind_param("sssi", $full_name, $username, $hashed_password, $user_id);
                if ($stmt->execute()) {
                    $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Updated!', 'Admin details and password have been updated.', 'success'); });</script>";
                }
                $stmt->close();
            }
        } else {
            // Update only the name and username
            $sql = "UPDATE user SET full_name = ?, username = ? WHERE user_id = ? AND role = 'Admin'";
            if ($stmt = $mysql->prepare($sql)) {
                $stmt->bind_param("ssi", $full_name, $username, $user_id);
                if ($stmt->execute()) {
                    $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Updated!', 'Admin details have been updated.', 'success'); });</script>";
                }
                $stmt->close();
            }
        }
    }
}

// --- 3. HANDLE ARCHIVE ADMIN ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['archive_admin'])) {
    $user_id = $_POST['user_id'];
    $sql = "UPDATE user SET status = 0 WHERE user_id = ? AND role = 'Admin'";
    if ($stmt = $mysql->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Archived!', 'Admin account deactivated.', 'success'); });</script>";
        }
        $stmt->close();
    }
}

// --- 4. HANDLE PERMANENT DELETE ADMIN ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_admin'])) {
    $user_id = $_POST['user_id'];
    $sql = "DELETE FROM user WHERE user_id = ? AND role = 'Admin'";
    if ($stmt = $mysql->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Deleted!', 'Admin account permanently removed.', 'success'); });</script>";
        }
        $stmt->close();
    }
}

// Fetch all Active Admins
$query = "SELECT user_id, full_name, username, status FROM user WHERE role = 'Admin' ORDER BY user_id ASC";
$result = $mysql->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard - EquipTrack</title>
    
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
                            <span class="nav-link font-weight-bold text-light p-0">Master Control</span>
                        </li>
                    </ul>
                </div>
            </nav>

            <div class="container-fluid-custom">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-0 text-dark">System Administrators</h2>
                        <p class="text-muted small mb-0">Manage top-level Admin access for EquipTrack.</p>
                    </div>
                    <div>
                        <button type="button" class="btn btn-success fw-bold px-4" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                            <i class="bi me-1"></i> Add New Admin
                        </button>
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
                                            <?php if ($row['status'] == 1): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1">Archived</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-primary px-3 edit-btn" 
                                                    data-id="<?= $row['user_id'] ?>" 
                                                    data-name="<?= htmlspecialchars($row['full_name']) ?>"
                                                    data-user="<?= htmlspecialchars($row['username']) ?>">
                                                <i class="bi bi-pencil-square me-1"></i> Edit
                                            </button>
                                            
                                            <?php if ($row['status'] == 1): ?>
                                                <button class="btn btn-sm btn-outline-warning text-dark px-3 ms-1 archive-btn" 
                                                        data-id="<?= $row['user_id'] ?>" 
                                                        data-name="<?= htmlspecialchars($row['full_name']) ?>">
                                                    <i class="bi bi-archive me-1"></i> Archive
                                                </button>
                                            <?php endif; ?>

                                            <button class="btn btn-sm btn-outline-danger px-3 ms-1 delete-btn" 
                                                    data-id="<?= $row['user_id'] ?>" 
                                                    data-name="<?= htmlspecialchars($row['full_name']) ?>">
                                                <i class="bi bi-trash3 me-1"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center text-muted py-5"><i class="bi bi-shield-x fs-2 d-block mb-2 text-light-muted"></i>No Admin accounts found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php if(file_exists('../INCLUDES/footer.php')) include '../INCLUDES/footer.php'; ?>
        </div>
    </div>

<div class="modal fade" id="addAdminModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-shield-plus me-2"></i>Create Admin Account</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Full Name</label>
                        <input type="text" class="form-control" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Username</label>
                        <input type="text" class="form-control" name="username" minlength="8" maxlength="16" required>
                        <div class="form-text">Must be 8-16 characters.</div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label text-muted small fw-bold">Secure Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_admin" class="btn btn-success fw-bold px-4">Create Admin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editAdminModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Admin Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Full Name</label>
                        <input type="text" class="form-control" name="full_name" id="edit_full_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Username</label>
                        <input type="text" class="form-control" name="username" id="edit_username" minlength="8" maxlength="16" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label text-muted small fw-bold text-danger">New Password (Optional)</label>
                        <input type="password" class="form-control" name="password" placeholder="Leave blank to keep current password">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_admin" class="btn btn-primary fw-bold px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form id="archiveForm" method="POST" style="display: none;">
    <input type="hidden" name="user_id" id="archive_user_id">
    <input type="hidden" name="archive_admin" value="1">
</form>

<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="user_id" id="delete_user_id">
    <input type="hidden" name="delete_admin" value="1">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- EDIT LOGIC ---
        const editModal = new bootstrap.Modal(document.getElementById('editAdminModal'));
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('edit_user_id').value = this.getAttribute('data-id');
                document.getElementById('edit_full_name').value = this.getAttribute('data-name');
                document.getElementById('edit_username').value = this.getAttribute('data-user');
                editModal.show();
            });
        });

        // --- ARCHIVE LOGIC ---
        document.querySelectorAll('.archive-btn').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-id');
                const fullName = this.getAttribute('data-name');
                Swal.fire({
                    title: 'Deactivate Admin?',
                    html: `Are you sure you want to suspend access for <strong>${fullName}</strong>?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ffc107',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, suspend account'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('archive_user_id').value = userId;
                        document.getElementById('archiveForm').submit();
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