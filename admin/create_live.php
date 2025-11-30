<?php
require_once __DIR__ . '/../includes/json_db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$channels = json_load("live_tv", []);
$categories = json_load("categories", []);

$editing = false;
$data = [
    "id"=>null,
    "name"=>"",
    "category_id"=>"",
    "stream_url"=>"",
    "logo"=>"",
    "description"=>"",
    "status"=>1
];

if (isset($_GET["id"])) {
    $editing = true;
    foreach ($channels as $ch) {
        if ($ch["id"] == $_GET["id"]) $data = $ch;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"]);
    $cat = (int)$_POST["category_id"];
    $url = trim($_POST["stream_url"]);
    $desc = trim($_POST["description"]);
    $status = isset($_POST["status"]) ? 1 : 0;

    $logo = $data["logo"];
    if (!empty($_FILES["logo"]["name"])) {
        $path = "../uploads/live/";
        if (!is_dir($path)) mkdir($path,0777,true);
        $filename = time()."_".$_FILES["logo"]["name"];
        move_uploaded_file($_FILES["logo"]["tmp_name"], $path.$filename);
        $logo = "uploads/live/".$filename;
    }

    if ($editing) {
        foreach ($channels as &$c) {
            if ($c["id"] == $_POST["id"]) {
                $c["name"] = $name;
                $c["category_id"] = $cat;
                $c["stream_url"] = $url;
                $c["description"] = $desc;
                $c["status"] = $status;
                $c["logo"] = $logo;
            }
        }
    } else {
        $id = json_next_id($channels);
        $channels[] = [
            "id"=>$id,
            "name"=>$name,
            "category_id"=>$cat,
            "stream_url"=>$url,
            "description"=>$desc,
            "status"=>$status,
            "logo"=>$logo
        ];
    }

    json_save("live_tv", $channels);
    header("Location: manage_live.php");
    exit;
}

include 'header.php';
?>

<h1><?php echo $editing ? "Edit" : "Add"; ?> Live TV Channel</h1>

<form method="POST" enctype="multipart/form-data">
    <?php if ($editing): ?>
        <input type="hidden" name="id" value="<?php echo $data["id"]; ?>">
    <?php endif; ?>

    <p>Name:<br>
    <input type="text" name="name" value="<?php echo $data["name"]; ?>" required></p>

    <p>Category:<br>
    <select name="category_id" required>
        <option value="">Select Category</option>
        <?php foreach ($categories as $c): ?>
            <option value="<?php echo $c["id"]; ?>"
                <?php echo $data["category_id"]==$c["id"] ? "selected" : ""; ?>>
                <?php echo $c["name"]; ?>
            </option>
        <?php endforeach; ?>
    </select></p>

    <p>Stream URL:<br>
    <input type="text" name="stream_url" style="width:100%;" value="<?php echo $data["stream_url"]; ?>" required></p>

    <p>Logo:<br>
    <?php if ($data["logo"]): ?>
        <img src="../<?php echo $data["logo"]; ?>" width="80"><br>
    <?php endif; ?>
    <input type="file" name="logo"></p>

    <p>Description:<br>
    <textarea name="description" style="width:100%;height:80px"><?php echo $data["description"]; ?></textarea></p>

    <p>
        <label><input type="checkbox" name="status" <?php echo $data["status"]?"checked":""; ?>> Active</label>
    </p>

    <p><button type="submit">Save</button></p>
</form>

<?php include 'footer.php'; ?>