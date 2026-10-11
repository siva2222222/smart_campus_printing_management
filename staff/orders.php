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
    $uploads = normalize_uploads("document");

    if ($service === "") {
        $msg = "Enter valid order details.";
    } elseif (count($uploads) === 0) {
        $msg = "Please upload at least one file.";
    } else {
        $allowed = ["pdf", "png", "jpg", "jpeg", "webp", "gif"];

        $stored_files = [];

        // =============================================
        // VALIDATE + READ EVERY UPLOADED FILE
        // =============================================

        foreach ($uploads as $file) {
            if ($file["error"] !== UPLOAD_ERR_OK) {
                $msg = "One of the files failed to upload. Please try again.";

                break;
            }

            $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

            // FILE TYPE
            if (!in_array($ext, $allowed, true)) {
                $msg =
                    "Allowed files: PDF, PNG, JPG, JPEG, WEBP and GIF. " .
                    "Invalid: " .
                    basename($file["name"]);

                break;
            }

            // FILE SIZE
            if ($file["size"] > UPLOAD_MAX_BYTES) {
                $msg = "Maximum file size is 500 MB per file.";

                break;
            }

            $file_data = file_get_contents($file["tmp_name"]);

            if ($file_data === false) {
                $msg = "Unable to read the uploaded document.";

                break;
            }

            $pages = document_page_count($file_data, $ext);

            $page_count += $pages;

            $stored_files[] = [
                "name" => basename($file["name"]),

                "data" => new MongoDB\BSON\Binary(
                    $file_data,
                    MongoDB\BSON\Binary::TYPE_GENERIC,
                ),

                "size" => (int) $file["size"],

                "extension" => $ext,

                "page_count" => $pages,
            ];
        }

        if (!$msg) {
            $base_amount = print_amount($page_count, $service);

            $page_rate = service_page_rate($service);

            $amount = $base_amount;

            try {
                $order_id = next_id("orders");

                $token = make_token();

                $created_by = (int) $_SESSION["user_id"];

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

                        "files" => $stored_files,

                        "file_count" => count($stored_files),

                        "file_name" => $stored_files[0]["name"],

                        "file_size" => $stored_files[0]["size"],

                        "file_extension" => $stored_files[0]["extension"],

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
    name="document[]"
    id="document"
    accept=".pdf,.png,.jpg,.jpeg,.webp,.gif"
    multiple
    required
>

<small class="muted">

PDF, PNG, JPG, JPEG, WEBP, GIF
•
Max 500 MB per file • Multiple files allowed

</small>

<div id="file_list" class="file-list"></div>

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

<?php if (count($orders) > 0): ?>

<small class="muted">
    <?= count($orders) ?> order<?= count($orders) === 1 ? "" : "s" ?>
</small>

<?php endif; ?>

</div>


<?php if (count($orders) === 0): ?>

<div class="order-empty">

<?= icon("clipboard", "xl") ?>

<b>
    No orders yet
</b>

<p>
    Orders created here will show up in this queue.
</p>

</div>


<?php else: ?>

<div class="order-list">

<?php foreach ($orders as $o): ?>

<div class="order-card">

<div class="order-main">


<div class="order-identity">

<div class="order-token-line">

<a
    class="order-token"
    href="../track.php?id=<?= (int) ($o["id"] ?? 0) ?>"
    title="Track this order"
>
<b>
<?= e($o["order_token"] ?? "") ?>
</b>
</a>

<span class="tag status-<?= strtolower($o["status"] ?? "pending") ?>">
<?= e($o["status"] ?? "Pending") ?>
</span>

</div>

<div class="order-service">
<?= e($o["service"] ?? "") ?>
</div>

<div class="order-sub">

<span><?= icon("file-text") ?> <?= (int) ($o["page_count"] ?? 0) ?> page<?= (int) ($o["page_count"] ?? 0) === 1 ? "" : "s" ?></span>

<span><?= icon("printer") ?> <?= e($o["print_side"] ?? "Single") ?></span>

</div>

</div>


<div class="order-files">

<?php $orderFiles = order_files($o); ?>

<?php if (count($orderFiles) === 0): ?>

<span class="muted">No document</span>

<?php else: ?>

<?php foreach ($orderFiles as $fi => $of): ?>

<a
    href="../download.php?id=<?= (int) $o["id"] ?>&file=<?= (int) $fi ?>"
    class="file-chip"
    title="Download <?= e($of["name"]) ?>"
>

<?= icon("file-text") ?>

<span><?= e($of["name"]) ?></span>

<?= icon("download") ?>

</a>

<?php endforeach; ?>

<?php endif; ?>

</div>


<div class="order-side">

<div class="order-total">
₹<?= number_format((float) ($o["amount"] ?? 0), 2) ?>
</div>

<div class="order-tags">

<span class="tag">Normal</span>

<span
    class="tag status-<?= strtolower($o["payment_status"] ?? "pending") ?>"
>
<?= e($o["payment_status"] ?? "Pending") ?>
</span>

</div>

<div class="order-actions">

<a
    class="btn small outline"
    href="../track.php?id=<?= (int) ($o["id"] ?? 0) ?>"
>
<?= icon("package") ?> Track
</a>

</div>

</div>


</div>

</div>

<?php endforeach; ?>

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


const fileList =
    document.getElementById('file_list');

let selectedFiles = [];


const FILE_ICON =
    '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" ' +
    'stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
    '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>' +
    '<polyline points="14 2 14 8 20 8"></polyline>' +
    '<line x1="16" y1="13" x2="8" y2="13"></line>' +
    '<line x1="16" y1="17" x2="8" y2="17"></line></svg>';


function formatSize(bytes) {

    if (bytes === undefined || bytes === null) {
        return '';
    }

    const units = ['B', 'KB', 'MB', 'GB'];

    let i = 0;
    let n = bytes;

    while (n >= 1024 && i < units.length - 1) {
        n /= 1024;
        i++;
    }

    return (i === 0 ? n : n.toFixed(1)) + ' ' + units[i];
}


function sameFile(a, b) {

    return (
        a.name === b.name &&
        a.size === b.size &&
        a.lastModified === b.lastModified
    );
}


function syncInput() {

    const dt =
        new DataTransfer();

    selectedFiles.forEach(
        function(file) { dt.items.add(file); }
    );

    documentInput.files = dt.files;
}


function removeFile(index) {

    selectedFiles.splice(index, 1);

    syncInput();

    refreshList();
}


async function refreshList() {

    if (fileList) {

        fileList.innerHTML = '';

        selectedFiles.forEach(function(file, index) {

            const row =
                document.createElement('div');

            row.className = 'file-item';

            const nameWrap =
                document.createElement('div');

            nameWrap.className = 'fi-name';

            nameWrap.innerHTML = FILE_ICON;

            const nameText =
                document.createElement('span');

            nameText.textContent = file.name;

            nameWrap.appendChild(nameText);

            const size =
                document.createElement('span');

            size.className = 'fi-size';

            size.textContent = formatSize(file.size);

            const remove =
                document.createElement('button');

            remove.type = 'button';

            remove.textContent = 'Remove';

            remove.className = 'btn small outline';

            remove.addEventListener('click', function(e) {

                e.preventDefault();

                e.stopPropagation();

                removeFile(index);
            });

            row.appendChild(nameWrap);

            row.appendChild(size);

            row.appendChild(remove);

            fileList.appendChild(row);
        });
    }

    let total = 0;

    for (const file of selectedFiles) {

        total += await countPages(file);
    }

    pageCount = total;

    updateTotal();
}


function addFiles(list) {

    Array.from(list || []).forEach(function(file) {

        if (
            !selectedFiles.some(
                function(f) { return sameFile(f, file); }
            )
        ) {

            selectedFiles.push(file);
        }
    });

    syncInput();

    refreshList();
}


documentInput.addEventListener(
    'change',
    function() {

        if (this.files && this.files.length) {

            addFiles(this.files);
        }
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