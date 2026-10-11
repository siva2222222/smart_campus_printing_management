<?php require_once "../database.php";
require_role("student");
$rows = iterator_to_array(
    $conn->orders->find(
        ["student_id" => $_SESSION["user_id"]],
        ["sort" => ["id" => -1]],
    ),
);
$base = "../";
$active = "my-orders";
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Orders - Smart Campus</title>
<link rel="stylesheet" href="../css/style.css?v=<?= filemtime(__DIR__ . "/../css/style.css") ?>">
</head>
<body>
<div class="layout">
<?php include __DIR__ . "/../partials/sidebar.php"; ?>
<main>
<header class="topbar">
  <div class="topbar-title">
    <div class="page-icon"><?= icon("package", "lg") ?></div>
    <div>
      <h1>My Print Orders</h1>
      <p class="muted">Track the status of everything you've ordered.</p>
    </div>
  </div>
  <div class="avatar"><?= strtoupper(substr($_SESSION["name"], 0, 1)) ?></div>
</header>
<div class="content">
<div class="card animate">
  <div class="card-head">
    <div>
      <h2>Orders</h2>
      <p class="muted" style="margin-top:6px"><?= count($rows) ?> order<?= count($rows) === 1 ? "" : "s" ?> placed</p>
    </div>
    <a class="btn small" href="create_order.php">New Order <?= icon("arrow-right") ?></a>
  </div>

  <?php if (count($rows) === 0): ?>
  <div class="order-empty">
    <?= icon("printer", "xl") ?>
    <b>No orders yet</b>
    <p>Create your first print order to see it tracked here.</p>
    <a class="btn small" href="create_order.php" style="margin-top:18px">New Order <?= icon("arrow-right") ?></a>
  </div>
  <?php else: ?>
  <div class="order-list">
    <?php foreach ($rows as $o): ?>
      <?php
      $files = order_files($o);
      $emergency = ($o["order_type"] ?? "Normal") === "Emergency";
      $isFree = !empty($o["is_free"]);
      $payStatus = strtolower((string) ($o["payment_status"] ?? "pending"));
      $trkStatus = strtolower((string) ($o["status"] ?? "pending"));
      ?>
    <div class="order-card <?= $emergency ? "emergency" : "" ?>">
      <div class="order-main">

        <div class="order-identity">
          <div class="order-token-line">
            <a class="order-token" href="../track.php?id=<?= (int) $o["id"] ?>" title="Track this order"><b><?= e($o["order_token"]) ?></b></a>
            <span class="tag status-<?= $trkStatus ?>"><?= e($o["status"]) ?></span>
          </div>
          <div class="order-service"><?= e($o["service"]) ?></div>
          <div class="order-sub">
            <span><?= icon("file-text") ?> <?= (int) ($o["page_count"] ?? 0) ?> page<?= (int) ($o["page_count"] ?? 0) === 1 ? "" : "s" ?></span>
            <span><?= icon("printer") ?> <?= e($o["print_side"] ?? "Single") ?></span>
          </div>
        </div>

        <div class="order-files">
          <?php if (count($files) === 0): ?>
            <span class="muted">No document</span>
          <?php else: ?>
            <?php foreach ($files as $fi => $of): ?>
              <?php $ext = strtoupper(pathinfo($of["name"], PATHINFO_EXTENSION)); ?>
              <a class="file-chip" href="../download.php?id=<?= (int) $o["id"] ?>&file=<?= (int) $fi ?>" title="Download <?= e($of["name"]) ?>">
                <?= icon("file-text") ?>
                <span><?= e($of["name"] ?: $ext) ?></span>
                <?= icon("download") ?>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <div class="order-side">
          <div class="order-total <?= $isFree ? "free" : "" ?>"><?= $isFree ? "FREE" : "₹" . number_format((float) $o["amount"], 2) ?></div>
          <div class="order-tags">
            <?php if ($emergency): ?>
              <span class="tag emergency-tag"><?= icon("alert") ?> Emergency</span>
            <?php else: ?>
              <span class="tag">Normal</span>
            <?php endif; ?>
            <span class="tag status-<?= $payStatus ?>"><?= e($o["payment_status"]) ?></span>
          </div>
          <div class="order-actions">
            <a class="btn small outline" href="../track.php?id=<?= (int) $o["id"] ?>"><?= icon("package") ?> Track</a>
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
</body>
</html>
