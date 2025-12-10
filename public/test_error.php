<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
echo "Testing Error Display...<br>";
echo $undefined_variable; // Warning
non_existent_function(); // Fatal Error
?>
