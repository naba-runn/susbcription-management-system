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

$result = mysqli_query($conn, "
    SELECT p.plan_id, p.name, p.price, p.duration_days, p.category_id, c.name AS category_name 
    FROM plans p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    ORDER BY p.price ASC
");

if (!$result) {
    echo json_encode(["success" => false, "message" => "Failed to fetch plans."]);
    exit();
}

$plans = [];
while ($row = mysqli_fetch_assoc($result)) {
    $plans[] = [
        "plan_id"       => (int)$row['plan_id'],
        "name"          => $row['name'],
        "price"         => (float)$row['price'],
        "duration_days" => (int)$row['duration_days'],
        "category_id"   => $row['category_id'] ? (int)$row['category_id'] : null,
        "category_name" => $row['category_name']
    ];
}

echo json_encode(["success" => true, "plans" => $plans]);

mysqli_close($conn);
?>
