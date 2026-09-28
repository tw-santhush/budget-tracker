<?php

require "db.php";

if (!isset($_SESSION["user_id"])) { header("Location: auth.php"); exit; }

$userId = $_SESSION["user_id"];

/* ---------- Read the id ---------- */
$txId = 0;
if (isset($_POST["tx_id"])) {
    $txId = (int)$_POST["tx_id"];
} elseif (isset($_GET["id"])) {
    $txId = (int)$_GET["id"];
}

if ($txId <= 0) {
    header("Location: transactions.php");
    exit;
}

/* POST → actually delete */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["confirm"])) {

    $stmt = mysqli_prepare($conn,
        "DELETE FROM transactions WHERE tx_id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $txId, $userId);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        $_SESSION["flash"] = "Transaction deleted.";
        $_SESSION["flash_type"] = "success";
    } else {
        $_SESSION["flash"] = "Transaction not found.";
        $_SESSION["flash_type"] = "error";
    }

    header("Location: transactions.php");
    exit;
}

/* GET → show confirmation */
$stmt = mysqli_prepare($conn,
    "SELECT t.tx_id, t.tx_type, t.amount, t.tx_date, t.description,
            c.category_name
     FROM transactions t
     JOIN categories c ON t.category_id = c.category_id
     WHERE t.tx_id = ? AND t.user_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $txId, $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$tx = mysqli_fetch_assoc($result);

if (!$tx) {
    $_SESSION["flash"] = "Transaction not found.";
    $_SESSION["flash_type"] = "error";
    header("Location: transactions.php");
    exit;
}

function money($n) {
    return "Rs. " . number_format((float)$n, 2);
}

$page  = "transactions";
$title = "Delete Transaction";
require "header.php";
?>

<section class="panel" style="max-width:520px;">
    <h2>Delete this transaction?</h2>

    <p style="color:var(--muted); margin:0 0 18px;">
        This action cannot be undone.
    </p>

    <table style="margin-bottom:22px;">
        <tr>
            <th style="width:130px;">Date</th>
            <td><?php echo htmlspecialchars($tx["tx_date"]); ?></td>
        </tr>
        <tr>
            <th>Category</th>
            <td><?php echo htmlspecialchars($tx["category_name"]); ?></td>
        </tr>
        <tr>
            <th>Description</th>
            <td><?php echo $tx["description"] !== "" ? htmlspecialchars($tx["description"]) : "—"; ?></td>
        </tr>
        <tr>
            <th>Amount</th>
            <td class="<?php echo $tx["tx_type"] === "Income" ? "amt-in" : "amt-ex"; ?>">
                <?php echo ($tx["tx_type"] === "Income" ? "+" : "−") . money($tx["amount"]); ?>
            </td>
        </tr>
        <tr>
            <th>Type</th>
            <td><?php echo htmlspecialchars($tx["tx_type"]); ?></td>
        </tr>
    </table>

    <form method="post" action="delete.php" style="display:flex;gap:8px;flex-wrap:wrap;">
        <input type="hidden" name="tx_id" value="<?php echo (int)$tx["tx_id"]; ?>">
        <input type="hidden" name="confirm" value="1">
        <button type="submit" class="btn" style="background:var(--red);">Yes, delete it</button>
        <a href="transactions.php" class="btn ghost">Cancel</a>
    </form>
</section>

<?php require "footer.php"; ?>