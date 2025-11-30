<?php
require_once __DIR__ . '/includes/json_db.php';

header("Content-Type: application/json; charset=utf-8");

// Global API response format expected by Android app
$response = [
    "API_NAME"     => "NEMOSOFTS_APP",   // Required by app
    "status"       => "success",         // Required
    "success"      => "1",               // Required
    "message"      => "",
    "server_time"  => gmdate("Y-m-d H:i:s"),
    "data"         => []
];

$action = $_GET["action"] ?? "";

switch ($action) {

    // ----------------------------------------------------------------------
    // 1️⃣ CATEGORIES
    // ----------------------------------------------------------------------
    case "categories":

        $categories = json_load("categories", []);
        $categories = array_values(array_filter($categories, fn($c) => $c["status"] == 1));

        $response["message"] = "category_list";
        $response["data"] = $categories;

        echo json_encode($response);
        break;


    // ----------------------------------------------------------------------
    // 2️⃣ LIVE TV BY CATEGORY
    // ----------------------------------------------------------------------
    case "live_tv":

        $categoryId = isset($_GET["category_id"])
            ? (int)$_GET["category_id"]
            : 0;

        $live = json_load("live_tv", []);

        // Filter active channels
        $live = array_values(array_filter($live, function($c) use ($categoryId) {
            if ($c["status"] != 1) return false;
            if ($categoryId > 0 && $c["category_id"] != $categoryId) return false;
            return true;
        }));

        $response["message"] = "live_list";
        $response["data"] = $live;

        echo json_encode($response);
        break;


    // ----------------------------------------------------------------------
    // 3️⃣ INVALID ACTION
    // ----------------------------------------------------------------------
    default:
        $response["message"] = "invalid_action";
        $response["success"] = "0";
        $response["status"]  = "failed";
        echo json_encode($response);
}
