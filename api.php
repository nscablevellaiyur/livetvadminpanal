<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");

function sendResponse($arr) {
    echo json_encode(["NEMOSOFTS_APP" => [$arr]]);
    exit;
}

# Load JSON Files
$tbl_cat          = json_decode(file_get_contents("data/tbl_category.json"), true);
$tbl_live         = json_decode(file_get_contents("data/tbl_live.json"), true);
$tbl_home_sections = json_decode(file_get_contents("data/tbl_home_sections.json"), true);
$tbl_settings     = json_decode(file_get_contents("data/tbl_settings.json"), true);
$tbl_users        = json_decode(file_get_contents("data/tbl_users.json"), true);

# Decode incoming request
if (!isset($_POST["data"])) {
    sendResponse(["success" => "0", "MSG" => "no_data"]);
}

$req = json_decode(base64_decode($_POST["data"]), true);
$helper = $req["helper_name"] ?? "";

# -------------------------------------------------------------------
# APP DETAILS / SETTINGS
# -------------------------------------------------------------------
if ($helper == "get_app_details") {
    sendResponse([
        "success" => "1",
        "MSG" => "success",
        "app_details" => $tbl_settings,
        "home_sections" => $tbl_home_sections
    ]);
}

# -------------------------------------------------------------------
# HOME PAGE API
# -------------------------------------------------------------------
if ($helper == "get_home") {

    sendResponse([
        "success" => "1",
        "MSG" => "success",
        "home_sections" => $tbl_home_sections,
        "category" => $tbl_cat,
        "live" => $tbl_live
    ]);
}

# -------------------------------------------------------------------
# CATEGORY LIST
# -------------------------------------------------------------------
if ($helper == "cat_list") {

    sendResponse([
        "success" => "1",
        "MSG" => "success",
        "category" => $tbl_cat
    ]);
}

# -------------------------------------------------------------------
# CATEGORY BY ID
# -------------------------------------------------------------------
if ($helper == "get_cat_by") {

    $cid = $req["cat_id"] ?? "";

    $result = [];
    foreach ($tbl_live as $row) {
        if ($row["cat_id"] == $cid && $row["status"] == "1") {
            $result[] = $row;
        }
    }

    sendResponse([
        "success" => "1",
        "MSG" => "success",
        "data" => $result
    ]);
}

# -------------------------------------------------------------------
# LIVE TV DETAILS
# -------------------------------------------------------------------
if ($helper == "get_live_id") {

    $id = $req["post_id"] ?? "";

    foreach ($tbl_live as $row) {
        if ($row["id"] == $id) {
            sendResponse([
                "success" => "1",
                "MSG" => "success",
                "live" => $row
            ]);
        }
    }

    sendResponse(["success" => "0", "MSG" => "not_found"]);
}

# -------------------------------------------------------------------
# SEARCH
# -------------------------------------------------------------------
if ($helper == "search") {

    $text = strtolower($req["search_text"] ?? "");
    $result = [];

    foreach ($tbl_live as $row) {
        if (strpos(strtolower($row["title"]), $text) !== false) {
            $result[] = $row;
        }
    }

    sendResponse([
        "success" => "1",
        "MSG" => "success",
        "data" => $result
    ]);
}

# -------------------------------------------------------------------
# LATEST
# -------------------------------------------------------------------
if ($helper == "latest") {
    sendResponse([
        "success" => "1",
        "MSG" => "success",
        "data" => array_reverse($tbl_live)
    ]);
}

# -------------------------------------------------------------------
# MOST VIEWED
# -------------------------------------------------------------------
if ($helper == "most_viewed") {
    usort($tbl_live, fn($a, $b) => $b["views"] <=> $a["views"]);

    sendResponse([
        "success" => "1",
        "MSG" => "success",
        "data" => $tbl_live
    ]);
}

# -------------------------------------------------------------------
# LOGIN (Dummy JSON login)
# -------------------------------------------------------------------
if ($helper == "login") {

    $email = $req["user_email"] ?? "";
    $pass  = $req["user_password"] ?? "";

    foreach ($tbl_users as $u) {
        if ($u["email"] == $email && $u["password"] == $pass) {
            sendResponse([
                "success" => "1",
                "MSG" => "success",
                "user" => $u
            ]);
        }
    }

    sendResponse(["success" => "0", "MSG" => "invalid_login"]);
}

# -------------------------------------------------------------------
# DEFAULT: UNKNOWN HELPER
# -------------------------------------------------------------------
sendResponse(["success" => "0", "MSG" => "unknown_helper"]);
?>
