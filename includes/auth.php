<?php

require_once __DIR__ . "/bootstrap.php";

if (session_status() === PHP_SESSION_NONE) {

    session_set_cookie_params([
        "httponly" => true,
        "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
        "samesite" => "Lax"
    ]);

    session_start();
}


function isLoggedIn(): bool
{
    return isset($_SESSION["user_id"]);
}


function requireLogin(): void
{
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}


function currentUserId(): ?int
{
    return isset($_SESSION["user_id"])
        ? (int) $_SESSION["user_id"]
        : null;
}