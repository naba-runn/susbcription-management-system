<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['user_id']) || !isset($data['type']) || !isset($data['details'])) {
    echo json_encode(["success" => false, "message" => "All fields (user_id, type, details) are required."]);
    exit();
}

$user_id = (int)$data['user_id'];
$type    = trim($data['type']);
$details = trim($data['details']);

if ($user_id <= 0 || empty($type) || empty($details)) {
    echo json_encode(["success" => false, "message" => "Fields cannot be empty."]);
    exit();
}

$stmt = mysqli_prepare($conn, "INSERT INTO payment_methods (user_id, type, details) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($stmt, "iss", $user_id, $type, $details);

if (mysqli_stmt_execute($stmt)) {
    $method_id = mysqli_insert_id($conn);
    echo json_encode([
        "success"   => true,
        "message"   => "Payment method added successfully.",
        "method_id" => $method_id
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to add payment method."]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
