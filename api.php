<?php
require_once __DIR__ . '/includes/json_db.php';

header("Content-Type: application/json; charset=utf-8");

$action = $_GET["action"] ?? "";

$response = [
    "success" => "0",
    "message" => "error",
    "data" => []
];

switch ($action) {

    case "categories":
        $categories = json_load("categories", []);
        $categories = array_values(array_filter($categories, fn($c) => $c["status"] == 1));

        $response["success"] = "1";
        $response["message"] = "category_list";
        $response["data"] = $categories;

        echo json_encode($response);
        break;

    case "live_tv":
        $categoryId = isset($_GET["category_id"]) ? (int)$_GET["category_id"] : 0;
        $channels = json_load("live_tv", []);

        $channels = array_values(array_filter($channels, function ($c) use ($categoryId) {
            if ($c["status"] != 1) return false;
            if ($categoryId > 0 && $c["category_id"] != $categoryId) return false;
            return true;
        }));

        $response["success"] = "1";
        $response["message"] = "live_list";
        $response["data"] = $channels;

        echo json_encode($response);
        break;

    default:
        $response["message"] = "invalid_action";
        echo json_encode($response);
}
