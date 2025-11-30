<?php
require_once __DIR__ . '/../includes/json_db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$categories = json_load('categories', []);
$editing = false;
$data = ["id"=>null,"name"=>"","image"=>"","status"=>1];

if (isset($_GET["id"])) {
    $editing = true;
    foreach ($categories as $c) {
        if ($c["id"] == $_GET["id"]) {
            $data = $c;
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"]);
    $status = isset($_POST["status"]) ? 1 : 0;

    $image = $data["image"];
    if (!empty($_FILES["image"]["name"])) {
        $path = "../uploads/category/";
        if (!is_dir($path)) mkdir($path,0777,true);
        $filename = time()."_".$_FILES["image"]["name"];
        move_uploaded_file($_FILES["image"]["tmp_name"], $path.$filename);
        $image = "uploads/category/".$filename;
    }

    if ($editing) {
        foreach ($categories as &$c) {
            if ($c["id"] == $_POST["id"]) {
                $c["name"] = $name;
                $c["status"] = $status;
                $c["image"] = $image;
            }
        }
    } else {
        $id = json_next_id($categories);
        $categories[] = [
            "id" => $id,
            "name" => $name,
            "image" => $image,
            "status" => $status
        ];
    }

    json_save("categories", $categories);
    header("Location: manage_category.php");
    exit;
}

include 'header.php';
?>

<h1><?php echo $editing ? "Edit" : "Add"; ?> Category</h1>

<form method="POST" enctype="multipart/form-data">
    <?php if ($editing): ?>
        <input type="hidden" name="id" value="<?php echo $data["id"]; ?>">
    <?php endif; ?>

    <p>Name:<br>
    <input type="text" name="name" value="<?php echo $data["name"]; ?>" required></p>

    <p>Image:<br>
    <?php if ($data["image"]): ?>
        <img src="../<?php echo $data["image"]; ?>" width="80"><br>
    <?php endif; ?>
    <input type="file" name="image"></p>

    <p><label><input type="checkbox" name="status" <?php echo $data["status"]?"checked":""; ?>> Active</label></p>

    <p><button type="submit">Save</button></p>
</form>

<?php include 'footer.php'; ?>