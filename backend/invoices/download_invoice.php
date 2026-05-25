<?php
require_once '../config/database.php';

if (!isset($_GET['invoice_id']) || !isset($_GET['user_id'])) {
    die("Missing parameters.");
}

$invoice_id = (int)$_GET['invoice_id'];
$user_id = (int)$_GET['user_id'];

// Fetch invoice and user details
$stmt = mysqli_prepare($conn, 
    "SELECT i.invoice_id, i.amount, i.generated_date, p.name AS plan_name, u.name AS user_name, u.email 
     FROM invoices i 
     JOIN subscriptions s ON i.sub_id = s.sub_id 
     JOIN plans p ON s.plan_id = p.plan_id 
     JOIN users u ON s.user_id = u.user_id 
     WHERE i.invoice_id = ? AND s.user_id = ?"
);
mysqli_stmt_bind_param($stmt, "ii", $invoice_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$invoice = mysqli_fetch_assoc($result);

if (!$invoice) {
    die("Invoice not found or unauthorized.");
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Invoice #INV-<?= str_pad($invoice['invoice_id'], 4, '0', STR_PAD_LEFT) ?></title>
  <style>
    body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background: #fff; color: #333; margin: 0; padding: 40px; }
    .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, 0.05); }
    .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; }
    .header .logo { font-size: 28px; font-weight: bold; color: #6c63ff; }
    .header .details { text-align: right; color: #555; }
    .title { font-size: 24px; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 10px; }
    .info { display: flex; justify-content: space-between; margin-bottom: 40px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
    th { background: #f9f9f9; font-weight: bold; color: #555; }
    .total { font-size: 20px; font-weight: bold; text-align: right; padding-top: 20px; color: #6c63ff; }
    .footer { text-align: center; margin-top: 50px; font-size: 12px; color: #888; }
    @media print {
       body { padding: 0; }
       .invoice-box { border: none; box-shadow: none; }
    }
  </style>
</head>
<body onload="window.print()">
  <div class="invoice-box">
    <div class="header">
      <div class="logo">SubMS Inc.</div>
      <div class="details">
        INVOICE #INV-<?= str_pad($invoice['invoice_id'], 4, '0', STR_PAD_LEFT) ?><br>
        Date: <?= date('d M Y', strtotime($invoice['generated_date'])) ?>
      </div>
    </div>
    
    <div class="info">
      <div>
        <strong>Billed To:</strong><br>
        <?= htmlspecialchars($invoice['user_name']) ?><br>
        <?= htmlspecialchars($invoice['email']) ?>
      </div>
      <div style="text-align: right;">
        <strong>From:</strong><br>
        SubMS Operations<br>
        contact@subms.local
      </div>
    </div>

    <div class="title">Invoice Details</div>
    
    <table>
      <thead>
        <tr>
          <th>Description</th>
          <th style="text-align: right;">Amount Paid</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Subscription Plan: <strong><?= htmlspecialchars($invoice['plan_name']) ?></strong></td>
          <td style="text-align: right;">₹<?= number_format($invoice['amount'], 2) ?></td>
        </tr>
      </tbody>
    </table>

    <div class="total">
      Total Paid: ₹<?= number_format($invoice['amount'], 2) ?>
    </div>

    <div class="footer">
      Thank you for your business. This is a system generated document.
    </div>
  </div>
</body>
</html>
