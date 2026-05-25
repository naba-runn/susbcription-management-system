<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

// Support GET for fetching, POST for marking as read
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_GET['user_id'])) {
        echo json_encode(["success" => false, "message" => "user_id is required."]);
        exit();
    }

    $user_id = (int)$_GET['user_id'];

    if ($user_id <= 0) {
        echo json_encode(["success" => false, "message" => "Invalid user_id."]);
        exit();
    }

    $stmt = mysqli_prepare($conn,
        "SELECT notification_id, message, created_at, is_read
         FROM notifications
         WHERE user_id = ?
         ORDER BY created_at DESC"
    );
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $notifications = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $notifications[] = [
            "notification_id" => (int)$row['notification_id'],
            "message"         => $row['message'],
            "created_at"      => $row['created_at'],
            "is_read"         => (bool)$row['is_read']
        ];
    }

    echo json_encode(["success" => true, "notifications" => $notifications]);
    mysqli_stmt_close($stmt);
} 
// Mark as read optionally
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!isset($data['notification_id'])) {
         echo json_encode(["success" => false, "message" => "notification_id required."]);
         exit();
    }
    $notif_id = (int)$data['notification_id'];
    $update = mysqli_prepare($conn, "UPDATE notifications SET is_read = 1 WHERE notification_id = ?");
    mysqli_stmt_bind_param($update, "i", $notif_id);
    mysqli_stmt_execute($update);
    echo json_encode(["success" => true]);
    mysqli_stmt_close($update);
}

mysqli_close($conn);
