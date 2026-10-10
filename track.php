<?php
require_once "database.php";
require_login();

$role = $_SESSION["role"];
$uid = (int) $_SESSION["user_id"];

$id = (int) ($_GET["id"] ?? 0);
$token = trim((string) ($_GET["token"] ?? ""));

$filter = $id > 0 ? ["id" => $id] : ($token !== "" ? ["order_token" => $token] : null);

if ($filter === null) {
    http_response_code(400);
    exit("Order not specified.");
}

$o = $conn->orders->findOne($filter);

if (!$o) {
    http_response_code(404);
    exit("Order not found.");
}

// =====================================================
// ACCESS CONTROL
// =====================================================

$isOwner = false;

if ($role === "student") {
    $isOwner = (int) ($o["student_id"] ?? 0) === $uid;
} elseif ($role === "staff") {
    $isOwner = (int) ($o["created_by"] ?? 0) === $uid;
} elseif ($role === "admin") {
    $isOwner = true;
}

// Staff may also view orders in the shared queue? Keep it restricted to owned/admin.
if (!$isOwner) {
    http_response_code(403);
    exit("Access denied.");
}

// =====================================================
// STATUS PIPELINE
// =====================================================

$phases = ["Pending", "Processing", "Ready", "Completed"];
$status = (string) ($o["status"] ?? "Pending");
$cancelled = strtolower($status) === "cancelled";
$currentIndex = array_search($status, $phases, true);
$currentIndex = $currentIndex === false ? 0 : (int) $currentIndex;

$meta = [
    "Pending" => ["Order placed", "Your order has been received and is waiting in the print queue."],
    "Processing" => ["Printing in progress", "Your document is being printed on the selected service."],
    "Ready" => ["Ready for pickup", "Your printout is ready at the collection desk. Show your token to collect it."],
    "Completed" => ["Collected", "This order has been collected. Thanks for printing with Smart Campus."],
    "Cancelled" => ["Order cancelled", "This order was cancelled. Please contact the print desk if this is unexpected."],
];

$service = (string) ($o["service"] ?? "");
$file = trim((string) ($o["file_name"] ?? ""));
$ext = $file !== "" ? strtoupper(pathinfo($file, PATHINFO_EXTENSION)) : "";
$base = "";

if ($role === "staff") {
    $active = "orders";
} elseif ($role === "admin") {
    $active = "tracking";
} else {
    $active = "my-orders";
}

$backLink = $role === "staff" ? "staff/orders.php" : ($role === "admin" ? "admin/orders.php" : "student/orders.php");
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Order Tracking - Smart Campus</title>
<link rel="stylesheet" href="css/style.css?v=<?= filemtime(__DIR__ . "/css/style.css") ?>">
</head>
<body>
<div class="layout">
<?php include __DIR__ . "/partials/sidebar.php"; ?>
<main>
<header class="topbar">
  <div class="topbar-title">
    <div class="page-icon"><?= icon("package", "lg") ?></div>
    <div><h1>Order Tracking</h1><p class="muted">Live status for <?= e($o["order_token"] ?? "") ?></p></div>
  </div>
  <div class="avatar"><?= strtoupper(substr($_SESSION["name"] ?? "S", 0, 1)) ?></div>
</header>

<div class="content">
  <a class="back" href="<?= e($backLink) ?>"><?= icon("chevron-left") ?> Back to orders</a>

  <div class="card animate">
    <div class="card-head">
      <div class="token" style="min-width:300px">
        <small>Order token</small>
        <b><?= e($o["order_token"] ?? "") ?></b>
      </div>
      <div style="text-align:right">
        <span class="tag status-<?= strtolower($status) ?>"><?= e($status) ?></span>
        <p class="muted" style="margin-top:10px">Placed <?= e($o["created_at"] ?? "—") ?></p>
      </div>
    </div>

    <div class="track-steps">
      <?php foreach ($phases as $i => $phase): ?>
        <?php
        $cls = "";
        if ($cancelled && $i === 0) {
            $cls = "cancelled";
        } elseif ($i < $currentIndex) {
            $cls = "done";
        } elseif ($i === $currentIndex) {
            $cls = "active";
        }
        ?>
        <div class="track-step <?= $cls ?>">
          <i><?= $i < $currentIndex || $cls === "done" ? icon("check") : $i + 1 ?></i>
          <span><?= e($phase) ?></span>
        </div>
      <?php endforeach; ?>
    </div>

    <?php
      $m = $meta[$cancelled ? "Cancelled" : $status] ?? $meta["Pending"];
    ?>
    <div class="track-panel" style="margin-top:30px">
      <div class="tp-head"><?= icon($cancelled ? "alert" : "check-circle") ?> <b><?= e($m[0]) ?></b></div>
      <p><?= e($m[1]) ?></p>
    </div>
  </div>

  <div class="card animate-1">
    <div class="card-head"><h2>Order details</h2></div>
    <div class="summary">
      <div><span>Service</span><b><?= e($service) ?></b></div>
      <div><span>Pages</span><b><?= (int) ($o["page_count"] ?? 0) ?></b></div>
      <div><span>Rate per page</span><b>₹<?= number_format((float) ($o["page_rate"] ?? 0), 2) ?></b></div>
      <div><span>Print side</span><b><?= e($o["print_side"] ?? "Single") ?></b></div>
      <div><span>Priority</span><b class="<?= ($o["order_type"] ?? "Normal") === "Emergency" ? "priority-emergency" : "priority-normal" ?>"><?= e($o["order_type"] ?? "Normal") ?></b></div>
      <div><span>Payment</span><span class="<?= strtolower((string) ($o["payment_status"] ?? "")) === "paid" ? "free" : "fee" ?>"><?= e($o["payment_status"] ?? "Pending") ?></span></div>
      <hr>
      <div class="total"><b><?= !empty($o["is_free"]) ? "Loyalty free order" : "Total paid" ?></b><strong>₹<?= number_format((float) ($o["amount"] ?? 0), 2) ?></strong></div>
    </div>

    <?php if ($file !== ""): ?>
      <a class="btn outline" href="download.php?id=<?= (int) $o["id"] ?>"><?= icon("download") ?> Download <?= e($ext) ?></a>
    <?php endif; ?>
  </div>
</div>
</main>
</div>
</body>
</html>
