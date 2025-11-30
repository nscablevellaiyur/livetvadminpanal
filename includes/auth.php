<?php
session_start();

function require_admin() {
    if (!isset($_SESSION["admin"])) {
        header("Location: /admin/login.php");
        exit;
    }
}

function current_admin() {
    return $_SESSION["admin"] ?? null;
}