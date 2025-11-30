<?php
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

define('ROOT', "NEMOSOFTS_APP");
define('DATA_DIR', __DIR__ . '/data');

function load($file, $default=[]) {
    $path = DATA_DIR . '/' . $file;
    if (!file_exists($path)) return $default;
    $x = json_decode(file_get_contents($path), true);
    return $x ?: $default;
}

function output($array) {
    echo json_encode([ ROOT => [$array] ], JSON_UNESCAPED_SLASHES);
    exit;
}

/* Load JSON Data */
$settings   = load("settings.json", []);
$categories = load("categories.json", []);
$live       = load("live_tv.json", []);
$sections   = load("sections.json", []);
$banners    = load("banners.json", []);
$events     = load("events.json", []);

$action = $_REQUEST["action"] ?? "";

/* ---------------- NEMOSOFT FORMAT HELPERS ---------------- */

function ok($payload) {
    $base = [
        "success"       => "1",
        "verifyStatus"  => "1",
        "MSG"           => "",
    ];
    output(array_merge($base, $payload));
}

function error($msg) {
    $base = [
        "success"       => "0",
        "verifyStatus"  => "-1",
        "MSG"           => $msg,
    ];
    output($base);
}

/* ---------------- APP DETAILS ---------------- */

if ($action == "app_details") {

    if (!$settings) error("settings_not_found");

    ok($settings);
}

/* ---------------- CATEGORY LIST ---------------- */

if ($action == "cat_list") {

    ok([
        "category" => array_values(
            array_filter($categories, fn($c)=>intval($c["status"] ?? 1)==1 )
        )
    ]);
}

/* ---------------- LATEST ---------------- */

if ($action == "get_latest") {

    $latest = array_values(
        array_filter($live, fn($ch)=>intval($ch["status"] ?? 1)==1)
    );

    usort($latest, fn($a,$b)=>intval($b["id"]) <=> intval($a["id"]));

    ok([ "live" => $latest ]);
}

/* ---------------- HOME SECTIONS ---------------- */

if ($action == "get_home") {

    $home = [];

    foreach ($sections as $sec) {
        $ids = $sec["channel_ids"] ?? [];
        $list = [];

        foreach ($live as $ch) {
            if (in_array($ch["id"], $ids)) $list[] = $ch;
        }

        $home[] = [
            "section_name" => $sec["title"],
            "section_type" => $sec["type"],
            "post"         => $list
        ];
    }

    ok([ "home" => $home ]);
}

/* ---------------- LIVE BY CATEGORY ---------------- */

if ($action == "get_cat_by") {

    $cid = intval($_REQUEST["cat_id"] ?? 0);

    $channels = array_values(
        array_filter($live, fn($c)=>intval($c["category_id"]) == $cid)
    );

    ok(["live" => $channels]);
}

/* ---------------- SINGLE LIVE ID ---------------- */

if ($action == "get_live_id") {

    $id = intval($_REQUEST["id"] ?? 0);

    $found = array_values(
        array_filter($live, fn($c)=>intval($c["id"]) == $id)
    );

    ok(["live" => $found]);
}

/* ---------------- BANNER ---------------- */

if ($action == "get_banner_by") {
    ok(["banner" => $banners]);
}

/* ---------------- EVENTS ---------------- */

if ($action == "get_event") {
    ok(["event" => $events]);
}

/* ---------------- SEARCH LIVE ---------------- */

if ($action == "get_search_live") {

    $q = strtolower($_REQUEST["search_text"] ?? "");

    $result = array_values(
        array_filter($live, function($c) use($q){
            return $q == "" || strpos(strtolower($c["name"]), $q) !== false;
        })
    );

    ok(["live" => $result]);
}

/* ---------------- DEFAULT ---------------- */

error("unknown_action");
