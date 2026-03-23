<?php
include '../INCLUDES/database.php';
session_start();

// Determine which dashboard to redirect to based on the user's role
$dashboard = ($_SESSION['role'] === 'Admin') ? 'adminDashboard.php' : 'staffDashboard.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST['student_id'];
    $item_id = $_POST['item_id'];

    // 1. Verify the item exists and check its current status
    $check_stmt = $mysql->prepare("SELECT status FROM items WHERE item_id = ?");
    $check_stmt->bind_param("i", $item_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $item = $result->fetch_assoc();

    if ($item) {
        // Check if the item is actually available
        if ($item['status'] == 'Available') {
            // 2. Insert the new active transaction
            $insert_stmt = $mysql->prepare("INSERT INTO transactions (student_id, item_id, transaction_status) VALUES (?, ?, 'Active')");
            $insert_stmt->bind_param("si", $student_id, $item_id);
            $insert_stmt->execute();

            // 3. Update the equipment's status to 'Borrowed'
            $update_stmt = $mysql->prepare("UPDATE items SET status = 'Borrowed' WHERE item_id = ?");
            $update_stmt->bind_param("i", $item_id);
            $update_stmt->execute();

            // Success! Redirect with success status
            header("Location: " . $dashboard . "?status=success");
        } else {
            // The item is Borrowed, Defective, or Lost
            header("Location: " . $dashboard . "?status=unavailable");
        }
    } else {
        // The item doesn't exist in the database at all
        header("Location: " . $dashboard . "?status=error");
    }
    
    exit();
}
?>