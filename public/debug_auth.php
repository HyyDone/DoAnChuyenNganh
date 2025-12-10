<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
$_SESSION['user_id'] = 1; // Assuming 1 exists, or just to pass !empty check
echo "Session set. User ID: " . $_SESSION['user_id'] . "<br>";
echo "Including listings.php...<br>";
// We need to fix the path because listings.php expects to be in public
// and it includes header via __DIR__
// But if we run this from public/debug_auth.php it should be fine.

// Mock REQUEST_URI to avoid notices in header
$_SERVER['REQUEST_URI'] = '/listings.php';

require_once __DIR__ . '/listings.php';
?>
