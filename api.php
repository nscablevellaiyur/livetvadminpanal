<?php
require_once __DIR__ . '/includes/json_db.php';

header("Content-Type: application/json; charset=utf-8");

// Base response structure
function baseResponse() {
    return [
        "API_NAME"    => "NEMOSOFTS_APP",
        "status"      => "success",
        "success"     => "1",
        "message"     => "",
        "server_time" => gmdate("Y-m-d H:i:s"),
        "data"        => []
    ];
}

$response = baseResponse();
$action = $_GET["action"] ?? "";

// Load all JSON files
$categories    = json_load("categories", []);
$live          = json_load("live_tv", []);
$sections      = json_load("sections", []);
$banners       = json_load("banners", []);
$events        = json_load("events", []);
$suggestions   = json_load("suggestions", []);
$subscriptions = json_load("subscriptions", []);
$settings      = json_load("settings", []);

// Convert stream_url → url
foreach ($live as &$c) {
    if (isset($c["stream_url"])) {
        $c["url"] = $c["stream_url"];
        unset($c["stream_url"]);
    }
}
unset($c);

switch ($action) {

    /* -------------------------- SETTINGS -------------------------- */
    case "settings":
        $response["message"] = "settings";
        $response["data"] = $settings;
        break;

    /* ------------------------- CATEGORIES ------------------------- */
    case "categories":
        $response["message"] = "category_list";
        $response["data"] = array_values(array_filter($categories, fn($c) => $c["status"] == 1));
        break;

    /* -------------------------- LIVE TV --------------------------- */
    case "live_tv":
        $catId = isset($_GET["category_id"]) ? (int)$_GET["category_id"] : 0;

        $filtered = array_values(array_filter($live, function ($c) use ($catId) {
            if ($c["status"] != 1) return false;
            if ($catId > 0 && $c["category_id"] != $catId) return false;
            return true;
        }));

        $response["message"] = "live_list";
        $response["data"] = $filtered;
        break;

    /* ------------------------- BANNERS ---------------------------- */
    case "banners":
        $response["message"] = "banners";
        $response["data"] = $banners;
        break;

    /* ------------------------- SECTIONS --------------------------- */
    case "sections":
        $response["message"] = "sections";
        $response["data"] = $sections;
        break;

    /* ------------------------- FEATURED --------------------------- */
    case "featured":
        $ids = [];

        foreach ($sections as $sec) {
            if (strtolower($sec["type"]) === "featured") {
                $ids = $sec["channel_ids"] ?? [];
                break;
            }
        }

        $response["message"] = "featured";
        $response["data"] = array_values(array_filter($live, fn($c) => in_array($c["id"], $ids)));
        break;

    /* --------------------------- LATEST --------------------------- */
    case "latest":
        $ids = [];

        foreach ($sections as $sec) {
            if (strtolower($sec["type"]) === "latest") {
                $ids = $sec["channel_ids"] ?? [];
                break;
            }
        }

        $response["message"] = "latest";
        $response["data"] = array_values(array_filter($live, fn($c) => in_array($c["id"], $ids)));
        break;

    /* --------------------------- EVENTS --------------------------- */
    case "events":
        $response["message"] = "events";
        $response["data"] = $events;
        break;

    /* ------------------------ SUGGESTIONS ------------------------- */
    case "suggestions":
        $response["message"] = "suggestions";
        $response["data"] = $suggestions;
        break;

    /* ----------------------- SUBSCRIPTIONS ------------------------ */
    case "subscriptions":
        $response["message"] = "subscriptions";
        $response["data"] = $subscriptions;
        break;

    /* ---------------------------- HOME ---------------------------- */
    case "home":
        $response["message"] = "home";
        $response["data"] = [
            "banners"  => $banners,
            "sections" => $sections
        ];
        break;

    /* ---------------------- INVALID ACTION ------------------------ */
    default:
        $response = baseResponse();
        $response["status"]  = "failed";
        $response["success"] = "0";
        $response["message"] = "invalid_action";
}

echo json_encode($response);
