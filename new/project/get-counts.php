<?php
require_once 'includes/config.php';

header('Content-Type: application/json');

$response = [
    'cart_count' => 0,
    'notify_count' => 0,
    'success' => false
];

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Get cart count
    $cart_query = "SELECT SUM(quantity) as total FROM cart WHERE user_id = $user_id";
    $cart_result = $conn->query($cart_query);
    if ($cart_result && $cart_result->num_rows > 0) {
        $response['cart_count'] = (int)($cart_result->fetch_assoc()['total'] ?? 0);
    }
    
    // Get notification count
    $notify_query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = $user_id AND is_read = FALSE";
    $notify_result = $conn->query($notify_query);
    if ($notify_result && $notify_result->num_rows > 0) {
        $response['notify_count'] = (int)($notify_result->fetch_assoc()['count'] ?? 0);
    }
    
    $response['success'] = true;
}

echo json_encode($response);
$conn->close();
?>