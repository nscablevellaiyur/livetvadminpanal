<?php
header("Content-Type: application/json");
date_default_timezone_set("Asia/Kolkata");

// Root key must match BuildConfig.API_NAME
$ROOT = "NEMOSOFTS_APP";

// Load JSON database
$live_tv  = json_decode(file_get_contents("data/live_tv.json"), true)['tbl_live'];
$category = json_decode(file_get_contents("data/category.json"), true)['tbl_category'];

// Response function
function send($success, $msg, $extra = [])
{
    global $ROOT;
    echo json_encode([
        $ROOT => array_merge([
            "success" => $success,
            "MSG"     => $msg
        ], $extra)
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

// Read POST data (Nemosofts uses Base64 encoded "data")
if (!isset($_POST['data'])) {
    send("0", "invalid_method");
}

$data = json_decode(base64_decode($_POST['data']), true);
if (!$data) {
    send("0", "invalid_json");
}

$method = $data['helper_name'] ?? "";

// ----------------------------------------------------
// APP DETAILS (Launcher needs this)
// ----------------------------------------------------
if ($method == "app_details") {

    send("1", "success", [
        "app_name" => "Online Live TV",
        "app_version" => "1",
        "user_id" => "",
        "isLogin" => false,
        "isMaintenance" => false
    ]);
}

// ----------------------------------------------------
// HOME → return ALL LIVE TV channels
// ----------------------------------------------------
if ($method == "get_home") {

    $channels = [];

    foreach ($live_tv as $ch) {
        if ($ch['status'] == "1") {
            $channels[] = $ch;
        }
    }

    send("1", "success", [
        "live_data" => $channels,
        "related"   => []
    ]);
}

// ----------------------------------------------------
// CATEGORY LIST
// ----------------------------------------------------
if ($method == "cat_list") {

    $cats = [];
    foreach ($category as $cat) {
        if ($cat['status'] == "1") {
            $cats[] = $cat;
        }
    }

    send("1", "success", [
        "category" => $cats
    ]);
}

// ----------------------------------------------------
// LIVE BY CATEGORY
// ----------------------------------------------------
if ($method == "get_cat_by") {

    $cid = $data['cat_id'] ?? "";
    if ($cid == "") send("0", "category_required");

    $list = [];
    foreach ($live_tv as $ch) {
        if ($ch['cat_id'] == $cid && $ch['status'] == "1") {
            $list[] = $ch;
        }
    }

    send("1", "success", [
        "live_data" => $list
    ]);
}

// ----------------------------------------------------
// SINGLE LIVE CHANNEL DETAILS
// ----------------------------------------------------
if ($method == "get_live_id") {

    $id = $data['post_id'] ?? "";
    if ($id == "") send("0", "id_required");

    foreach ($live_tv as $ch) {
        if ($ch['id'] == $id) {
            send("1", "success", [
                "live_data" => [$ch],
                "related" => []
            ]);
        }
    }

    send("0", "not_found");
}

// ----------------------------------------------------
// SEARCH LIVE CHANNELS
// ----------------------------------------------------
if ($method == "get_search_live") {

    $keyword = strtolower($data['search_text'] ?? "");

    $result = [];
    foreach ($live_tv as $ch) {
        if (strpos(strtolower($ch['live_title']), $keyword) !== false) {
            $result[] = $ch;
        }
    }

    send("1", "success", [
        "live_data" => $result
    ]);
}

// ----------------------------------------------------
// UNKNOWN METHOD
// ----------------------------------------------------
send("0", "invalid_method");

?>
