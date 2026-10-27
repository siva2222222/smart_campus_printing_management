<?php
// Load session, helpers, and MongoDB connection before processing the request.
require_once __DIR__ . "/database.php";

// If the user is already logged in, redirect before any HTML output.
if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $role = $_POST["role"] ?? "";

    $allowed_roles = ["student", "staff", "admin"];

    if (!in_array($role, $allowed_roles, true)) {
        $error = "Invalid module selected.";
    } else {
        $u = $conn->users->findOne([
            "email" => $email,
            "role" => $role,
        ]);

        if ($u && password_verify($password, $u["password"])) {
            $_SESSION["user_id"] = $u["id"];
            $_SESSION["name"] = $u["name"];
            $_SESSION["role"] = $u["role"];

            header("Location: dashboard.php");
            exit();
        }

        $error = "Invalid email, password or module.";
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login - Smart Campus</title>
    <link rel="stylesheet" href="css/style.css?v=<?= filemtime(__DIR__ . "/css/style.css") ?>">
</head>
<body>
<div class="authshell">
    <div class="authbrand">
        <a class="brand" href="index.php">Smart<span>Campus</span></a>
        <div class="authbrand-content">
            <h2>From digital<br>to physical.</h2>
            <p>Your campus printing workflow, finally simplified. Upload, customize and collect with complete order visibility.</p>
            <ul class="authfeatures">
                <li><span class="chk"><?= icon("check") ?></span> Upload &amp; order in minutes</li>
                <li><span class="chk"><?= icon("check") ?></span> Secure Razorpay checkout</li>
                <li><span class="chk"><?= icon("check") ?></span> Real-time order tracking</li>
            </ul>
        </div>
        <div class="authquote">
            <?= icon("package") ?>
            &ldquo;From Placed to Collected — track every order the moment it moves.&rdquo;
        </div>
    </div>

    <div class="authform">
        <div class="login card animate">
            <div class="brand">Smart<span>Campus</span></div>
            <h2>Welcome back</h2>
            <p>Choose your module to continue.</p>

            <?php if ($error !== ""): ?>
                <div class="alert bad">
                    <?= icon("alert") ?>
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="login.php">
                <label for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                    placeholder="you@example.com"
                    value="<?= e($_POST["email"] ?? "") ?>"
                >

                <label for="password_input">Password</label>
                <div class="pwd-wrap">
                    <input
                        type="password"
                        name="password"
                        id="password_input"
                        required
                        placeholder="••••••••"
                    >
                    <button type="button" class="pwd-toggle" id="pwd_toggle" tabindex="-1" aria-label="Show or hide password">
                        <?= icon("eye") ?>
                    </button>
                </div>

                <label>Module</label>
                <div class="role-tabs">
                    <label class="role-tab">
                        <input type="radio" name="role" value="student" <?= ($_POST["role"] ?? "student") === "student" ? "checked" : "" ?>>
                        <span><?= icon("user") ?></span>
                        Student
                    </label>
                    <label class="role-tab">
                        <input type="radio" name="role" value="staff" <?= ($_POST["role"] ?? "") === "staff" ? "checked" : "" ?>>
                        <span><?= icon("tool") ?></span>
                        Staff
                    </label>
                    <label class="role-tab">
                        <input type="radio" name="role" value="admin" <?= ($_POST["role"] ?? "") === "admin" ? "checked" : "" ?>>
                        <span><?= icon("shield") ?></span>
                        Admin
                    </label>
                </div>

                <button class="btn full" type="submit">
                    Login
                    <?= icon("arrow-right") ?>
                </button>
            </form>

            <a class="back" href="index.php">
                <?= icon("chevron-left") ?>
                Back to home
            </a>
        </div>
    </div>
</div>

<script>
document.getElementById("pwd_toggle").addEventListener("click", function () {
    const pwd = document.getElementById("password_input");
    if (pwd.type === "password") {
        pwd.type = "text";
        this.innerHTML = <?= json_encode(icon("eye-off")) ?>;
    } else {
        pwd.type = "password";
        this.innerHTML = <?= json_encode(icon("eye")) ?>;
    }
});
</script>
</body>
</html>
