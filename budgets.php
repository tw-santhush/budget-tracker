<?php

require "db.php";

if (!isset($_SESSION["user_id"])) { header("Location: auth.php"); exit; }

$userId = $_SESSION["user_id"];

/*  All categories */
$categories = array();
$result = mysqli_query($conn, "SELECT category_id, category_name FROM categories ORDER BY category_name");
while ($row = mysqli_fetch_assoc($result)) {
    $categories[] = $row;
}

/*  This user's budget limits  →  [category_id => limit] */
$limits = array();
$stmt = mysqli_prepare($conn,
    "SELECT category_id, limit_amount FROM budget_limits WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $limits[(int)$row["category_id"]] = (float)$row["limit_amount"];
}

/* This month's expenses per category →  [category_id => spent] */
$monthStart = date("Y-m-01");
$monthEnd   = date("Y-m-t");   // last day of this month

$spent = array();
$stmt = mysqli_prepare($conn,
    "SELECT category_id, SUM(amount) AS total
     FROM transactions
     WHERE user_id = ?
       AND tx_type = 'Expense'
       AND tx_date BETWEEN ? AND ?
     GROUP BY category_id");
mysqli_stmt_bind_param($stmt, "iss", $userId, $monthStart, $monthEnd);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $spent[(int)$row["category_id"]] = (float)$row["total"];
}

/* Helpers  */
function money($n) {
    return "Rs. " . number_format((float)$n, 2);
}

/* Flash */
$flash     = $_SESSION["flash"]      ?? "";
$flashType = $_SESSION["flash_type"] ?? "success";
unset($_SESSION["flash"], $_SESSION["flash_type"]);

/* Render */
$page  = "budgets";
$title = "Budgets";
require "header.php";
?>

<?php if ($flash !== ""): ?>
    <div class="alert <?php echo htmlspecialchars($flashType); ?>">
        <?php echo htmlspecialchars($flash); ?>
    </div>
<?php endif; ?>

<section class="panel">
    <h2>Budget Limits — <?php echo date("F Y"); ?></h2>

    <p style="color:var(--muted); font-size:0.83rem; margin:0 0 18px;">
        Set a monthly limit for each category. Only expenses count towards these limits.
        Leave a limit empty (or set to 0) to remove it.
    </p>

    <?php if (count($categories) === 0): ?>
        <div class="empty">No categories yet.</div>
    <?php else: ?>
        <div class="budget-list">
            <?php foreach ($categories as $c):
                $cid   = (int)$c["category_id"];
                $limit = isset($limits[$cid]) ? $limits[$cid] : 0;
                $used  = isset($spent[$cid])  ? $spent[$cid]  : 0;

                $pct   = ($limit > 0) ? min(100, ($used / $limit) * 100) : 0;
                $cls   = "";
                if      ($pct >= 100) $cls = "over";
                elseif  ($pct >= 75)  $cls = "warn";
            ?>
                <div class="budget-row">

                    <div class="cat-name"><?php echo htmlspecialchars($c["category_name"]); ?></div>

                    <div>
                        <div class="progress-track">
                            <div class="progress-fill <?php echo $cls; ?>" style="width:<?php echo $pct; ?>%"></div>
                        </div>
                        <div style="font-size:0.75rem; font-family:var(--font-mono); color:var(--muted); margin-top:4px;">
                            <?php echo money($used); ?>
                            <?php if ($limit > 0): ?>
                                of <?php echo money($limit); ?>
                            <?php else: ?>
                                — no limit set
                            <?php endif; ?>
                        </div>
                    </div>

                    <form method="post" action="save_budget.php" style="display:flex; gap:6px; align-items:center;">
                        <input type="hidden" name="category_id" value="<?php echo $cid; ?>">
                        <input type="number" name="limit_amount" min="0" step="0.01"
                               class="budget-limit-input"
                               placeholder="Limit"
                               value="<?php echo $limit > 0 ? $limit : ''; ?>">
                        <button type="submit" class="btn small">Save</button>
                    </form>

                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require "footer.php"; ?>