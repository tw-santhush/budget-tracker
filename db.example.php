<?php

/*
 * db.example.php — TEMPLATE ONLY
 *
 * Copy this file to db.php and replace the placeholder values below
 * with your own database credentials:
 *
 *     cp db.example.php db.php      (Linux / macOS)
 *     copy db.example.php db.php    (Windows CMD)
 *
 * db.php is the file the application actually loads.
 * db.example.php is the version that lives in version control.
 */

session_start();

/* ---- Fill these in with your own values ---- */
$DB_HOST = "YOUR_HOST_HERE";      // usually "localhost" for XAMPP
$DB_USER = "YOUR_USER_HERE";      // XAMPP default is "root"
$DB_PASS = "YOUR_PASSWORD_HERE";  // XAMPP default is "" (empty)
$DB_NAME = "YOUR_DATABASE_HERE";  // e.g. "budget_tracker_v2"

$conn = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
?>
