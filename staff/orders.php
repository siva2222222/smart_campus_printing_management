<?php

require_once "../database.php";
require_role("staff");

$msg = "";

// Price per printed page (₹); PDF pages counted automatically.
$page_rate = defined("PAGE_RATE") ? (float) PAGE_RATE : 2.0;

$service_rates = defined("SERVICE_PAGE_RATES") ? SERVICE_PAGE_RATES : [];

// =====================================================
// CREATE ORDER
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $service = trim($_POST["service"] ?? "");

    // Staff orders are always Normal
    $order_type = "Normal";
    $emergency_fee = 0.0;

    // Print side: single-sided or front & back (duplex)
    $print_side =
        ($_POST["print_side"] ?? "") === "Front & Back"
            ? "Front & Back"
            : "Single";

    // Amount is calculated from the document page count
    // at PAGE_RATE (₹2) per page. Filled after the file
    // is read below.
    $page_count = 0;
    $base_amount = 0.0;
    $amount = 0.0;

    // =================================================
    // BASIC VALIDATION
    // =================================================

    if ($service === "") {
        $msg = "Enter valid order details.";
    } elseif (
        !isset($_FILES["document"]) ||
        $_FILES["document"]["error"] !== UPLOAD_ERR_OK
    ) {
        $msg = "Please upload a file.";
    } else {
        $file = $_FILES["document"];

        $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

        // =============================================
        // FILE TYPE
        // =============================================

        $allowed = ["pdf", "png", "jpg", "jpeg", "webp", "gif"];

        if (!in_array($ext, $allowed, true)) {
            $msg = "Allowed files: PDF, PNG, JPG, JPEG, WEBP and GIF.";
        }

        // =============================================
        // FILE SIZE
        // =============================================
        elseif ($file["size"] > UPLOAD_MAX_BYTES) {
            $msg = "Maximum file size is 500 MB.";
        }

        // =============================================
        // SAVE ORDER
        // =============================================
        else {
            $file_data = file_get_contents($file["tmp_name"]);

            if ($file_data === false) {
                $msg = "Unable to read the uploaded document.";
            } else {
                // =====================================
                // CALCULATE AMOUNT FROM PAGE COUNT
                // =====================================

                $page_count = document_page_count($file_data, $ext);

                $base_amount = print_amount($page_count, $service);

                $page_rate = service_page_rate($service);

                $amount = $base_amount;

                try {
                    $order_id = next_id("orders");

                    $token = make_token();

                    $created_by = (int) $_SESSION["user_id"];

                    $file_name = basename($file["name"]);

                    // =================================
                    // ORDER DOCUMENT
                    // =================================

                    $doc = [
                        "id" => $order_id,

                        "order_token" => $token,

                        // No student required
                        "student_id" => null,

                        // Logged-in staff member
                        "created_by" => $created_by,

                        "service" => $service,

                        "page_count" => $page_count,

                        "page_rate" => $page_rate,

                        "base_amount" => $base_amount,

                        "emergency_fee" => 0.0,

                        "amount" => $amount,

                        "order_type" => "Normal",

                        "print_side" => $print_side,

                        "file_name" => $file_name,

                        "file_data" => new MongoDB\BSON\Binary(
                            $file_data,
                            MongoDB\BSON\Binary::TYPE_GENERIC,
                        ),

                        "file_size" => (int) $file["size"],

                        "file_extension" => $ext,

                        "payment_status" => "Pending",

                        "status" => "Pending",

                        "payment_txn_id" => null,

                        "created_at" => date("Y-m-d H:i:s"),
                    ];

                    // =================================
                    // INSERT
                    // =================================

                    $conn->orders->insertOne($doc);

                    // =================================
                    // PAYMENT
                    // =================================

                    header("Location: ../payment.php?id=" . $order_id);

                    exit();
                } catch (Throwable $e) {
                    $msg = "Unable to create order: " . $e->getMessage();
                }
            }
        }
    }
}

// =====================================================
// STAFF ORDERS
// =====================================================

$orders = iterator_to_array(
    $conn->orders->find(
        [
            "created_by" => (int) $_SESSION["user_id"],
        ],

        [
            "sort" => [
                "id" => -1,
            ],
        ],
    ),
);

// =====================================================
// PAGE SETTINGS
// =====================================================

$base = "../";
$active = "orders";
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
    Staff Orders - Smart Campus
</title>

<link
    rel="stylesheet"
    href="../css/style.css?v=<?= filemtime(__DIR__ . "/../css/style.css") ?>"
>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>

</head>


<body>

<div class="layout">

<?php include __DIR__ . "/../partials/sidebar.php"; ?>


<main>


<header class="topbar">

<div class="topbar-title">

<div class="page-icon">
    <?= icon("clipboard", "lg") ?>
</div>

<div>

<h1>
    Staff Order Booking
</h1>

<p class="muted">
    Staff can create normal print orders.
    Only Admin can change the official tracking status.
</p>

</div>

</div>


<div class="avatar">

<?= strtoupper(substr($_SESSION["name"] ?? "S", 0, 1)) ?>

</div>

</header>


<div class="content">


<!-- =================================================
     CREATE ORDER
================================================== -->

<div class="card animate">

<?php if ($msg): ?>

<div class="alert bad">

<?= icon("alert") ?>

<?= e($msg) ?>

</div>

<?php endif; ?>


<form
    method="post"
    enctype="multipart/form-data"
    class="booking-grid"
>


<!-- SERVICE -->

<div>

<label>
    Service
</label>

<select
    name="service"
    required
>

<option value="">
    Select Service
</option>

<option value="Black & White Printing">
    Black &amp; White Printing
</option>

<option value="Color Printing">
    Color Printing
</option>

<option value="Spiral Binding">
    Spiral Binding
</option>

<option value="Stationery">
    Stationery
</option>

</select>

</div>


<!-- AMOUNT (AUTO-CALCULATED) -->

<div>

<label>
    Printing Amount (₹)
</label>

<input
    type="number"
    name="amount"
    id="amount"
    min="0"
    step="0.01"
    readonly
    value="0.00"
>

<small class="muted">

Calculated automatically at
₹<?= number_format($page_rate, 2) ?>
per page • Pages:
<span id="pageCount">0</span>

</small>

</div>


<!-- PRIORITY -->

<div>

<label>
    Order Priority
</label>

<div
    style="
        padding:12px 14px;
        border:1px solid var(--border);
        border-radius:var(--r-md);
        background:var(--panel);
    "
>

<b>
    Normal Order
</b>

<div
    class="muted"
    style="font-size:12px;margin-top:3px"
>

Staff orders use Normal delivery only.

</div>

</div>

</div>


<!-- PRINT SIDE -->

<div>

<label>
    Print Side
</label>

<select
    name="print_side"
    id="print_side"
>

<option value="Single">
    Single Side
</option>

<option value="Front & Back">
    Front &amp; Back (Both Sides)
</option>

</select>

</div>


<!-- DOCUMENT -->

<div>

<label>
    Document / Image
</label>

<input
    type="file"
    name="document"
    id="document"
    accept=".pdf,.png,.jpg,.jpeg,.webp,.gif"
    required
>

<small class="muted">

PDF, PNG, JPG, JPEG, WEBP, GIF
•
Max 500 MB

</small>

</div>


<!-- BUTTON -->

<div style="grid-column:1/-1">

<button
    class="btn full"
    type="submit"
>

Create Order &amp; Continue to Razorpay

<?= icon("arrow-right") ?>

</button>

</div>


</form>

</div>


<!-- =================================================
     ORDER QUEUE
================================================== -->

<div class="card animate-1">

<div class="card-head">

<h2>
    Order Queue
</h2>

</div>


<?php if (count($orders) === 0): ?>

<div class="empty-state">

<?= icon("clipboard", "xl") ?>

<b>
    No orders yet
</b>

<p>
    Orders created here will show up in this queue.
</p>

</div>


<?php else: ?>


<div class="tablewrap">

<table>

<tr>

<th>Token</th>
<th>Service</th>
<th>Document</th>
<th>Priority</th>
<th>Total</th>
<th>Payment</th>
<th>Status</th>

</tr>


<?php foreach ($orders as $o): ?>

<tr>


<td>

<a
    class="order-token"
    href="../track.php?id=<?= (int) ($o["id"] ?? 0) ?>"
    title="Track this order"
>
<b>
<?= e($o["order_token"] ?? "") ?>
</b>
</a>

</td>


<td>

<?= e($o["service"] ?? "") ?>

</td>


<td>

<?php if (!empty($o["file_name"])): ?>

<?php $ext = strtoupper(pathinfo($o["file_name"], PATHINFO_EXTENSION)); ?>

<a
    href="../download.php?id=<?= (int) $o["id"] ?>"
    class="btn small outline"
    style="
        gap:5px;
        padding:5px 10px;
        font-size:12px;
    "
>

<?= icon("file-text") ?>

<span>
<?= e($ext) ?>
</span>

<?= icon("download") ?>

</a>

<?php else: ?>

<span class="muted">
—
</span>

<?php endif; ?>

</td>


<td>

<span class="tag">
Normal
</span>

</td>


<td>

₹<?= number_format((float) ($o["amount"] ?? 0), 2) ?>

</td>


<td>

<span
    class="tag status-<?= strtolower($o["payment_status"] ?? "pending") ?>"
>

<?= e($o["payment_status"] ?? "Pending") ?>

</span>

</td>


<td>

<span
    class="tag status-<?= strtolower($o["status"] ?? "pending") ?>"
>

<?= e($o["status"] ?? "Pending") ?>

</span>

</td>


</tr>

<?php endforeach; ?>


</table>

</div>

<?php endif; ?>


</div>


</div>

</main>

</div>


<script>

/*
 * =====================================================
 * STAFF ORDER AMOUNT CALCULATION
 * Amount = pages × PAGE_RATE
 * =====================================================
 */

const amountInput =
    document.getElementById('amount');

const pageCountDisplay =
    document.getElementById('pageCount');

const documentInput =
    document.getElementById('document');

const serviceSelect =
    document.querySelector(
        'select[name="service"]'
    );

const defaultRate =
    <?= json_encode($page_rate) ?>;

const serviceRates =
    <?= json_encode($service_rates) ?>;


function currentRate() {

    const service =
        serviceSelect ? serviceSelect.value : '';

    if (
        service &&
        Object.prototype.hasOwnProperty
            .call(serviceRates, service)
    ) {

        return parseFloat(serviceRates[service]) || 0;
    }

    return defaultRate;
}


async function countPages(file) {

    if (!file) {
        return 0;
    }

    const name =
        (file.name || '').toLowerCase();


    if (!name.endsWith('.pdf')) {
        return 1;
    }


    if (
        window.pdfjsLib &&
        typeof window.pdfjsLib.getDocument === 'function'
    ) {

        try {

            if (window.pdfjsLib.GlobalWorkerOptions) {

                window.pdfjsLib.GlobalWorkerOptions
                    .workerSrc =
                    'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
            }


            const buffer =
                await file.arrayBuffer();


            const pdf =
                await window.pdfjsLib
                    .getDocument({ data: buffer })
                    .promise;


            return pdf.numPages || 0;

        } catch (e) {

            return 0;
        }
    }


    return 0;
}


let pageCount = 0;


function updateTotal() {

    const total =
        pageCount * currentRate();

    amountInput.value =
        total.toFixed(2);

    if (pageCountDisplay) {

        pageCountDisplay.textContent =
            pageCount;
    }
}


documentInput.addEventListener(
    'change',
    async function() {

        const file =
            this.files && this.files[0]
                ? this.files[0]
                : null;

        pageCount =
            await countPages(file);

        updateTotal();
    }
);


if (serviceSelect) {

    serviceSelect.addEventListener(
        'change',
        updateTotal
    );
}


updateTotal();

</script>


</body>

</html>