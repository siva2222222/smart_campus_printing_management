<?php
require_once "database.php";
require_login();
$razorpay_payment_id = $_GET["razorpay_payment_id"] ?? "";
$razorpay_order_id = $_GET["razorpay_order_id"] ?? "";
$razorpay_signature = $_GET["razorpay_signature"] ?? "";
$txnid = $_GET["txnid"] ?? "";

$expected = hash_hmac(
    "sha256",
    $razorpay_order_id . "|" . $razorpay_payment_id,
    RAZORPAY_KEY_SECRET,
);
$valid = hash_equals($expected, $razorpay_signature);

if ($valid && $razorpay_order_id && $razorpay_payment_id) {
    $conn->orders->updateOne(
        ["payment_txn_id" => $txnid],
        ['$set' => ["payment_status" => "Paid"]],
    );
    $msg = "Payment successful!";
} else {
    $msg = "Payment verification failed.";
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Payment Result - Smart Campus</title><link rel="stylesheet" href="css/style.css?v=<?= filemtime(
    __DIR__ . "/css/style.css",
) ?>"></head><body class="center"><div class="card result animate"><div class="successicon<?= $valid
    ? ""
    : " badicon" ?>"><?= $valid
    ? icon("check", "lg")
    : icon("x", "lg") ?></div><h2><?= e(
    $msg,
) ?></h2><p class="muted">Transaction: <?= e(
    $txnid,
) ?></p><a class="btn" href="<?= $_SESSION["role"] === "staff"
    ? "staff/orders.php"
    : "student/orders.php" ?>">View My Orders <?= icon(
    "arrow-right",
) ?></a></div></body></html>