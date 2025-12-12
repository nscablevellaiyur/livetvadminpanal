<?php
header("Content-Type: application/json");

$ROOT = "NEMOSOFTS_APP";

// Read POST base64 data
if (!isset($_POST['data'])) {
    echo json_encode([$ROOT => [["success" => "0", "MSG" => "invalid_request"]]]);
    exit;
}

$raw = base64_decode($_POST['data']);
$request = json_decode($raw, true);

// Main parameter from app
$method = $request['helper_name'] ?? "";

$categories = json_decode(file_get_contents("data/categories.json"), true);
$live_tv    = json_decode(file_get_contents("data/live_tv.json"), true);
$sections   = json_decode(file_get_contents("data/sections.json"), true);

// Response
function sendResponse($success, $msg, $data = [])
{
    global $ROOT;
    echo json_encode([
        $ROOT => [
            [
                "success" => $success,
                "MSG" => $msg,
                "data" => $data
            ]
        ]
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

//////////////////////////////////////////////////////
// METHOD: get_home
//////////////////////////////////////////////////////
if ($method == "get_home") {

    $result = [];
    foreach ($sections as $sec) {

        $list = [];

        foreach ($sec['channel_ids'] as $id) {
            foreach ($live_tv as $ch) {
                if ($ch['id'] == $id && $ch['status'] == 1) {
                    $list[] = $ch;
                }
            }
        }

        $result[] = [
            "type" => $sec['type'],
            "title" => $sec['title'],
            "list" => $list
        ];
    }

    sendResponse("1", "success", $result);
}


//////////////////////////////////////////////////////
// METHOD: cat_list
//////////////////////////////////////////////////////
if ($method == "cat_list") {

    $active = array_values(array_filter($categories, fn ($c) => $c['status'] == 1));

    sendResponse("1", "success", $active);
}


//////////////////////////////////////////////////////
// METHOD: get_cat_by
//////////////////////////////////////////////////////
if ($method == "get_cat_by") {

    $cid = $request['cat_id'] ?? 0;
    $cid = intval($cid);

    $list = [];
    foreach ($live_tv as $ch) {
        if ($ch['category_id'] == $cid && $ch['status'] == 1) {
            $list[] = $ch;
        }
    }

    sendResponse("1", "success", $list);
}


//////////////////////////////////////////////////////
// METHOD: get_live_id
//////////////////////////////////////////////////////
if ($method == "get_live_id") {

    $id = intval($request['post_id'] ?? 0);

    foreach ($live_tv as $ch) {
        if ($ch['id'] == $id) {
            sendResponse("1", "success", [$ch]);
        }
    }

    sendResponse("0", "not_found");
}


//////////////////////////////////////////////////////
// METHOD: get_search_live
//////////////////////////////////////////////////////
if ($method == "get_search_live") {

    $key = strtolower($request['search_text'] ?? "");
    $result = [];

    foreach ($live_tv as $ch) {
        if (strpos(strtolower($ch['name']), $key) !== false) {
            $result[] = $ch;
        }
    }

    sendResponse("1", "success", $result);
}

//////////////////////////////////////////////////////
// DEFAULT
//////////////////////////////////////////////////////
sendResponse("0", "invalid_method");
?>
