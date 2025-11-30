<?php
require_once __DIR__ . '/../includes/json_db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$channels = json_load("live_tv", []);
$categories = json_load("categories", []);

if (isset($_GET["delete"])) {
    $id = $_GET["delete"];
    $channels = array_filter($channels, fn($c) => $c["id"] != $id);
    json_save("live_tv", array_values($channels));
    header("Location: manage_live.php");
    exit;
}

function catName($id, $cats) {
    foreach ($cats as $c) if ($c["id"] == $id) return $c["name"];
    return "-";
}

include 'header.php';
?>

<h1>Live TV</h1>

<a href="create_live.php">+ Add Live TV</a>
<br><br>

<table border="1" cellspacing="0" cellpadding="5">
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Category</th>
    <th>Logo</th>
    <th>URL</th>
    <th>Status</th>
    <th>Actions</th>
</tr>

<?php foreach ($channels as $ch): ?>
<tr>
    <td><?php echo $ch["id"]; ?></td>
    <td><?php echo $ch["name"]; ?></td>
    <td><?php echo catName($ch["category_id"], $categories); ?></td>
    <td>
        <?php if ($ch["logo"]): ?>
            <img src="../<?php echo $ch["logo"]; ?>" width="50">
        <?php endif; ?>
    </td>
    <td><?php echo $ch["stream_url"]; ?></td>
    <td><?php echo $ch["status"] ? "Active" : "Inactive"; ?></td>
    <td>
        <a href="create_live.php?id=<?php echo $ch["id"]; ?>">Edit</a> |
        <a href="?delete=<?php echo $ch["id"]; ?>" onclick="return confirm('Delete?')">Delete</a>
    </td>
</tr>
<?php endforeach; ?>

</table>

<?php include 'footer.php'; ?>