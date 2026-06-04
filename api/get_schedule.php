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
    $stmt = $pdo->prepare("SELECT * FROM schedule WHERE teacher_id = ? ORDER BY FIELD(day, 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье'), time");
    $stmt->execute([$teacher_id]);
    $schedule = $stmt->fetchAll();
} else {
    $schedule = [];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($schedule);
?>