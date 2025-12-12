<?php
header("Content-Type: application/json");
date_default_timezone_set("Asia/Kolkata");

$ROOT = "NEMOSOFTS_APP";

// Load correct JSON files
$live_tv  = json_decode(file_get_contents("data/tbl_live.json"), true)['tbl_live'];
$category = json_decode(file_get_contents("data/tbl_category.json"), true)['tbl_category'];

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

if (!isset($_POST['data'])) {
    send("0", "invalid_method");
}

$data = json_decode(base64_decode($_POST['data']), true);
if (!$data) send("0", "invalid_json");

$method = $data['helper_name'] ?? "";

// APP DETAILS
if ($method == "app_details") {
    send("1", "success", [
        "app_name" => "Online Live TV",
        "app_version" => "1",
        "isLogin" => false,
        "isMaintenance" => false
    ]);
}

// HOME → return ALL channels
if ($method == "get_home") {

    $list = [];
    foreach ($live_tv as $ch) {
        if ($ch["status"] == "1") {
            $list[] = $ch;
        }
    }

    send("1", "success", [
        "live_data" => $list,
        "related" => []
    ]);
}

// CATEGORY LIST
if ($method == "cat_list") {

    $cats = [];
    foreach ($category as $cat) {
        if ($cat["status"] == "1") {
            $cats[] = $cat;
        }
    }

    send("1", "success", [
        "category" => $cats
    ]);
}

// BY CATEGORY
if ($method == "get_cat_by") {

    $cid = $data['cat_id'] ?? "";
    if ($cid == "") send("0", "category_required");

    $list = [];
    foreach ($live_tv as $ch) {
        if ($ch['cat_id'] == $cid && $ch['status'] == "1") {
            $list[] = $ch;
        }
    }

    send("1", "success", ["live_data" => $list]);
}

// SINGLE LIVE
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

// SEARCH
if ($method == "get_search_live") {

    $key = strtolower($data['search_text'] ?? "");

    $result = [];
    foreach ($live_tv as $ch) {
        if (strpos(strtolower($ch['live_title']), $key) !== false) {
            $result[] = $ch;
        }
    }

    send("1", "success", ["live_data" => $result]);
}

send("0", "invalid_method");
?>
