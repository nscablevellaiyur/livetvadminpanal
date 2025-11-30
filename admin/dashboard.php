<?php
require_once __DIR__ . '/../includes/json_db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$categories = json_load('categories', []);
$live = json_load('live_tv', []);
$users = json_load('users', []);

include 'header.php';
?>

<h1>Dashboard</h1>

<ul>
    <li>Total Categories: <?php echo count($categories); ?></li>
    <li>Total Live TV Channels: <?php echo count($live); ?></li>
    <li>Total Users: <?php echo count($users); ?></li>
</ul>

<?php include 'footer.php'; ?>