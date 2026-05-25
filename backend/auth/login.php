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

if (!isset($data['email']) || !isset($data['password'])) {
    echo json_encode(["success" => false, "message" => "Email and password are required."]);
    exit();
}

$email    = trim($data['email']);
$password = $data['password'];

if (empty($email) || empty($password)) {
    echo json_encode(["success" => false, "message" => "Fields cannot be empty."]);
    exit();
}

$stmt = mysqli_prepare($conn, "SELECT user_id, name, email, password FROM users WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    if (password_verify($password, $row['password'])) {
        echo json_encode([
            "success" => true,
            "message" => "Login successful.",
            "user_id" => $row['user_id'],
            "name"    => $row['name'],
            "email"   => $row['email']
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Invalid email or password."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid email or password."]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
