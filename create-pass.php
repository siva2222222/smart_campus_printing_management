<?php

require_once "database.php";

$now = date("Y-m-d H:i:s");

$users = [
    [
        "id" => 1,
        "name" => "Admin",
        "email" => "admin@smartcampus.com",
        "password" => password_hash("admin123", PASSWORD_DEFAULT),
        "role" => "admin",
        "created_at" => $now,
    ],
    [
        "id" => 2,
        "name" => "Staff",
        "email" => "staff@smartcampus.com",
        "password" => password_hash("staff123", PASSWORD_DEFAULT),
        "role" => "staff",
        "created_at" => $now,
    ],
    [
        "id" => 3,
        "name" => "Student",
        "email" => "student@smartcampus.com",
        "password" => password_hash("student123", PASSWORD_DEFAULT),
        "role" => "student",
        "created_at" => $now,
    ],
];

foreach ($users as $user) {
    $existing = $conn->users->findOne([
        "email" => $user["email"],
    ]);

    if (!$existing) {
        $conn->users->insertOne($user);
        echo "Created: {$user["role"]} - {$user["email"]}<br>";
    } else {
        // Backfill missing fields (e.g. created_at) on existing users.
        if (empty($existing["created_at"])) {
            $conn->users->updateOne(
                ["_id" => $existing["_id"]],
                ['$set' => ["created_at" => $user["created_at"]]],
            );
            echo "Updated: {$user["email"]} (added created_at)<br>";
        } else {
            echo "Already exists: {$user["email"]}<br>";
        }
    }
}

echo "<br>Users creation completed.";
