<?php
// JSON Database Helper

define('DATA_PATH', __DIR__ . '/../data/');

function json_db_path($table) {
    return DATA_PATH . $table . '.json';
}

function json_load($table, $default = []) {
    $file = json_db_path($table);

    if (!file_exists($file)) {
        return $default;
    }

    $json = file_get_contents($file);
    if (!$json) {
        return $default;
    }

    $data = json_decode($json, true);
    return $data ?? $default;
}

function json_save($table, $data) {
    $file = json_db_path($table);
    if (!is_dir(DATA_PATH)) {
        mkdir(DATA_PATH, 0777, true);
    }

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    file_put_contents($file, $json, LOCK_EX);
}

function json_next_id($items) {
    $max = 0;
    foreach ($items as $item) {
        if (isset($item["id"]) && $item["id"] > $max) {
            $max = $item["id"];
        }
    }
    return $max + 1;
}