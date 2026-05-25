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

if (!isset($data['user_id']) || !isset($data['plan_id']) || !isset($data['method_id'])) {
    echo json_encode(["success" => false, "message" => "user_id, plan_id, and method_id are required."]);
    exit();
}

$user_id = (int)$data['user_id'];
$plan_id = (int)$data['plan_id'];
$method_id = (int)$data['method_id'];
$coupon_code = isset($data['coupon_code']) ? strtoupper(trim($data['coupon_code'])) : null;

if ($user_id <= 0 || $plan_id <= 0 || $method_id <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid user_id, plan_id, or method_id."]);
    exit();
}

// Fetch plan duration
$plan_stmt = mysqli_prepare($conn, "SELECT name, price, duration_days FROM plans WHERE plan_id = ?");
mysqli_stmt_bind_param($plan_stmt, "i", $plan_id);
mysqli_stmt_execute($plan_stmt);
$plan_result = mysqli_stmt_get_result($plan_stmt);
$plan = mysqli_fetch_assoc($plan_result);
mysqli_stmt_close($plan_stmt);

if (!$plan) {
    echo json_encode(["success" => false, "message" => "Plan not found."]);
    exit();
}

$duration_days = (int)$plan['duration_days'];
$start_date    = date('Y-m-d');
$end_date      = date('Y-m-d', strtotime("+{$duration_days} days"));
$status        = "ACTIVE";
$auto_renew    = 0;

// Check if user already has an ACTIVE subscription for this plan
$check = mysqli_prepare($conn, "SELECT sub_id FROM subscriptions WHERE user_id = ? AND plan_id = ? AND status = 'ACTIVE'");
mysqli_stmt_bind_param($check, "ii", $user_id, $plan_id);
mysqli_stmt_execute($check);
mysqli_stmt_store_result($check);

if (mysqli_stmt_num_rows($check) > 0) {
    echo json_encode(["success" => false, "message" => "You already have an active subscription for this plan."]);
    mysqli_stmt_close($check);
    exit();
}
mysqli_stmt_close($check);

$final_price = (float)$plan['price'];
$discount_applied = 0;

if ($coupon_code) {
    $coup_stmt = mysqli_prepare($conn, "SELECT discount_percent, valid_until FROM coupons WHERE code = ?");
    mysqli_stmt_bind_param($coup_stmt, "s", $coupon_code);
    mysqli_stmt_execute($coup_stmt);
    $coup_res = mysqli_stmt_get_result($coup_stmt);
    if ($coup_row = mysqli_fetch_assoc($coup_res)) {
        if (strtotime($coup_row['valid_until']) >= strtotime(date('Y-m-d'))) {
            $discount_applied = (int)$coup_row['discount_percent'];
            $final_price = $final_price - ($final_price * ($discount_applied / 100));
        }
    }
    mysqli_stmt_close($coup_stmt);
}

$meth_stmt = mysqli_prepare($conn, "SELECT type, details FROM payment_methods WHERE method_id = ? AND user_id = ?");
mysqli_stmt_bind_param($meth_stmt, "ii", $method_id, $user_id);
mysqli_stmt_execute($meth_stmt);
$meth_res = mysqli_stmt_get_result($meth_stmt);
$method_info = mysqli_fetch_assoc($meth_res);
mysqli_stmt_close($meth_stmt);

if (!$method_info) {
    echo json_encode(["success" => false, "message" => "Invalid payment method."]);
    exit();
}

$stmt = mysqli_prepare($conn, "INSERT INTO subscriptions (user_id, plan_id, start_date, end_date, status, auto_renew) VALUES (?, ?, ?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt, "iisssi", $user_id, $plan_id, $start_date, $end_date, $status, $auto_renew);

if (mysqli_stmt_execute($stmt)) {
    $sub_id = mysqli_insert_id($conn);
    $now_datetime = date('Y-m-d H:i:s');
    $now_date = date('Y-m-d');

    // Create Payment record showing the linked method_id
    $pay_stmt = mysqli_prepare($conn, "INSERT INTO payments (user_id, sub_id, amount, payment_date, status, method_id) VALUES (?, ?, ?, ?, 'SUCCESS', ?)");
    mysqli_stmt_bind_param($pay_stmt, "iidsi", $user_id, $sub_id, $final_price, $now_datetime, $method_id);
    mysqli_stmt_execute($pay_stmt);
    mysqli_stmt_close($pay_stmt);

    // Create Invoice record
    $inv_stmt = mysqli_prepare($conn, "INSERT INTO invoices (sub_id, amount, generated_date) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($inv_stmt, "ids", $sub_id, $final_price, $now_date);
    mysqli_stmt_execute($inv_stmt);
    mysqli_stmt_close($inv_stmt);

    // Create Notification record
    $msg = "You successfully subscribed to {$plan['name']} for ₹{$final_price}";
    if ($discount_applied > 0) $msg .= " ({$discount_applied}% off via {$coupon_code}). ";
    else $msg .= ". ";
    $msg .= "Paid using {$method_info['type']}.";

    $notif_stmt = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($notif_stmt, "iss", $user_id, $msg, $now_datetime);
    mysqli_stmt_execute($notif_stmt);
    mysqli_stmt_close($notif_stmt);

    // Create Reminder record
    $remind_stmt = mysqli_prepare($conn, "INSERT INTO reminders (sub_id, remind_before_days) VALUES (?, 3)");
    mysqli_stmt_bind_param($remind_stmt, "i", $sub_id);
    mysqli_stmt_execute($remind_stmt);
    mysqli_stmt_close($remind_stmt);

    echo json_encode([
        "success"    => true,
        "message"    => "Subscription activated successfully.",
        "sub_id"     => $sub_id,
        "start_date" => $start_date,
        "end_date"   => $end_date
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Subscription failed. Please try again."]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
