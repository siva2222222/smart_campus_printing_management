<?php
// Smart Campus configuration
define('MONGO_URI', '');
define('MONGO_DB', 'smart_campus');

// Razorpay (Test Keys)
define('RAZORPAY_KEY_ID', '');
define('RAZORPAY_KEY_SECRET', '');
define('RAZORPAY_API_URL', 'https://api.razorpay.com/v1');
define('APP_URL', 'http://localhost/smart');

define('EMERGENCY_FEE', 10.00);
define('UPLOAD_MAX_BYTES', 500 * 1024 * 1024);

// Default price per printed page (₹) when service is unknown.
define('PAGE_RATE', 2.00);

// Price per printed page (₹) per printing service.
define('SERVICE_PAGE_RATES', [
    'Black & White Printing' => 2.00,
    'Color Printing'         => 10.00,
    'Spiral Binding'         => 30.00,
]);

// Every Nth order placed by a user is FREE (loyalty).
define('FREE_ORDER_EVERY', 11);
date_default_timezone_set('Asia/Kolkata');
?>
