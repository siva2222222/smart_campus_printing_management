<?php
require_once "database.php";
require_login();

$role = $_SESSION["role"] ?? "";
if (!in_array($role, ["admin", "staff", "student"], true)) {
    http_response_code(403);
    exit("Access denied.");
}

$order_id = (int) ($_GET["id"] ?? 0);
if ($order_id <= 0) {
    http_response_code(400);
    exit("Invalid order ID.");
}

$row = $conn->orders->findOne(
    ["id" => $order_id],
    [
        "projection" => [
            "files" => 1,
            "file_name" => 1,
            "file_data" => 1,
            "file_size" => 1,
            "file_extension" => 1,
            "page_count" => 1,
            "student_id" => 1,
        ],
    ],
);

if (!$row) {
    http_response_code(404);
    exit("Order or document not found.");
}

// Students may only download their own documents.
// Staff and Admin retain queue access.
if (
    $role === "student" &&
    (int) ($row["student_id"] ?? 0) !== (int) ($_SESSION["user_id"] ?? 0)
) {
    http_response_code(403);
    exit("Access denied.");
}

$files = order_files($row);

$index = (int) ($_GET["file"] ?? 0);
if (!isset($files[$index])) {
    $index = 0;
}

if (!isset($files[$index])) {
    http_response_code(404);
    exit("Document is not stored in the database.");
}

$file_name = basename(trim((string) $files[$index]["name"]));

$file_data = $files[$index]["data"];

if ($file_data instanceof MongoDB\BSON\Binary) {
    $file_data = $file_data->getData();
}

if ($file_name === "" || $file_data === null || $file_data === "") {
    http_response_code(404);
    exit("Document is not stored in the database.");
}

$extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
$mime = [
    "pdf" => "application/pdf",
    "png" => "image/png",
    "jpg" => "image/jpeg",
    "jpeg" => "image/jpeg",
    "webp" => "image/webp",
    "gif" => "image/gif",
];
$content_type = $mime[$extension] ?? "application/octet-stream";

while (ob_get_level()) {
    ob_end_clean();
}
header("Content-Type: " . $content_type);
header(
    'Content-Disposition: attachment; filename="' .
        str_replace('"', "", $file_name) .
        '"',
);
header("Content-Transfer-Encoding: binary");
header("Content-Length: " . strlen($file_data));
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: public");
header("Expires: 0");
echo $file_data;
exit();
