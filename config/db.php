<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

$host = 'localhost';
$dbname = 'music_platform';
$username = 'root';
$password = 'root';
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    die('数据库连接失败：' . $e->getMessage());
}
?>
