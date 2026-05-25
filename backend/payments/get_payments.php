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

$stmt = mysqli_prepare($conn,
    "SELECT p.payment_id, p.sub_id, p.amount, p.payment_date, p.status, p.method_id, 
            pl.name AS plan_name, pm.type AS method_type, pm.details AS method_details
     FROM payments p
     LEFT JOIN subscriptions s ON p.sub_id = s.sub_id
     LEFT JOIN plans pl ON s.plan_id = pl.plan_id
     LEFT JOIN payment_methods pm ON p.method_id = pm.method_id
     WHERE p.user_id = ?
     ORDER BY p.payment_date DESC"
);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$payments = [];
while ($row = mysqli_fetch_assoc($result)) {
    $payments[] = [
        "payment_id"     => (int)$row['payment_id'],
        "sub_id"         => (int)$row['sub_id'],
        "plan_name"      => $row['plan_name'] ?? 'Unknown Plan',
        "amount"         => (float)$row['amount'],
        "payment_date"   => $row['payment_date'],
        "status"         => $row['status'],
        "method_type"    => $row['method_type'],
        "method_details" => $row['method_details']
    ];
}

echo json_encode(["success" => true, "payments" => $payments]);

mysqli_stmt_close($stmt);
mysqli_close($conn);
