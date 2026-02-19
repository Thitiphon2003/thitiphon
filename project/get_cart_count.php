<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

$count = 0;
if (isset($_SESSION['user_id'])) {
    $result = fetchOne("SELECT COUNT(*) as count FROM cart_items WHERE user_id = ?", [$_SESSION['user_id']]);
    $count = $result ? (int)$result['count'] : 0;
}

echo json_encode(['count' => $count]);
?>