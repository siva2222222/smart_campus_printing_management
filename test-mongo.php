<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config/config.php';

try {
    $client = new MongoDB\Client(MONGO_URI);
    $client->selectDatabase(MONGO_DB)->command(['ping' => 1]);

    echo "MongoDB Atlas connected successfully!";
} catch (Throwable $exception) {
    echo "Connection failed: " . $exception->getMessage();
}