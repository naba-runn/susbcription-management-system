<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "subscription_system2";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode(["success" => false, "message" => "Database connection failed: " . mysqli_connect_error()]);
    exit();
}

mysqli_set_charset($conn, "utf8mb4");