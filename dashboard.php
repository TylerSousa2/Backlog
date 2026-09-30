<?php

require_once "includes/auth.php";
require_once "includes/functions.php";

requireLogin();

$pageTitle = "Dashboard - GameBacklog";

require_once "includes/header.php";

?>

<h1>
    Olá, <?php echo e($_SESSION["username"] ?? ""); ?>!
</h1>

<p>
    Bem-vindo ao GameBacklog!
</p>


<h2>
    Atalhos
</h2>

<p>
    <a href="search.php">
        Pesquisar jogos
    </a>
</p>

<p>
    <a href="library.php">
        Ver a minha biblioteca
    </a>
</p>


<?php require_once "includes/footer.php"; ?>