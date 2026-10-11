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

$isAdmin = $_SESSION["role"] === "admin";
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


<div class="card-head">

<div>

<h2>
    Orders
</h2>

<p class="muted" style="margin-top:6px">

<?= count($orders) ?> order<?= count($orders) === 1 ? "" : "s" ?> in the system

</p>

</div>

</div>


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


<?php if (count($orders) === 0): ?>

<div class="order-empty">

<?= icon("inbox", "xl") ?>

<b>
    No orders found
</b>

<p>
    Orders will appear here once students or staff place them.
</p>

</div>

<?php else: ?>


<div class="order-list">


<?php foreach ($orders as $row): ?>

<?php
// ===================================================
// ORDER TYPE
// ===================================================

$orderType = (string) ($row["order_type"] ?? "Normal");

$emergency = strtolower(trim($orderType)) === "emergency";

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

$paymentStatus = (string) ($row["payment_status"] ?? "Pending");

$status = (string) ($row["status"] ?? "Pending");

$files = order_files($row);
?>


<div class="order-card <?= $emergency ? "emergency" : "" ?>" data-status="<?= e(strtolower($status)) ?>">

<div class="order-main">


<div class="order-identity">

<div class="order-token-line">

<a
    class="order-token"
    href="../track.php?id=<?= (int) $row["id"] ?>"
    title="Track this order"
>
<b>
<?= e($row["order_token"] ?? "") ?>
</b>
</a>

<span class="tag status-<?= e(strtolower($status)) ?>">
<?= e($status) ?>
</span>

</div>

<div class="order-service">
<?= e($row["service"] ?? "") ?>
</div>

<div class="order-sub">

<span><?= icon("file-text") ?> <?= (int) ($row["page_count"] ?? 0) ?> page<?= (int) ($row["page_count"] ?? 0) === 1 ? "" : "s" ?></span>

<span><?= icon("printer") ?> <?= e($row["print_side"] ?? "Single") ?></span>

</div>

</div>


<div class="order-files">

<?php if (count($files) === 0): ?>

<span class="muted">No file</span>

<?php else: ?>

<?php foreach ($files as $fi => $of): ?>

<a
    href="../download.php?id=<?= (int) $row["id"] ?>&file=<?= (int) $fi ?>"
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
₹<?= number_format((float) ($row["amount"] ?? 0), 2) ?>
</div>

<div class="order-tags">

<?php if ($emergency): ?>

<span class="tag emergency-tag"><?= icon("alert") ?> Emergency</span>

<?php else: ?>

<span class="tag"><?= e($orderType) ?></span>

<?php endif; ?>


<?php if (strtolower($paymentStatus) === "paid"): ?>

<span class="tag status-paid">Paid</span>

<?php else: ?>

<span class="tag status-<?= e(strtolower($paymentStatus)) ?>"><?= e($paymentStatus) ?></span>

<?php endif; ?>

</div>

<div class="order-actions">

<a
    class="btn small outline"
    href="../track.php?id=<?= (int) $row["id"] ?>"
>
<?= icon("package") ?> Track
</a>

</div>

</div>


</div>


<div class="order-foot">

<div class="who">

<?= icon("user") ?>

<span>
<?= e($ownerType) ?> order by <b><?= e($orderBy) ?></b>
</span>

</div>


<?php if ($isAdmin): ?>

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

<option value="Pending" <?= $status === "Pending" ? "selected" : "" ?>>Pending</option>
<option value="Processing" <?= $status === "Processing" ? "selected" : "" ?>>Processing</option>
<option value="Ready" <?= $status === "Ready" ? "selected" : "" ?>>Ready</option>
<option value="Completed" <?= $status === "Completed" ? "selected" : "" ?>>Completed</option>
<option value="Cancelled" <?= $status === "Cancelled" ? "selected" : "" ?>>Cancelled</option>

</select>

<button
    type="submit"
    name="update_status"
    class="btn small"
>
<?= icon("check") ?> Save
</button>

</form>

<?php else: ?>

<span class="readonly-status">
<?= e($status) ?>
</span>

<?php endif; ?>

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
  (function () {
    var q = document.getElementById("orderSearch");
    var st = document.getElementById("orderStatusFilter");
    var cards = Array.prototype.slice.call(document.querySelectorAll(".order-card[data-status]"));
    if (!cards.length) return;
    function apply() {
      var term = (q.value || "").toLowerCase();
      var status = st ? st.value : "";
      cards.forEach(function (c) {
        var ok = c.textContent.toLowerCase().indexOf(term) > -1;
        if (status) ok = ok && (c.getAttribute("data-status") || "") === status;
        c.style.display = ok ? "" : "none";
      });
    }
    if (q) q.addEventListener("input", apply);
    if (st) st.addEventListener("change", apply);
  })();
</script>

</body>

</html>
