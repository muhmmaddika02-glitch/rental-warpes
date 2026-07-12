<?php
declare(strict_types=1);

$db_host = 'localhost';
$db_name = 'db_gamezone';
$db_user = 'root';
$db_pass = '';
$db_charset = 'utf8mb4';

$dsn = "mysql:host=$db_host;dbname=$db_name;charset=$db_charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$pdo = null;

function getPdo(): PDO {
    global $pdo, $dsn, $db_user, $db_pass, $options;
    if ($pdo === null) {
        try {
            $pdo = new PDO($dsn, $db_user, $db_pass, $options);
            $pdo->setAttribute(PDO::ATTR_TIMEOUT, 5);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new RuntimeException("Database connection failed");
        }
    }
    return $pdo;
}

function resetPdo(): void {
    global $pdo;
    $pdo = null;
}

global $pdo;
$pdo = getPdo();
