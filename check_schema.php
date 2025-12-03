<?php
require_once __DIR__ . '/config/db.php';

function getTableSchema($pdo, $tableName) {
    try {
        $stmt = $pdo->query("DESCRIBE $tableName");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return "Table $tableName does not exist or error: " . $e->getMessage();
    }
}

echo "Users Table:\n";
print_r(getTableSchema($pdo, 'users'));
echo "\nPosts Table:\n";
print_r(getTableSchema($pdo, 'posts'));
echo "\nLikes Table:\n";
print_r(getTableSchema($pdo, 'likes'));
echo "\nComments Table:\n";
print_r(getTableSchema($pdo, 'comments'));
