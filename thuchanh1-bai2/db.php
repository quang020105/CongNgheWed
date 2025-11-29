<?php
// db.php
// Sửa lại $dbHost, $dbName, $dbUser, $dbPass nếu khác
$dbHost = '127.0.0.1';
$dbName = 'quiz_db';
$dbUser = 'root';
$dbPass = ''; // mặc định XAMPP là rỗng, nếu bạn thay đổi thì cập nhật

$dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connect error: ' . $e->getMessage()]);
    exit;
}
