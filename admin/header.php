<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Live TV Admin</title>
    <style>
        body { margin:0; font-family:Arial; background:#f5f5f5; }
        .topbar { background:#333; padding:10px; color:#fff; }
        .sidebar { width:200px; background:#fff; float:left; height:100vh; border-right:1px solid #ccc; }
        .sidebar a { display:block; padding:12px; text-decoration:none; color:#333; border-bottom:1px solid #eee; }
        .sidebar a:hover { background:#f0f0f0; }
        .content { margin-left:200px; padding:20px; }
    </style>
</head>
<body>

<div class="topbar">
    Logged in as: <?php echo current_admin()["name"]; ?> |
    <a href="logout.php" style="color:#fff;">Logout</a>
</div>

<div class="sidebar">
    <a href="dashboard.php">Dashboard</a>
    <a href="manage_category.php">Categories</a>
    <a href="manage_live.php">Live TV</a>
</div>

<div class="content">