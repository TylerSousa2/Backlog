<?php

require_once "includes/auth.php";


/*
|--------------------------------------------------------------------------
| Destruir sessão
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_ACTIVE) {

    $_SESSION = [];


    /*
     * Apagar cookie da sessão
     */

    if (ini_get("session.use_cookies")) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            "",
            [
                "expires" => time() - 42000,
                "path" => $params["path"],
                "domain" => $params["domain"],
                "secure" => $params["secure"],
                "httponly" => $params["httponly"],
                "samesite" => $params["samesite"] ?? "Lax"
            ]
        );
    }


    /*
     * Destruir sessão no servidor
     */

    session_destroy();
}


header("Location: login.php");

exit;