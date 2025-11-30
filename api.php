<?php
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

define('DATA_DIR', __DIR__ . '/data');

function read_json_file($filename, $default = []) {
    $path = DATA_DIR . '/' . $filename;
    if (!file_exists($path)) return $default;

    $data = json_decode(file_get_contents($path), true);
    return $data ?: $default;
}

function write_json_file($filename, $data) {
    file_put_contents(DATA_DIR . '/' . $filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function response_nemosoft($arr) {
    echo json_encode(["NEMOSOFTS_APP" => [$arr]], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

function success($message, $data = []) {
    response_nemosoft([
        "success" => "1",
        "verifyStatus" => "1",
        "MSG" => "",
        "message" => $message,
        "data" => $data
    ]);
}

function error($msg) {
    response_nemosoft([
        "success" => "0",
        "verifyStatus" => "-1",
        "MSG" => $msg,
        "data" => []
    ]);
}

// Load all JSON tables
$settings    = read_json_file('settings.json', []);
$categories  = read_json_file('categories.json', []);
$liveTV      = read_json_file('live_tv.json', []);
$sections    = read_json_file('sections.json', []);
$banners     = read_json_file('banners.json', []);
$events      = read_json_file('events.json', []);
$users       = read_json_file('users.json', []);
$subs        = read_json_file('subscriptions.json', []);
$suggestions = read_json_file('suggestions.json', []);
$reports     = read_json_file('reports.json', []);

$action = $_REQUEST['action'] ?? '';

$payload = null;
if (isset($_POST['data'])) {
    $decoded = json_decode($_POST['data'], true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $payload = $decoded;
    }
}

// Helper: fetch channels by ID
function get_live_by_ids($all, $ids) {
    $map = [];
    foreach ($all as $ch) $map[$ch['id']] = $ch;

    $out = [];
    foreach ($ids as $id) {
        if (isset($map[$id])) $out[] = $map[$id];
    }
    return $out;
}

// ==========================
// ACTION HANDLERS
// ==========================

// 1) APP DETAILS (NEMOSOFT FORMAT)
if ($action === "app_details") {

    if (empty($settings)) {
        error("settings_not_found");
    }

    // Force required fields for Nemosoft launcher
    $settings["success"] = "1";
    $settings["verifyStatus"] = "1";
    $settings["MSG"] = "";

    response_nemosoft($settings);
}

// 2) CATEGORY LIST
if ($action === "cat_list" || $action === "categories") {
    $active = array_values(array_filter($categories, fn($c) => !isset($c['status']) || $c['status'] == 1));
    success("category_list", $active);
}

// 3) LIVE TV LIST / CATEGORY FILTER
if ($action === "get_cat_by" || $action === "live_list") {

    $catId = $_REQUEST['cat_id'] ?? ($payload['cat_id'] ?? null);

    $list = array_values(array_filter($liveTV, function($ch) use ($catId) {
        if (isset($ch['status']) && $ch['status'] != 1) return false;
        if ($catId) return $ch['category_id'] == $catId;
        return true;
    }));

    success("live_list", $list);
}

// 4) LATEST CHANNELS
if ($action === "get_latest") {

    $list = array_values(array_filter($liveTV, fn($ch) => !isset($ch['status']) || $ch['status'] == 1));

    usort($list, fn($a,$b) => $b['id'] <=> $a['id']);

    $limit = $settings["api_latest_limit"] ?? 20;
    $list = array_slice($list, 0, $limit);

    success("latest", $list);
}

// 5) HOME SECTIONS
if ($action === "get_home") {

    $result = [];

    foreach ($sections as $sec) {
        $result[] = [
            "type" => $sec['type'],
            "title" => $sec['title'],
            "channel" => get_live_by_ids($liveTV, $sec['channel_ids'])
        ];
    }

    success("home", $result);
}

// 6) BANNERS
if ($action === "get_banner_by") {
    success("banner_list", $banners);
}

// 7) EVENTS
if ($action === "get_event") {
    success("event_list", $events);
}

// 8) SEARCH
if ($action === "get_search" || $action === "search") {

    $term = strtolower($_REQUEST['search_text'] ?? ($payload['search_text'] ?? ""));

    $matches = array_values(array_filter($liveTV, function($ch) use ($term) {
        return $term === "" || strpos(strtolower($ch['name']), $term) !== false;
    }));

    success("search", $matches);
}

// 9) LOGIN
if ($action === "user_login") {

    if (!$payload || !isset($payload["email"], $payload["password"])) {
        error("missing_credentials");
    }

    $email = strtolower(trim($payload["email"]));
    $pass = $payload["password"];

    foreach ($users as $u) {
        if ($u['email'] === $email && $u['password'] === $pass) {
            success("login", [$u]);
        }
    }

    error("invalid_login");
}

// 10) REGISTER
if ($action === "user_register") {

    if (!$payload || !isset($payload["email"], $payload["password"], $payload["name"])) {
        error("missing_registration_fields");
    }

    $email = strtolower(trim($payload["email"]));

    foreach ($users as $u) {
        if ($u["email"] === $email) error("email_exists");
    }

    $newId = empty($users) ? 1 : max(array_column($users, 'id')) + 1;

    $newUser = [
        "id" => $newId,
        "name" => $payload["name"],
        "email" => $email,
        "password" => $payload["password"],
        "status" => 1
    ];

    $users[] = $newUser;
    write_json_file('users.json', $users);

    success("register", [$newUser]);
}

// 11) SUGGESTION
if ($action === "post_suggest") {

    if (!$payload || !isset($payload["user_id"], $payload["message"])) {
        error("missing_suggestion_fields");
    }

    $newId = empty($suggestions) ? 1 : max(array_column($suggestions, 'id')) + 1;

    $new = [
        "id" => $newId,
        "user_id" => $payload["user_id"],
        "message" => $payload["message"],
        "created" => date("Y-m-d H:i:s")
    ];

    $suggestions[] = $new;
    write_json_file('suggestions.json', $suggestions);

    success("suggestion_sent", [$new]);
}

// 12) REPORT
if ($action === "post_report") {

    if (!$payload || !isset($payload["channel_id"], $payload["user_id"], $payload["message"])) {
        error("missing_report_fields");
    }

    $newId = empty($reports) ? 1 : max(array_column($reports, 'id')) + 1;

    $new = [
        "id" => $newId,
        "channel_id" => $payload["channel_id"],
        "user_id" => $payload["user_id"],
        "message" => $payload["message"],
        "created" => date("Y-m-d H:i:s")
    ];

    $reports[] = $new;
    write_json_file('reports.json', $reports);

    success("report_sent", [$new]);
}

// 13) SUBSCRIPTIONS
if ($action === "subscription_list") {
    success("subscription_list", $subs);
}

// DEFAULT
error("unknown_action");
