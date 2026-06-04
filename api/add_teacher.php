<?php
session_start();

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../config.php';

// Получаем данные из POST-запроса
$lastname = $_POST['lastname'] ?? '';
$firstname = $_POST['firstname'] ?? '';
$middlename = $_POST['middlename'] ?? '';
$position = $_POST['position'] ?? '';
$department = $_POST['department'] ?? '';
$experience = $_POST['experience'] ?? 0;
$category = $_POST['category'] ?? 'Без категории';
$specialty = $_POST['specialty'] ?? 'Художественное';

// Валидация
if (empty($lastname) || empty($firstname)) {
    echo json_encode(['success' => false, 'error' => 'Фамилия и имя обязательны']);
    exit;
}

// Добавляем в БД
$sql = "INSERT INTO teachers (lastname, firstname, middlename, position, department, experience, category, specialty) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $pdo->prepare($sql);
$result = $stmt->execute([$lastname, $firstname, $middlename, $position, $department, $experience, $category, $specialty]);

if ($result) {
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
} else {
    echo json_encode(['success' => false, 'error' => 'Ошибка добавления']);
}
?>