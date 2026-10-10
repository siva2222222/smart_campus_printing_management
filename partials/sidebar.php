<?php

$__role = $_SESSION["role"] ?? "";
$__name = $_SESSION["name"] ?? "";
$active = $active ?? "";
?>

<aside class="sidebar">

  <a class="brand" href="/dashboard.php">
    Smart<span>Campus</span>
  </a>

  <nav class="nav-group">

    <span class="nav-label">Menu</span>

    <a class="nav-item<?= $active === "dashboard" ? " active" : "" ?>"
       href="/dashboard.php">
      <span class="nav-icon"><?= icon("home") ?></span>
      <span class="nav-text">Dashboard</span>
    </a>

<?php if ($__role === "student"): ?>

      <a class="nav-item<?= $active === "new-order" ? " active" : "" ?>"
         href="/student/create_order.php">
        <span class="nav-icon"><?= icon("printer") ?></span>
        <span class="nav-text">New Order</span>
      </a>

      <a class="nav-item<?= $active === "my-orders" ? " active" : "" ?>"
         href="/student/orders.php">
        <span class="nav-icon"><?= icon("package") ?></span>
        <span class="nav-text">My Orders</span>
      </a>

      <!-- <a class="nav-item<?= $active === "stationery" ? " active" : "" ?>"
         href="/stationery.php">
        <span class="nav-icon"><?= icon("store") ?></span>
        <span class="nav-text">Stationery</span>
      </a> -->

<?php elseif ($__role === "staff"): ?>

      <a class="nav-item<?= $active === "orders" ? " active" : "" ?>"
         href="/staff/orders.php">
        <span class="nav-icon"><?= icon("clipboard") ?></span>
        <span class="nav-text">Orders</span>
      </a>

      

    <?php elseif ($__role === "admin"): ?>

      <a class="nav-item<?= $active === "tracking" ? " active" : "" ?>"
         href="/admin/orders.php">
        <span class="nav-icon"><?= icon("package") ?></span>
        <span class="nav-text">Tracking Orders</span>
      </a>

      <a class="nav-item<?= $active === "users" ? " active" : "" ?>"
         href="/admin/users.php">
        <span class="nav-icon"><?= icon("users") ?></span>
        <span class="nav-text">Users</span>
      </a>

    <?php endif; ?>

  </nav>

  <div class="sidebar-foot">

    <div class="sidebar-user">

      <div class="avatar sm">
        <?= strtoupper(substr($__name, 0, 1)) ?>
      </div>

      <div>
        <b><?= e($__name) ?></b>
        <small><?= e(ucfirst($__role)) ?></small>
      </div>

    </div>

    <!-- LOGOUT -->
    <a class="nav-item logout"
       href="/logout.php">

      <span class="nav-icon">
        <?= icon("logout") ?>
      </span>

      <span class="nav-text">
        Logout
      </span>

    </a>

  </div>

</aside>

<button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="Toggle navigation" aria-expanded="false">
  <?= icon("grid") ?>
</button>
<div class="sidebar-scrim" id="sidebarScrim" hidden></div>

<script>
  (function () {
    var toggle = document.getElementById("sidebarToggle");
    var scrim = document.getElementById("sidebarScrim");

    function setOpen(open) {
      document.body.classList.toggle("nav-open", open);
      if (scrim) scrim.hidden = !open;
      if (toggle) toggle.setAttribute("aria-expanded", open ? "true" : "false");
    }

    if (toggle) {
      toggle.addEventListener("click", function () {
        setOpen(!document.body.classList.contains("nav-open"));
      });
    }
    if (scrim) scrim.addEventListener("click", function () { setOpen(false); });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") setOpen(false);
    });

    // Toast helper: showToast("Saved", "success")
    window.showToast = function (message, type) {
      var wrap = document.querySelector(".toast-wrap");
      if (!wrap) {
        wrap = document.createElement("div");
        wrap.className = "toast-wrap";
        document.body.appendChild(wrap);
      }
      var el = document.createElement("div");
      el.className = "toast " + (type || "info");
      el.setAttribute("role", "status");
      el.innerHTML = (type === "success" ? "&#10003; " : type === "error" ? "&#10005; " : "&#9432; ") + message;
      wrap.appendChild(el);
      setTimeout(function () {
        el.style.transition = "opacity .3s, transform .3s";
        el.style.opacity = "0";
        el.style.transform = "translateY(-8px)";
        setTimeout(function () { el.remove(); }, 320);
      }, 3600);
    };

    // Lightweight dropdown menus
    document.querySelectorAll("[data-menu]").forEach(function (menu) {
      var trigger = menu.querySelector(".menu-trigger");
      if (!trigger) return;
      trigger.addEventListener("click", function (e) {
        e.stopPropagation();
        menu.classList.toggle("open");
      });
    });
    document.addEventListener("click", function () {
      document.querySelectorAll(".menu.open").forEach(function (m) { m.classList.remove("open"); });
    });

    // Scroll reveal
    var reveals = document.querySelectorAll(".reveal");
    if (reveals.length) {
      if ("IntersectionObserver" in window) {
        var io = new IntersectionObserver(function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              entry.target.classList.add("in");
              io.unobserve(entry.target);
            }
          });
        }, { threshold: 0.14 });
        reveals.forEach(function (el) { io.observe(el); });
      } else {
        reveals.forEach(function (el) { el.classList.add("in"); });
      }
    }
  })();
</script>
