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

if (!isset($data['sub_id']) || !isset($data['user_id'])) {
    echo json_encode(["success" => false, "message" => "sub_id and user_id are required."]);
    exit();
}

$sub_id  = (int)$data['sub_id'];
$user_id = (int)$data['user_id'];

if ($sub_id <= 0 || $user_id <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid sub_id or user_id."]);
    exit();
}

// Ensure subscription belongs to this user and is ACTIVE
$check = mysqli_prepare($conn,
    "SELECT sub_id FROM subscriptions WHERE sub_id = ? AND user_id = ? AND status = 'ACTIVE'"
);
mysqli_stmt_bind_param($check, "ii", $sub_id, $user_id);
mysqli_stmt_execute($check);
mysqli_stmt_store_result($check);

if (mysqli_stmt_num_rows($check) === 0) {
    echo json_encode(["success" => false, "message" => "Subscription not found or already cancelled/expired."]);
    mysqli_stmt_close($check);
    exit();
}
mysqli_stmt_close($check);

$stmt = mysqli_prepare($conn,
    "UPDATE subscriptions SET status = 'CANCELLED' WHERE sub_id = ? AND user_id = ?"
);
mysqli_stmt_bind_param($stmt, "ii", $sub_id, $user_id);

if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
    $now_datetime = date('Y-m-d H:i:s');
    $msg = "Your subscription was successfully cancelled.";
    $notif_stmt = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($notif_stmt, "iss", $user_id, $msg, $now_datetime);
    mysqli_stmt_execute($notif_stmt);
    mysqli_stmt_close($notif_stmt);

    echo json_encode(["success" => true, "message" => "Subscription cancelled successfully."]);
} else {
    echo json_encode(["success" => false, "message" => "Cancellation failed. Please try again."]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
