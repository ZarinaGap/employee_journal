<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../config.php';

$stmt = $pdo->query("SELECT * FROM teachers ORDER BY lastname");
$teachers = $stmt->fetchAll();

header('Content-Type: application/json; charset=utf-8');
echo json_encode($teachers);
?>