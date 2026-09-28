<?php
require "db.php";

$current = $_SESSION["theme"] ?? "light";
$_SESSION["theme"] = ($current === "dark") ? "light" : "dark";

$back = "auth.php";
if (isset($_POST["redirect"]) && $_POST["redirect"] !== "") {
    $back = $_POST["redirect"];
}

header("Location: " . $back);
exit;
?>