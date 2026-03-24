<?php
session_start();
include '../INCLUDES/database.php';
$message = "";

// Security check: Only Admin and Staff can manage equipment
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Staff' && $_SESSION['role'] !== 'Admin')) {
    header("Location: ../PAGES/login.php");
    exit();
}

// DYNAMIC ROUTING & SIDEBAR LOGIC
if ($_SESSION['role'] === 'Admin') {
    $sidebar_file = '../INCLUDES/sidebarAdmin.php';
} else {
    $sidebar_file = '../INCLUDES/sidebarStaff.php';
}

// ADD EQUIPMENT
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_equipment'])) {
    $item_name = $_POST['item_name'];
    $serial_number = $_POST['serial_Number']; 
    $status = $_POST['status'];

    $sql = "INSERT INTO items (item_name, serial_Number, status) VALUES (?, ?, ?)";
    
    if ($stmt = $mysql->prepare($sql)) {
        $stmt->bind_param("sss", $item_name, $serial_number, $status);
        if ($stmt->execute()) {
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Success!', 'Equipment Added.', 'success'); });</script>";
        } else {
            // Prints the exact database error to help you debug
            $error_msg = addslashes($stmt->error);
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Database Error', 'Failed to add: {$error_msg}', 'error'); });</script>";
        }
        $stmt->close();
    } else {
        // Prints an error if the SQL syntax fails to prepare
        $error_msg = addslashes($mysql->error);
        $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Query Error', 'SQL Error: {$error_msg}', 'error'); });</script>";
    }
}

// EDIT EQUIPMENT
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_equipment'])) {
    $item_id = $_POST['item_id'];
    $item_name = $_POST['item_name'];
    $serial_number = $_POST['serial_Number']; 
    $status = $_POST['status'];

    $sql = "UPDATE items SET item_name = ?, serial_Number = ?, status = ? WHERE item_id = ?";
    if ($stmt = $mysql->prepare($sql)) {
        $stmt->bind_param("sssi", $item_name, $serial_number, $status, $item_id);
        if ($stmt->execute()) {
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Updated!', 'Equipment has been updated.', 'success'); });</script>";
        } else {
            $error_msg = addslashes($stmt->error);
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Error', 'Failed to update: {$error_msg}', 'error'); });</script>";
        }
        $stmt->close();
    }
}

// ARCHIVE EQUIPMENT (Instead of Hard Delete)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_equipment'])) {
    $item_id = $_POST['item_id'];

    // We set the status to 'Archived' instead of deleting
    $sql = "UPDATE items SET status = 'Archived' WHERE item_id = ?";
    if ($stmt = $mysql->prepare($sql)) {
        $stmt->bind_param("i", $item_id);
        if ($stmt->execute()) {
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Archived!', 'Equipment moved to archives.', 'success'); });</script>";
        } else {
            $error_msg = addslashes($stmt->error);
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Error', 'Failed to archive: {$error_msg}', 'error'); });</script>";
        }
        $stmt->close();
    }
}

// FETCH TABLE (Make sure we hide archived items from the active list!)
$query = "SELECT item_id, item_name, serial_Number, status FROM items WHERE status != 'Archived' ORDER BY item_id DESC";
$result = $mysql->query($query); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Equipment - EquipTrack</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
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
        .table-hover tbody tr:hover { 
            background-color: #f1f3f5; 
        }
        .badge-status { 
            font-size: 0.9em; 
            padding: 0.5em 0.8em;
            min-width: 85px; 
        }
        .wrapper { 
            display: flex; 
            width: 100%; 
            min-height: 100vh; 
            position: relative; 
            overflow: hidden; 
        }
        
        /* Main Content Area */
        .content-wrapper { 
            flex-grow: 1; 
            display: flex; 
            flex-direction: column; 
            width: calc(100% - 250px);
            transition: width 0.3s ease;
        }
        
        /* Class added via JS to expand content when sidebar hides */
        .content-wrapper.expanded {
            width: calc(100% - 70px);
        }
        
        .main-header { background-color: #3a5a40; padding: 10px 20px; }

        /* Mobile Responsive adjustments */
        @media (max-width: 768px) {
            .content-wrapper {
                width: 100%; /* Always full width on mobile */
            }
            .content-wrapper.expanded {
                width: 100%;
            }
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
                            <span class="nav-link font-weight-bold text-light p-0">Manage Equipments</span>
                        </li>
                    </ul>
                </div>
            </nav>

            <div class="container-fluid-custom">
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-0 text-dark">Manage Equipment</h2>
                        <p class="text-muted small mb-0">Faculty & Admin Access Only</p>
                    </div>
                    <div>
                        <button type="button" class="btn btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#addEquipmentModal">
                            <i class="bi bi-plus-circle me-1"></i> Add New Equipment
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle">
                        <thead class="table-light ">
                            <tr>
                                <th>Item Name</th>
                                <th>Serial Number</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['item_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['serial_Number']); ?></td>
                                        <td class="text-center">
                                            <?php 
                                                $status = $row['status'];
                                                if ($status === 'Available') echo '<span class="badge bg-success badge-status">Available</span>';
                                                elseif ($status === 'Borrowed') echo '<span class="badge bg-info text-dark badge-status">Borrowed</span>';
                                                elseif ($status === 'Defective') echo '<span class="badge bg-warning text-dark badge-status">Defective</span>';
                                                elseif ($status === 'Lost') echo '<span class="badge bg-danger badge-status">Lost</span>';
                                                else echo '<span class="badge bg-secondary badge-status">Unknown</span>';
                                            ?>
                                        </td>
                                       <td class="text-center action-btns">
    <button class="btn btn-sm btn-outline-primary px-3 edit-btn"
            data-id="<?php echo htmlspecialchars($row['item_id']); ?>"
            data-name="<?php echo htmlspecialchars($row['item_name']); ?>"
            data-serial="<?php echo htmlspecialchars($row['serial_Number']); ?>"
            data-status="<?php echo htmlspecialchars($row['status']); ?>">
        <i class="bi bi-pencil-square me-1"></i> Edit
    </button>
    
    <button class="btn btn-sm btn-outline-danger px-3 ms-1 delete-btn"
            data-id="<?php echo htmlspecialchars($row['item_id']); ?>"
            data-name="<?php echo htmlspecialchars($row['item_name']); ?>">
        <i class="bi bi-trash3 me-1"></i> Delete
    </button>
</td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">No equipment found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if(file_exists('../INCLUDES/footer.php')) include '../INCLUDES/footer.php'; ?>
        </div>
    </div>

<div class="modal fade" id="addEquipmentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add Equipment</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
          <div class="modal-body">
            <div class="mb-3">
                <label class="form-label text-muted small">Item Name</label>
                <input type="text" class="form-control" name="item_name" required>
            </div>
            <div class="mb-3">
                <label class="form-label text-muted small">Serial Number</label>
                <input type="text" class="form-control" name="serial_Number" required>
            </div>
            <div class="mb-3">
                <label class="form-label text-muted small">Status</label>
                <select class="form-select" name="status">
                    <option value="Available">Available</option>
                </select>
            </div>
          </div>
          <div class="modal-footer bg-light">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="add_equipment" class="btn btn-success fw-bold">Save</button>
          </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editEquipmentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Equipment</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
          <div class="modal-body">
            <input type="hidden" name="item_id" id="edit_item_id">
            <div class="mb-3">
                <label class="form-label text-muted small">Item Name</label>
                <input type="text" class="form-control" name="item_name" id="edit_item_name" required>
            </div>
            <div class="mb-3">
                <label class="form-label text-muted small">Serial Number</label>
                <input type="text" class="form-control" name="serial_Number" id="edit_serial_Number" required>
            </div>
            <div class="mb-3">
                <label class="form-label text-muted small">Status</label>
                <select class="form-select" name="status" id="edit_status" required>
                    <option value="Available">Available</option>
                    <option value="Borrowed">Borrowed</option>
                    <option value="Defective">Defective</option>
                    <option value="Lost">Lost</option>
                </select>
            </div>
          </div>
          <div class="modal-footer bg-light">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="edit_equipment" class="btn btn-primary fw-bold">Update</button>
          </div>
      </form>
    </div>
  </div>
</div>

<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="item_id" id="delete_item_id">
    <input type="hidden" name="delete_equipment" value="1">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // Sidebar Toggle Logic
        document.getElementById('sidebarToggle').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('mainContent').classList.toggle('expanded');
        });

        // Edit Button Logic
        const editButtons = document.querySelectorAll('.edit-btn');
        const editModal = new bootstrap.Modal(document.getElementById('editEquipmentModal'));
        
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('edit_item_id').value = this.getAttribute('data-id');
                document.getElementById('edit_item_name').value = this.getAttribute('data-name');
                document.getElementById('edit_serial_Number').value = this.getAttribute('data-serial');
                document.getElementById('edit_status').value = this.getAttribute('data-status');
                editModal.show();
            });
        });

        // Delete Button Logic
        const deleteButtons = document.querySelectorAll('.delete-btn');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const itemId = this.getAttribute('data-id');
                const itemName = this.getAttribute('data-name');

                Swal.fire({
                    title: 'Are you sure?',
                    html: `You are about to delete <strong>${itemName}</strong>.<br>This cannot be undone!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('delete_item_id').value = itemId;
                        document.getElementById('deleteForm').submit();
                    }
                });
            });
        });
    });
</script>

</body>
</html>
