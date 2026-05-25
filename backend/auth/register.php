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

if (!isset($data['name']) || !isset($data['email']) || !isset($data['password'])) {
    echo json_encode(["success" => false, "message" => "All fields are required."]);
    exit();
}

$name     = trim($data['name']);
$email    = trim($data['email']);
$password = $data['password'];

if (empty($name) || empty($email) || empty($password)) {
    echo json_encode(["success" => false, "message" => "Fields cannot be empty."]);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email format."]);
    exit();
}

// Check if email already exists
$check = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ?");
mysqli_stmt_bind_param($check, "s", $email);
mysqli_stmt_execute($check);
mysqli_stmt_store_result($check);

if (mysqli_stmt_num_rows($check) > 0) {
    echo json_encode(["success" => false, "message" => "Email already registered."]);
    mysqli_stmt_close($check);
    exit();
}
mysqli_stmt_close($check);

$hashed = password_hash($password, PASSWORD_DEFAULT);

$stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($stmt, "sss", $name, $email, $hashed);

if (mysqli_stmt_execute($stmt)) {
    $user_id = mysqli_insert_id($conn);
    echo json_encode([
        "success" => true,
        "message" => "Registration successful.",
        "user_id" => $user_id,
        "name"    => $name,
        "email"   => $email
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Registration failed. Please try again."]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
