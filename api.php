<?php
header('Content-Type: application/json');
$ROOT = "NEMOSOFTS_APP";

function send($data) {
    global $ROOT;
    echo json_encode([$ROOT => $data], JSON_UNESCAPED_SLASHES);
    exit;
}

# Load JSON files
function load_json($file) {
    $path = "data/" . $file;
    if (!file_exists($path)) return [];
    return json_decode(file_get_contents($path), true);
}

$tbl_live      = load_json("tbl_live.json");
$tbl_category  = load_json("tbl_category.json");
$tbl_home      = load_json("tbl_home_sections.json");
$tbl_settings  = load_json("tbl_settings.json");
$tbl_banner    = load_json("tbl_banner.json");

$data = isset($_GET['data']) ? $_GET['data'] : "";

###############################################################
# 1. app_details (FIRST API CALL FROM THE APP)
###############################################################
if ($data == "app_details") {

    if (empty($tbl_settings)) {
        send([
            [
                "success" => "0",
                "MSG" => "settings_not_found"
            ]
        ]);
    }

    send([
        [
            "success" => "1",
            "MSG" => "success",
            "app_name" => "Online Live TV",
            "app_logo" => $tbl_settings["app_logo"],
            "app_email" => $tbl_settings["app_email"],
            "app_author" => $tbl_settings["app_author"],
            "app_contact" => $tbl_settings["app_contact"],
            "app_website" => $tbl_settings["app_website"],
            "app_description" => $tbl_settings["app_description"],
            "app_developed_by" => $tbl_settings["app_developed_by"],
            "ad_status" => "false"
        ]
    ]);
}

###############################################################
# 2. get_home (Home page sections)
###############################################################
if ($data == "get_home") {

    $final = [];

    foreach ($tbl_home["tbl_home_sections"] as $sec) {
        $section_items = [];
        foreach ($sec["channel_ids"] as $id) {
            foreach ($tbl_live as $ch) {
                if ($ch["id"] == $id && $ch["status"] == "1") {
                    $section_items[] = $ch;
                }
            }
        }

        $final[] = [
            "type" => $sec["type"],
            "title" => $sec["title"],
            "list" => $section_items
        ];
    }

    # Add banners if exist
    if (!empty($tbl_banner)) {
        $final[] = [
            "type" => "banner",
            "title" => "Banners",
            "list" => $tbl_banner
        ];
    }

    send([
        [
            "success" => "1",
            "MSG" => "success",
            "home" => $final
        ]
    ]);
}

###############################################################
# 3. cat_list (Category list)
###############################################################
if ($data == "cat_list") {
    send([
        [
            "success" => "1",
            "MSG" => "success",
            "category" => $tbl_category
        ]
    ]);
}

###############################################################
# 4. get_live_id (Single channel details)
###############################################################
if ($data == "get_live_id") {

    $id = $_GET["id"] ?? "";
    if ($id == "") send([["success" => "0", "MSG" => "missing_id"]]);

    foreach ($tbl_live as $ch) {
        if ($ch["id"] == $id) {
            send([
                [
                    "success" => "1",
                    "MSG" => "success",
                    "live_data" => [$ch],
                    "related" => []
                ]
            ]);
        }
    }

    send([["success" => "0", "MSG" => "not_found"]]);
}

###############################################################
# 5. get_cat_by (Channels by category)
###############################################################
if ($data == "get_cat_by") {

    $cid = $_GET["cid"] ?? "";
    if ($cid == "") send([["success" => "0", "MSG" => "missing_cid"]]);

    $result = [];

    foreach ($tbl_live as $ch) {
        if ($ch["cat_id"] == $cid && $ch["status"] == "1") {
            $result[] = $ch;
        }
    }

    send([
        [
            "success" => "1",
            "MSG" => "success",
            "data" => $result
        ]
    ]);
}

###############################################################
# 6. get_latest (last added channels)
###############################################################
if ($data == "get_latest") {
    send([
        [
            "success" => "1",
            "MSG" => "success",
            "latest" => array_reverse($tbl_live)
        ]
    ]);
}

###############################################################
# 7. get_trending (most viewed)
###############################################################
if ($data == "get_trending") {
    usort($tbl_live, fn($a, $b) => $b["total_views"] <=> $a["total_views"]);
    send([
        [
            "success" => "1",
            "MSG" => "success",
            "trending" => $tbl_live
        ]
    ]);
}

###############################################################
# 8. get_search_live
###############################################################
if ($data == "get_search_live") {

    $keyword = strtolower($_GET["keyword"] ?? "");
    $result = [];

    foreach ($tbl_live as $ch) {
        if (strpos(strtolower($ch["live_title"]), $keyword) !== false) {
            $result[] = $ch;
        }
    }

    send([
        [
            "success" => "1",
            "MSG" => "success",
            "data" => $result
        ]
    ]);
}

###############################################################
# DEFAULT ERROR
###############################################################
send([
    [
        "success" => "0",
        "MSG" => "invalid_method"
    ]
]);

?>
