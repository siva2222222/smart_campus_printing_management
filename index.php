<?php session_start();
require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/partials/icons.php";

$rates = defined("SERVICE_PAGE_RATES") ? SERVICE_PAGE_RATES : [];
$fallbackRate = defined("PAGE_RATE") ? (float) PAGE_RATE : 2.0;
$emergencyFee = defined("EMERGENCY_FEE") ? (float) EMERGENCY_FEE : 10.0;
$freeEvery = defined("FREE_ORDER_EVERY") ? (int) FREE_ORDER_EVERY : 11;

$services = [
    "Black & White Printing" => [
        "icon" => "printer",
        "desc" => "Crisp, economical everyday printing for notes, assignments and handouts.",
    ],
    "Color Printing" => [
        "icon" => "grid",
        "desc" => "Vivid full-colour prints for posters, presentations and project reports.",
    ],
    "Spiral Binding" => [
        "icon" => "files",
        "desc" => "Durable spiral-bound documents with a clean, professional finish.",
    ],
];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Smart Campus — From Digital to Physical.</title>
<meta name="description" content="Your campus printing workflow, finally simplified. Upload documents, customize every detail, and collect your prints with complete order visibility.">
<link rel="stylesheet" href="css/style.css?v=<?= filemtime(__DIR__ . "/css/style.css") ?>">
</head>
<body>

<!-- 1. NAVIGATION -->
<nav>
  <a class="brand" href="index.php">Smart<span>Campus</span></a>
  <button class="nav-toggle" aria-label="Toggle navigation"><?= icon("grid") ?></button>
  <div class="nav-links">
    <a href="#services">Services</a>
    <a href="#how">How it works</a>
    <a href="#tracking">Tracking</a>
    <a href="#rewards">Rewards</a>
    <a href="#faq">FAQ</a>
    <a class="btn small" href="login.php">Login <?= icon("arrow-right") ?></a>
  </div>
</nav>

<!-- 2. HERO -->
<section class="hero">
  <div>
    <div class="label-tag animate">SMART CAMPUS / PRINTING, REIMAGINED</div>
    <h1 class="animate-1">FROM DIGITAL<br>TO <span>PHYSICAL.</span><br>WITHOUT THE WAIT.</h1>
    <p class="animate-2">Your campus printing workflow, finally simplified. Upload documents, customize every detail, and collect your prints with complete order visibility.</p>
    <div class="hero-actions animate-3">
      <a class="btn" href="login.php">Start a Print Order <?= icon("arrow-right") ?></a>
      <a class="btn outline" href="#how">See How It Works</a>
    </div>
    <div class="trust animate-4">
      <div><?= icon("shield-check") ?> Secure Razorpay checkout</div>
      <div><?= icon("clock") ?> Real-time order tracking</div>
      <div><?= icon("star") ?> Every <?= $freeEvery ?>th order free</div>
    </div>
  </div>

  <div class="scene animate-4" aria-hidden="true">
    <div class="scene-inner">
      <div class="sheet a"><span class="tagline">SMART CAMPUS</span></div>
      <div class="sheet b"><span class="tagline">PRINT READY</span></div>
      <div class="sheet c"><span class="tagline">PDF · IMAGE</span></div>
      <div class="printer3d">
        <span class="label">SMART PRINT</span>
        <span class="led"></span>
        <span class="paper-out"></span>
      </div>
      <div class="token-orb">
        <small>Order Token</small>
        <b>#SC-DEMO-01</b>
        <span class="st"><span class="dot"></span> Ready for pickup</span>
      </div>
    </div>
  </div>
</section>

<!-- 3. SERVICE OVERVIEW (real configured data) -->
<section class="section">
  <div class="section-head reveal">
    <span class="eyebrow">At a glance</span>
    <h2>Printing built around your campus</h2>
    <p class="section-sub">Transparent per-page pricing, automatic page counting and secure payments — no queues, no guesswork.</p>
  </div>
  <div class="strip reveal">
    <div class="strip-item"><b>₹<?= number_format($rates["Black & White Printing"] ?? $fallbackRate, 0) ?></b><small>Per B&amp;W page</small></div>
    <div class="strip-item"><b>₹<?= number_format($rates["Color Printing"] ?? $fallbackRate, 0) ?></b><small>Per colour page</small></div>
    <div class="strip-item"><b>₹<?= number_format($rates["Spiral Binding"] ?? $fallbackRate, 0) ?></b><small>Spiral binding</small></div>
    <div class="strip-item"><b>₹<?= number_format($emergencyFee, 0) ?></b><small>Emergency priority</small></div>
  </div>
</section>

<!-- 4. PRINTING SERVICES -->
<section id="services" class="section">
  <div class="section-head reveal">
    <span class="eyebrow">Services</span>
    <h2>Printing priced by the page</h2>
    <p class="section-sub">Your total is calculated automatically from the page count of every file you upload.</p>
  </div>
  <div class="grid3">
    <?php $i = 1; foreach ($services as $name => $s): $rate = $rates[$name] ?? $fallbackRate; ?>
    <div class="card service-card reveal">
      <div class="prod"><span class="rate-chip">₹<?= number_format($rate, 0) ?>/page</span></div>
      <div class="icon-box"><?= icon($s["icon"], "lg") ?></div>
      <b><?= htmlspecialchars($name) ?></b>
      <p><?= htmlspecialchars($s["desc"]) ?></p>
      <div class="price"><b>₹<?= number_format($rate, 0) ?></b><small>per page</small></div>
    </div>
    <?php $i++; endforeach; ?>
  </div>
</section>

<!-- 5. WORKFLOW -->
<section id="how" class="section">
  <div class="section-head reveal">
    <span class="eyebrow">How it works</span>
    <h2>Upload. Customize. Collect.</h2>
    <p class="section-sub">Three simple steps from your screen to the printer.</p>
  </div>
  <div class="flow three">
    <div class="step reveal"><div class="num">1</div><b>Upload</b><p>Pick a service and attach a PDF or image. We count the pages and show the price instantly.</p></div>
    <div class="step reveal"><div class="num">2</div><b>Customize</b><p>Choose colour or black &amp; white, print side and Normal or Emergency priority.</p></div>
    <div class="step reveal"><div class="num">3</div><b>Collect</b><p>Pay securely with Razorpay, then track your token from printing to ready.</p></div>
  </div>
</section>

<!-- 6. ORDER TRACKING DEMO -->
<section id="tracking" class="section">
  <div class="section-head reveal">
    <span class="eyebrow">Tracking</span>
    <h2>Watch your order move in real time</h2>
    <p class="section-sub">Every order gets a unique token and a clear status trail. Tap a step below to preview the experience.</p>
  </div>
  <div class="track-demo reveal">
    <div class="token-row">
      <div class="token" style="flex:1;min-width:240px">
        <small>Order token</small>
        <b>#SC-DEMO-24680</b>
      </div>
      <span class="demo-tag"><span class="dot"></span> Interactive demo — sample data</span>
    </div>

    <div class="track-steps" id="demoSteps">
      <div class="track-step active" data-step="0"><i><?= icon("check") ?></i><span>Placed</span></div>
      <div class="track-step" data-step="1"><i>2</i><span>Printing</span></div>
      <div class="track-step" data-step="2"><i>3</i><span>Ready</span></div>
      <div class="track-step" data-step="3"><i>4</i><span>Collected</span></div>
    </div>

    <div class="track-panel" id="demoPanel">
      <div class="tp-head"><?= icon("package") ?> <b id="demoTitle">Order placed</b></div>
      <p id="demoDesc">Your order has been received and the payment is confirmed. It is now waiting in the print queue.</p>
    </div>
  </div>
</section>

<!-- 7. FEATURE SHOWCASE -->
<section class="section">
  <div class="section-head reveal">
    <span class="eyebrow">Features</span>
    <h2>Built for every role on campus</h2>
    <p class="section-sub">Students, staff and admins each get exactly the tools they need.</p>
  </div>

  <div class="feature-row">
    <div class="feature-copy reveal">
      <span class="eyebrow">For Students</span>
      <h3>Order in seconds, track in real time</h3>
      <p>Upload documents, watch the page count and price update live, and pay with confidence through Razorpay.</p>
      <ul>
        <li><?= icon("check") ?> Automatic page-based pricing per service</li>
        <li><?= icon("check") ?> Rewards on every <?= $freeEvery ?>th order</li>
        <li><?= icon("check") ?> Digital token for queue-free pickup</li>
      </ul>
    </div>
    <div class="stack reveal" aria-hidden="true">
      <div class="layer l1"><span class="chip">ORDER</span><div class="lh"></div><div class="ln med"></div><div class="ln short"></div></div>
      <div class="layer l2"><span class="chip">PRICE</span><div class="lh"></div><div class="ln short"></div><div class="ln med"></div></div>
      <div class="layer l3"><span class="chip">TRACK</span><div class="ln med"></div><div class="ln short"></div></div>
    </div>
  </div>

  <div class="feature-row flip">
    <div class="feature-copy reveal">
      <span class="eyebrow">For Staff &amp; Admin</span>
      <h3>A clear view of every order</h3>
      <p>Staff manage the printing queue while admins oversee users and move orders through the tracking pipeline.</p>
      <ul>
        <li><?= icon("check") ?> Live queue with priority flags</li>
        <li><?= icon("check") ?> Admin-controlled status updates</li>
        <li><?= icon("check") ?> Secure user &amp; role management</li>
      </ul>
    </div>
    <div class="stack reveal" aria-hidden="true">
      <div class="layer l1"><span class="chip">QUEUE</span><div class="lh"></div><div class="ln short"></div><div class="ln med"></div></div>
      <div class="layer l2"><span class="chip">USERS</span><div class="lh"></div><div class="ln med"></div><div class="ln short"></div></div>
      <div class="layer l3"><span class="chip">STATUS</span><div class="ln med"></div><div class="ln short"></div></div>
    </div>
  </div>
</section>

<!-- 8. REWARDS -->
<section id="rewards" class="section">
  <div class="section-head reveal">
    <span class="eyebrow">Rewards</span>
    <h2>Loyalty that prints for free</h2>
  </div>
  <div class="reward-card reveal">
    <div class="reward-copy">
      <span class="eyebrow">Free order benefit</span>
      <h3>Every <?= $freeEvery ?>th paid order is on us</h3>
      <p>Keep printing and your loyalty builds automatically from your paid orders. When you reach the milestone, your next order — printing and emergency fee included — is completely free.</p>
    </div>
    <div class="reward-dots" aria-hidden="true">
      <?php for ($n = 1; $n < $freeEvery; $n++): ?>
        <i><?= $n ?></i>
      <?php endfor; ?>
      <i class="free">FREE</i>
    </div>
  </div>
</section>

<!-- 9. EMERGENCY PRIORITY -->
<section class="section">
  <div class="section-head reveal">
    <span class="eyebrow">Priority</span>
    <h2>In a hurry? Jump the queue.</h2>
  </div>
  <div class="emergency-band reveal">
    <div class="eb-icon"><?= icon("alert", "lg") ?></div>
    <div>
      <h3>Emergency Order</h3>
      <p>Flag any order as Emergency and it is moved to priority handling once payment succeeds. Ideal for deadlines that cannot wait.</p>
    </div>
    <div class="eb-fee">
      <b>₹<?= number_format($emergencyFee, 0) ?></b>
      <small>Priority fee</small>
    </div>
  </div>
</section>

<!-- 10. FAQ -->
<section id="faq" class="section">
  <div class="section-head reveal">
    <span class="eyebrow">FAQ</span>
    <h2>Questions, answered</h2>
  </div>
  <div class="faq">
    <details class="reveal" open>
      <summary>What file types can I upload?</summary>
      <p>PDF, PNG, JPG, JPEG, WEBP and GIF up to 500&nbsp;MB. PDF page counts are detected automatically; images count as a single page.</p>
    </details>
    <details class="reveal">
      <summary>How is the price calculated?</summary>
      <p>Your total is the page count multiplied by the per-page rate of the selected service, plus the emergency fee if the order is marked Emergency. All totals are verified on the server before payment.</p>
    </details>
    <details class="reveal">
      <summary>How does the free order work?</summary>
      <p>Every <?= $freeEvery ?>th paid order qualifies for a completely free order, including the emergency fee. Your progress is based on your real paid orders.</p>
    </details>
    <details class="reveal">
      <summary>How do I collect my printout?</summary>
      <p>After payment you receive a unique order token. Track it on your dashboard and collect it once the status shows Ready.</p>
    </details>
    <details class="reveal">
      <summary>Is my payment secure?</summary>
      <p>Yes. Payments are processed through Razorpay and verified server-side before an order is marked as paid.</p>
    </details>
  </div>
</section>

<!-- 11. FINAL CTA -->
<div class="cta-band reveal">
  <div>
    <h2>Ready to skip the queue?</h2>
    <p>Log in with your student, staff or admin account and place your first order today.</p>
  </div>
  <a class="btn" href="login.php">Start Printing <?= icon("arrow-right") ?></a>
</div>

<!-- 12. FOOTER -->
<footer>
  <div class="footer-top">
    <div>
      <div class="brand">Smart<span>Campus</span></div>
      <p>Digital printing, stationery ordering and live order tracking, built for campus life.</p>
    </div>
    <div class="footer-cols">
      <div>
        <b>Product</b>
        <a href="#services">Services</a>
        <a href="#how">How it works</a>
        <a href="#tracking">Tracking</a>
        <a href="#faq">FAQ</a>
      </div>
      <div>
        <b>Roles</b>
        <a href="login.php">Student</a>
        <a href="login.php">Staff</a>
        <a href="login.php">Admin</a>
      </div>
      <div>
        <b>Support</b>
        <a href="login.php">Log in</a>
        <a href="#rewards">Rewards</a>
        <a href="#how">How it works</a>
      </div>
    </div>
  </div>
  <div class="footer-bottom">Smart Campus &middot; Smart Campus Printing &amp; Stationery Management</div>
</footer>

<script>
  // Mobile navigation
  var nav = document.querySelector("nav");
  var navToggle = document.querySelector(".nav-toggle");
  if (navToggle) {
    navToggle.addEventListener("click", function () { nav.classList.toggle("open"); });
  }
  document.querySelectorAll('nav .nav-links a[href^="#"]').forEach(function (a) {
    a.addEventListener("click", function () { nav.classList.remove("open"); });
  });

  // Order tracking demo (sample data only)
  var steps = [
    { title: "Order placed", desc: "Your order has been received and the payment is confirmed. It is now waiting in the print queue." },
    { title: "Printing in progress", desc: "Your document is being printed on the selected service." },
    { title: "Ready for pickup", desc: "Your printout is ready at the collection desk. Show your token to collect it." },
    { title: "Collected", desc: "This order has been collected. Thanks for printing with Smart Campus." }
  ];
  var stepEls = document.querySelectorAll("#demoSteps .track-step");
  var titleEl = document.getElementById("demoTitle");
  var descEl = document.getElementById("demoDesc");

  stepEls.forEach(function (el) {
    el.addEventListener("click", function () {
      var index = parseInt(el.getAttribute("data-step"), 10);
      stepEls.forEach(function (s, i) {
        s.classList.toggle("active", i === index);
        s.classList.toggle("done", i < index);
      });
      titleEl.textContent = steps[index].title;
      descEl.textContent = steps[index].desc;
    });
  });

  // Scroll reveal
  var reveals = document.querySelectorAll(".reveal");
  if ("IntersectionObserver" in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) { entry.target.classList.add("in"); io.unobserve(entry.target); }
      });
    }, { threshold: 0.12 });
    reveals.forEach(function (el) { io.observe(el); });
  } else {
    reveals.forEach(function (el) { el.classList.add("in"); });
  }
</script>
</body>
</html>
