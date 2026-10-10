<?php

require_once "database.php";

require_login();

// =====================================================
// ALLOW STUDENT AND STAFF
// =====================================================

if (!in_array($_SESSION["role"], ["student", "staff"], true)) {
    http_response_code(403);
    exit("Access denied.");
}

// =====================================================
// GET ORDER
// =====================================================

$id = (int) ($_GET["id"] ?? 0);

$o = $conn->orders->findOne([
    "id" => $id,
]);

if (!$o) {
    die("Order not found.");
}

// =====================================================
// ACCESS CONTROL
// =====================================================

// STUDENT ORDER
if ($_SESSION["role"] === "student") {
    if (
        !isset($o["student_id"]) ||
        (int) $o["student_id"] !== (int) $_SESSION["user_id"]
    ) {
        http_response_code(403);
        exit("Access denied.");
    }
}

// STAFF ORDER
if ($_SESSION["role"] === "staff") {
    if (
        !isset($o["created_by"]) ||
        (int) $o["created_by"] !== (int) $_SESSION["user_id"]
    ) {
        http_response_code(403);
        exit("Access denied.");
    }
}

// =====================================================
// FREE / LOYALTY ORDER (no payment required)
// =====================================================

if (!empty($o["is_free"]) || (float) ($o["amount"] ?? 0) <= 0) {

    if (($o["payment_status"] ?? "") !== "Paid") {
        $conn->orders->updateOne(
            [
                "id" => $id,
            ],

            [
                '$set' => [
                    "payment_status" => "Paid",

                    "payment_txn_id" => "FREE" . $o["id"] . "_" . time(),

                    "payment_method" => "Loyalty (Free)",
                ],
            ],
        );
    }

    $back =
        $_SESSION["role"] === "staff"
            ? "staff/orders.php"
            : "student/orders.php";
    ?>

    <!doctype html>

    <html lang="en">

    <head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width,initial-scale=1"
    >

    <title>
        Free Order - Smart Campus
    </title>

    <link
        rel="stylesheet"
        href="css/style.css?v=<?= filemtime(__DIR__ . "/css/style.css") ?>"
    >

    </head>

    <body class="center">

    <div class="card result animate">

    <div class="successicon">
        <?= icon("check", "lg") ?>
    </div>

    <div class="brand">
        Smart<span>Campus</span>
    </div>

    <h2>
        Free Order Confirmed!
    </h2>

    <p class="muted">

        Congratulations! This is your loyalty order
        and it is completely free.

        <br>

        Token:
        <b><?= e($o["order_token"] ?? "") ?></b>

    </p>

    <a
        class="btn"
        href="<?= e($back) ?>"
    >

        View My Orders

        <?= icon("arrow-right") ?>

    </a>

    </div>

    </body>

    </html>

    <?php exit();
}

// =====================================================
// GET USER FOR PAYMENT PREFILL
// =====================================================

$user_id = null;

// Student order → student_id
if (
    isset($o["student_id"]) &&
    $o["student_id"] !== null &&
    (int) $o["student_id"] > 0
) {
    $user_id = (int) $o["student_id"];
}

// Staff order → created_by
elseif (isset($o["created_by"]) && (int) $o["created_by"] > 0) {
    $user_id = (int) $o["created_by"];
}

$u = null;

if ($user_id !== null) {
    $u = $conn->users->findOne([
        "id" => $user_id,
    ]);
}

// =====================================================
// FALLBACK USER INFORMATION
// =====================================================

if (!$u) {
    $u = [
        "name" => $_SESSION["name"] ?? "Smart Campus User",
        "email" => "",
    ];
}

// =====================================================
// PAYMENT DETAILS
// =====================================================

$txnid = "SCTXN" . $o["id"] . "_" . time();

$amount_paise = (int) round((float) $o["amount"] * 100);

$productinfo =
    ($o["service"] ?? "Printing") .
    (($o["order_type"] ?? "Normal") === "Emergency"
        ? " - Emergency Order"
        : "");

// =====================================================
// RAZORPAY ORDER
// =====================================================

$ch = curl_init(RAZORPAY_API_URL . "/orders");

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,

    CURLOPT_USERPWD => RAZORPAY_KEY_ID . ":" . RAZORPAY_KEY_SECRET,

    CURLOPT_POST => true,

    CURLOPT_POSTFIELDS => json_encode([
        "amount" => $amount_paise,

        "currency" => "INR",

        "receipt" => $txnid,

        "payment_capture" => 1,
    ]),

    CURLOPT_HTTPHEADER => ["Content-Type: application/json"],

    CURLOPT_TIMEOUT => 10,

    CURLOPT_CONNECTTIMEOUT => 5,
]);

$resp = curl_exec($ch);

$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);

$curl_error = curl_error($ch);

curl_close($ch);

// =====================================================
// RAZORPAY RESPONSE
// =====================================================

$rzp = json_decode($resp, true);

if (!$rzp || !isset($rzp["id"])) {
    die(
        "Payment gateway error. " .
            "Please check Razorpay configuration and try again."
    );
}

$razorpay_order_id = $rzp["id"];

// =====================================================
// SAVE PAYMENT TRANSACTION
// =====================================================

$conn->orders->updateOne(
    [
        "id" => $id,
    ],

    [
        '$set' => [
            "payment_txn_id" => $txnid,

            "razorpay_order_id" => $razorpay_order_id,
        ],
    ],
);
?>

<!doctype html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1"
>

<title>
    Payment - Smart Campus
</title>


<link
    rel="stylesheet"
    href="css/style.css?v=<?= filemtime(__DIR__ . "/css/style.css") ?>"
>


<script
    src="https://checkout.razorpay.com/v1/checkout.js"
></script>

</head>


<body class="center">


<div class="card payment-card animate">


<!-- =================================================
     BRAND
================================================== -->

<div class="brand">
    Smart<span>Campus</span>
</div>


<!-- =================================================
     SECURE BANNER
================================================== -->

<div class="secure-banner">

<?= icon("shield-check", "lg") ?>

<div>

<b>
    Secure Payment
</b>

<small>
    Your payment details are safe with us.
</small>

</div>

<span class="lock">

<?= icon("lock") ?>

</span>

</div>


<!-- =================================================
     ORDER SUMMARY
================================================== -->

<h2>
    Order Summary
</h2>


<div class="summary">


<div>

<span>
    <?= e($o["service"] ?? "Printing") ?>
</span>

<b>
₹<?= e(number_format((float) ($o["base_amount"] ?? ($o["amount"] ?? 0)), 2)) ?>
</b>

</div>


<!-- NORMAL DELIVERY -->

<div>

<span>
    Normal Delivery
</span>

<span class="free">
    FREE
</span>

</div>


<!-- PRINT SIDE -->

<div>

<span>
    Print Side
</span>

<b>
    <?= e($o["print_side"] ?? "Single") ?>
</b>

</div>


<!-- EMERGENCY -->

<div>

<span>
    Emergency Order
</span>

<span
    class="<?= ($o["order_type"] ?? "Normal") === "Emergency"
        ? "fee"
        : "free" ?>"
>

<?php if (($o["order_type"] ?? "Normal") === "Emergency"): ?>

₹<?= e(number_format((float) ($o["emergency_fee"] ?? 0), 2)) ?>

<?php else: ?>

Not selected

<?php endif; ?>

</span>

</div>


<hr>


<!-- TOTAL -->

<div class="total">

<b>
    Total Amount
</b>

<strong>

₹<?= e(number_format((float) ($o["amount"] ?? 0), 2)) ?>

</strong>

</div>


</div>


<!-- =================================================
     EMERGENCY BANNER
================================================== -->

<?php if (($o["order_type"] ?? "Normal") === "Emergency"): ?>

<div class="priority-banner">

<?= icon("alert", "lg") ?>

<div>

<b>
    Emergency Order Selected
</b>

<small>
    Priority printing • Target handling within 1 hour*
</small>

</div>

</div>

<?php endif; ?>


<!-- =================================================
     PAYMENT
================================================== -->

<h2>
    Payment
</h2>


<div style="text-align:center">

<span
    style="
        display:inline-block;
        font-size:15px;
        font-weight:900;
        color:#fff;
        background:var(--gradient-primary);
        padding:10px 22px;
        border-radius:var(--r-sm);
        letter-spacing:1px;
    "
>

RAZORPAY

</span>

</div>


<br>


<button
    class="btn full"
    id="rzp-btn"
    type="button"
>

<?= icon("lock") ?>

Pay ₹<?= e(number_format((float) $o["amount"], 2)) ?>

Securely

</button>


<p class="tiny">
    Sandbox mode. Use any test card.
</p>


</div>


<script>


// =====================================================
// RAZORPAY OPTIONS
// =====================================================

var options = {

    key:
        <?= json_encode(RAZORPAY_KEY_ID) ?>,

    amount:
        <?= (int) $amount_paise ?>,

    currency:
        "INR",

    name:
        "Smart Campus",

    description:
        <?= json_encode($productinfo) ?>,

    order_id:
        <?= json_encode($razorpay_order_id) ?>,


    // ================================================
    // SUCCESS
    // ================================================

    handler: function(response) {

        window.location.href =
            "payment_success.php" +

            "?razorpay_payment_id=" +
            encodeURIComponent(
                response.razorpay_payment_id
            ) +

            "&razorpay_order_id=" +
            encodeURIComponent(
                response.razorpay_order_id
            ) +

            "&razorpay_signature=" +
            encodeURIComponent(
                response.razorpay_signature
            ) +

            "&txnid=" +
            encodeURIComponent(
                <?= json_encode($txnid) ?>
            );
    },


    // ================================================
    // PREFILL
    // ================================================

    prefill: {

        name:
            <?= json_encode((string) ($u["name"] ?? "")) ?>,

        email:
            <?= json_encode((string) ($u["email"] ?? "")) ?>,

        contact:
            "9999999999"
    },


    // ================================================
    // NOTES
    // ================================================

    notes: {

        order_token:
            <?= json_encode((string) ($o["order_token"] ?? "")) ?>,

        order_type:
            <?= json_encode((string) ($o["order_type"] ?? "Normal")) ?>
    },


    theme: {
        color: "#5278FF"
    }

};


// =====================================================
// CREATE RAZORPAY
// =====================================================

var rzp =
    new Razorpay(options);


// =====================================================
// OPEN CHECKOUT
// =====================================================

document
    .getElementById('rzp-btn')
    .addEventListener(
        'click',
        function(e) {

            e.preventDefault();

            rzp.open();

        }
    );


// =====================================================
// PAYMENT FAILED
// =====================================================

rzp.on(
    'payment.failed',
    function(response) {

        var description =
            response &&
            response.error &&
            response.error.description
                ? response.error.description
                : 'Payment failed';


        window.location.href =
            "payment_failure.php" +

            "?txnid=" +
            encodeURIComponent(
                <?= json_encode($txnid) ?>
            ) +

            "&error=" +
            encodeURIComponent(
                description
            );
    }
);

</script>


</body>

</html>