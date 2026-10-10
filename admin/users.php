<?php require_once "../database.php";
require_role("admin");
$rows = iterator_to_array($conn->users->find([], ["sort" => ["id" => -1]]));
$base = "../";
$active = "users";
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Users - Smart Campus</title>
<link rel="stylesheet" href="../css/style.css?v=<?= filemtime(__DIR__ . "/../css/style.css") ?>">
</head>
<body>
<div class="layout">
<?php include __DIR__ . "/../partials/sidebar.php"; ?>
<main>
<header class="topbar">
  <div class="topbar-title">
    <div class="page-icon"><?= icon("users", "lg") ?></div>
    <div><h1>Users</h1><p class="muted">Everyone registered across the platform.</p></div>
  </div>
  <div class="avatar"><?= strtoupper(substr($_SESSION["name"], 0, 1)) ?></div>
</header>
<div class="content">
<div class="card animate">
<?php if (count($rows) === 0): ?>
  <div class="empty-state"><?= icon("users", "xl") ?><b>No users found</b></div>
<?php else: ?>
  <div class="searchbar">
    <input type="search" id="userSearch" placeholder="Search by name or email…" aria-label="Search users">
  </div>
  <div class="tablewrap">
    <table>
      <thead>
        <tr><th>Name</th><th>Email</th><th>Role</th><th>Joined</th></tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $u): ?>
        <?php
        $joined = trim((string) ($u["created_at"] ?? ""));
        $joinedLabel = $joined !== "" ? date("M j, Y", strtotime($joined)) : "—";
        ?>
        <tr>
          <td><b><?= e($u["name"] ?? "—") ?></b></td>
          <td><?= e($u["email"] ?? "—") ?></td>
          <td><span class="tag status-<?= e(strtolower($u["role"] ?? "student")) ?>"><?= e(ucfirst($u["role"] ?? "student")) ?></span></td>
          <td class="<?= $joined !== "" ? "" : "muted" ?>"><?= e($joinedLabel) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
</div>
</div>
</main>
</div>
<script>
  (function () {
    var q = document.getElementById("userSearch");
    var rows = Array.prototype.slice.call(document.querySelectorAll("table tbody tr"));
    if (!q || !rows.length) return;
    q.addEventListener("input", function () {
      var term = q.value.toLowerCase();
      rows.forEach(function (r) {
        r.style.display = r.textContent.toLowerCase().indexOf(term) > -1 ? "" : "none";
      });
    });
  })();
</script>
</body>
</html>
