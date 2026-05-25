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

// Auto-expire logic: update expired subscriptions first
$select_expire = mysqli_prepare($conn, 
    "SELECT s.sub_id, p.name FROM subscriptions s JOIN plans p ON s.plan_id = p.plan_id WHERE s.user_id = ? AND s.status = 'ACTIVE' AND s.end_date < CURDATE()"
);
mysqli_stmt_bind_param($select_expire, "i", $user_id);
mysqli_stmt_execute($select_expire);
$res_expire = mysqli_stmt_get_result($select_expire);

$now_datetime = date('Y-m-d H:i:s');
$notif_stmt = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, ?)");
while ($row = mysqli_fetch_assoc($res_expire)) {
    $msg = "Your subscription to {$row['name']} has expired.";
    mysqli_stmt_bind_param($notif_stmt, "iss", $user_id, $msg, $now_datetime);
    mysqli_stmt_execute($notif_stmt);
}
if (isset($notif_stmt) && $notif_stmt) { mysqli_stmt_close($notif_stmt); }
mysqli_stmt_close($select_expire);

$expire_stmt = mysqli_prepare($conn,
    "UPDATE subscriptions SET status = 'EXPIRED' WHERE user_id = ? AND status = 'ACTIVE' AND end_date < CURDATE()"
);
mysqli_stmt_bind_param($expire_stmt, "i", $user_id);
mysqli_stmt_execute($expire_stmt);
mysqli_stmt_close($expire_stmt);

// Fetch subscriptions joined with plan name
$stmt = mysqli_prepare($conn,
    "SELECT s.sub_id, p.name AS plan_name, s.status, s.start_date, s.end_date, s.auto_renew, p.price
     FROM subscriptions s
     JOIN plans p ON s.plan_id = p.plan_id
     WHERE s.user_id = ?
     ORDER BY s.sub_id DESC"
);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$subscriptions = [];
while ($row = mysqli_fetch_assoc($result)) {
    $subscriptions[] = [
        "sub_id"     => (int)$row['sub_id'],
        "plan_name"  => $row['plan_name'],
        "status"     => $row['status'],
        "start_date" => $row['start_date'],
        "end_date"   => $row['end_date'],
        "auto_renew" => (bool)$row['auto_renew'],
        "price"      => (float)$row['price']
    ];
}

echo json_encode(["success" => true, "subscriptions" => $subscriptions]);

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
