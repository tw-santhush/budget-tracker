<?php
require "db.php";

/* ---------- Which tab is active? ---------- */
$tab = $_GET["tab"] ?? "login";
if ($tab !== "register") $tab = "login";

/* ---------- Error + success messages ---------- */
$loginError    = "";
$registerError = "";

/* ---------- Handle login ---------- */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "login") {
    $email    = trim($_POST["email"]);
    $password = $_POST["password"];

    if ($email === "" || $password === "") {
        $loginError = "Please fill in both fields.";
        $tab = "login";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT user_id, full_name, password FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user   = mysqli_fetch_assoc($result);

        if ($user && password_verify($password, $user["password"])) {
            $_SESSION["user_id"]   = $user["user_id"];
            $_SESSION["full_name"] = $user["full_name"];
            header("Location: index.php");
            exit;
        } else {
            $loginError = "Invalid email or password.";
            $tab = "login";
        }
    }
}

/* ---------- Handle register ---------- */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "register") {
    $full_name = trim($_POST["full_name"]);
    $email     = trim($_POST["email"]);
    $password  = $_POST["password"];
    $confirm   = $_POST["confirm"];

    if ($full_name === "" || $email === "" || $password === "" || $confirm === "") {
        $registerError = "Please fill in all fields.";
        $tab = "register";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registerError = "Please enter a valid email address.";
        $tab = "register";
    } elseif (strlen($password) < 6) {
        $registerError = "Password must be at least 6 characters.";
        $tab = "register";
    } elseif ($password !== $confirm) {
        $registerError = "Passwords do not match.";
        $tab = "register";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $registerError = "That email is already registered.";
            $tab = "register";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = mysqli_prepare($conn,
                "INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sss", $full_name, $email, $hash);

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION["flash"]      = "Account created. Please log in.";
                $_SESSION["flash_type"] = "success";
                header("Location: auth.php?tab=login");
                exit;
            } else {
                $registerError = "Something went wrong. Please try again.";
                $tab = "register";
            }
        }
    }
}

/* ---------- Flash message ---------- */
$flash     = $_SESSION["flash"]      ?? "";
$flashType = $_SESSION["flash_type"] ?? "success";
unset($_SESSION["flash"], $_SESSION["flash_type"]);

/* ---------- Theme ---------- */
$theme = $_SESSION["theme"] ?? "light";
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars($theme); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Budget Tracker — Sign In</title>
<link rel="icon" type="image/x-icon" href="1.ico">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#eef0f4; --card:#ffffff; --border:#e2e5ec; --text:#20232c; --muted:#767c8a;
  --accent:#4650d6; --accent2:#0a8f8a; --tertiary:#b8860b;
  --accent-soft:#eceeff; --green:#158a52; --red:#c62f2f; --radius:12px;
  --shadow:0 1px 2px rgba(20,24,40,0.05), 0 1px 1px rgba(20,24,40,0.03);
  --font-head:"Space Grotesk",-apple-system,sans-serif;
  --font-mono:"JetBrains Mono",ui-monospace,monospace;
  padding-top:env(safe-area-inset-top,0px); padding-bottom:env(safe-area-inset-bottom,0px);
}
[data-theme="dark"]{
  --bg:#121319; --card:#1a1c24; --border:#2a2d38; --text:#e9eaf0; --muted:#8c92a3;
  --accent:#818cf8; --accent2:#2dd4bf; --tertiary:#e0b23d;
  --accent-soft:#23253a; --green:#3ecb85; --red:#f0625f;
  --shadow:0 1px 3px rgba(0,0,0,0.35);
}
*{box-sizing:border-box;}
html,body{height:100%;}
body{margin:0;background:var(--bg);color:var(--text);font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;-webkit-font-smoothing:antialiased;display:flex;flex-direction:column;min-height:100%;}

/* Header */
header{
  display:flex;align-items:center;justify-content:space-between;padding:16px 24px;
  background-color:var(--card);
  background-image:radial-gradient(color-mix(in srgb, var(--accent) 14%, transparent) 1px, transparent 1px);
  background-size:16px 16px;
  border-bottom:1px solid var(--border);padding-top:calc(16px + env(safe-area-inset-top,0px));
}
.brand{display:flex;align-items:center;gap:9px;}
.logo-mark{display:block;width:26px;height:26px;object-fit:contain;}
header h1{font-family:var(--font-head);font-size:1.2rem;margin:0;font-weight:700;letter-spacing:-0.01em;}

/* Theme switch — button that submits a form */
.switch-form{display:inline;margin:0;}
.switch{position:relative;width:42px;height:24px;border-radius:20px;background:var(--border);cursor:pointer;border:none;padding:0;flex-shrink:0;display:block;}
.switch .knob{position:absolute;top:2px;left:2px;width:20px;height:20px;border-radius:50%;background:var(--card);transition:transform 0.2s;box-shadow:0 1px 2px rgba(0,0,0,0.2);}
[data-theme="dark"] .switch{background:var(--accent);}
[data-theme="dark"] .switch .knob{transform:translateX(18px);}

/* Main */
main{flex:1;display:flex;align-items:center;justify-content:center;padding:30px 20px;}
.auth-wrap{width:100%;max-width:400px;}

/* Tabs */
.tabs{display:flex;background:var(--card);border:1px solid var(--border);border-radius:10px;padding:3px;margin-bottom:20px;gap:2px;}
.tab-btn{flex:1;text-align:center;text-decoration:none;padding:9px 12px;font-size:0.85rem;font-weight:600;color:var(--muted);border-radius:7px;transition:background 0.15s, color 0.15s;}
.tab-btn.active{background:linear-gradient(135deg, var(--accent), var(--accent2));color:#fff;}

/* Panel */
.panel{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:28px;box-shadow:var(--shadow);}
.panel h2{font-family:var(--font-head);font-size:1.1rem;margin:0 0 4px 0;font-weight:600;}
.panel .sub{font-size:0.82rem;color:var(--muted);margin:0 0 20px 0;}

/* Fields */
.field{display:flex;flex-direction:column;gap:5px;margin-bottom:14px;}
.field label{font-size:0.78rem;color:var(--muted);font-weight:600;}
input{padding:10px 12px;border:1px solid var(--border);border-radius:8px;background:var(--bg);color:var(--text);font-size:0.88rem;width:100%;font-family:inherit;}
input:focus{outline:none;border-color:var(--accent);}

/* Button */
.btn{background:var(--accent);color:#fff;border:none;border-radius:8px;padding:11px 16px;cursor:pointer;font-size:0.9rem;font-weight:700;width:100%;transition:opacity 0.15s;font-family:inherit;}
.btn:hover{opacity:0.88;}

/* Messages */
.msg{border-radius:8px;padding:9px 12px;font-size:0.8rem;margin-bottom:14px;}
.msg.error{background:var(--accent-soft);color:var(--red);}
.msg.success{background:#e5f6ec;color:var(--green);}
[data-theme="dark"] .msg.success{background:#0f2c1e;}

.switch-line{text-align:center;font-size:0.83rem;color:var(--muted);margin-top:18px;}
.switch-line a{color:var(--accent);text-decoration:none;font-weight:600;}
.switch-line a:hover{text-decoration:underline;}

footer{text-align:center;padding:20px;color:var(--muted);font-size:0.78rem;}
</style>
</head>
<body>

<header>
  <div class="brand">
    <img src="1.ico" alt="Budget Tracker" width="26" height="26" class="logo-mark">
    <h1>Budget Tracker</h1>
  </div>

  <!-- Theme toggle — no JS, uses a form POST -->
  <form class="switch-form" method="post" action="toggle_theme.php">
    <input type="hidden" name="redirect" value="auth.php<?php echo $tab === 'register' ? '?tab=register' : ''; ?>">
    <button type="submit" class="switch" title="Toggle light / dark"><span class="knob"></span></button>
  </form>
</header>

<main>
  <div class="auth-wrap">

    <div class="tabs">
      <a class="tab-btn <?php echo $tab === 'login'    ? 'active' : ''; ?>" href="auth.php?tab=login">Log In</a>
      <a class="tab-btn <?php echo $tab === 'register' ? 'active' : ''; ?>" href="auth.php?tab=register">Register</a>
    </div>

    <div class="panel">

      <?php if ($flash !== ""): ?>
        <div class="msg <?php echo htmlspecialchars($flashType); ?>">
          <?php echo htmlspecialchars($flash); ?>
        </div>
      <?php endif; ?>

      <?php if ($tab === "login"): ?>

        <!-- ================= LOGIN ================= -->
        <h2>Welcome back</h2>
        <p class="sub">Log in to see your budget dashboard.</p>

        <?php if ($loginError !== ""): ?>
          <div class="msg error"><?php echo htmlspecialchars($loginError); ?></div>
        <?php endif; ?>

        <form method="post" action="auth.php">
          <input type="hidden" name="action" value="login">

          <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="you@example.com" required autofocus>
          </div>

          <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="••••••••" required>
          </div>

          <button class="btn" type="submit">Log In</button>
        </form>

        <p class="switch-line">
          Don't have an account? <a href="auth.php?tab=register">Register</a>
        </p>

      <?php else: ?>

        <!-- ================= REGISTER ================= -->
        <h2>Create your account</h2>
        <p class="sub">Start tracking your income and expenses.</p>

        <?php if ($registerError !== ""): ?>
          <div class="msg error"><?php echo htmlspecialchars($registerError); ?></div>
        <?php endif; ?>

        <form method="post" action="auth.php">
          <input type="hidden" name="action" value="register">

          <div class="field">
            <label for="full_name">Full name</label>
            <input type="text" id="full_name" name="full_name" placeholder="Your name" required autofocus>
          </div>

          <div class="field">
            <label for="reg_email">Email</label>
            <input type="email" id="reg_email" name="email" placeholder="you@example.com" required>
          </div>

          <div class="field">
            <label for="reg_password">Password</label>
            <input type="password" id="reg_password" name="password" placeholder="At least 6 characters" required>
          </div>

          <div class="field">
            <label for="confirm">Confirm password</label>
            <input type="password" id="confirm" name="confirm" placeholder="Re-enter password" required>
          </div>

          <button class="btn" type="submit">Create Account</button>
        </form>

        <p class="switch-line">
          Already have an account? <a href="auth.php?tab=login">Log in</a>
        </p>

      <?php endif; ?>

    </div>
  </div>
</main>

<footer>Personal Budget Tracker</footer>

</body>
</html>