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

if (!isset($_GET['user_id'])) {
    echo json_encode(["success" => false, "message" => "user_id is required."]);
    exit();
}

$user_id = (int)$_GET['user_id'];

if ($user_id <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid user_id."]);
    exit();
}

$stmt = mysqli_prepare($conn, "SELECT method_id, type, details FROM payment_methods WHERE user_id = ? ORDER BY method_id DESC");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$methods = [];
while ($row = mysqli_fetch_assoc($result)) {
    $methods[] = [
        "method_id" => (int)$row['method_id'],
        "type"      => $row['type'],
        "details"   => $row['details']
    ];
}

echo json_encode(["success" => true, "methods" => $methods]);

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
