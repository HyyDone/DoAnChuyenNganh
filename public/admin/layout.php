<?php

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Thuê Trọ</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #34495e;
            --accent: #3498db;
            --text-light: #ecf0f1;
            --text-dark: #2c3e50;
            --bg-light: #f4f6f9;
        }
        body {
            display: flex;
            min-height: 100vh;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-light);
        }
        .sidebar {
            width: 250px;
            background: var(--primary);
            color: var(--text-light);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100%;
        }
        .sidebar-header {
            padding: 20px;
            text-align: center;
            background: var(--secondary);
            font-weight: bold;
            font-size: 1.2rem;
        }
        .nav-link {
            padding: 15px 20px;
            color: var(--text-light);
            text-decoration: none;
            transition: 0.3s;
            display: flex;
            align-items: center;
        }
        .nav-link:hover, .nav-link.active {
            background: var(--accent);
        }
        .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }
        .header {
            background: white;
            padding: 15px 30px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border-left: 5px solid var(--accent);
        }
        .stat-card h3 { margin: 0; font-size: 2rem; color: var(--text-dark); }
        .stat-card p { margin: 5px 0 0; color: #7f8c8d; }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        th { background-color: #f8f9fa; }
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
            display: inline-block;
        }
        .btn-sm { font-size: 0.8rem; padding: 4px 8px; }
        .btn-primary { background: var(--accent); color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-warning { background: #f1c40f; color: #333; }
        .btn-success { background: #2ecc71; color: white; }
        
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .badge-success { background: #e8f8f5; color: #2ecc71; }
        .badge-danger { background: #fdedec; color: #e74c3c; }
        .badge-warning { background: #fef9e7; color: #f1c40f; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <i class="fa-solid fa-shield-halved"></i> Admin Panel
    </div>
    <a href="/admin/index.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'index.php') !== false ? 'active' : '' ?>">
        <i class="fa-solid fa-chart-line"></i> Dashboard
    </a>
    <a href="/admin/users.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'users.php') !== false ? 'active' : '' ?>">
        <i class="fa-solid fa-users"></i> Tài khoản
    </a>
    <a href="/admin/posts.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'posts.php') !== false ? 'active' : '' ?>">
        <i class="fa-solid fa-newspaper"></i> Bài đăng
    </a>
    <a href="/admin/reports.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'reports.php') !== false ? 'active' : '' ?>">
        <i class="fa-solid fa-flag"></i> Báo cáo
    </a>
    <a href="/index.php" class="nav-link" style="margin-top: auto; background: #c0392b;">
        <i class="fa-solid fa-right-from-bracket"></i> Thoát
    </a>
</div>

<div class="main-content">
    <div class="header">
        <h2><?php echo $pageTitle ?? 'Dashboard'; ?></h2>
        <div>Xin chào, <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></strong></div>
    </div>
