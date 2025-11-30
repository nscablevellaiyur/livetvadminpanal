<?php
require_once __DIR__ . '/../includes/json_db.php';
require_once __DIR__ . '/../includes/auth.php';

$admins = json_load('admins', []);
$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    foreach ($admins as $admin) {
        if ($admin["username"] === $username && $admin["password"] === $password) {
            $_SESSION["admin"] = [
                "id" => $admin["id"],
                "username" => $admin["username"],
                "name" => $admin["name"]
            ];
            header("Location: dashboard.php");
            exit;
        }
    }

    $error = "Invalid username or password";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
</head>
<body style="font-family:Arial;background:#f0f0f0;padding:40px">
    <h1>Live TV Admin Login</h1>

    <?php if ($error): ?>
        <p style="color:red;"><?php echo $error; ?></p>
    <?php endif; ?>

    <form method="POST">
        <p>
            Username:<br>
            <input type="text" name="username" value="admin" required>
        </p>

        <p>
            Password:<br>
            <input type="password" name="password" value="admin123" required>
        </p>

        <button type="submit">Login</button>
    </form>

</body>
</html>