<?php

require_once __DIR__ . "/bootstrap.php";

$rawgApiKey = $_ENV["RAWG_API_KEY"] ?? "";

if ($rawgApiKey === "") {
    die("A API do RAWG não está configurada.");
}


function rawgRequest(string $endpoint): ?array
{
    global $rawgApiKey;

    $url = "https://api.rawg.io/api/" .
        ltrim($endpoint, "/") .
        (str_contains($endpoint, "?") ? "&" : "?") .
        "key=" .
        urlencode($rawgApiKey);

    $context = stream_context_create([
        "http" => [
            "method" => "GET",
            "timeout" => 10,
            "ignore_errors" => true
        ]
    ]);

    $response = file_get_contents(
        $url,
        false,
        $context
    );

    if ($response === false) {
        return null;
    }

    $data = json_decode(
        $response,
        true
    );

    if (!is_array($data)) {
        return null;
    }

    return $data;
}