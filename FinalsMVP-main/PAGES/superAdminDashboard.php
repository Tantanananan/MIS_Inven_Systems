<?php
session_start();
include '../INCLUDES/database.php';
$message = "";

// Security check: ONLY Super Admins can access this page


$sidebar_file = '../INCLUDES/sidebarSuperAdmin.php';

// --- 1. HANDLE ADD ADMIN ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $role = 'Admin'; // Hardcoded to prevent creating anything else
    $password_raw = $_POST['password'];
    
    if (strlen($username) < 8 || strlen($username) > 16) {
        $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Error', 'Username must be 8-16 characters.', 'error'); });</script>";
    } else {
        $password = password_hash($password_raw, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO user (full_name, username, password, role, status) VALUES (?, ?, ?, ?, 1)";
        if ($stmt = $mysql->prepare($sql)) {
            $stmt->bind_param("ssss", $full_name, $username, $password, $role);
            if ($stmt->execute()) {
                $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Success!', 'New Admin account created.', 'success'); });</script>";
            } else {
                $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Error', 'Username may already exist.', 'error'); });</script>";
            }
            $stmt->close();
        }
    }
}

// --- 2. HANDLE EDIT ADMIN ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_user'])) {
    $user_id = $_POST['user_id'];
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $role = 'Admin'; // Hardcoded 

    $sql = "UPDATE user SET full_name = ?, username = ?, role = ? WHERE user_id = ?";
    if ($stmt = $mysql->prepare($sql)) {
        $stmt->bind_param("sssi", $full_name, $username, $role, $user_id);
        if ($stmt->execute()) {
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Updated!', 'Admin details updated successfully.', 'success'); });</script>";
        } else {
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Error', 'Failed to update Admin.', 'error'); });</script>";
        }
        $stmt->close();
    }
}

// --- 3. HANDLE ARCHIVE ADMIN ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['archive_user'])) {
    $user_id = $_POST['user_id'];
    
    $sql = "UPDATE user SET status = 0 WHERE user_id = ?";
    if ($stmt = $mysql->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Archived!', 'Admin has been moved to the archive.', 'success'); });</script>";
        } else {
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Error', 'Failed to archive Admin.', 'error'); });</script>";
        }
        $stmt->close();
    }
}

// --- 4. HANDLE DELETE ADMIN ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];

    $sql = "DELETE FROM user WHERE user_id = ?";
    if ($stmt = $mysql->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Deleted!', 'Admin has been permanently removed.', 'success'); });</script>";
        } else {
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Error', 'Failed to delete Admin.', 'error'); });</script>";
        }
        $stmt->close();
    }
}

// --- 5. FETCH ACTIVE ADMINS ONLY ---
$query = "SELECT user_id, full_name, username, role FROM user WHERE status = 1 AND role = 'Admin' ORDER BY user_id ASC";
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
        body { 
            background-color: #f8f9fa; 
            margin: 0;
            overflow-x: hidden;
            font-family: 'Source Sans Pro', sans-serif;
        }
        .container-fluid-custom { 
            background: white; 
            padding: 30px; 
            border-radius: 10px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.05); 
            margin: 20px;
        }
        .table-hover tbody tr:hover { background-color: #f1f3f5; }
        .badge-role { font-size: 0.85em; padding: 0.5em 0.8em; min-width: 80px; letter-spacing: 0.5px; }
        .action-btns .btn { margin: 0 2px; }
        
        .wrapper { display: flex; width: 100%; min-height: 100vh; position: relative; overflow: hidden; }
        .content-wrapper { flex-grow: 1; display: flex; flex-direction: column; width: calc(100% - 250px); transition: width 0.3s ease; }
        .content-wrapper.expanded { width: calc(100% - 70px); }
        .main-header { background-color: #3a5a40; padding: 10px 20px; }

        @media (max-width: 768px) {
            .content-wrapper { width: 100%; }
            .content-wrapper.expanded { width: 100%; }
        }
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
                            <span class="nav-link font-weight-bold text-light p-0">Manage System Admins</span>
                        </li>
                    </ul>
                </div>
            </nav>

            <div class="container-fluid-custom">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-0 text-dark">Administrator Accounts</h2>
                        <p class="text-muted small mb-0">Super Admin Access Only</p>
                    </div>
                    <div>
                        <button type="button" class="btn btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="bi bi-person-plus-fill me-1"></i> Add New Admin
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Full Name</th>
                                <th>Username</th>
                                <th class="text-center">Role</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-danger badge-role text-uppercase">Admin</span>
                                        </td>
                                        <td class="text-center action-btns">
                                            <button class="btn btn-primary btn-sm edit-btn" 
                                                    data-id="<?php echo htmlspecialchars($row['user_id']); ?>"
                                                    data-name="<?php echo htmlspecialchars($row['full_name']); ?>"
                                                    data-user="<?php echo htmlspecialchars($row['username']); ?>"
                                                    title="Edit">
                                                <i class="bi bi-pencil-square"></i> Edit
                                            </button>
                                            
                                            <button class="btn btn-warning btn-sm archive-btn text-dark fw-semibold" 
                                                    data-id="<?php echo htmlspecialchars($row['user_id']); ?>"
                                                    data-name="<?php echo htmlspecialchars($row['full_name']); ?>"
                                                    title="Archive Admin">
                                                <i class="bi bi-archive-fill"></i> Archive
                                            </button>

                                            <button class="btn btn-danger btn-sm delete-btn" 
                                                    data-id="<?php echo htmlspecialchars($row['user_id']); ?>" 
                                                    data-name="<?php echo htmlspecialchars($row['full_name']); ?>"
                                                    title="Delete">
                                                <i class="bi bi-trash3"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">No active admins found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if(file_exists('../INCLUDES/footer.php')) include '../INCLUDES/footer.php'; ?>
        </div>
    </div>

<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="bi bi-shield-lock-fill me-2"></i>Create Admin Account</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
          <div class="modal-body">
            <div class="mb-3">
                <label class="form-label text-muted small">Full Name</label>
                <input type="text" class="form-control" name="full_name" required>
            </div>
            <div class="mb-3">
                <label class="form-label text-muted small">Username</label>
                <input type="text" class="form-control" name="username" minlength="8" maxlength="16" placeholder="8-16 characters" required>
            </div>
            <div class="mb-3">
                <label class="form-label text-muted small">Temporary Password</label>
                <input type="password" class="form-control" name="password" required>
            </div>
            <div class="mb-3">
                <label class="form-label text-muted small">Role</label>
                <input type="text" class="form-control bg-light" value="Admin" disabled>
                <small class="text-muted mt-1 d-block">Super Admins manage Admin accounts exclusively.</small>
            </div>
          </div>
          <div class="modal-footer bg-light">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="add_user" class="btn btn-success fw-bold">Save Admin</button>
          </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Admin Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
          <div class="modal-body">
            <input type="hidden" name="user_id" id="edit_user_id">
            
            <div class="mb-3">
                <label class="form-label text-muted small">Full Name</label>
                <input type="text" class="form-control" name="full_name" id="edit_full_name" required>
            </div>
            <div class="mb-3">
                <label class="form-label text-muted small">Username</label>
                <input type="text" class="form-control" name="username" id="edit_username" required>
            </div>
            <div class="mb-3">
                <label class="form-label text-muted small">Role</label>
                <input type="text" class="form-control bg-light" value="Admin" disabled>
            </div>
          </div>
          <div class="modal-footer bg-light">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="edit_user" class="btn btn-primary fw-bold">Update Account</button>
          </div>
      </form>
    </div>
  </div>
</div>

<form id="archiveForm" method="POST" style="display: none;">
    <input type="hidden" name="user_id" id="archive_user_id">
    <input type="hidden" name="archive_user" value="1">
</form>

<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="user_id" id="delete_user_id">
    <input type="hidden" name="delete_user" value="1">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>


    document.addEventListener('DOMContentLoaded', function() {
        
        document.getElementById('sidebarToggle').addEventListener('click', function (e) {
            e.preventDefault();
            document.getElementById('mainContent').classList.toggle('expanded');
        });

        const editButtons = document.querySelectorAll('.edit-btn');
        const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
        
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('edit_user_id').value = this.getAttribute('data-id');
                document.getElementById('edit_full_name').value = this.getAttribute('data-name');
                document.getElementById('edit_username').value = this.getAttribute('data-user');
                editModal.show();
            });
        });

        const archiveButtons = document.querySelectorAll('.archive-btn');
        archiveButtons.forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-id');
                const fullName = this.getAttribute('data-name');

                Swal.fire({
                    title: 'Archive Admin?',
                    html: `Are you sure you want to archive <strong>${fullName}</strong>?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ffc107',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, archive them!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('archive_user_id').value = userId;
                        document.getElementById('archiveForm').submit();
                    }
                });
            });
        });

        const deleteButtons = document.querySelectorAll('.delete-btn');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-id');
                const fullName = this.getAttribute('data-name');

                Swal.fire({
                    title: 'Delete Admin?',
                    html: `Are you sure you want to permanently remove <strong>${fullName}</strong>?<br>This action cannot be undone.`,
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete them!'
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