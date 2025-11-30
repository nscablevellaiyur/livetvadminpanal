<?php
require_once __DIR__ . '/includes/json_db.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'categories':
        $categories = json_load('categories', []);
        $categories = array_values(array_filter($categories, fn($c) => !empty($c['status'])));
        echo json_encode([
            'success' => 1,
            'data' => $categories
        ]);
        break;

    case 'live_tv':
        $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
        $liveTv  = json_load('live_tv', []);
        $liveTv = array_values(array_filter($liveTv, function ($ch) use ($categoryId) {
            if (empty($ch['status'])) return false;
            if ($categoryId !== null && (int)$ch['category_id'] !== $categoryId) return false;
            return true;
        }));

        echo json_encode([
            'success' => 1,
            'data' => $liveTv
        ]);
        break;

    default:
        echo json_encode([
            'success' => 0,
            'message' => 'Invalid action'
        ]);
}
