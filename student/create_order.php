<?php

require_once "../database.php";

require_role("student");

$msg = "";
$success = "";

// =====================================================
// SETTINGS
// =====================================================

$allowed_extensions = ["pdf", "png", "jpg", "jpeg", "webp", "gif"];

$max_file_size = 500 * 1024 * 1024; // 500 MB

$emergency_fee = defined("EMERGENCY_FEE") ? (float) EMERGENCY_FEE : 10.0;

$page_rate = defined("PAGE_RATE") ? (float) PAGE_RATE : 2.0;

$service_rates = defined("SERVICE_PAGE_RATES") ? SERVICE_PAGE_RATES : [];

$free_order_every = defined("FREE_ORDER_EVERY") ? (int) FREE_ORDER_EVERY : 11;

// =====================================================
// FORM SUBMISSION
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $service = trim($_POST["service"] ?? "");

    $order_type =
        ($_POST["order_type"] ?? "Normal") === "Emergency"
            ? "Emergency"
            : "Normal";

    // Print side: single-sided or front & back (duplex)
    $print_side =
        ($_POST["print_side"] ?? "") === "Front & Back"
            ? "Front & Back"
            : "Single";

    // Emergency fee
    $selected_emergency_fee =
        $order_type === "Emergency" ? $emergency_fee : 0.0;

    // Amount is calculated from the document page count
    // at PAGE_RATE (₹2) per page. Filled after the file
    // is read below.
    $page_count = 0;
    $base_amount = 0.0;
    $amount = 0.0;

    // =================================================
    // VALIDATION
    // =================================================
    $uploads = normalize_uploads("document");

    if ($service === "") {
        $msg = "Please select a printing service.";
    } elseif (count($uploads) === 0) {
        $msg = "Please upload at least one PDF or image.";
    } else {
        // =============================================
        // VALIDATE + READ EVERY UPLOADED FILE
        // =============================================

        $stored_files = [];

        foreach ($uploads as $file) {
            if ($file["error"] !== UPLOAD_ERR_OK) {
                $msg = "One of the files failed to upload. Please try again.";

                break;
            }

            $original_name = $file["name"];

            $tmp_name = $file["tmp_name"];

            $file_size = (int) $file["size"];

            $extension = strtolower(
                pathinfo($original_name, PATHINFO_EXTENSION),
            );

            // =========================================
            // FILE TYPE
            // =========================================

            if (!in_array($extension, $allowed_extensions, true)) {
                $msg =
                    "Allowed files: PDF, PNG, JPG, JPEG, WEBP and GIF. " .
                    "Invalid: " .
                    basename($original_name);

                break;
            }

            // =========================================
            // FILE SIZE
            // =========================================

            if ($file_size > $max_file_size) {
                $msg = "Maximum file size is 500 MB per file.";

                break;
            }

            // =========================================
            // READ FILE
            // =========================================

            $file_data = file_get_contents($tmp_name);

            if ($file_data === false) {
                $msg = "Unable to read the uploaded document.";

                break;
            }

            $pages = document_page_count($file_data, $extension);

            $page_count += $pages;

            $stored_files[] = [
                "name" => basename($original_name),

                "data" => new MongoDB\BSON\Binary(
                    $file_data,
                    MongoDB\BSON\Binary::TYPE_GENERIC,
                ),

                "size" => $file_size,

                "extension" => $extension,

                "page_count" => $pages,
            ];
        }

        if (!$msg) {
            // =========================================
            // CALCULATE AMOUNT FROM PAGE COUNT
            // =========================================


                $page_rate = service_page_rate($service);

                $base_amount = print_amount($page_count, $service);
                // =========================================
                // LOYALTY - EVERY 11TH ORDER IS FREE
                // =========================================

                $is_free = loyalty_free_order(
                    "student_id",
                    $_SESSION["user_id"],
                );

                if ($is_free) {
                    // Free order: no charge at all,
                    // including the emergency fee.
                    $emergency_waived = $selected_emergency_fee;

                    $amount = 0.0;

                    $selected_emergency_fee = 0.0;
                } else {
                    $emergency_waived = 0.0;

                    $amount = $base_amount + $selected_emergency_fee;
                }

                try {
                    // =================================
                    // GENERATE ORDER TOKEN
                    // =================================

                    $token = make_token();

                    // =================================
                    // GENERATE ORDER ID
                    // =================================

                    $order_id = next_id("orders");

                    // =================================
                    // CREATE ORDER DOCUMENT
                    // =================================

                    $doc = [
                        "id" => $order_id,

                        "order_token" => $token,

                        "student_id" => $_SESSION["user_id"],

                        "created_by" => null,

                        "service" => $service,

                        "page_count" => $page_count,

                        "page_rate" => $page_rate,

                        "base_amount" => $base_amount,

                        "is_free" => $is_free,

                        "loyalty_discount" => $is_free
                            ? round($base_amount + $emergency_waived, 2)
                            : 0.0,

                        "emergency_fee" => $selected_emergency_fee,

                        "amount" => $amount,

                        "order_type" => $order_type,

                        "print_side" => $print_side,

                        /*
                         * Store all uploaded files directly
                         * inside MongoDB. No uploads folder
                         * required.
                         */
                        "files" => $stored_files,

                        "file_count" => count($stored_files),

                        // First file kept at top level for
                        // backward compatibility.
                        "file_name" => $stored_files[0]["name"],

                        "file_size" => $stored_files[0]["size"],

                        "file_extension" => $stored_files[0]["extension"],

                        "payment_status" => "Pending",

                        "status" => "Pending",

                        "payment_txn_id" => null,

                        "created_at" => date("Y-m-d H:i:s"),
                    ];

                    // =================================
                    // INSERT INTO MONGODB
                    // =================================

                    $conn->orders->insertOne($doc);

                    // =================================
                    // REDIRECT TO PAYMENT
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
// PAGE SETTINGS
// =====================================================

$base = "../";

$active = "new-order";
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
    New Print Order - Smart Campus
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
            <?= icon("printer", "lg") ?>
        </div>

        <div>

            <h1>
                New Print Order
            </h1>

            <p class="muted">
                Choose Emergency Order for priority printing.
            </p>

        </div>

    </div>


    <div class="avatar">
        <?= strtoupper(substr($_SESSION["name"] ?? "S", 0, 1)) ?>
    </div>

</header>


<div class="content">


<?php if ($msg): ?>

<div class="alert bad">

    <?= icon("alert") ?>

    <?= e($msg) ?>

</div>

<?php endif; ?>


<form
    method="post"
    enctype="multipart/form-data"
    id="orderForm"
>


<div class="create-grid">


<div class="create-col">


<div class="card animate">


<h2 class="create-card-title">Print details</h2>

<p class="create-card-sub">Pick a service, set your options and add your files.</p>


<!-- ==================================================
     SERVICE
================================================== -->

<div class="steps-nav">

    <span class="step-pill active">
        <span class="n">1</span>
        Choose service
    </span>

    <span class="step-pill">
        <span class="n">2</span>
        Customize
    </span>

    <span class="step-pill">
        <span class="n">3</span>
        Upload &amp; pay
    </span>

</div>


<label>
    Service
</label>

<select
    name="service"
    id="service"
    required
>

    <option value="">
        Select Printing Service
    </option>

    <option
        value="Black & White Printing"
        <?= ($_POST["service"] ?? "") === "Black & White Printing"
            ? "selected"
            : "" ?>
    >
        Black &amp; White Printing
    </option>

    <option
        value="Color Printing"
        <?= ($_POST["service"] ?? "") === "Color Printing" ? "selected" : "" ?>
    >
        Color Printing
    </option>

    <option
        value="Spiral Binding"
        <?= ($_POST["service"] ?? "") === "Spiral Binding" ? "selected" : "" ?>
    >
        Spiral Binding
    </option>

</select>


<!-- ==================================================
     ORDER PRIORITY
================================================== -->

<label>
    Order Priority
</label>


<div class="priority-grid">


<!-- NORMAL -->

<label class="priority-card">

    <input
        type="radio"
        name="order_type"
        value="Normal"
        <?= ($_POST["order_type"] ?? "Normal") === "Normal" ? "checked" : "" ?>
    >

    <span class="priority-icon">
        <?= icon("truck") ?>
    </span>

    <span>

        <b>
            Normal Order
        </b>

        <small>
            Regular printing • No extra fee
        </small>

    </span>

    <strong class="free">
        FREE
    </strong>

</label>


<!-- EMERGENCY -->

<label class="priority-card emergency">

    <input
        type="radio"
        name="order_type"
        value="Emergency"
        <?= ($_POST["order_type"] ?? "") === "Emergency" ? "checked" : "" ?>
    >

    <span class="priority-icon">
        <?= icon("alert") ?>
    </span>

    <span>

        <b>
            Emergency Order
        </b>

        <small>
            Priority printing • Target within 1 hour*
        </small>

    </span>

    <strong class="fee">
        ₹<?= number_format($emergency_fee, 2) ?>
    </strong>

</label>


</div>


<!-- ==================================================
     EMERGENCY NOTE
================================================== -->

<div class="emergency-note">

    <?= icon("shield") ?>

    Emergency orders are moved to priority handling
    after successful payment.

</div>


<!-- ==================================================
     PRINT SIDE
================================================== -->

<label>
    Print Side
</label>

<select
    name="print_side"
    id="print_side"
>
    <option
        value="Single"
        <?= (($_POST["print_side"] ?? "Single") === "Single") ? "selected" : "" ?>
    >
        Single Side
    </option>

    <option
        value="Front & Back"
        <?= (($_POST["print_side"] ?? "") === "Front & Back") ? "selected" : "" ?>
    >
        Front &amp; Back (Both Sides)
    </option>
</select>


<!-- ==================================================
     DOCUMENT
================================================== -->

<label>
    PDF / Image Documents (you can select multiple)
</label>

<label class="upload-area" id="drop_zone" for="document">
    <?= icon("upload", "lg") ?>
    <b style="display:block;margin-bottom:8px;color:#fff;">Drag and drop your files here</b>
    <span class="muted" style="display:block;margin-bottom:16px;">or</span>
    <span class="btn outline small">Browse Files</span>

    <input
        type="file"
        name="document[]"
        id="document"
        accept=".pdf,.png,.jpg,.jpeg,.webp,.gif"
        multiple
        required
        style="position:absolute;width:1px;height:1px;opacity:0;overflow:hidden;"
    >
    <div id="file_name_display" style="margin-top:16px;font-weight:600;color:var(--secondary);"></div>
</label>

<div id="file_list" class="file-list"></div>


<div class="file-info">

    Maximum file size:
    <strong>500 MB per file</strong>

    <br>

    Supported:
    PDF, PNG, JPG, JPEG, WEBP, GIF

    <br>

    You can upload
    <strong>multiple files</strong>
    in one order — pages are added together.

</div>


</div>


</div>


<aside class="create-side">


<div class="card animate-1">


<div class="summary-title">
    Order Summary
</div>


<div class="order-total-box">

    <div class="order-total-row">

        <span>
            Printing Amount
        </span>

        <strong>
            ₹<span id="baseDisplay">0.00</span>
        </strong>

    </div>


    <div class="order-total-row">

        <span>
            Emergency Fee
        </span>

        <strong class="emergency-fee-display">

            ₹<span id="emergencyDisplay">0.00</span>

        </strong>

    </div>


    <div class="order-total-row total">

        <span>
            Total Amount
        </span>

        <strong>
            ₹<span id="totalDisplay">0.00</span>
        </strong>

    </div>

</div>


<div class="file-info" style="margin-top:18px">

    <?= icon("star") ?>

    Rate
    <strong>₹<span id="rateDisplay"><?= number_format(
        $page_rate,
        2,
    ) ?></span></strong>
    per page

    <br>

    Pages detected:
    <strong id="pageCount">0</strong>

    <br><br>

    Loyalty: every
    <strong><?= (int) $free_order_every ?>th</strong>
    order is completely
    <strong>FREE</strong>.

</div>


<button
    class="btn full"
    type="submit"
>

    Continue to Secure Payment

    <?= icon("arrow-right") ?>

</button>


</div>


</aside>


</div>


<input
    type="hidden"
    name="amount"
    id="amount"
    value="<?= e($_POST["amount"] ?? "") ?>"
>


</form>


</div>

</main>

</div>


<script>

/*
 * =====================================================
 * SMART CAMPUS ORDER CALCULATION
 * Amount = pages × PAGE_RATE  +  emergency fee
 * =====================================================
 */

const amountInput =
    document.getElementById('amount');

const baseDisplay =
    document.getElementById('baseDisplay');

const emergencyDisplay =
    document.getElementById('emergencyDisplay');

const totalDisplay =
    document.getElementById('totalDisplay');

const pageCountDisplay =
    document.getElementById('pageCount');

const documentInput =
    document.getElementById('document');

const orderTypes =
    document.querySelectorAll(
        'input[name="order_type"]'
    );


const emergencyFee =
    <?= json_encode($emergency_fee) ?>;

const defaultRate =
    <?= json_encode($page_rate) ?>;

const serviceRates =
    <?= json_encode($service_rates) ?>;

const rateDisplay =
    document.getElementById('rateDisplay');

const serviceSelect =
    document.getElementById('service');


let pageCount = 0;


/*
 * =====================================================
 * RATE FOR THE SELECTED SERVICE (₹ per page)
 * =====================================================
 */

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


/*
 * =====================================================
 * COUNT PAGES IN THE SELECTED FILE
 * =====================================================
 */

async function countPages(file) {

    if (!file) {
        return 0;
    }

    const name =
        (file.name || '').toLowerCase();


    // Images count as a single page
    if (!name.endsWith('.pdf')) {
        return 1;
    }


    // PDF → use pdf.js when available
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


/*
 * =====================================================
 * UPDATE TOTAL
 * =====================================================
 */

function updateTotal() {

    const rate =
        currentRate();

    let base =
        pageCount * rate;

    let selectedType =
        document.querySelector(
            'input[name="order_type"]:checked'
        );

    let emergency =
        0;


    if (
        selectedType &&
        selectedType.value === 'Emergency'
    ) {

        emergency = emergencyFee;

    }


    let total =
        base + emergency;


    amountInput.value =
        base.toFixed(2);

    baseDisplay.textContent =
        base.toFixed(2);

    emergencyDisplay.textContent =
        emergency.toFixed(2);

    totalDisplay.textContent =
        total.toFixed(2);

    if (pageCountDisplay) {

        pageCountDisplay.textContent =
            pageCount;
    }

    if (rateDisplay) {

        rateDisplay.textContent =
            rate.toFixed(2);
    }
}


/*
 * =====================================================
 * FILE SELECTED
 * =====================================================
 */

orderTypes.forEach(function(radio) { radio.addEventListener('change', updateTotal); });
if (serviceSelect) { serviceSelect.addEventListener('change', updateTotal); }
updateTotal();

/* ============ Drag and Drop + cumulative file list ============ */
const dropZone = document.getElementById('drop_zone');
const fileNameDisplay = document.getElementById('file_name_display');
const fileList = document.getElementById('file_list');

let selectedFiles = [];

['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, preventDefaults, false);
});
function preventDefaults(e) { e.preventDefault(); e.stopPropagation(); }

['dragenter', 'dragover'].forEach(eventName => {
    dropZone.addEventListener(eventName, () => dropZone.classList.add('dragover'), false);
});
['dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, () => dropZone.classList.remove('dragover'), false);
});

const FILE_ICON =
    '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" ' +
    'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
    '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>' +
    '<polyline points="14 2 14 8 20 8"></polyline>' +
    '<line x1="16" y1="13" x2="8" y2="13"></line>' +
    '<line x1="16" y1="17" x2="8" y2="17"></line></svg>';

function formatSize(bytes) {
    if (bytes === undefined || bytes === null) return '';
    const units = ['B', 'KB', 'MB', 'GB'];
    let i = 0, n = bytes;
    while (n >= 1024 && i < units.length - 1) { n /= 1024; i++; }
    return (i === 0 ? n : n.toFixed(1)) + ' ' + units[i];
}

function sameFile(a, b) {
    return a.name === b.name && a.size === b.size && a.lastModified === b.lastModified;
}

function syncInput() {
    const dt = new DataTransfer();
    selectedFiles.forEach(function(file) { dt.items.add(file); });
    documentInput.files = dt.files;
}

function addFiles(list) {
    Array.from(list || []).forEach(function(file) {
        if (!selectedFiles.some(function(f) { return sameFile(f, file); })) {
            selectedFiles.push(file);
        }
    });
    syncInput();
    refreshList();
}

function removeFile(index) {
    selectedFiles.splice(index, 1);
    syncInput();
    refreshList();
}

async function refreshList() {
    if (fileNameDisplay) {
        fileNameDisplay.textContent =
            selectedFiles.length > 1
                ? selectedFiles.length + ' files selected'
                : (selectedFiles[0] ? selectedFiles[0].name : '');
    }

    if (fileList) {
        fileList.innerHTML = '';

        selectedFiles.forEach(function(file, index) {
            const row = document.createElement('div');
            row.className = 'file-item';

            const nameWrap = document.createElement('div');
            nameWrap.className = 'fi-name';
            nameWrap.innerHTML = FILE_ICON;

            const nameText = document.createElement('span');
            nameText.textContent = file.name;
            nameWrap.appendChild(nameText);

            const size = document.createElement('span');
            size.className = 'fi-size';
            size.textContent = formatSize(file.size);

            const remove = document.createElement('button');
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

dropZone.addEventListener('drop', (e) => {
    const dt = e.dataTransfer;
    if (dt.files && dt.files.length) {
        addFiles(dt.files);
    }
});

documentInput.addEventListener('change', function() {
    if (this.files && this.files.length) {
        addFiles(this.files);
    }
});

</script>


</body>

</html>