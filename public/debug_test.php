<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
echo "PHP is working.<br>";

$path = __DIR__ . '/../config/db.php';
echo "Checking db path: $path<br>";

if (file_exists($path)) {
    echo "DB file exists.<br>";
    require_once $path;
    echo "DB included explicitly.<br>";
    if (isset($pdo)) {
        echo "PDO is set.";
    } else {
        echo "PDO is NOT set.";
    }
} else {
    echo "DB file NOT found.";
}
?>
