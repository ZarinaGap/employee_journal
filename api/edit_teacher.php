<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../config.php';

$id = $_POST['id'] ?? 0;
$lastname = $_POST['lastname'] ?? '';
$firstname = $_POST['firstname'] ?? '';
$middlename = $_POST['middlename'] ?? '';
$position = $_POST['position'] ?? '';
$department = $_POST['department'] ?? '';
$experience = $_POST['experience'] ?? 0;
$category = $_POST['category'] ?? 'Без категории';
$specialty = $_POST['specialty'] ?? 'Художественное';

if (empty($lastname) || empty($firstname)) {
    echo json_encode(['success' => false, 'error' => 'Фамилия и имя обязательны']);
    exit;
}

$sql = "UPDATE teachers SET 
        lastname = ?, firstname = ?, middlename = ?, 
        position = ?, department = ?, experience = ?, 
        category = ?, specialty = ? 
        WHERE id = ?";
$stmt = $pdo->prepare($sql);
$result = $stmt->execute([$lastname, $firstname, $middlename, $position, $department, $experience, $category, $specialty, $id]);

if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Ошибка обновления']);
}
?>