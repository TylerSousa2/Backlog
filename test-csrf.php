<?php

require_once "includes/csrf.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    verifyCsrfToken();

    echo "CSRF válido.";

    exit;
}

?>

<form method="POST">

    <?php echo csrfField(); ?>

    <button type="submit">
        Testar CSRF
    </button>

</form>