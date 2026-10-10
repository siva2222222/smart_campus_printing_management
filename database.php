<?php

// =====================================================
// COMPOSER
// =====================================================

require_once __DIR__ . "/vendor/autoload.php";

// =====================================================
// CONFIG
// =====================================================

require_once __DIR__ . "/config/config.php";

require_once __DIR__ . "/partials/icons.php";

// =====================================================
// SESSION
// =====================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =====================================================
// MONGODB CONNECTION
// =====================================================

try {
    $client = new MongoDB\Client(MONGO_URI);

    $conn = $client->selectDatabase(MONGO_DB);

    // Force connection/test
    $conn->command([
        "ping" => 1,
    ]);
} catch (Throwable $e) {
    die("MongoDB connection error: " . e($e->getMessage()));
}

// =====================================================
// HTML ESCAPE
// =====================================================

if (!function_exists("e")) {
    function e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
    }
}

// =====================================================
// LOGIN
// =====================================================

if (!function_exists("require_login")) {
    function require_login()
    {
        if (!isset($_SESSION["user_id"]) || !isset($_SESSION["role"])) {
            header("Location: login.php");

            exit();
        }
    }
}

// =====================================================
// ROLE
// =====================================================

if (!function_exists("require_role")) {
    function require_role($role)
    {
        require_login();

        if (($_SESSION["role"] ?? "") !== $role) {
            header("Location: dashboard.php");

            exit();
        }
    }
}

// =====================================================
// ORDER TOKEN
// =====================================================

if (!function_exists("make_token")) {
    function make_token($prefix = "SC")
    {
        global $conn;

        do {
            $token =
                $prefix .
                date("ymd") .
                strtoupper(substr(bin2hex(random_bytes(8)), 0, 8));

            $existing = $conn->orders->findOne([
                "order_token" => $token,
            ]);
        } while ($existing !== null);

        return $token;
    }
}

// =====================================================
// AUTO INCREMENT ID
// =====================================================

if (!function_exists("next_id")) {
    function next_id($coll)
    {
        global $conn;

        $result = $conn->counters->findOneAndUpdate(
            [
                "_id" => $coll,
            ],

            [
                '$inc' => [
                    "seq" => 1,
                ],
            ],

            [
                "upsert" => true,

                "returnDocument" =>
                    MongoDB\Operation\FindOneAndUpdate::RETURN_DOCUMENT_AFTER,
            ],
        );

        return (int) $result->seq;
    }
}

// =====================================================
// PDF PAGE COUNT
// =====================================================

if (!function_exists("pdf_count_pages")) {
    function pdf_count_pages($data)
    {
        if (!is_string($data) || $data === "") {
            return 0;
        }

        // 1) Plain page objects: "/Type /Page"

        $count = preg_match_all("/\/Type\s*\/Page(?![a-zA-Z])/", $data);

        if ($count > 0) {
            return $count;
        }

        // 2) Page tree "/Count" value

        $max = 0;

        if (preg_match_all("/\/Count\s+(\d+)/", $data, $m)) {
            foreach ($m[1] as $c) {
                $max = max($max, (int) $c);
            }

            if ($max > 0) {
                return $max;
            }
        }

        // 3) Compressed object streams

        if (
            preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $data, $streams)
        ) {
            $total = 0;

            foreach ($streams[1] as $s) {
                $decoded = @gzuncompress($s);

                if ($decoded === false) {
                    $decoded = @gzinflate($s);
                }

                if ($decoded !== false && $decoded !== "") {
                    $total += preg_match_all(
                        "/\/Type\s*\/Page(?![a-zA-Z])/",
                        $decoded,
                    );
                }
            }

            if ($total > 0) {
                return $total;
            }
        }

        return 0;
    }
}

// =====================================================
// DOCUMENT PAGE COUNT
// =====================================================

if (!function_exists("document_page_count")) {
    function document_page_count($data, $extension)
    {
        $extension = strtolower((string) $extension);

        // PDF → real page count
        if ($extension === "pdf") {
            $pages = pdf_count_pages($data);

            // Never charge for zero pages
            return $pages > 0 ? $pages : 1;
        }

        // Images → single page
        return 1;
    }
}

// =====================================================
// SERVICE PAGE RATE
// =====================================================

if (!function_exists("service_page_rate")) {
    function service_page_rate($service)
    {
        $rates = defined("SERVICE_PAGE_RATES") ? SERVICE_PAGE_RATES : [];

        if (is_string($service) && isset($rates[$service])) {
            return (float) $rates[$service];
        }

        return defined("PAGE_RATE") ? (float) PAGE_RATE : 2.0;
    }
}

// =====================================================
// PRINT AMOUNT
// =====================================================

if (!function_exists("print_amount")) {
    function print_amount($pages, $service = null)
    {
        $rate =
            $service === null
                ? (defined("PAGE_RATE")
                    ? (float) PAGE_RATE
                    : 2.0)
                : service_page_rate($service);

        return round(max(0, (int) $pages) * $rate, 2);
    }
}

// =====================================================
// LOYALTY - FREE ORDER
// =====================================================

if (!function_exists("loyalty_free_order")) {
    function loyalty_free_order($owner_field, $owner_id)
    {
        global $conn;

        $every = defined("FREE_ORDER_EVERY") ? (int) FREE_ORDER_EVERY : 11;

        if ($every < 2) {
            return false;
        }

        $count = $conn->orders->countDocuments([
            $owner_field => (int) $owner_id,

            "payment_status" => "Paid",
        ]);

        // 10 paid orders → 11th free, and so on.
        return $count % $every === $every - 1;
    }
}

// =====================================================
// INDEXES
// =====================================================

try {
    // Orders

    $conn->orders->createIndex(["order_token" => 1], ["unique" => true]);

    $conn->orders->createIndex(["student_id" => 1]);

    $conn->orders->createIndex(["created_by" => 1]);

    $conn->orders->createIndex(["payment_txn_id" => 1]);

    // Users

    $conn->users->createIndex(["email" => 1], ["unique" => true]);

    // Stationery

    $conn->stationery_products->createIndex(["name" => 1]);

    $conn->stationery_orders->createIndex(
        ["order_token" => 1],
        ["unique" => true],
    );
} catch (Throwable $e) {
    // Do not stop the entire application
    // if an index already exists.
}

?>
