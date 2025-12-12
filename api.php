<?php
header('Content-Type: application/json');
function sendResponse($data){
    echo json_encode(["NEMOSOFTS_APP" => [$data]], JSON_UNESCAPED_SLASHES);
    exit;
}

function safeGet($arr, $key){
    return isset($arr[$key]) ? $arr[$key] : "";
}

// Decode incoming POST Base64 JSON
$raw = $_POST['data'] ?? "";
if(empty($raw)){
    sendResponse(["success"=>"0","MSG"=>"invalid_request"]);
}

$json = base64_decode($raw);
$req = json_decode($json, true);

if(!$req){
    sendResponse(["success"=>"0","MSG"=>"invalid_json"]);
}

$helper = safeGet($req, "helper_name");

// Load JSON files
function load_json($file){
    $path = __DIR__."/data/".$file;
    return file_exists($path) ? json_decode(file_get_contents($path), true) : [];
}

$tbl_live = load_json("tbl_live.json")["tbl_live"] ?? [];
$tbl_cat  = load_json("tbl_category.json")["tbl_category"] ?? [];
$tbl_home = load_json("tbl_home_sections.json")["tbl_home_sections"] ?? [];
$tbl_banner = load_json("tbl_banner.json")["tbl_banner"] ?? [];

// ---------------- HELPERS ---------------- //
function filterActive($arr){
    return array_values(array_filter($arr, fn($a) => ($a["status"] ?? "")=="1"));
}

// ---------------- API HANDLERS ---------------- //

if($helper == "app_details"){
    $settings = load_json("tbl_settings.json");
    if(empty($settings)){
        sendResponse(["success"=>"0","MSG"=>"settings_not_found"]);
    }
    sendResponse([
        "success"=>"1","MSG"=>"success",
        "app_name"=>$settings["app_name"] ?? "",
        "app_logo"=>$settings["app_logo"] ?? "",
        "app_email"=>$settings["app_email"] ?? "",
        "app_contact"=>$settings["app_contact"] ?? "",
        "app_website"=>$settings["app_website"] ?? "",
        "app_description"=>$settings["app_description"] ?? "",
        "app_developed_by"=>$settings["app_developed_by"] ?? ""
    ]);
}

if($helper == "home"){
    $home = [];
    foreach($tbl_home as $sec){
        $items = [];
        foreach($sec["channel_ids"] ?? [] as $id){
            foreach($tbl_live as $ch){
                if($ch["id"] == $id && $ch["status"]=="1"){
                    $items[] = $ch;
                }
            }
        }
        $home[] = [
            "section"=>$sec["title"],
            "list"=>$items
        ];
    }
    sendResponse([
        "success"=>"1","MSG"=>"success",
        "home"=>$home,
        "banner"=>$tbl_banner,
        "category"=>$tbl_cat
    ]);
}

if($helper == "latest"){
    $sorted = array_reverse(filterActive($tbl_live));
    sendResponse(["success"=>"1","MSG"=>"success","latest"=>$sorted]);
}

if($helper == "most_viewed"){
    $sorted = filterActive($tbl_live);
    usort($sorted, fn($a,$b) => ($b["total_views"] ?? 0) <=> ($a["total_views"] ?? 0));
    sendResponse(["success"=>"1","MSG"=>"success","most_viewed"=>$sorted]);
}

if($helper == "live_id"){
    $id = safeGet($req, "post_id");
    foreach($tbl_live as $ch){
        if($ch["id"] == $id){
            sendResponse(["success"=>"1","MSG"=>"success","live_data"=>$ch]);
        }
    }
    sendResponse(["success"=>"0","MSG"=>"not_found"]);
}

if($helper == "search_live"){
    $search = strtolower(safeGet($req,"search_text"));
    $results = array_values(array_filter($tbl_live, fn($ch) => strpos(strtolower($ch["live_title"]), $search) !== false));
    sendResponse(["success"=>"1","MSG"=>"success","search"=>$results]);
}

sendResponse(["success"=>"0","MSG"=>"unknown_helper"]);

?>
