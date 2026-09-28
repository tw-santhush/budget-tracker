<?php

require "db.php";

if (!isset($_SESSION["user_id"])) { header("Location: auth.php"); exit; }
if ($_SERVER["REQUEST_METHOD"] !== "POST") { header("Location: budgets.php"); exit; }

$userId     = $_SESSION["user_id"];
$categoryId = (int)($_POST["category_id"] ?? 0);
$limit      = (float)($_POST["limit_amount"] ?? 0);

if ($categoryId <= 0) {
    $_SESSION["flash"] = "Invalid category.";
    $_SESSION["flash_type"] = "error";
    header("Location: budgets.php"); exit;
}

/* ---------- Does a row already exist? ---------- */
$stmt = mysqli_prepare($conn,
    "SELECT limit_id FROM budget_limits WHERE user_id = ? AND category_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $userId, $categoryId);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    if ($limit > 0) {
        $stmt = mysqli_prepare($conn,
            "UPDATE budget_limits SET limit_amount = ?
             WHERE user_id = ? AND category_id = ?");
        mysqli_stmt_bind_param($stmt, "dii", $limit, $userId, $categoryId);
        mysqli_stmt_execute($stmt);
        $_SESSION["flash"] = "Limit updated.";
    } else {
        // 0 means "no limit" → remove the row
        $stmt = mysqli_prepare($conn,
            "DELETE FROM budget_limits WHERE user_id = ? AND category_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $userId, $categoryId);
        mysqli_stmt_execute($stmt);
        $_SESSION["flash"] = "Limit removed.";
    }
} else {
    if ($limit > 0) {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO budget_limits (user_id, category_id, limit_amount)
             VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iid", $userId, $categoryId, $limit);
        mysqli_stmt_execute($stmt);
        $_SESSION["flash"] = "Limit saved.";
    } else {
        $_SESSION["flash"] = "No limit set.";
    }
}

$_SESSION["flash_type"] = "success";
header("Location: budgets.php");
exit;
?>