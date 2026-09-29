<?php

require_once "includes/auth.php";

if (isLoggedIn()) {

    echo "Utilizador autenticado.<br>";
    echo "ID: " . currentUserId();

} else {

    echo "Nenhum utilizador autenticado.";

}