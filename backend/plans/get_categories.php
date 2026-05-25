<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

$result = mysqli_query($conn, "SELECT category_id, name FROM categories ORDER BY name ASC");

if (!$result) {
    echo json_encode(["success" => false, "message" => "Failed to fetch categories."]);
    exit();
}

$categories = [];
while ($row = mysqli_fetch_assoc($result)) {
    $categories[] = [
        "category_id" => (int)$row['category_id'],
        "name"        => $row['name']
    ];
}

echo json_encode(["success" => true, "categories" => $categories]);

mysqli_close($conn);
