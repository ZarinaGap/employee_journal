<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../config.php';

$id = $_GET['id'] ?? 0;

if ($id > 0) {
    $stmt = $pdo->prepare("DELETE FROM schedule WHERE id = ?");
    $result = $stmt->execute([$id]);
    echo json_encode(['success' => $result]);
} else {
    echo json_encode(['success' => false, 'error' => 'Неверный ID']);
}
?>