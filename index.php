<?php
// index.php — Overview page

require "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: auth.php");
    exit;
}

$userId = $_SESSION["user_id"];

/* Totals (income / expense / balance / savings rate) */
$sql = "SELECT
            SUM(CASE WHEN tx_type = 'Income'  THEN amount ELSE 0 END) AS income,
            SUM(CASE WHEN tx_type = 'Expense' THEN amount ELSE 0 END) AS expense
        FROM transactions
        WHERE user_id = $userId";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

$totalIncome  = $row["income"]  ? $row["income"]  : 0;
$totalExpense = $row["expense"] ? $row["expense"] : 0;
$balance      = $totalIncome - $totalExpense;

$savingsRate = 0;
if ($totalIncome > 0) {
    $savingsRate = (($totalIncome - $totalExpense) / $totalIncome) * 100;
}

/* Last 6 months — build the 6 slots first, then fill them */
$months = array();
for ($i = 5; $i >= 0; $i--) {
    $timestamp = mktime(0, 0, 0, date("n") - $i, 1, date("Y"));
    $months[] = array(
        "key"     => date("Y-m", $timestamp),     // e.g. "2026-09"
        "label"   => date("M", $timestamp),        // e.g. "Sep"
        "income"  => 0,
        "expense" => 0
    );
}

$sql = "SELECT DATE_FORMAT(tx_date, '%Y-%m') AS ym,
               tx_type,
               SUM(amount) AS total
        FROM transactions
        WHERE user_id = $userId
          AND tx_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY ym, tx_type";
$result = mysqli_query($conn, $sql);

while ($row = mysqli_fetch_assoc($result)) {
    foreach ($months as $i => $mo) {
        if ($mo["key"] === $row["ym"]) {
            if ($row["tx_type"] === "Income") {
                $months[$i]["income"]  = (float)$row["total"];
            } else {
                $months[$i]["expense"] = (float)$row["total"];
            }
        }
    }
}

/* Tallest bar becomes 100px tall */
$maxMonthly = 1;
foreach ($months as $mo) {
    if ($mo["income"]  > $maxMonthly) $maxMonthly = $mo["income"];
    if ($mo["expense"] > $maxMonthly) $maxMonthly = $mo["expense"];
}

/* Flash message */
$flash     = $_SESSION["flash"]      ?? "";
$flashType = $_SESSION["flash_type"] ?? "success";
unset($_SESSION["flash"], $_SESSION["flash_type"]);

/*  Small helper */
function money($n) {
    return "Rs. " . number_format((float)$n, 2);
}

/* Render */
$page  = "overview";
$title = "Overview";
require "header.php";
?>

<?php if ($flash !== ""): ?>
    <div class="alert <?php echo htmlspecialchars($flashType); ?>">
        <?php echo htmlspecialchars($flash); ?>
    </div>
<?php endif; ?>

<!-- Summary cards -->
<div class="grid-cards">

    <div class="card balance <?php echo $balance < 0 ? 'negative' : ''; ?>">
        <div class="label">Net Balance</div>
        <div class="value"><?php echo money($balance); ?></div>
    </div>

    <div class="card income">
        <div class="label">Total Income</div>
        <div class="value"><?php echo money($totalIncome); ?></div>
    </div>

    <div class="card expense">
        <div class="label">Total Expenses</div>
        <div class="value"><?php echo money($totalExpense); ?></div>
    </div>

</div>

<div class="card rate" style="margin-bottom:28px; max-width:220px;">
    <div class="label">Savings Rate</div>
    <div class="value"><?php echo number_format($savingsRate, 1); ?>%</div>
</div>

<!-- Monthly chart -->
<section class="panel">
    <h2>Monthly Overview — last 6 months</h2>

    <div class="chart">
        <?php foreach ($months as $mo):
            $inH = round(($mo["income"]  / $maxMonthly) * 100);
            $exH = round(($mo["expense"] / $maxMonthly) * 100);
            if ($mo["income"]  > 0 && $inH < 2) $inH = 2;
            if ($mo["expense"] > 0 && $exH < 2) $exH = 2;
        ?>
            <div class="chart-month">
                <div class="bars">
                    <div class="bar in" style="height: <?php echo $inH; ?>px;"
                         title="Income: <?php echo money($mo['income']); ?>"></div>
                    <div class="bar ex" style="height: <?php echo $exH; ?>px;"
                         title="Expense: <?php echo money($mo['expense']); ?>"></div>
                </div>
                <div class="m-label"><?php echo $mo["label"]; ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="legend">
        <span><i class="dot in"></i>Income</span>
        <span><i class="dot ex"></i>Expense</span>
    </div>
</section>

<?php require "footer.php"; ?>