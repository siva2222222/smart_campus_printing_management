<?php
require_once "../database.php";
require_login();
if (!in_array($_SESSION["role"], ["admin", "staff"], true)) {
    http_response_code(403);
    exit("Access denied.");
}
$id = (int) ($_GET["id"] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit("Invalid order ID.");
}
header("Location: ../download.php?id=" . $id);
exit();
