<?php
// Simulate logged in user
session_start();
$_SESSION['user_id'] = 1;

// Define simulated paths/environment if needed
// Run listings.php
require_once __DIR__ . '/listings.php';
