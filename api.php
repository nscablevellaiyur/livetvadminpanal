<?php

header('Content-Type: application/json');

$rootKey = "NEMOSOFTS_APP";   // Must match API_NAME in gradle.properties
$action = isset($_GET['action']) ? $_GET['action'] : '';

function sendResponse($success, $msg, $data = []) {
    global $rootKey;

    echo json_encode([
        $rootKey => [
            [
                "success" => $success,
                "MSG" => $msg,
                "data" => $data
            ]
        ]
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

// Load JSON files
$categories = json_decode(file_get_contents("data/categories.json"), true);
$live_tv    = json_decode(file_get_contents("data/live_tv.json"), true);
$sections   = json_decode(file_get_contents("data/sections.json"), true);


// ACTION: HOME (Featured, Latest, etc.)
if ($action == "home") {

    $homeData = [];

    foreach ($sections as $section) {

        $channels = [];

        foreach ($section['channel_ids'] as $id) {
            foreach ($live_tv as $ch) {
                if ($ch['id'] == $id && $ch['status'] == 1) {
                    $channels[] = $ch;
                }
            }
        }

        $homeData[] = [
            "type" => $section['type'],
            "title" => $section['title'],
            "list" => $channels
        ];
    }

    sendResponse(1, "success", $homeData);
}



// ACTION: CATEGORY LIST
if ($action == "category") {
    $active = array_filter($categories, fn($c) => $c['status'] == 1);
    sendResponse(1, "success", array_values($active));
}



// ACTION: LIVE TV LIST
if ($action == "live_tv") {

    if (!isset($_GET['category_id'])) {
        sendResponse(0, "category_id required");
    }

    $cat_id = (int) $_GET['category_id'];

    $filtered = [];

    foreach ($live_tv as $ch) {
        if ($ch['category_id'] == $cat_id && $ch['status'] == 1) {
            $filtered[] = $ch;
        }
    }

    sendResponse(1, "success", $filtered);
}



// ACTION: SINGLE TV DETAILS
if ($action == "single_tv") {

    if (!isset($_GET['id'])) {
        sendResponse(0, "id required");
    }

    $id = (int) $_GET['id'];

    foreach ($live_tv as $ch) {
        if ($ch['id'] == $id) {
            sendResponse(1, "success", [$ch]);
        }
    }

    sendResponse(0, "not found");
}



// ACTION: SEARCH
if ($action == "search") {

    if (!isset($_GET['keyword'])) {
        sendResponse(0, "keyword required");
    }

    $keyword = strtolower($_GET['keyword']);
    $result = [];

    foreach ($live_tv as $ch) {
        if (strpos(strtolower($ch['name']), $keyword) !== false) {
            $result[] = $ch;
        }
    }

    sendResponse(1, "success", $result);
}



// DEFAULT (Unknown action)
sendResponse(0, "unknown_action", []);

?>
