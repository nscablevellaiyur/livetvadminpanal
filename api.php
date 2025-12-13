
<?php
/* =========================================================
   FORCE OUTPUT BUFFER (FIXES HEADER ALREADY SENT ERROR)
   ========================================================= */
ob_start();

/* =========================================================
   HEADERS
   ========================================================= */
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");

/* =========================================================
   COMMON RESPONSE FUNCTION
   ========================================================= */
function sendResponse($arr) {
    // Clear any accidental output
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode(["NEMOSOFTS_APP" => [$arr]], JSON_UNESCAPED_SLASHES);
    exit;
}

/* =========================================================
   SAFE JSON LOADER
   ========================================================= */
function loadJson($file, $key = null) {
    if (!file_exists($file)) return [];
    $json = file_get_contents($file);
    if (!$json) return [];
    $data = json_decode($json, true);
    if (!is_array($data)) return [];
    return ($key && isset($data[$key]) && is_array($data[$key])) ? $data[$key] : $data;
}

/* =========================================================
   LOAD DATA FILES (MATCH YOUR JSON STRUCTURE)
   ========================================================= */
$tbl_live          = loadJson(__DIR__ . "/data/tbl_live.json", "tbl_live");
$tbl_category      = loadJson(__DIR__ . "/data/tbl_category.json", "tbl_category");
$tbl_home_sections = loadJson(__DIR__ . "/data/tbl_home_sections.json", "tbl_home_sections");
$tbl_settings      = loadJson(__DIR__ . "/data/tbl_settings.json");
$tbl_users         = loadJson(__DIR__ . "/data/tbl_users.json", "tbl_users");

/* =========================================================
   READ POST DATA
   ========================================================= */
if (empty($_POST["data"])) {
    sendResponse(["success" => "0", "MSG" => "no_data"]);
}

$decoded = base64_decode($_POST["data"], true);
if ($decoded === false) {
    sendResponse(["success" => "0", "MSG" => "invalid_base64"]);
}

$req = json_decode($decoded, true);
if (!is_array($req)) {
    sendResponse(["success" => "0", "MSG" => "invalid_json"]);
}

$helper = $req["helper_name"] ?? "";

/* =========================================================
   APP DETAILS
   ========================================================= */
if ($helper === "get_app_details") {
    sendResponse([
        "success" => "1",
        "MSG" => "success",
        "app_name" => $tbl_settings["app_name"] ?? "",
        "app_logo" => $tbl_settings["app_logo"] ?? "",
        "app_email" => $tbl_settings["app_email"] ?? "",
        "app_author" => $tbl_settings["app_author"] ?? "",
        "app_contact" => $tbl_settings["app_contact"] ?? "",
        "app_website" => $tbl_settings["app_website"] ?? "",
        "app_description" => $tbl_settings["app_description"] ?? "",
        "app_developed_by" => $tbl_settings["app_developed_by"] ?? "",
        "ad_status" => "false"
    ]);
}

/* =========================================================
   HOME API
   ========================================================= */
if ($helper === "get_home") {

    $home = [];

    foreach ($tbl_home_sections as $section) {

        $list = [];

        if (!empty($section["channel_ids"]) && is_array($section["channel_ids"])) {
            foreach ($section["channel_ids"] as $cid) {
                foreach ($tbl_live as $live) {
                    if (
                        isset($live["id"], $live["status"]) &&
                        $live["id"] == $cid &&
                        $live["status"] === "1"
                    ) {
                        $list[] = $live;
                    }
                }
            }
        }

        $home[] = [
            "type"  => $section["type"] ?? "live",
            "title" => $section["title"] ?? "",
            "list"  => $list
        ];
    }

    sendResponse([
        "success" => "1",
        "MSG" => "success",
        "home" => $home
    ]);
}

/* =========================================================
   CATEGORY LIST
   ========================================================= */
if ($helper === "cat_list") {
    sendResponse([
        "success" => "1",
        "MSG" => "success",
        "category" => $tbl_category
    ]);
}

/* =========================================================
   CATEGORY BY ID
   ========================================================= */
if ($helper === "get_cat_by") {

    $cid = $req["cat_id"] ?? "";
    $result = [];

    foreach ($tbl_live as $live) {
        if (
            isset($live["cat_id"], $live["status"]) &&
            $live["cat_id"] == $cid &&
            $live["status"] === "1"
        ) {
            $result[] = $live;
        }
    }

    sendResponse([
        "success" => "1",
        "MSG" => "success",
        "data" => $result
    ]);
}

/* =========================================================
   LIVE DETAILS
   ========================================================= */
if ($helper === "get_live_id") {

    $id = $req["post_id"] ?? "";

    foreach ($tbl_live as $live) {
        if (isset($live["id"]) && $live["id"] == $id) {
            sendResponse([
                "success" => "1",
                "MSG" => "success",
                "live_data" => [$live]
            ]);
        }
    }

    sendResponse(["success" => "0", "MSG" => "not_found"]);
}

/* =========================================================
   SEARCH
   ========================================================= */
if ($helper === "search") {

    $text = strtolower($req["search_text"] ?? "");
    $result = [];

    foreach ($tbl_live as $live) {
        if (
            isset($live["live_title"]) &&
            strpos(strtolower($live["live_title"]), $text) !== false
        ) {
            $result[] = $live;
        }
    }

    sendResponse([
        "success" => "1",
        "MSG" => "success",
        "data" => $result
    ]);
}

/* =========================================================
   LATEST
   ========================================================= */
if ($helper === "latest") {
    sendResponse([
        "success" => "1",
        "MSG" => "success",
        "data" => array_reverse($tbl_live)
    ]);
}

/* =========================================================
   MOST VIEWED
   ========================================================= */
if ($helper === "most_viewed") {

    usort($tbl_live, function ($a, $b) {
        return (int)($b["total_views"] ?? 0) <=> (int)($a["total_views"] ?? 0);
    });

    sendResponse([
        "success" => "1",
        "MSG" => "success",
        "data" => $tbl_live
    ]);
}

/* =========================================================
   LOGIN
   ========================================================= */
if ($helper === "login") {

    $email = $req["user_email"] ?? "";
    $pass  = $req["user_password"] ?? "";

    foreach ($tbl_users as $u) {
        if (
            isset($u["user_email"], $u["user_password"]) &&
            $u["user_email"] === $email &&
            $u["user_password"] === $pass
        ) {
            sendResponse([
                "success" => "1",
                "MSG" => "success",
                "user" => $u
            ]);
        }
    }

    sendResponse(["success" => "0", "MSG" => "invalid_login"]);
}

/* =========================================================
   UNKNOWN HELPER
   ========================================================= */
sendResponse(["success" => "0", "MSG" => "unknown_helper"]);
