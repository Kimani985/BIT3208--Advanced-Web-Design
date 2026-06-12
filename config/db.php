<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Database Connection
|--------------------------------------------------------------------------
| This file creates one reusable PDO connection for the whole application.
| Every page that needs the database can include this file and use $pdo.
*/

$dsn = "mysql:host=localhost;dbname=campus_event_board;charset=utf8mb4";
$dbUser = "root";
$dbPassword = "";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPassword, $options);
} catch (PDOException $exception) {
    /*
     * In production, log the real error instead of displaying it.
     * Showing database errors publicly can reveal sensitive information.
     */
    error_log("Database connection failed: " . $exception->getMessage());
    exit("Database connection failed. Please try again later.");
}