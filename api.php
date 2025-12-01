<?php
header('Content-Type: application/json');

// Main JSON root key (matches BuildConfig.API_NAME)
$ROOT = "NEMOSOFTS_APP";

// Load JSON files
$categories = json_decode(file_get_contents("data/categories.json"), true);
$live_tv    = json_decode(file_get_contents("data/live_tv.json"), true);
$sections   = json_decode(file_get_contents("data/sections.json"), true);
$banners    = file_exists("data/banners.json") ? json_decode(file_get_contents("data/banners.json"), true) : [];

$action = isset($_GET['action']) ? $_GET['action'] : "";

// Response format
function response($success, $msg, $data = []) {
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
// ACTION: HOME (Sections)
//////////////////////////////////////////////////////
if ($action == "home") {
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

    // Attach banners if exist
    if (!empty($banners)) {
        $result[] = [
            "type" => "banner",
            "title" => "Banners",
            "list" => $banners
        ];
    }

    response(1, "success", $result);
}

//////////////////////////////////////////////////////
// ACTION: CATEGORY LIST
//////////////////////////////////////////////////////
if ($action == "category") {
    $active = array_filter($categories, function($cat) {
        return $cat['status'] == 1;
    });
    response(1, "success", array_values($active));
}

//////////////////////////////////////////////////////
// ACTION: LIVE TV BY CATEGORY
//////////////////////////////////////////////////////
if ($action == "live_tv") {

    if (!isset($_GET['category_id'])) {
        response(0, "category_id required");
    }

    $cid = intval($_GET['category_id']);
    $list = [];

    foreach ($live_tv as $ch) {
        if ($ch['category_id'] == $cid && $ch['status'] == 1) {
            $list[] = $ch;
        }
    }

    response(1, "success", $list);
}

//////////////////////////////////////////////////////
// ACTION: SINGLE TV
//////////////////////////////////////////////////////
if ($action == "single_tv") {

    if (!isset($_GET['id'])) {
        response(0, "id required");
    }

    $id = intval($_GET['id']);

    foreach ($live_tv as $ch) {
        if ($ch['id'] == $id) {
            response(1, "success", [$ch]);
        }
    }

    response(0, "not found");
}

//////////////////////////////////////////////////////
// ACTION: SEARCH
//////////////////////////////////////////////////////
if ($action == "search") {

    if (!isset($_GET['keyword'])) {
        response(0, "keyword required");
    }

    $key = strtolower($_GET['keyword']);
    $result = [];

    foreach ($live_tv as $ch) {
        if (strpos(strtolower($ch['name']), $key) !== false) {
            $result[] = $ch;
        }
    }

    response(1, "success", $result);
}

//////////////////////////////////////////////////////
// DEFAULT
//////////////////////////////////////////////////////
response(0, "unknown_action", []);

?>
