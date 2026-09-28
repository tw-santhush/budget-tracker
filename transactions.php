<?php

require "db.php";

if (!isset($_SESSION["user_id"])) { header("Location: auth.php"); exit; }

$userId = $_SESSION["user_id"];

/* Load categories for the dropdown */
$categories = array();
$result = mysqli_query($conn, "SELECT category_id, category_name FROM categories ORDER BY category_name");
while ($row = mysqli_fetch_assoc($result)) {
    $categories[] = $row;
}

/* Read GET params (filter / search / sort) */
$filter = $_GET["filter"] ?? "";
$search = trim($_GET["search"] ?? "");
$sort   = $_GET["sort"] ?? "date";
$dir    = $_GET["dir"]  ?? "desc";

if ($dir !== "asc") $dir = "desc";

$sortMap = array(
    "date"     => "t.tx_date",
    "category" => "c.category_name",
    "amount"   => "t.amount"
);
$sortCol = isset($sortMap[$sort]) ? $sortMap[$sort] : "t.tx_date";
$dirSQL  = strtoupper($dir);

/* Build the query */
$sql = "SELECT t.tx_id, t.tx_type, t.amount, t.tx_date, t.description,
               c.category_name
        FROM transactions t
        JOIN categories c ON t.category_id = c.category_id
        WHERE t.user_id = $userId";

if ($filter === "Income" || $filter === "Expense") {
    $sql .= " AND t.tx_type = '$filter'";
}

if ($search !== "") {
    $sql .= " AND (t.description LIKE '%$search%' OR c.category_name LIKE '%$search%')";
}

$sql .= " ORDER BY $sortCol $dirSQL, t.tx_id DESC";

$transactions = array();
$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $transactions[] = $row;
}

/* Helpers */
function money($n) {
    return "Rs. " . number_format((float)$n, 2);
}

function sortUrl($field, $currentSort, $currentDir, $filter, $search) {
    $nextDir = "asc";
    if ($currentSort === $field) {
        $nextDir = ($currentDir === "asc") ? "desc" : "asc";
    }
    $params = array("sort" => $field, "dir" => $nextDir);
    if ($filter !== "") $params["filter"] = $filter;
    if ($search !== "") $params["search"] = $search;
    return "transactions.php?" . http_build_query($params);
}

function sortArrow($field, $currentSort, $currentDir) {
    if ($currentSort !== $field) return "";
    return ($currentDir === "asc") ? " ↑" : " ↓";
}

/* Flash */
$flash     = $_SESSION["flash"]      ?? "";
$flashType = $_SESSION["flash_type"] ?? "success";
unset($_SESSION["flash"], $_SESSION["flash_type"]);

/* Render */
$page  = "transactions";
$title = "Transactions";
require "header.php";
?>

<?php if ($flash !== ""): ?>
    <div class="alert <?php echo htmlspecialchars($flashType); ?>">
        <?php echo htmlspecialchars($flash); ?>
    </div>
<?php endif; ?>

<!-- ==================== ADD FORM ==================== -->
<section class="panel">
    <h2>Add Transaction</h2>

    <form method="post" action="add.php">

        <!-- Type toggle (radio + labels, no JS) -->
        <div class="type-toggle">
            <input type="radio" name="tx_type" id="typeExpense" value="Expense" checked>
            <label for="typeExpense">Expense</label>

            <input type="radio" name="tx_type" id="typeIncome" value="Income">
            <label for="typeIncome">Income</label>
        </div>

        <div id="txForm">

            <div class="field">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id">
                    <?php foreach ($categories as $c): ?>
                        <option value="<?php echo (int)$c["category_id"]; ?>">
                            <?php echo htmlspecialchars($c["category_name"]); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="new_category">New category <span style="font-weight:normal;">(optional)</span></label>
                <input type="text" id="new_category" name="new_category" maxlength="50"
                       placeholder="Type here to add one">
            </div>

            <div class="field">
                <label for="amount">Amount (Rs.)</label>
                <input type="number" id="amount" name="amount" min="0.01" step="0.01" required placeholder="0.00">
            </div>

            <div class="field">
                <label for="tx_date">Date</label>
                <input type="date" id="tx_date" name="tx_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div class="field">
                <label for="description">Description <span style="font-weight:normal;">(optional)</span></label>
                <input type="text" id="description" name="description" maxlength="150" placeholder="e.g. Groceries">
            </div>

            <div class="field">
                <label>&nbsp;</label>
                <button type="submit" class="btn">Add Transaction</button>
            </div>

        </div>
    </form>
</section>

<!-- ==================== FILTER + SEARCH ==================== -->
<section class="panel">
    <h2>All Transactions</h2>

    <form method="get" action="transactions.php" class="toolbar">
        <select name="filter">
            <option value=""        <?php if ($filter === "")        echo "selected"; ?>>All types</option>
            <option value="Income"  <?php if ($filter === "Income")  echo "selected"; ?>>Income</option>
            <option value="Expense" <?php if ($filter === "Expense") echo "selected"; ?>>Expense</option>
        </select>

        <input type="text" name="search" placeholder="Search description or category…"
               value="<?php echo htmlspecialchars($search); ?>">

        <button type="submit" class="btn small">Search</button>

        <?php if ($filter !== "" || $search !== ""): ?>
            <a href="transactions.php" class="btn ghost small">Clear</a>
        <?php endif; ?>

        <span class="count">
            <?php echo count($transactions); ?>
            entr<?php echo count($transactions) === 1 ? "y" : "ies"; ?>
        </span>
    </form>

    <?php if (count($transactions) === 0): ?>
        <div class="empty">No transactions found.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th><a href="<?php echo htmlspecialchars(sortUrl("date",     $sort, $dir, $filter, $search)); ?>">Date<?php     echo sortArrow("date",     $sort, $dir); ?></a></th>
                        <th><a href="<?php echo htmlspecialchars(sortUrl("category", $sort, $dir, $filter, $search)); ?>">Category<?php echo sortArrow("category", $sort, $dir); ?></a></th>
                        <th>Description</th>
                        <th><a href="<?php echo htmlspecialchars(sortUrl("amount",   $sort, $dir, $filter, $search)); ?>">Amount<?php   echo sortArrow("amount",   $sort, $dir); ?></a></th>
                        <th>Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($transactions as $t):
                    $isIncome = ($t["tx_type"] === "Income");
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($t["tx_date"]); ?></td>
                        <td><?php echo htmlspecialchars($t["category_name"]); ?></td>
                        <td><?php echo $t["description"] !== "" ? htmlspecialchars($t["description"]) : "—"; ?></td>
                        <td class="<?php echo $isIncome ? 'amt-in' : 'amt-ex'; ?>">
                            <?php echo ($isIncome ? "+" : "−") . money($t["amount"]); ?>
                        </td>
                        <td><?php echo htmlspecialchars($t["tx_type"]); ?></td>
                        <td class="actions">
                            <a class="btn ghost small" href="edit.php?id=<?php echo (int)$t["tx_id"]; ?>">Edit</a>
                            <a class="btn ghost small" href="delete.php?id=<?php echo (int)$t["tx_id"]; ?>">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require "footer.php"; ?>