# Smart Campus — Printing & Stationery Management System

A web-based campus printing and stationery management platform built with **PHP 8** and **MongoDB**. Students upload a PDF/image, the system automatically counts the pages and calculates the price, payment is collected securely through **Razorpay**, and a digital token is issued for live order tracking.

> **Note:** The old `README.txt` and `database.sql` in this folder describe an earlier
> MySQL + PayU version of this project. They are **legacy files** and are not used by
> the current code. The running application uses **MongoDB** and **Razorpay**
> (this document describes the real, current system).

---

## Table of Contents

1. [Overview](#overview)
2. [Key Features](#key-features)
3. [Tech Stack](#tech-stack)
4. [Roles & Permissions](#roles--permissions)
5. [Project Structure](#project-structure)
6. [Request Flow](#request-flow)
7. [Pricing Model](#pricing-model)
8. [Loyalty Program (Every 11th Order Free)](#loyalty-pr

ogram-every-11th-order-free)
9. [Order Lifecycle & Statuses](#order-lifecycle--statuses)
10. [Payment Flow (Razorpay)](#payment-flow-razorpay)
11. [File Upload & Storage](#file-upload--storage)
12. [Database Design](#database-design)
13. [Configuration](#configuration)

14. [Installation & Setup](#installation--setup)
15. [Demo Accounts](#demo-accounts)
16. [Using the Application](#using-the-application)
17. [Security Notes](#security-notes)
18. [Known Issues / Legacy Notes](#known-issues--legacy-notes)
19. [Possible Future Enhancements](#possible-future-enhancements)

---

## Overview

Smart Campus is a three-role (Student / Staff / Admin) printing shop management
system. It digitises the entire print-and-collect workflow:

- A **student** uploads a document (PDF or image), picks a printing service and
  priority, the platform computes the cost automatically, pays via Razorpay, and
  receives a unique order token.
- A **staff** member can view the queue and create print orders on behalf of
  customers (normal delivery only).
- An **admin** oversees all users and orders, and is the only role allowed to
  advance an order's tracking status.

Uploaded files are stored **directly inside MongoDB** (as binary data), so no
separate `uploads/` directory is required.

---

## Key Features

- **Automatic page-based pricing** — PDF pages are counted server-side; price =
  pages × per-service page rate.
- **Multiple services** with different per-page rates (Black & White, Color,
  Spiral Binding).
- **Emergency priority orders** with an extra fee.
- **Loyalty program** — every 11th paid order is completely free.
- **Razorpay** hosted checkout with signature verification.
- **Live order tracking** with statuses: Pending → Processing → Ready →
  Completed / Cancelled.
- **Document download** streamed from MongoDB.
- **Role-based access control** for students, staff and admin.
- **Admin dashboard** with totals (orders, students, staff, paid revenue) and
  pending count.
- Clean, responsive, dependency-free UI (custom CSS + inline SVG icon set).

---

## Tech Stack

| Layer            | Technology                                              |
| ---------------- | ------------------------------------------------------- |
| Language         | PHP 8.2                                                 |
| Database         | MongoDB (via `mongodb/mongodb` PHP driver)              |
| Payments         | Razorpay (Orders API + Checkout.js), test mode          |
| Frontend         | Plain HTML/CSS/JS, PDF.js (page count preview)          |
| Dependency mgr.  | Composer                                                |
| PDF page count   | Custom server parser + PDF.js (client preview)          |
| Dev tooling      | Prettier / php-parser (present in `node_modules`)       |

**Composer dependencies** (`composer.json` / `composer.lock`):

- `mongodb/mongodb ^2.4` (installed 2.4.2)
- `psr/log 3.0.2` (transitive)
- `symfony/polyfill-php85 v1.41.0` (transitive)

---

## Roles & Permissions

| Capability                      | Student | Staff | Admin |
| ------------------------------- | :-----: | :---: | :---: |
| Log in                          |    ✔    |   ✔   |   ✔   |
| Create print order              |    ✔    |   ✔   |   ✘   |
| Choose Emergency priority       |    ✔    |   ✘   |   ✘   |
| View own orders                 |    ✔    |   ✔   |   ✔   |
| View **all** orders             |    ✘    |   ✔   |   ✔   |
| Change tracking status          |    ✘    |   ✘   |   ✔   |
| View users list                 |    ✘    |   ✘   |   ✔   |
| Download documents              | own     |  ✔    |   ✔   |

- Login requires the correct **email + password + selected role/module** to match.
- Passwords are stored using PHP `password_hash()` (bcrypt) and verified with
  `password_verify()`.

---

## Project Structure

```
smart/
├── index.php                 # Public landing page (hero, features, how-it-works)
├── login.php                 # Login with role/module selector
├── logout.php                # Session destroy + redirect
├── dashboard.php             # Role-aware dashboard with stats
│
├── database.php              # Bootstrap: Composer, config, MongoDB, helpers, indexes
├── config/
│   └── config.php            # MongoDB URI/DB, Razorpay keys, pricing & fee constants
│
├── payment.php               # Creates Razorpay order & renders checkout (also free orders)
├── payment_success.php       # Verifies signature, marks order Paid
├── payment_failure.php       # Marks order Failed
├── download.php              # Streams stored document from MongoDB
│
├── create-pass.php           # One-time seeder for demo users
│
├── student/
│   ├── create_order.php      # Upload + auto price + priority → payment
│   └── orders.php            # Student's own order list
│
├── staff/
│   └── orders.php            # Staff: create normal orders + view queue
│
├── admin/
│   ├── orders.php            # Admin/Staff: all orders + tracking control (admin only)
│   ├── users.php             # Admin: list all users
│   └── download.php          # Admin/staff download redirect → ../download.php
│
├── partials/
│   ├── sidebar.php           # Role-based navigation sidebar
│   └── icons.php             # Inline SVG icon set (icon($name))
│
├── css/
│   └── style.css             # Entire application stylesheet
│
├── vendor/                   # Composer packages (mongodb driver, etc.)
├── node_modules/             # Dev tooling only (prettier) — not used at runtime
├── uploads/                  # Legacy; documents are now stored in MongoDB
│
├── .htaccess                 # PHP upload/time limits
├── php.ini                   # Contains: extension=mongodb
├── composer.json / .lock     # PHP dependencies
├── database.sql              # LEGACY MySQL schema (not used)
└── README.txt                # LEGACY PayU/MySQL readme (not used)
```

---

## Request Flow

```
Browser
  │
  ▼
login.php ──(email + password + role)──▶ users collection
  │ success → session(user_id, name, role)
  ▼
dashboard.php ──role-based stats & links
  │
  ├─ Student ─▶ student/create_order.php
  │                │ upload PDF/image
  │                │ count pages → price = pages × service_rate (+ emergency fee)
  │                │ loyalty check (every 11th order free)
  │                ▼
  │            payment.php ──amount<=0──▶ free confirmation (marked Paid)
  │                │ else create Razorpay order
  │                ▼
  │            Razorpay Checkout ──▶ payment_success.php (verify HMAC) → Paid
  │                             └──▶ payment_failure.php → Failed
  │
  ├─ Staff ───▶ staff/orders.php (book normal orders) + admin/orders.php (view queue)
  └─ Admin ───▶ admin/orders.php (all orders + change tracking), admin/users.php
```

All pages include `database.php`, which starts the session, connects to MongoDB,
verifies the connection with a `ping`, and registers helpers + indexes.

---

## Pricing Model

Defined in `config/config.php` and computed by helpers in `database.php`.

**Per-page service rates** (`SERVICE_PAGE_RATES`):

| Service               | Rate (₹ / page) |
| --------------------- | :-------------: |
| Black & White Printing|       2.00      |
| Color Printing        |      10.00      |
| Spiral Binding        |      30.00      |
| Any other/unknown     |  `PAGE_RATE` = 2.00 (fallback) |

**Formula**

```
base_amount = page_count × service_page_rate(service)
amount      = base_amount + emergency_fee      (normal case)
```

- **Page count**: PDFs are counted server-side; images count as **1 page**.
- **Emergency fee** (`EMERGENCY_FEE`) is currently **10.00** and is only added
  when `order_type = 'Emergency'` (students only). If the order is a loyalty
  free order, the emergency fee is waived too.

**Server vs. client counting**

- Server (`pdf_count_pages()` in `database.php`) is authoritative:
  1. Counts `/Type /Page` objects.
  2. Falls back to the page-tree `/Count` value.
  3. Falls back to inflating compressed object streams.
- Client (PDF.js in the forms) shows a **live preview** only; the final amount is
  always decided on the server.

---

## Loyalty Program (Every 11th Order Free)

Controlled by `FREE_ORDER_EVERY` (default **11**) and `loyalty_free_order()`.

- The system counts the user's **Paid** orders.
- When that count is `10, 21, 32, …` (`count % 11 == 10`), the **next order is
  free** — the 11th, 22nd, 33rd order, and so on.
- A free order:
  - is charged **₹0** (emergency fee waived),
  - is inserted with `is_free = true` and `loyalty_discount` = waived amount,
  - is marked **Paid** immediately in `payment.php` (no Razorpay call), and
  - shows a **“Free Order Confirmed”** page.

> Currently the loyalty cycle applies to **student** orders (keyed on
> `student_id`). Staff-created orders get service pricing but do not draw from the
> free cycle.

---

## Order Lifecycle & Statuses

**Tracking status** (`status`) — changed only by **Admin**:

```
Pending ─▶ Processing ─▶ Ready ─▶ Completed
   └────────────────▶ Cancelled
```

**Payment status** (`payment_status`):

```
Pending ─▶ Paid
        └▶ Failed
```

- New orders start as `payment_status = Pending`, `status = Pending`.
- Successful Razorpay verification sets `payment_status = Paid`.
- Free/loyalty orders set `payment_status = Paid` directly.

---

## Payment Flow (Razorpay)

1. `payment.php` loads the order, checks access, and computes the amount in
   **paise** (`amount × 100`).
2. If the order is free (`is_free` or amount ≤ 0) → marked Paid and a
   confirmation page is shown. **No** Razorpay call.
3. Otherwise it creates a Razorpay **Order** via the Orders API
   (`POST /orders` with cURL, Basic auth using key id/secret) and stores
   `razorpay_order_id` and `payment_txn_id` (`SCTXN<id>_<timestamp>`) on the order.
4. The browser opens **Razorpay Checkout** (`checkout.js`) using the created
   order id and prefilled user details.
5. On success, the browser is redirected to `payment_success.php` with
   `razorpay_payment_id`, `razorpay_order_id`, `razorpay_signature` and `txnid`.
6. `payment_success.php` verifies the signature using
   `hash_hmac('sha256', order_id|payment_id, secret)` and `hash_equals()`. Only
   then is the order marked **Paid**.
7. On failure, `payment_failure.php` marks the order **Failed** and shows the
   error reason.

> The keys in `config/config.php` are **Razorpay test/sandbox keys**. Replace them
> with your own and never publish live secrets.

---

## File Upload & Storage

- **Allowed types:** `pdf`, `png`, `jpg`, `jpeg`, `webp`, `gif`.
- **Max size:** 500 MB (`UPLOAD_MAX_BYTES`), enforced in PHP and in `.htaccess`.
- Files are read with `file_get_contents()` and stored in MongoDB as
  `MongoDB\BSON\Binary` (`TYPE_GENERIC`) in the order's `file_data` field.
- `download.php` streams the stored bytes back with the correct `Content-Type`
  and `Content-Disposition: attachment` header, after checking login/role.
- `admin/download.php` simply redirects to `download.php` (with an admin/staff
  access check).

---

## Database Design

MongoDB database: **`smart_campus`** (name from `MONGO_DB`).

### Collections

**`users`**

| Field        | Type   | Notes                                   |
| ------------ | ------ | --------------------------------------- |
| `id`         | int    | Incrementing id                         |
| `name`       | string | Display name                            |
| `email`      | string | Unique (indexed)                        |
| `password`   | string | bcrypt hash                             |
| `role`       | string | `admin` / `staff` / `student`           |
| `created_at` | string | Timestamp                               |

**`orders`**

| Field               | Type            | Notes                                       |
| ------------------- | --------------- | ------------------------------------------- |
| `id`                | int             | Incrementing id                             |
| `order_token`       | string          | Unique, e.g. `SC260901A1B2C3D4`             |
| `student_id`        | int / null      | Set for student orders                      |
| `created_by`        | int / null      | Set for staff-created orders                |
| `service`           | string          | Printing service name                       |
| `page_count`        | int             | Detected pages                              |
| `page_rate`         | float           | Rate applied per page                       |
| `base_amount`       | float           | pages × rate                                |
| `is_free`           | bool            | Loyalty free order                          |
| `loyalty_discount`  | float           | Amount waived on free orders                |
| `emergency_fee`     | float           | Emergency priority fee                      |
| `amount`            | float           | Final charge (0 if free)                    |
| `order_type`        | string          | `Normal` / `Emergency`                      |
| `file_name`         | string          | Original filename                           |
| `file_data`         | Binary          | Document bytes (MongoDB Binary)             |
| `file_size`         | int             | Bytes                                       |
| `file_extension`    | string          | Lowercase extension                         |
| `payment_status`    | string          | `Pending` / `Paid` / `Failed`               |
| `payment_txn_id`    | string / null   | Internal txn id (`SCTXN…` / `FREE…`)        |
| `razorpay_order_id` | string / null   | Set when Razorpay order is created          |
| `payment_method`    | string          | e.g. `Loyalty (Free)`                       |
| `status`            | string          | Tracking status                             |
| `created_at`        | string          | Timestamp                                   |

**`counters`** — used by `next_id()` for atomic auto-increment via
`findOneAndUpdate` with `$inc`.

**`stationery_products`**, **`stationery_orders`** — indexed for a planned
stationery module (UI currently hidden/disabled in the sidebar).

### Indexes (created automatically on bootstrap)

- `orders.order_token` (unique), `orders.student_id`, `orders.created_by`,
  `orders.payment_txn_id`
- `users.email` (unique)
- `stationery_products.name`, `stationery_orders.order_token` (unique)

---

## Configuration

Edit `config/config.php`:

```php
define('MONGO_URI', 'mongodb://localhost:27017');
define('MONGO_DB', 'smart_campus');

// Razorpay (replace with your own keys)
define('RAZORPAY_KEY_ID', 'rzp_test_...');
define('RAZORPAY_KEY_SECRET', '...');
define('RAZORPAY_API_URL', 'https://api.razorpay.com/v1');
define('APP_URL', 'http://localhost/smart');

define('EMERGENCY_FEE', 10.00);          // Emergency priority fee (₹)
define('UPLOAD_MAX_BYTES', 500 * 1024 * 1024); // 500 MB

define('PAGE_RATE', 2.00);               // Fallback per-page rate
define('SERVICE_PAGE_RATES', [
    'Black & White Printing' => 2.00,
    'Color Printing'         => 10.00,
    'Spiral Binding'         => 30.00,
]);
define('FREE_ORDER_EVERY', 11);          // Every 11th order is free
```

---

## Installation & Setup

### Prerequisites

- PHP **8.2+** with the **MongoDB PHP extension** enabled
- **MongoDB** server running locally on port `27017`
- **Composer**
- A web server (XAMPP/Apache recommended on Windows)

### Steps

1. **Place the project** in your web root, e.g. `C:\xampp\htdocs\smart`.

2. **Enable the MongoDB extension** in PHP. Ensure your `php.ini` has:
   ```ini
   extension=mongodb
   ```
   (The root `php.ini` in this project is a marker for this setting.)

3. **Start** Apache and MongoDB.

4. **Install PHP dependencies**:
   ```bash
   composer install
   ```
   (or `php composer.phar install`).

5. **Verify** `MONGO_URI` / `MONGO_DB` in `config/config.php`. The database and
   indexes are created automatically on first request.

6. **Seed demo users** (creates admin/staff/student) by opening:
   ```
   http://localhost/smart/create-pass.php
   ```

7. **Open the app**:
   ```
   http://localhost/smart/
   ```

> For large uploads, `.htaccess` already sets `upload_max_filesize=500M`,
> `post_max_size=520M`, `max_execution_time=600`, `max_input_time=600`.
> Restart Apache after changing PHP settings.

---

## Demo Accounts

Created by `create-pass.php` (or the legacy `database.sql`):

| Role    | Email                     | Password   |
| ------- | ------------------------- | ---------- |
| Admin   | `admin@smartcampus.com`   | `admin123` |
| Staff   | `staff@smartcampus.com`   | `staff123` |
| Student | `student@smartcampus.com` | `student123`|

Select the matching **module** (role) on the login page.

---

## Using the Application

### Student
1. Log in as **Student** → **New Order**.
2. Choose a service, upload a PDF/image, optionally enable **Emergency**.
3. The total updates automatically (pages × rate). Submit.
4. Complete payment on Razorpay (or see the free-order confirmation).
5. Track status under **My Orders**.

### Staff
1. Log in as **Staff** → **Orders**.
2. Book a **normal** order on behalf of a customer (file + amount auto-calc).
3. Staff can view the full order queue but **cannot** change tracking status.

### Admin
1. Log in as **Admin** → **Tracking Orders**.
2. Review all orders (owner, service, priority, total, payment, document).
3. Advance the **tracking status** (Pending → Processing → Ready → Completed /
   Cancelled).
4. Manage users via **Users**.

---

## Security Notes

- Passwords are hashed with bcrypt (`password_hash` / `password_verify`).
- Output is escaped with `e()` (`htmlspecialchars`) to reduce XSS.
- Order access is checked per role (students only see their own orders).
- Tracking-status updates are admin-only (enforced server-side).
- Razorpay signatures are verified with `hash_hmac` + `hash_equals` before an
  order is marked Paid.
- **Do not commit real secrets.** Replace the test keys in `config/config.php`
  and keep `config/` out of any public repository.

---

## Known Issues / Legacy Notes

- **Legacy files:** `README.txt` and `database.sql` describe an obsolete
  MySQL + PayU stack and are not used by the running code (which is MongoDB +
  Razorpay). They can be removed.
- **Sidebar links are root-absolute** (e.g. `/dashboard.php`), which assumes the
  app is served from the web root. If hosted under a subfolder (e.g. `/smart`),
  those links may need to be adjusted.
- **Dashboard stats are global**, not scoped to the logged-in user (e.g. a
  student sees platform-wide totals). Consider scoping per role/user.
- **`vendors/` and `node_modules/`** contain third-party code; `node_modules`
  (prettier) is development-only.
- `uploads/` still contains a leftover test image but is no longer used — files
  are stored in MongoDB.
- The stationery module (tables/indexes exist, sidebar entries commented out) is
  not active in the UI.
- `phpinfo.php` exists for debugging and should be removed in production.

---

## Possible Future Enhancements

- Per-user dashboards and personal revenue/order counts.
- Admin UI to add/edit services and rates, and manage the loyalty rule.
- Real-time status notifications (email/SMS) on status changes.
- Stationery cart/checkout activation.
- Payment refunds and partial payments.
- Role management (create staff/admin from the UI).
- Pagination and search/filter on orders and users.
- CSRF protection and configurable session hardening.
- Unit/integration tests and a CI pipeline.

---

*Smart Campus — Digital printing, stationery ordering and order tracking, built
for campus life.*
