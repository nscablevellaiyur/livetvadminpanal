<?php
/*
 * FINAL WORKING api.php (Simple Mode)
 * - Supports GET (for browser/testing) and POST (Android app: base64 encoded 'data' param)
 * - Reads/writes JSON files from /json_db/ (folder must be next to this file)
 * - Implements helper aliases used by your Android app
 * - Registration is DISABLED (per your request)
 *a<?php
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

 * Place this file next to the folder: /json_db/
 * Images expected under: /uploads/
 */

header("Content-Type: application/json; charset=utf-8");
error_reporting(0);
date_default_timezone_set("Asia/Colombo");

$API_NAME = "NEMOSOFTS_APP";

/* ----------------------
   Base URL helper
   ---------------------- */
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? "localhost";
    $path = dirname($_SERVER['SCRIPT_NAME']);
    $path = rtrim($path, "/") . "/";
    return $protocol . $host . $path;
}

$file_path = getBaseUrl();

/* ----------------------
   JSON path
   ---------------------- */
define("JSON_TABLES_PATH", __DIR__ . "/json_db/"); // ensure trailing slash

/* ----------------------
   JSON helpers
   ---------------------- */
function read_table($table) {
    $path = JSON_TABLES_PATH . $table . ".json";
    if (!file_exists($path)) return [];
    $raw = file_get_contents($path);
    $arr = json_decode($raw, true);
    return (is_array($arr) && isset($arr[$table]) && is_array($arr[$table])) ? $arr[$table] : [];
}

function write_table($table, $rows) {
    $path = JSON_TABLES_PATH . $table . ".json";
    $payload = [$table => array_values($rows)];
    file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/* ----------------------
   API data decode (base64 -> urldecode -> json)
   ---------------------- */
function get_api_data($data_info) {
    $json = base64_decode($data_info);
    $json = urldecode($json);
    $arr = json_decode($json, true);
    return is_array($arr) ? $arr : [];
}

/* ----------------------
   Utilities
   ---------------------- */
function clean($v) { return trim($v ?? ""); }

function send_json_and_exit($payload) {
    global $API_NAME;
    echo json_encode([$API_NAME => $payload], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function send_json_list_and_exit($list) {
    global $API_NAME;
    echo json_encode([$API_NAME => $list], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/* ----------------------
   GET -> POST conversion for easy browser testing
   ---------------------- */
if (!isset($_POST['data']) && isset($_GET['helper_name'])) {
    $tmp = [];
    foreach ($_GET as $k => $v) {
        $tmp[$k] = $v;
    }
    $_POST['data'] = base64_encode(json_encode($tmp));
}

/* ----------------------
   Decode request
   ---------------------- */
$raw = $_POST['data'] ?? '';
$get_helper = ($raw !== '') ? get_api_data($raw) : [];
$helper_name_raw = $get_helper['helper_name'] ?? '';

/* ----------------------
   Alias map: map many helper names to canonical handlers
   ---------------------- */
$alias_map = [
    // Home
    "home" => "get_home",
    "get_home" => "get_home",
    "get_latest" => "get_home",

    // Category
    "cat_list" => "cat_list",

    // Live details
    "get_live_id" => "get_live_id",
    "live_id" => "get_live_id",

    // Search
    "get_search_live" => "get_search_live",
    "search_live" => "get_search_live",

    // Favourite
    "favourite_post" => "favourite_post",
    "get_favourite" => "get_favourite",

    // Login aliases
    "user_login" => "user_login",
    "login" => "user_login",

    // Registration (disabled) - mapped to disabled handler
    "register" => "register_disabled",
    "user_register" => "register_disabled",

    // Settings / App details
    "app_details" => "app_details",
    "settings" => "app_details",

    // Events
    "get_event" => "get_event",
    "event_list" => "get_event",

    // Banner helpers
    "get_banner" => "get_banner",
    "get_banner_by" => "get_banner"
];

/* canonical helper */
$helper_name = $alias_map[$helper_name_raw] ?? $helper_name_raw;

/* ----------------------
   Load settings (once)
   ---------------------- */
$settings_rows = read_table("tbl_settings");
$settings = !empty($settings_rows) ? $settings_rows[0] : [];
define("HOME_LIMIT", intval($settings['home_limit'] ?? 10));

/* ----------------------
   Handlers
   ---------------------- */

/* GET_HOME handler */
if ($helper_name === "get_home") {
    $response = ["success" => "1"];

    // banners
    $banners = read_table("tbl_banner");
    $slider = [];
    foreach ($banners as $b) {
        if (isset($b['status']) && (string)$b['status'] !== "1") continue;
        $slider[] = [
            "bid" => $b['bid'] ?? "",
            "banner_title" => $b['banner_title'] ?? "",
            "banner_info" => $b['banner_info'] ?? "",
            "banner_image" => $file_path . "uploads/" . ($b['banner_image'] ?? "")
        ];
    }
    $response['slider'] = $slider;

    // latest live
    $live = read_table("tbl_live");
    // only active
    $live = array_values(array_filter($live, function($l){ return !isset($l['status']) || (string)$l['status'] === "1"; }));
    usort($live, function($a,$b){ return intval($b['id'] ?? 0) - intval($a['id'] ?? 0); });
    $live = array_slice($live, 0, HOME_LIMIT);

    $latest = [];
    foreach ($live as $l) {
        $latest[] = [
            "id" => $l['id'] ?? "",
            "cat_id" => $l['cat_id'] ?? "",
            "live_title" => $l['live_title'] ?? "",
            "live_url" => $l['live_url'] ?? "",
            "image" => $file_path . "uploads/" . ($l['live_image'] ?? ""),
            "live_type" => $l['live_type'] ?? "",
            "live_description" => $l['live_description'] ?? "",
            "is_premium" => $l['isPremium'] ?? "0",
            "player_type" => $l['player_type'] ?? ""
        ];
    }
    $response['latest'] = $latest;

    // events
    $events = read_table("tbl_live_event");
    $elist = [];
    foreach ($events as $e) {
        if (isset($e['status']) && (string)$e['status'] !== "1") continue;
        $elist[] = [
            "id" => $e['id'] ?? "",
            "post_id" => $e['post_id'] ?? "",
            "event_title" => $e['event_title'] ?? "",
            "event_time" => $e['event_time'] ?? "",
            "event_date" => $e['event_date'] ?? "",
            "team_title_one" => $e['team_title_one'] ?? "",
            "team_one_thumbnail" => $file_path . "uploads/" . ($e['team_one_thumbnail'] ?? ""),
            "team_title_two" => $e['team_title_two'] ?? "",
            "team_two_thumbnail" => $file_path . "uploads/" . ($e['team_two_thumbnail'] ?? "")
        ];
    }
    $response['event'] = $elist;

    send_json_and_exit($response);
}

/* CAT_LIST handler */
if ($helper_name === "cat_list") {
    $cats = read_table("tbl_category");
    $cats = array_values(array_filter($cats, function($c){ return !isset($c['status']) || (string)$c['status'] === "1"; }));
    usort($cats, function($a,$b){ return intval($b['cid'] ?? 0) - intval($a['cid'] ?? 0); });

    $out = [];
    foreach ($cats as $c) {
        $out[] = [
            "cid" => $c['cid'] ?? "",
            "category_name" => $c['category_name'] ?? "",
            "category_image" => $file_path . "uploads/" . ($c['category_image'] ?? "")
        ];
    }
    send_json_list_and_exit($out);
}

/* GET_LIVE_ID (and live_id) handler */
if ($helper_name === "get_live_id") {
    $post_id = clean($get_helper['post_id'] ?? $get_helper['id'] ?? 0);
    $user_id = clean($get_helper['user_id'] ?? 0);

    $live = read_table("tbl_live");
    $categories = read_table("tbl_category");
    $cat_map = [];
    foreach ($categories as $c) $cat_map[$c['cid']] = $c;

    $main = null;
    foreach ($live as $l) {
        if ((string)$l['id'] === (string)$post_id) { $main = $l; break; }
    }

    if (!$main) send_json_and_exit(["success"=>"0","MSG"=>"not_found"]);

    $main_out = [
        "id" => $main['id'],
        "cat_id" => $main['cat_id'],
        "live_title" => $main['live_title'],
        "live_url" => $main['live_url'],
        "image" => $file_path . "uploads/" . ($main['live_image'] ?? ""),
        "live_type" => $main['live_type'] ?? "",
        "live_description" => $main['live_description'] ?? "",
        "rate_avg" => $main['rate_avg'] ?? 0,
        "total_rate" => $main['total_rate'] ?? 0,
        "total_views" => $main['total_views'] ?? 0,
        "total_share" => $main['total_share'] ?? 0,
        "is_premium" => $main['isPremium'] ?? "0",
        "player_type" => $main['player_type'] ?? "",
        "is_favorite" => (is_favorite_post($main['id'], $user_id) ? 1 : 0),
        "category_name" => isset($cat_map[$main['cat_id']]) ? $cat_map[$main['cat_id']]['category_name'] : ""
    ];

    // related (same category)
    $related = [];
    foreach ($live as $l) {
        if ((string)$l['id'] === (string)$post_id) continue;
        if ((string)$l['cat_id'] !== (string)$main['cat_id']) continue;
        if (isset($l['status']) && (string)$l['status'] !== "1") continue;
        $related[] = [
            "id" => $l['id'],
            "cat_id" => $l['cat_id'],
            "live_title" => $l['live_title'],
            "live_url" => $l['live_url'],
            "image" => $file_path . "uploads/" . ($l['live_image'] ?? ""),
            "live_type" => $l['live_type'] ?? "",
            "is_premium" => $l['isPremium'] ?? "0",
            "player_type" => $l['player_type'] ?? ""
        ];
    }

    send_json_and_exit(["live_data"=>[$main_out],"related"=>$related]);
}

/* SEARCH handler */
if ($helper_name === "get_search_live") {
    $search_text = strtolower(clean($get_helper['search_text'] ?? ""));
    $page = max(1, intval($get_helper['page'] ?? 1));
    $limit = 10; $offset = ($page-1)*$limit;

    $live = read_table("tbl_live");
    $filtered = array_values(array_filter($live, function($l) use ($search_text){
        if (isset($l['status']) && (string)$l['status'] !== "1") return false;
        if ($search_text === "") return true;
        return strpos(strtolower($l['live_title'] ?? ""), $search_text) !== false;
    }));
    usort($filtered, function($a,$b){ return strcmp(strtolower($b['live_title'] ?? ''), strtolower($a['live_title'] ?? '')); });
    $page_items = array_slice($filtered, $offset, $limit);

    $out = [];
    foreach ($page_items as $l) {
        $out[] = [
            "id" => $l['id'],
            "cat_id" => $l['cat_id'],
            "live_title" => $l['live_title'],
            "live_url" => $l['live_url'],
            "image" => $file_path . "uploads/" . ($l['live_image'] ?? ""),
            "live_type" => $l['live_type'] ?? "",
            "live_description" => $l['live_description'] ?? "",
            "is_premium" => $l['isPremium'] ?? "0",
            "player_type" => $l['player_type'] ?? ""
        ];
    }
    send_json_list_and_exit($out);
}

/* FAVOURITE toggle */
if ($helper_name === "favourite_post") {
    $user_id = clean($get_helper['user_id'] ?? 0);
    $post_id = clean($get_helper['post_id'] ?? 0);
    $favs = read_table("tbl_favourite");
    $found = null;
    foreach ($favs as $i => $f) {
        if ((string)$f['post_id'] === (string)$post_id && (string)$f['user_id'] === (string)$user_id && strtolower($f['type'] ?? '') === 'live') {
            $found = $i; break;
        }
    }
    if ($found === null) {
        $favs[] = ['id'=>0,'post_id'=>$post_id,'user_id'=>$user_id,'type'=>'live','created_at'=>strval(time())];
        write_table("tbl_favourite", $favs);
        send_json_and_exit(["success"=>"1","MSG"=>"Added to favourites"]);
    } else {
        array_splice($favs,$found,1);
        write_table("tbl_favourite", $favs);
        send_json_and_exit(["success"=>"1","MSG"=>"Removed from favourites"]);
    }
}

/* GET_FAVOURITE */
if ($helper_name === "get_favourite") {
    $user_id = clean($get_helper['user_id'] ?? 0);
    $page = max(1, intval($get_helper['page'] ?? 1));
    $limit = 10; $offset = ($page-1)*$limit;

    $favs = read_table("tbl_favourite");
    $live = read_table("tbl_live");
    $live_map = [];
    foreach ($live as $l) $live_map[$l['id']] = $l;

    $user_favs = array_values(array_filter($favs, function($f) use ($user_id){ return (string)$f['user_id'] === (string)$user_id && strtolower($f['type'] ?? '') === 'live'; }));
    usort($user_favs, function($a,$b){ return intval($b['id'] ?? 0) - intval($a['id'] ?? 0); });
    $page_favs = array_slice($user_favs, $offset, $limit);

    $out = [];
    foreach ($page_favs as $f) {
        $pid = $f['post_id'];
        if (!isset($live_map[$pid])) continue;
        $l = $live_map[$pid];
        if (isset($l['status']) && (string)$l['status'] !== "1") continue;
        $out[] = [
            "id" => $l['id'],
            "cat_id" => $l['cat_id'],
            "live_title" => $l['live_title'],
            "live_url" => $l['live_url'],
            "image" => $file_path . "uploads/" . ($l['live_image'] ?? ""),
            "live_type" => $l['live_type'] ?? "",
            "live_description" => $l['live_description'] ?? "",
            "is_premium" => $l['isPremium'] ?? "0",
            "player_type" => $l['player_type'] ?? ""
        ];
    }
    send_json_list_and_exit($out);
}

/* LOGIN (user_login / login) */
if ($helper_name === "user_login") {
    $email = clean($get_helper['user_email'] ?? "");
    $password_raw = clean($get_helper['user_password'] ?? "");
    $pass_hash = md5($password_raw);

    $users = read_table("tbl_users");
    foreach ($users as $u) {
        if ((isset($u['user_email']) && strtolower($u['user_email']) === strtolower($email)) && (isset($u['user_password']) && $u['user_password'] === $pass_hash)) {
            // success
            $u_out = $u;
            $u_out['profile_img'] = $file_path . "uploads/" . ($u_out['profile_img'] ?? "");
            $u_out['success'] = "1";
            send_json_and_exit($u_out);
        }
    }
    send_json_and_exit(["success"=>"0","MSG"=>"invalid_password"]);
}

/* APP_DETAILS / settings */
if ($helper_name === "app_details") {
    if (empty($settings)) send_json_and_exit(["success"=>"0","MSG"=>"no_settings"]);
    $s = $settings;
    $s['app_logo'] = $file_path . "uploads/" . ($s['app_logo'] ?? "");
    send_json_and_exit($s);
}

/* BANNER handlers */
if ($helper_name === "get_banner" || $helper_name === "get_banner_by") {
    $banners = read_table("tbl_banner");
    $out = [];
    foreach ($banners as $b) {
        if (isset($b['status']) && (string)$b['status'] !== "1") continue;
        $out[] = [
            "bid" => $b['bid'] ?? "",
            "banner_title" => $b['banner_title'] ?? "",
            "banner_info" => $b['banner_info'] ?? "",
            "banner_image" => $file_path . "uploads/" . ($b['banner_image'] ?? "")
        ];
    }
    send_json_list_and_exit($out);
}

/* EVENTS (aliases get_event / event_list handled) */
if ($helper_name === "get_event") {
    $events = read_table("tbl_live_event");
    $out = [];
    foreach ($events as $e) {
        if (isset($e['status']) && (string)$e['status'] !== "1") continue;
        $out[] = [
            "id" => $e['id'] ?? "",
            "post_id" => $e['post_id'] ?? "",
            "event_title" => $e['event_title'] ?? "",
            "event_time" => $e['event_time'] ?? "",
            "event_date" => $e['event_date'] ?? "",
            "team_title_one" => $e['team_title_one'] ?? "",
            "team_one_thumbnail" => $file_path . "uploads/" . ($e['team_one_thumbnail'] ?? ""),
            "team_title_two" => $e['team_title_two'] ?? "",
            "team_two_thumbnail" => $file_path . "uploads/" . ($e['team_two_thumbnail'] ?? "")
        ];
    }
    send_json_list_and_exit($out);
}

/* REGISTER disabled handler */
if ($helper_name === "register_disabled") {
    send_json_and_exit(["success"=>"0","MSG"=>"registration_disabled"]);
}

/* Default unknown action */
send_json_and_exit(["success"=>"0","MSG"=>"unknown_action"]);

/* ----------------------
   Helper function: is_favorite_post
   (placed at bottom so earlier use works)
   ---------------------- */
function is_favorite_post($post_id, $user_id) {
    $rows = read_table("tbl_favourite");
    foreach ($rows as $r) {
        if ((string)($r['post_id'] ?? '') === (string)$post_id && (string)($r['user_id'] ?? '') === (string)$user_id && strtolower($r['type'] ?? '') === 'live') {
            return true;
        }
    }
    return false;
}

?>
