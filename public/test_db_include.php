<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
echo "Testing DB Include...<br>";
$path = __DIR__ . '/../config/db.php';
echo "Path: " . $path . "<br>";
if (file_exists($path)) echo "File exists.<br>";
else echo "File DOES NOT exist.<br>";

require_once $path;
echo "DB Included.<br>";
if (isset($pdo)) echo "PDO is set.<br>";
else echo "PDO is NOT set.<br>";
?>
