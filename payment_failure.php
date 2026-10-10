<?php
require_once "database.php";
require_login();
$txnid = $_GET["txnid"] ?? "";
if ($txnid) {
    $conn->orders->updateOne(
        ["payment_txn_id" => $txnid],
        ['$set' => ["payment_status" => "Failed"]],
    );
}
$error = $_GET["error"] ?? "";
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Payment Failed - Smart Campus</title><link rel="stylesheet" href="css/style.css?v=<?= filemtime(
    __DIR__ . "/css/style.css",
) ?>"></head><body class="center"><div class="card result animate"><div class="successicon badicon"><?= icon(
    "x",
    "lg",
) ?></div><h2>Payment Failed</h2><p class="muted"><?= e(
    $error,
) ?></p><a class="btn" href="<?= $_SESSION["role"] === "staff"
    ? "staff/orders.php"
    : "student/orders.php" ?>"><?= icon(
    "chevron-left",
) ?> Back to Orders</a></div></body></html>