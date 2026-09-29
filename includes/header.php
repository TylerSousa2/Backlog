<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$unreadNotifications = 0;

if (isset($_SESSION["user_id"])) {

    require_once __DIR__ . "/db.php";

    $sql = "SELECT COUNT(*)
            FROM notifications
            WHERE user_id = :user_id
            AND is_read = 0";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":user_id" => $_SESSION["user_id"]
    ]);

    $unreadNotifications = $stmt->fetchColumn();
}

?>

<!DOCTYPE html>
<html lang="pt">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="css/destyle.css">
    <link rel="stylesheet" href="css/style.css">

    <title><?php echo $pageTitle ?? "GameBacklog"; ?></title>

</head>

<body>

<header>

    <a href="index.php">
        <strong>GameBacklog</strong>
    </a>

    <nav>

        <a href="search.php">
            Jogos
        </a>

        <a href="users-search.php">
            Utilizadores
        </a>

        <?php if (isset($_SESSION["user_id"])) { ?>

            <a href="dashboard.php">
                Dashboard
            </a>

            <a href="library.php">
                Biblioteca
            </a>

            <a href="activity.php">
                Atividade
            </a>

            <a href="notifications.php">

                Notificações

                <?php if ($unreadNotifications > 0) { ?>

                    (<?php echo $unreadNotifications; ?>)

                <?php } ?>

            </a>

            <a href="profile.php">
                Perfil
            </a>

            <a href="logout.php">
                Terminar sessão
            </a>

        <?php } else { ?>

            <a href="login.php">
                Entrar
            </a>

            <a href="register.php">
                Criar conta
            </a>

        <?php } ?>

    </nav>

</header>

<hr>