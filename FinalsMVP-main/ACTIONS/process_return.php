<?php
include '../INCLUDES/database.php';
session_start();

// Check if the 'tid' (Transaction ID) was passed in the URL
if (isset($_GET['tid'])) {
    $transaction_id = $_GET['tid'];

    // 1. Find out which item was attached to this specific transaction
    $get_stmt = $mysql->prepare("SELECT item_id FROM transactions WHERE transaction_id = ?");
    $get_stmt->bind_param("i", $transaction_id); // 'i' because transaction_id is an integer
    $get_stmt->execute();
    $result = $get_stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row) {
        $item_id = $row['item_id'];

        // 2. Mark this transaction as Completed
        $update_trans = $mysql->prepare("UPDATE transactions SET transaction_status = 'Completed' WHERE transaction_id = ?");
        $update_trans->bind_param("i", $transaction_id);
        $update_trans->execute();

        // 3. Update the equipment's status back to 'Available'
        $update_item = $mysql->prepare("UPDATE items SET status = 'Available' WHERE item_id = ?");
        $update_item->bind_param("i", $item_id); // 'i' because item_id is an integer
        $update_item->execute();
    }
    
    // Redirect back to dashboard instantly
   header("Location: staffDashboard.php?status=success");
    exit();
}
?>