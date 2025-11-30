<?php
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

// ---- Basic helpers ----
define('DATA_DIR', __DIR__ . '/data');

function json_ok($message, $data = []) {
    echo json_encode([
        "API_NAME"   => "NEMOSOFTS_APP",
        "status"     => "success",
        "success"    => "1",
        "message"    => $message,
        "server_time"=> date("Y-m-d H:i:s"),
        "data"       => $data
    ]);
    exit;
}

function json_error($message) {
    echo json_encode([
        "API_NAME"   => "NEMOSOFTS_APP",
        "status"     => "failed",
        "success"    => "0",
        "message"    => $message,
        "server_time"=> date("Y-m-d H:i:s"),
        "data"       => []
    ]);
    exit;
}

function read_json_file($filename, $default = []) {
    $path = DATA_DIR . '/' . $filename;
    if (!file_exists($path)) {
        return $default;
    }
    $raw = file_get_contents($path);
    if ($raw === false || $raw === '') {
        return $default;
    }
    $data = json_decode($raw, true);
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        return $default;
    }
    return $data;
}

function write_json_file($filename, $data) {
    $path = DATA_DIR . '/' . $filename;
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

// ---- Parse input ----
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$dataRaw = isset($_POST['data']) ? $_POST['data'] : null;
$payload = null;

if ($dataRaw) {
    $decoded = json_decode($dataRaw, true);
    if ($decoded !== null) {
        $payload = $decoded;
    }
}

// ---- Load core JSON "tables" ----
$settings   = read_json_file('settings.json', []);
$categories = read_json_file('categories.json', []);
$liveTV     = read_json_file('live_tv.json', []);
$sections   = read_json_file('sections.json', []);
$banners    = read_json_file('banners.json', []);
$events     = read_json_file('events.json', []);
$users      = read_json_file('users.json', []);
$subs       = read_json_file('subscriptions.json', []);
$suggestions= read_json_file('suggestions.json', []);
$reports    = read_json_file('reports.json', []);

// ---- Utility lookups ----
function get_live_by_ids($liveTV, $ids) {
    $map = [];
    foreach ($liveTV as $item) {
        $map[$item['id']] = $item;
    }
    $out = [];
    foreach ($ids as $id) {
        if (isset($map[$id])) {
            $out[] = $map[$id];
        }
    }
    return $out;
}

// ---- ACTIONS ----
switch ($action) {

    // 1) App details / settings: called on splash/about
    case 'app_details':
    case 'settings':
        if (empty($settings)) {
            json_error('settings_not_configured');
        }
        json_ok('app_details', [$settings]);
        break;

    // 2) Category list
    case 'category_list':
    case 'categories':
        $active = array_values(array_filter($categories, function($c){
            return isset($c['status']) ? (int)$c['status'] === 1 : true;
        }));
        json_ok('category_list', $active);
        break;

    // 3) All live channels OR by category (cat_id)
    case 'live_list':
    case 'live_tv':
        $catId = null;
        if ($payload && isset($payload['cat_id'])) {
            $catId = (int)$payload['cat_id'];
        } elseif (isset($_REQUEST['cat_id'])) {
            $catId = (int)$_REQUEST['cat_id'];
        }

        $active = array_values(array_filter($liveTV, function($ch) use ($catId){
            if (isset($ch['status']) && (int)$ch['status'] !== 1) {
                return false;
            }
            if ($catId !== null && isset($ch['category_id'])) {
                return (int)$ch['category_id'] === $catId;
            }
            return true;
        }));

        json_ok('live_list', $active);
        break;

    // 4) Latest channels (by id desc)
    case 'latest':
        $active = array_values(array_filter($liveTV, function($ch){
            return !isset($ch['status']) || (int)$ch['status'] === 1;
        }));
        usort($active, function($a, $b){
            return (int)$b['id'] <=> (int)$a['id'];
        });
        // optional: limit by settings
        $limit = isset($settings['api_latest_limit']) ? (int)$settings['api_latest_limit'] : 20;
        $active = array_slice($active, 0, $limit);
        json_ok('latest', $active);
        break;

    // 5) Home / sections: mix of "featured" and "latest" etc.
    case 'home':
    case 'section_home':
        $resultSections = [];
        foreach ($sections as $sec) {
            if (!isset($sec['type'], $sec['title'], $sec['channel_ids'])) continue;
            $channels = get_live_by_ids($liveTV, $sec['channel_ids']);

            $resultSections[] = [
                "type"    => $sec['type'],
                "title"   => $sec['title'],
                "channel" => $channels
            ];
        }
        json_ok('home', $resultSections);
        break;

    // 6) Banners
    case 'banner_list':
        json_ok('banner_list', $banners);
        break;

    // 7) Events
    case 'event_list':
    case 'events':
        json_ok('event_list', $events);
        break;

    // 8) Simple search by name
    case 'search':
        $term = '';
        if ($payload && isset($payload['search_text'])) {
            $term = trim($payload['search_text']);
        } elseif (isset($_REQUEST['search_text'])) {
            $term = trim($_REQUEST['search_text']);
        }
        $termLower = mb_strtolower($term);
        $matches = array_values(array_filter($liveTV, function($ch) use ($termLower) {
            return $termLower === '' 
                ? true 
                : (strpos(mb_strtolower($ch['name']), $termLower) !== false);
        }));
        json_ok('search', $matches);
        break;

    // 9) Dummy user login/register (JSON based – can improve later)
    case 'user_login':
        if (!$payload || !isset($payload['email'], $payload['password'])) {
            json_error('missing_email_or_password');
        }
        $email = strtolower(trim($payload['email']));
        $pass  = $payload['password'];

        $found = null;
        foreach ($users as $u) {
            if (strtolower($u['email']) === $email && $u['password'] === $pass) {
                $found = $u;
                break;
            }
        }
        if (!$found) {
            json_error('invalid_login');
        }
        json_ok('login', [$found]);
        break;

    case 'user_register':
        if (!$payload || !isset($payload['name'], $payload['email'], $payload['password'])) {
            json_error('missing_registration_fields');
        }
        $email = strtolower(trim($payload['email']));

        foreach ($users as $u) {
            if (strtolower($u['email']) === $email) {
                json_error('email_already_exists');
            }
        }

        $newId = 1;
        if (!empty($users)) {
            $ids = array_column($users, 'id');
            $newId = max($ids) + 1;
        }

        $newUser = [
            "id"       => $newId,
            "name"     => $payload['name'],
            "email"    => $email,
            "password" => $payload['password'], // NOTE: plain text, same as many simple scripts
            "status"   => 1
        ];
        $users[] = $newUser;
        write_json_file('users.json', $users);

        json_ok('register', [$newUser]);
        break;

    // 10) Suggestions (report a channel, etc.)
    case 'send_suggestion':
        if (!$payload || !isset($payload['user_id'], $payload['message'])) {
            json_error('missing_suggestion_fields');
        }
        $newId = 1;
        if (!empty($suggestions)) {
            $ids = array_column($suggestions, 'id');
            $newId = max($ids) + 1;
        }
        $newSug = [
            "id"      => $newId,
            "user_id" => (int)$payload['user_id'],
            "message" => $payload['message'],
            "created" => date("Y-m-d H:i:s")
        ];
        $suggestions[] = $newSug;
        write_json_file('suggestions.json', $suggestions);
        json_ok('suggestion_sent', [$newSug]);
        break;

    // 11) Report channel broken
    case 'send_report':
        if (!$payload || !isset($payload['channel_id'], $payload['user_id'], $payload['message'])) {
            json_error('missing_report_fields');
        }
        $newId = 1;
        if (!empty($reports)) {
            $ids = array_column($reports, 'id');
            $newId = max($ids) + 1;
        }
        $newRep = [
            "id"         => $newId,
            "channel_id" => (int)$payload['channel_id'],
            "user_id"    => (int)$payload['user_id'],
            "message"    => $payload['message'],
            "created"    => date("Y-m-d H:i:s")
        ];
        $reports[] = $newRep;
        write_json_file('reports.json', $reports);
        json_ok('report_sent', [$newRep]);
        break;

    // 12) Just return ok for subscription-related calls (you can extend later)
    case 'subscription_list':
        json_ok('subscription_list', $subs);
        break;

    default:
        json_error('unknown_action');
}
