<?php
$DB_HOST = '127.0.0.1';
$DB_NAME = 'QuanLyNhaTro';
$DB_USER = 'root';
$DB_PASS = '1406';
$DB_CHAR = 'utf8mb4';

$dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=$DB_CHAR";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    die("DB Connection failed: " . $e->getMessage());
}
