<?php

require "db.php";

if (!isset($_SESSION["user_id"])) { header("Location: auth.php"); exit; }
if ($_SERVER["REQUEST_METHOD"] !== "POST") { header("Location: transactions.php"); exit; }

$userId = $_SESSION["user_id"];

/* ---------- Read values ---------- */
$txType      = $_POST["tx_type"]     ?? "Expense";
$categoryId  = (int)($_POST["category_id"] ?? 0);
$newCategory = trim($_POST["new_category"] ?? "");
$amount      = $_POST["amount"]      ?? "";
$txDate      = $_POST["tx_date"]     ?? "";
$description = trim($_POST["description"] ?? "");

/* ---------- Validation ---------- */
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

if ($newCategory === "" && $categoryId <= 0) {
    $_SESSION["flash"] = "Please choose or add a category.";
    $_SESSION["flash_type"] = "error";
    header("Location: transactions.php"); exit;
}

/* ---------- If a new category was typed, use/create it ---------- */
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
        if (mysqli_stmt_execute($stmt)) {
            $categoryId = mysqli_insert_id($conn);
        } else {
            $_SESSION["flash"] = "Could not add category.";
            $_SESSION["flash_type"] = "error";
            header("Location: transactions.php"); exit;
        }
    }
}

/* ---------- Insert the transaction ---------- */
$stmt = mysqli_prepare($conn,
    "INSERT INTO transactions (user_id, category_id, tx_type, amount, tx_date, description)
     VALUES (?, ?, ?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt, "iisdss",
    $userId, $categoryId, $txType, $amount, $txDate, $description);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION["flash"] = "Transaction added.";
    $_SESSION["flash_type"] = "success";
} else {
    $_SESSION["flash"] = "Database error: " . mysqli_error($conn);
    $_SESSION["flash_type"] = "error";
}

header("Location: transactions.php");
exit;
?>