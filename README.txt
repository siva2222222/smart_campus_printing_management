SMART CAMPUS PRINTING MANAGEMENT SYSTEM
=======================================

Purpose
-------
PHP/MySQL web application for one campus printing/stationery shop.
Students upload PDF/image files, choose Normal or Emergency printing,
pay securely through PayU and receive a digital token for tracking.

Emergency Order
---------------
- Normal Order: no extra priority fee.
- Emergency Order: adds EMERGENCY_FEE (default Rs.300).
- Emergency orders are displayed first in Admin tracking.
- Change the fee in config/config.php if required.
- "Within 1 hour" is a target shown in the UI; actual processing depends on staff availability.

XAMPP SETUP
-----------
1. Extract this project to:
   C:\xampp\htdocs\smartcampus

2. Start Apache and MySQL in XAMPP.

3. Open:
   http://localhost/phpmyadmin

4. Import:
   C:\xampp\htdocs\smartcampus\database.sql

5. Database name:
   smart_campus

6. Edit:
   C:\xampp\htdocs\smartcampus\config\config.php
   Add your PayU sandbox key and salt.

7. Open:
   http://localhost/smartcampus/

Demo accounts
-------------
Admin:
admin@smartcampus.com / admin123

Staff:
staff@smartcampus.com / staff123

Student:
student@smartcampus.com / student123

PAYU
----
The project uses PayU Hosted Checkout in sandbox mode.
Payment requests use SHA-512 hashing and the success response is verified
before the order is marked Paid.

FILE UPLOAD
-----------
Allowed: PDF, PNG, JPG, JPEG, WEBP, GIF.
Application limit: 500 MB.

If large uploads are needed, update php.ini:
upload_max_filesize = 500M
post_max_size = 520M
max_execution_time = 600
max_input_time = 600

Then restart Apache.

IMPORTANT
---------
Do not publish real PayU keys or salt.
Emergency Order is a priority-printing feature and does not guarantee
a fixed completion time.
