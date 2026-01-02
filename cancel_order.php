<?php
require_once 'includes/header.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('orders.php');
}

$order_id = intval($_POST['order_id']);
$user_id = $_SESSION['user_id'];

$conn = getDBConnection();

// Get order
$stmt = $conn->prepare("
    SELECT * FROM orders 
    WHERE id = ? AND user_id = ?
");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order || !in_array($order['status'], ['pending', 'processing'])) {
    closeDBConnection($conn);
    redirect("orders.php?order_id=$order_id");
}

// Determine refund
$refund_status = 'none';
$refund_amount = 0;

if ($order['payment_status'] === 'success') {
    $refund_status = 'pending';
    $refund_amount = $order['total_amount'];
}

// Cancel order
$stmt = $conn->prepare("
    UPDATE orders 
    SET status = 'cancelled',
        cancelled_at = NOW(),
        refund_status = ?,
        refund_amount = ?
    WHERE id = ?
");
$stmt->bind_param("sdi", $refund_status, $refund_amount, $order_id);
$stmt->execute();

closeDBConnection($conn);
redirect("orders.php?order_id=$order_id");
