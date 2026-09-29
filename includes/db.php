<?php

require_once __DIR__ . "/bootstrap.php";

$host = $_ENV["DB_HOST"] ?? "";
$dbname = $_ENV["DB_NAME"] ?? "";
$username = $_ENV["DB_USER"] ?? "";
$password = $_ENV["DB_PASSWORD"] ?? "";

if ($host === "" || $dbname === "" || $username === "") {
    die("A configuração da base de dados não está completa.");
}

try {

    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

} catch (PDOException $e) {

    error_log($e->getMessage());

    die("Não foi possível ligar à base de dados.");
}