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

if (!isset($_GET['code'])) {
    echo json_encode(["success" => false, "message" => "Coupon code is required."]);
    exit();
}

$code = strtoupper(trim($_GET['code']));

if (empty($code)) {
    echo json_encode(["success" => false, "message" => "Coupon code cannot be empty."]);
    exit();
}

$stmt = mysqli_prepare($conn, "SELECT discount_percent, valid_until FROM coupons WHERE code = ?");
mysqli_stmt_bind_param($stmt, "s", $code);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    if (strtotime($row['valid_until']) >= strtotime(date('Y-m-d'))) {
        echo json_encode([
            "success" => true,
            "discount_percent" => (int)$row['discount_percent']
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Coupon code has expired."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid coupon code."]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
