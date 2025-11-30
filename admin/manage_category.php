<?php
require_once __DIR__ . '/../includes/json_db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$categories = json_load('categories', []);

if (isset($_GET["delete"])) {
    $id = (int)$_GET["delete"];
    $categories = array_filter($categories, fn($c) => $c["id"] != $id);
    json_save("categories", array_values($categories));
    header("Location: manage_category.php");
    exit;
}

include 'header.php';
?>

<h1>Categories</h1>

<a href="create_category.php">+ Add Category</a>
<br><br>

<table border="1" cellspacing="0" cellpadding="5">
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Image</th>
    <th>Status</th>
    <th>Actions</th>
</tr>

<?php foreach ($categories as $c): ?>
<tr>
    <td><?php echo $c["id"]; ?></td>
    <td><?php echo $c["name"]; ?></td>
    <td>
        <?php if ($c["image"]): ?>
            <img src="../<?php echo $c["image"]; ?>" width="50">
        <?php endif; ?>
    </td>
    <td><?php echo $c["status"] ? "Active" : "Inactive"; ?></td>
    <td>
        <a href="create_category.php?id=<?php echo $c["id"]; ?>">Edit</a> |
        <a href="?delete=<?php echo $c["id"]; ?>" onclick="return confirm('Delete?')">Delete</a>
    </td>
</tr>
<?php endforeach; ?>

</table>

<?php include 'footer.php'; ?>