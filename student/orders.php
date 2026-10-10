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
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>My Orders - Smart Campus</title><link rel="stylesheet" href="../css/style.css?v=<?= filemtime(
    __DIR__ . "/../css/style.css",
) ?>"></head><body><div class="layout">
<?php include __DIR__ . "/../partials/sidebar.php"; ?>
<main>
<header class="topbar"><div class="topbar-title"><div class="page-icon"><?= icon(
    "package",
    "lg",
) ?></div><div><h1>My Print Orders</h1><p class="muted">Track the status of everything you've ordered.</p></div></div><div class="avatar"><?= strtoupper(
    substr($_SESSION["name"], 0, 1),
) ?></div></header>
<div class="content">
<div class="card animate">
<?php if (count($rows) === 0): ?>
<div class="empty-state"><?= icon(
    "printer",
    "xl",
) ?><b>No orders yet</b><p>Create your first print order to see it tracked here.</p><a class="btn small" href="create_order.php" style="margin-top:16px">New Order <?= icon(
    "arrow-right",
) ?></a></div>
<?php else: ?>
<div class="tablewrap"><table><tr><th>Token</th><th>Service</th><th>Document</th><th>Priority</th><th>Print Side</th><th>Total</th><th>Payment</th><th>Tracking</th></tr><?php foreach (
    $rows
    as $o
): ?><tr><td><a class="order-token" href="../track.php?id=<?= (int) $o[
    "id"
] ?>" title="Track this order"><b><?= e($o["order_token"]) ?></b></a></td><td><?= e(
    $o["service"],
) ?></td><td><?php if (!empty($o["file_name"])):
    $ext = strtoupper(
        pathinfo($o["file_name"], PATHINFO_EXTENSION),
    ); ?><a href="../download.php?id=<?= (int) $o[
    "id"
] ?>" class="btn small outline" style="gap:5px;padding:5px 10px;font-size:12px"><?= icon(
    "file-text",
) ?> <span><?= e($ext) ?></span> <?= icon("download") ?></a><?php
else:
     ?><span class="muted">—</span><?php
endif; ?></td><td><?php if (
    $o["order_type"] === "Emergency"
): ?><span class="tag emergency-tag"><?= icon(
    "alert",
) ?> Emergency</span><?php else: ?><span class="tag">Normal</span><?php endif; ?></td><td><?= e(
    $o["print_side"] ?? "Single",
) ?></td><td>₹<?= e(
    $o["amount"],
) ?></td><td><span class="tag status-<?= strtolower(
    $o["payment_status"],
) ?>"><?= e(
    $o["payment_status"],
) ?></span></td><td><span class="tag status-<?= strtolower(
    $o["status"],
) ?>"><?= e($o["status"]) ?></span></td></tr><?php endforeach; ?></table></div>
<?php endif; ?>
</div>
</div>
</main></div></body></html>