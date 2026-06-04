<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../config.php';

$teacher_id = $_POST['teacher_id'] ?? 0;
$day = $_POST['day'] ?? '';
$time = $_POST['time'] ?? '';
$lesson = $_POST['lesson'] ?? '';

if ($teacher_id > 0 && $day && $time && $lesson) {
    $sql = "INSERT INTO schedule (teacher_id, day, time, lesson) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$teacher_id, $day, $time, $lesson]);
    echo json_encode(['success' => $result]);
} else {
    echo json_encode(['success' => false, 'error' => 'Не хватает данных']);
}
?>