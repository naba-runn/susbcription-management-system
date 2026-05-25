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

// Fetch invoices for all subscriptions belonging to the user
$stmt = mysqli_prepare($conn,
    "SELECT i.invoice_id, i.sub_id, i.amount, i.generated_date, p.name AS plan_name
     FROM invoices i
     JOIN subscriptions s ON i.sub_id = s.sub_id
     JOIN plans p ON s.plan_id = p.plan_id
     WHERE s.user_id = ?
     ORDER BY i.generated_date DESC, i.invoice_id DESC"
);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$invoices = [];
while ($row = mysqli_fetch_assoc($result)) {
    $invoices[] = [
        "invoice_id"     => (int)$row['invoice_id'],
        "sub_id"         => (int)$row['sub_id'],
        "plan_name"      => $row['plan_name'],
        "amount"         => (float)$row['amount'],
        "generated_date" => $row['generated_date']
    ];
}

echo json_encode(["success" => true, "invoices" => $invoices]);

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
