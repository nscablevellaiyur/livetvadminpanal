
<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");

/*-------------------------------------------------
  COMMON RESPONSE FUNCTION
--------------------------------------------------*/
function sendResponse($arr) {
    echo json_encode(["NEMOSOFTS_APP" => [$arr]], JSON_UNESCAPED_SLASHES);
    exit;
}

/*-------------------------------------------------
  LOAD JSON SAFELY
--------------------------------------------------*/
function loadJson($file, $key = null) {
    if (!file_exists($file)) return [];
    $data = json_decode(file_get_contents($file), true);
    if (!is_array($data)) return [];
    return ($key && isset($data[$key])) ? $data[$key] : $data;
}

/*-------------------------------------------------
  LOAD DATA FILES (MATCH YOUR STRUCTURE)
--------------------------------------------------*/
$tbl_live          = loadJson("data/tbl_live.json", "tbl_live");
$tbl_category      = loadJson("data/tbl_category.json", "tbl_category");
$tbl_home_sections = loadJson("data/tbl_home_sections.json", "tbl_home_sections");
$tbl_settings      = loadJson("data/tbl_settings.json");
$tbl_users         = loadJson("data/tbl_users.json", "tbl_users");

/*-------------------------------------------------
  READ REQUEST
--------------------------------------------------*/
if (!isset($_POST["data"])) {
    sendResponse(["success" => "0", "MSG" => "no_data"]);
}

$req = json_decode(base64_decode($_POST["data"]), true);
if (!is_array($req)) {
    sendResponse(["success" => "0", "MSG" => "invalid_request"]);
}

$helper = $req["helper_name"] ?? "";

/*-------------------------------------------------
  APP DETAILS
--------------------------------------------------*/
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

/*-------------------------------------------------
  HOME API (MOST IMPORTANT)
--------------------------------------------------*/
if ($helper === "get_home") {

    $home = [];

    foreach ($tbl_home_sections as $section) {

        $list = [];

        if (!empty($section["channel_ids"])) {
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

/*-------------------------------------------------
  CATEGORY LIST
--------------------------------------------------*/
if ($helper === "cat_list") {
    sendResponse([
        "success" => "1",
        "MSG" => "success",
        "category" => $tbl_category
    ]);
}

/*-------------------------------------------------
  CATEGORY BY ID
--------------------------------------------------*/
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

/*-------------------------------------------------
  LIVE DETAILS
--------------------------------------------------*/
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

/*-------------------------------------------------
  SEARCH
--------------------------------------------------*/
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

/*-------------------------------------------------
  LATEST
--------------------------------------------------*/
if ($helper === "latest") {
    sendResponse([
        "success" => "1",
        "MSG" => "success",
        "data" => array_reverse($tbl_live)
    ]);
}

/*-------------------------------------------------
  TRENDING / MOST VIEWED
--------------------------------------------------*/
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

/*-------------------------------------------------
  LOGIN (JSON USER FILE)
--------------------------------------------------*/
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

/*-------------------------------------------------
  DEFAULT
--------------------------------------------------*/
sendResponse(["success" => "0", "MSG" => "unknown_helper"]);
