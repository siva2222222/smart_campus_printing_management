<?php

require_once "../database.php";

require_login();

// =====================================================
// ADMIN + STAFF ACCESS
// =====================================================

if (!in_array($_SESSION["role"], ["admin", "staff"], true)) {
    http_response_code(403);
    exit("Access denied.");
}

// =====================================================
// UPDATE ORDER STATUS
// ONLY ADMIN
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_status"])) {
    if ($_SESSION["role"] !== "admin") {
        http_response_code(403);

        exit("Only Admin can update order status.");
    }

    $order_id = (int) ($_POST["order_id"] ?? 0);

    $new_status = trim($_POST["status"] ?? "");

    $allowed_statuses = [
        "Pending",
        "Processing",
        "Ready",
        "Completed",
        "Cancelled",
    ];

    if ($order_id <= 0 || !in_array($new_status, $allowed_statuses, true)) {
        exit("Invalid order or status.");
    }

    $updateResult = $conn->orders->updateOne(
        [
            "id" => $order_id,
        ],

        [
            '$set' => [
                "status" => $new_status,
            ],
        ],
    );

    if ($updateResult->getMatchedCount() === 0) {
        exit("Order not found.");
    }

    header("Location: orders.php");

    exit();
}

// =====================================================
// GET ALL ORDERS
// =====================================================

$orders = iterator_to_array(
    $conn->orders->find(
        [],

        [
            "sort" => [
                "created_at" => -1,
            ],
        ],
    ),
);

// =====================================================
// GET ONLY VALID STUDENT IDs
// =====================================================

$studentIds = [];

foreach ($orders as $order) {
    if (
        isset($order["student_id"]) &&
        $order["student_id"] !== null &&
        is_numeric($order["student_id"]) &&
        (int) $order["student_id"] > 0
    ) {
        $studentIds[] = (int) $order["student_id"];
    }
}

$studentIds = array_values(array_unique($studentIds));

// =====================================================
// LOAD STUDENTS SAFELY
// =====================================================

$students = [];

if (count($studentIds) > 0) {
    foreach (
        $conn->users->find([
            "id" => [
                '$in' => $studentIds,
            ],
        ])
        as $student
    ) {
        $students[(int) $student["id"]] = $student["name"];
    }
}

// =====================================================
// GET STAFF NAMES
// =====================================================

$staffIds = [];

foreach ($orders as $order) {
    if (
        isset($order["created_by"]) &&
        $order["created_by"] !== null &&
        is_numeric($order["created_by"]) &&
        (int) $order["created_by"] > 0
    ) {
        $staffIds[] = (int) $order["created_by"];
    }
}

$staffIds = array_values(array_unique($staffIds));

$staff = [];

if (count($staffIds) > 0) {
    foreach (
        $conn->users->find([
            "id" => [
                '$in' => $staffIds,
            ],
        ])
        as $user
    ) {
        $staff[(int) $user["id"]] = $user["name"];
    }
}
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
    Order Tracking Control - Smart Campus
</title>


<link
    rel="stylesheet"
    href="../css/style.css?v=<?= filemtime(__DIR__ . "/../css/style.css") ?>"
>

</head>


<body>

<div class="layout">


<?php include __DIR__ . "/../partials/sidebar.php"; ?>


<main>


<!-- =================================================
     HEADER
================================================== -->

<header class="topbar">

<div class="topbar-title">

<div class="page-icon">

<?= icon("shield-check", "lg") ?>

</div>


<div>

<h1>
    Order Tracking Control
</h1>

<p class="muted">

Only Admin can change order tracking status.

</p>

</div>

</div>


<div class="avatar">

<?= strtoupper(substr($_SESSION["name"] ?? "A", 0, 1)) ?>

</div>

</header>


<div class="content">


<div class="card animate">


<div class="searchbar">

<input
    type="search"
    id="orderSearch"
    placeholder="Search token, service or order by…"
    aria-label="Search orders"
>

<select id="orderStatusFilter" aria-label="Filter by status">

<option value="">All statuses</option>
<option value="pending">Pending</option>
<option value="processing">Processing</option>
<option value="ready">Ready</option>
<option value="completed">Completed</option>
<option value="cancelled">Cancelled</option>

</select>

</div>


<div class="tablewrap">


<table>


<thead>

<tr>

<th>TOKEN</th>

<th>ORDER BY</th>

<th>SERVICE</th>

<th>PRIORITY</th>

<th>PRINT SIDE</th>

<th>TOTAL</th>

<th>PAYMENT</th>

<th>DOCUMENT</th>

<th>TRACKING</th>

</tr>

</thead>


<tbody>


<?php if (count($orders) > 0): ?>


<?php foreach ($orders as $row): ?>


<?php
// ===================================================
// ORDER TYPE
// ===================================================

$orderType = (string) ($row["order_type"] ?? "Normal");

// ===================================================
// STUDENT / STAFF
// ===================================================

$studentId =
    isset($row["student_id"]) && $row["student_id"] !== null
        ? (int) $row["student_id"]
        : 0;

$createdBy =
    isset($row["created_by"]) && $row["created_by"] !== null
        ? (int) $row["created_by"]
        : 0;

// ===================================================
// DISPLAY ORDER OWNER
// ===================================================

if ($studentId > 0) {
    $orderBy = $students[$studentId] ?? "Student";

    $ownerType = "Student";
} elseif ($createdBy > 0) {
    $orderBy = $staff[$createdBy] ?? "Staff";

    $ownerType = "Staff";
} else {
    $orderBy = "Unknown";

    $ownerType = "";
}

// ===================================================
// PAYMENT
// ===================================================

$paymentStatus = (string) ($row["payment_status"] ?? "Pending");

// ===================================================
// TRACKING
// ===================================================

$status = (string) ($row["status"] ?? "Pending");

// ===================================================
// DOCUMENT
// ===================================================

$fileName = trim((string) ($row["file_name"] ?? ""));
?>


<tr data-status="<?= e(strtolower($status)) ?>">


<!-- TOKEN -->

<td>

<a
    class="order-token"
    href="../track.php?id=<?= (int) $row["id"] ?>"
    title="Track this order"
>
<strong>
<?= e($row["order_token"] ?? "") ?>
</strong>
</a>

</td>


<!-- ORDER BY -->

<td>

<strong>

<?= e($orderBy) ?>

</strong>


<br>

<small class="muted">

<?= e($ownerType) ?> Order

</small>

</td>


<!-- SERVICE -->

<td>

<?= e($row["service"] ?? "") ?>

</td>


<!-- PRIORITY -->

<td>

<?php $priority = strtolower(trim($orderType)); ?>


<?php if ($priority === "emergency" || $priority === "urgent"): ?>

<span class="priority-emergency">

<?= icon("alert") ?> <?= e($orderType) ?>

</span>

<?php else: ?>

<span class="priority-normal">

<?= e($orderType) ?>

</span>

<?php endif; ?>

</td>


<!-- PRINT SIDE -->

<td>

<?= e($row["print_side"] ?? "Single") ?>

</td>


<!-- TOTAL -->

<td>

₹<?= number_format((float) ($row["amount"] ?? 0), 2) ?>

</td>


<!-- PAYMENT -->

<td>

<?php if (strtolower($paymentStatus) === "paid"): ?>

<span class="badge badge-paid">

Paid

</span>

<?php else: ?>

<span class="badge badge-pending">

<?= e($paymentStatus) ?>

</span>

<?php endif; ?>

</td>


<!-- DOCUMENT -->

<td>

<?php if ($fileName !== ""): ?>


<?php $fileExt = strtoupper(pathinfo($fileName, PATHINFO_EXTENSION)); ?>


<a
    href="../download.php?id=<?= (int) $row["id"] ?>"
    class="btn small secondary download-btn"
>

<?= icon("file-text") ?>

<span>

<?= e($fileExt) ?>

</span>

<?= icon("download") ?>

</a>


<?php else: ?>

<span class="muted">

No File

</span>

<?php endif; ?>

</td>


<!-- TRACKING -->

<td>


<?php if ($_SESSION["role"] === "admin"): ?>


<form
    method="post"
    class="tracking-form"
>


<input
    type="hidden"
    name="order_id"
    value="<?= (int) $row["id"] ?>"
>


<select
    name="status"
    class="form-control"
    required
>


<option
    value="Pending"
    <?= $status === "Pending" ? "selected" : "" ?>
>

Pending

</option>


<option
    value="Processing"
    <?= $status === "Processing" ? "selected" : "" ?>
>

Processing

</option>


<option
    value="Ready"
    <?= $status === "Ready" ? "selected" : "" ?>
>

Ready

</option>


<option
    value="Completed"
    <?= $status === "Completed" ? "selected" : "" ?>
>

Completed

</option>


<option
    value="Cancelled"
    <?= $status === "Cancelled" ? "selected" : "" ?>
>

Cancelled

</option>


</select>


<button
    type="submit"
    name="update_status"
    class="btn small"
>

Save

</button>


</form>


<?php else: ?>


<span class="readonly-status">

<?= e($status) ?>

</span>


<?php endif; ?>


</td>


</tr>


<?php endforeach; ?>


<?php else: ?>


<tr>

<td
    colspan="9"
    style="text-align:center;"
    class="muted"
>

No orders found.

</td>

</tr>


<?php endif; ?>


</tbody>

</table>

</div>

</div>

</div>

</main>

</div>


<script>
  (function () {
    var q = document.getElementById("orderSearch");
    var st = document.getElementById("orderStatusFilter");
    var rows = Array.prototype.slice.call(document.querySelectorAll("table tbody tr[data-status]"));
    if (!rows.length) return;
    function apply() {
      var term = (q.value || "").toLowerCase();
      var status = st ? st.value : "";
      rows.forEach(function (r) {
        var ok = r.textContent.toLowerCase().indexOf(term) > -1;
        if (status) ok = ok && (r.getAttribute("data-status") || "") === status;
        r.style.display = ok ? "" : "none";
      });
    }
    if (q) q.addEventListener("input", apply);
    if (st) st.addEventListener("change", apply);
  })();
</script>

</body>

</html>