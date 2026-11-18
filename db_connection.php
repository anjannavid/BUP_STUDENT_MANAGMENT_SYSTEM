<?php
// Database connection. Edit these values to match your local server.
$host = "127.0.0.1";
$user = "root";
$pass = "";            // XAMPP default is blank. MAMP default is "root".
$db   = "hostel_management";
$port = 3306;          // XAMPP: 3306. MAMP: 8889.

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $db, $port);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    die("Could not connect to the database. Check db_connection.php and make sure you imported hostel_management.sql. ("
        . htmlspecialchars($e->getMessage()) . ")");
}
