<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../config.php';

$teacher_id = $_GET['teacher_id'] ?? 0;

if ($teacher_id > 0) {
    $stmt = $pdo->prepare("DELETE FROM schedule WHERE teacher_id = ?");
    $result = $stmt->execute([$teacher_id]);
    echo json_encode(['success' => $result]);
} else {
    echo json_encode(['success' => false, 'error' => 'Неверный ID педагога']);
}
?>