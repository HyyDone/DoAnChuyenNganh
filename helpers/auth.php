<?php
session_start();
function is_logged_in()
{
    return !empty($_SESSION['user_id']);
}
function current_user_id()
{
    return $_SESSION['user_id'] ?? null;
}
function require_login()
{
    if (!is_logged_in()) {
        header('Location: /login.php');
        exit;
    }
}
function require_role($role)
{
    require_login();
    if (($_SESSION['role'] ?? '') !== $role && ($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo "Forbidden";
        exit;
    }
}
