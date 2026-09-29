<?php

require_once __DIR__ . "/bootstrap.php";

$rawgApiKey = $_ENV["RAWG_API_KEY"] ?? "";

if ($rawgApiKey === "") {
    die("A API do RAWG não está configurada.");
}