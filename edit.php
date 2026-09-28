<?php

require "db.php";

if (!isset($_SESSION["user_id"])) { header("Location: auth.php"); exit; }

$userId = $_SESSION["user_id"];

/* POST → save the changes */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $txId        = (int)($_POST["tx_id"] ?? 0);
    $txType      = $_POST["tx_type"]     ?? "Expense";
    $categoryId  = (int)($_POST["category_id"] ?? 0);
    $newCategory = trim($_POST["new_category"] ?? "");
    $amount      = $_POST["amount"]      ?? "";
    $txDate      = $_POST["tx_date"]     ?? "";
    $description = trim($_POST["description"] ?? "");

    /* Validate */
    if ($txType !== "Income" && $txType !== "Expense") {
        $_SESSION["flash"] = "Invalid transaction type.";
        $_SESSION["flash_type"] = "error";
        header("Location: transactions.php"); exit;
    }
    if (!is_numeric($amount) || $amount <= 0) {
        $_SESSION["flash"] = "Amount must be greater than 0.";
        $_SESSION["flash_type"] = "error";
        header("Location: transactions.php"); exit;
    }
    if ($txDate === "") {
        $_SESSION["flash"] = "Please pick a date.";
        $_SESSION["flash_type"] = "error";
        header("Location: transactions.php"); exit;
    }

    /* New category? */
    if ($newCategory !== "") {
        $stmt = mysqli_prepare($conn, "SELECT category_id FROM categories WHERE category_name = ?");
        mysqli_stmt_bind_param($stmt, "s", $newCategory);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);

        if ($row) {
            $categoryId = (int)$row["category_id"];
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO categories (category_name) VALUES (?)");
            mysqli_stmt_bind_param($stmt, "s", $newCategory);
            mysqli_stmt_execute($stmt);
            $categoryId = mysqli_insert_id($conn);
        }
    }

    if ($categoryId <= 0) {
        $_SESSION["flash"] = "Please choose a category.";
        $_SESSION["flash_type"] = "error";
        header("Location: transactions.php"); exit;
    }

    /* Update */
    $stmt = mysqli_prepare($conn,
        "UPDATE transactions
         SET category_id = ?, tx_type = ?, amount = ?, tx_date = ?, description = ?
         WHERE tx_id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "isdssii",
        $categoryId, $txType, $amount, $txDate, $description, $txId, $userId);
    mysqli_stmt_execute($stmt);

    $_SESSION["flash"] = "Transaction updated.";
    $_SESSION["flash_type"] = "success";
    header("Location: transactions.php");
    exit;
}

/* GET → show the form */
$txId = (int)($_GET["id"] ?? 0);
if ($txId <= 0) { header("Location: transactions.php"); exit; }

$stmt = mysqli_prepare($conn,
    "SELECT * FROM transactions WHERE tx_id = ? AND user_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $txId, $userId);
mysqli_stmt_execute($stmt);
$tx = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$tx) {
    $_SESSION["flash"] = "Transaction not found.";
    $_SESSION["flash_type"] = "error";
    header("Location: transactions.php");
    exit;
}

/* Load categories */
$categories = array();
$result = mysqli_query($conn, "SELECT category_id, category_name FROM categories ORDER BY category_name");
while ($row = mysqli_fetch_assoc($result)) {
    $categories[] = $row;
}

$page  = "transactions";
$title = "Edit Transaction";
require "header.php";
?>

<section class="panel" style="max-width:720px;">
    <h2>Edit Transaction</h2>

    <form method="post" action="edit.php">
        <input type="hidden" name="tx_id" value="<?php echo (int)$tx["tx_id"]; ?>">

        <!-- Type toggle -->
        <div class="type-toggle">
            <input type="radio" name="tx_type" id="typeExpense" value="Expense"
                <?php if ($tx["tx_type"] === "Expense") echo "checked"; ?>>
            <label for="typeExpense">Expense</label>

            <input type="radio" name="tx_type" id="typeIncome" value="Income"
                <?php if ($tx["tx_type"] === "Income") echo "checked"; ?>>
            <label for="typeIncome">Income</label>
        </div>

        <div id="txForm">

            <div class="field">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id">
                    <?php foreach ($categories as $c): ?>
                        <option value="<?php echo (int)$c["category_id"]; ?>"
                            <?php if ((int)$c["category_id"] === (int)$tx["category_id"]) echo "selected"; ?>>
                            <?php echo htmlspecialchars($c["category_name"]); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="new_category">New category <span style="font-weight:normal;">(optional)</span></label>
                <input type="text" id="new_category" name="new_category" maxlength="50"
                       placeholder="Leave empty to keep current">
            </div>

            <div class="field">
                <label for="amount">Amount (Rs.)</label>
                <input type="number" id="amount" name="amount" min="0.01" step="0.01"
                       required value="<?php echo htmlspecialchars($tx["amount"]); ?>">
            </div>

            <div class="field">
                <label for="tx_date">Date</label>
                <input type="date" id="tx_date" name="tx_date"
                       required value="<?php echo htmlspecialchars($tx["tx_date"]); ?>">
            </div>

            <div class="field">
                <label for="description">Description <span style="font-weight:normal;">(optional)</span></label>
                <input type="text" id="description" name="description" maxlength="150"
                       value="<?php echo htmlspecialchars($tx["description"]); ?>">
            </div>

            <div class="field">
                <label>&nbsp;</label>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <button type="submit" class="btn">Save Changes</button>
                    <a href="transactions.php" class="btn ghost">Cancel</a>
                </div>
            </div>

        </div>
    </form>
</section>

<?php require "footer.php"; ?>