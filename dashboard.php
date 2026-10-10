<?php
require_once "database.php";
require_login();

$role = $_SESSION["role"];
$name = $_SESSION["name"];
$base = "";
$active = "dashboard";
$uid = (int) $_SESSION["user_id"];

// =====================================================
// SHARED DATA
// =====================================================

$paidRevenue = function ($filter = []) use ($conn) {
    $filter["payment_status"] = "Paid";
    $rows = $conn->orders->aggregate([
        ['$match' => $filter],
        ['$group' => ["_id" => null, "c" => ['$sum' => '$amount']]],
    ])->toArray();
    return (float) ($rows[0]["c"] ?? 0);
};

// =====================================================
// ADMIN
// =====================================================

$totalOrders = $conn->orders->countDocuments([]);
$students = $conn->users->countDocuments(["role" => "student"]);
$staffCount = $conn->users->countDocuments(["role" => "staff"]);
$revenue = $paidRevenue();

// Last 7 days revenue for the chart
$chart = [];
for ($i = 6; $i >= 0; $i--) {
    $chart[date("Y-m-d", strtotime("-$i days"))] = 0.0;
}
foreach (
    $conn->orders->find(
        ["payment_status" => "Paid"],
        ["projection" => ["created_at" => 1, "amount" => 1]],
    )
    as $o
) {
    $day = substr((string) ($o["created_at"] ?? ""), 0, 10);
    if (isset($chart[$day])) {
        $chart[$day] += (float) ($o["amount"] ?? 0);
    }
}
$chartMax = max(1, max($chart));

// =====================================================
// STUDENT
// =====================================================

$every = defined("FREE_ORDER_EVERY") ? (int) FREE_ORDER_EVERY : 11;
$studentOrders = $conn->orders->countDocuments(["student_id" => $uid]);
$studentPending = $conn->orders->countDocuments([
    "student_id" => $uid,
    "status" => "Pending",
]);
$studentCompleted = $conn->orders->countDocuments([
    "student_id" => $uid,
    "status" => "Completed",
]);
$studentPaid = $conn->orders->countDocuments([
    "student_id" => $uid,
    "payment_status" => "Paid",
]);
$loyaltyPoints = $studentPaid % $every;
$ordersUntilFree = $every - $loyaltyPoints;
$loyaltyPct = (int) round(($loyaltyPoints / $every) * 100);
$recentOrders = iterator_to_array(
    $conn->orders->find(
        ["student_id" => $uid],
        ["sort" => ["id" => -1], "limit" => 4],
    ),
);

// =====================================================
// STAFF
// =====================================================

$staffOrders = $conn->orders->countDocuments(["created_by" => $uid]);
$staffPending = $conn->orders->countDocuments([
    "created_by" => $uid,
    "status" => "Pending",
]);
$staffReady = $conn->orders->countDocuments([
    "created_by" => $uid,
    "status" => "Ready",
]);
$staffRevenue = $paidRevenue(["created_by" => $uid]);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard - Smart Campus</title>
<link rel="stylesheet" href="css/style.css?v=<?= filemtime(__DIR__ . "/css/style.css") ?>">
</head>
<body>
<div class="layout">
<?php include __DIR__ . "/partials/sidebar.php"; ?>
<main>
<header class="topbar">
  <div class="topbar-title">
    <div class="page-icon"><?= icon("home", "lg") ?></div>
    <div>
      <h1>Welcome back, <?= e($name) ?></h1>
      <p class="muted">Smart Campus <?= e(ucfirst($role)) ?> Dashboard</p>
    </div>
  </div>
  <div class="avatar"><?= strtoupper(substr($name, 0, 1)) ?></div>
</header>

<div class="content">

<?php if ($role === "admin"): ?>

  <div class="stats animate">
    <div class="stat"><span class="stat-icon"><?= icon("package") ?></span><b><?= $totalOrders ?></b><small>Total Orders</small></div>
    <div class="stat violet"><span class="stat-icon"><?= icon("user") ?></span><b><?= $students ?></b><small>Students</small></div>
    <div class="stat accent"><span class="stat-icon"><?= icon("tool") ?></span><b><?= $staffCount ?></b><small>Staff</small></div>
    <div class="stat success"><span class="stat-icon"><?= icon("trend") ?></span><b>₹<?= number_format($revenue, 0) ?></b><small>Paid Revenue</small></div>
  </div>

  <div class="card animate-1">
    <div class="card-head">
      <div><h2>Revenue — last 7 days</h2><p class="muted">Total collected from paid orders.</p></div>
      <a class="btn small outline" href="admin/orders.php">Manage Tracking <?= icon("arrow-right") ?></a>
    </div>
    <div class="chart">
      <?php foreach ($chart as $day => $sum): ?>
      <div class="bar">
        <i style="height:<?= max(3, (int) round(($sum / $chartMax) * 100)) ?>%" title="₹<?= number_format($sum, 0) ?>"></i>
        <span><?= date("D", strtotime($day)) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="chart-legend"><span><i style="background:linear-gradient(180deg,#9DBBFF,#5278FF)"></i> Paid revenue (₹)</span></div>
  </div>

  <div class="card animate-2">
    <div class="card-head">
      <div><h2>Tracking control</h2><p class="muted">Only the Admin module can change the tracking status of orders.</p></div>
      <a class="btn" href="admin/orders.php">Open Tracking <?= icon("arrow-right") ?></a>
    </div>
  </div>

<?php elseif ($role === "student"): ?>

  <div class="stats animate">
    <div class="stat"><span class="stat-icon"><?= icon("package") ?></span><b><?= $studentOrders ?></b><small>Total Orders</small></div>
    <div class="stat accent"><span class="stat-icon"><?= icon("clock") ?></span><b><?= $studentPending ?></b><small>Pending</small></div>
    <div class="stat success"><span class="stat-icon"><?= icon("check-circle") ?></span><b><?= $studentCompleted ?></b><small>Completed</small></div>
    <div class="stat violet"><span class="stat-icon"><?= icon("star") ?></span><b><?= $loyaltyPoints ?>/<?= $every ?></b><small>Loyalty Points</small></div>
  </div>

  <div class="card animate-1">
    <div class="card-head">
      <div>
        <h2>Loyalty Rewards <?= icon("star") ?></h2>
        <p class="muted">You are <b><?= $ordersUntilFree ?></b> paid order<?= $ordersUntilFree === 1 ? "" : "s" ?> away from your next free order.</p>
      </div>
      <a class="btn small outline" href="student/create_order.php">New Order <?= icon("arrow-right") ?></a>
    </div>
    <div class="progress"><i style="width:<?= $loyaltyPct ?>%"></i></div>
    <div class="progress-meta"><span><?= $loyaltyPoints ?> of <?= $every ?> orders</span><span>Every <?= $every ?>th order free</span></div>
  </div>

  <div class="card animate-2">
    <div class="card-head">
      <div><h2>Recent orders</h2><p class="muted">Your latest print jobs and their tracking status.</p></div>
      <a class="btn small outline" href="student/orders.php">View all <?= icon("arrow-right") ?></a>
    </div>
    <?php if (count($recentOrders) === 0): ?>
      <div class="empty-state"><?= icon("printer", "xl") ?><b>No orders yet</b><p>Create your first print order to see it tracked here.</p></div>
    <?php else: ?>
      <div class="tablewrap">
        <table>
          <tr><th>Token</th><th>Service</th><th>Total</th><th>Payment</th><th>Status</th></tr>
          <?php foreach ($recentOrders as $o): ?>
          <tr>
            <td><a class="order-token" href="track.php?id=<?= (int) $o["id"] ?>" title="Track this order"><?= e($o["order_token"]) ?></a></td>
            <td><?= e($o["service"]) ?></td>
            <td>₹<?= number_format((float) $o["amount"], 2) ?></td>
            <td><span class="tag status-<?= strtolower($o["payment_status"]) ?>"><?= e($o["payment_status"]) ?></span></td>
            <td><span class="tag status-<?= strtolower($o["status"]) ?>"><?= e($o["status"]) ?></span></td>
          </tr>
          <?php endforeach; ?>
        </table>
      </div>
    <?php endif; ?>
  </div>

<?php else: ?>

  <div class="stats animate">
    <div class="stat"><span class="stat-icon"><?= icon("clipboard") ?></span><b><?= $staffOrders ?></b><small>My Orders</small></div>
    <div class="stat accent"><span class="stat-icon"><?= icon("clock") ?></span><b><?= $staffPending ?></b><small>Pending</small></div>
    <div class="stat success"><span class="stat-icon"><?= icon("check-circle") ?></span><b><?= $staffReady ?></b><small>Ready</small></div>
    <div class="stat violet"><span class="stat-icon"><?= icon("trend") ?></span><b>₹<?= number_format($staffRevenue, 0) ?></b><small>Revenue</small></div>
  </div>

  <div class="card animate-1">
    <div class="card-head">
      <div><h2>Staff workspace</h2><p class="muted">Book orders on behalf of students. Tracking status changes are restricted to Admin.</p></div>
      <a class="btn" href="staff/orders.php">Open Orders <?= icon("arrow-right") ?></a>
    </div>
  </div>

<?php endif; ?>

</div>
</main>
</div>
</body>
</html>
