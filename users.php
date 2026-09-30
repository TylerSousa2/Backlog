<?php

require_once "includes/auth.php";
require_once "includes/db.php";
require_once "includes/functions.php";

requireLogin();

$type = $_GET["type"] ?? null;

if (!in_array($type, ["followers", "following"], true)) {
    die("Tipo de utilizadores inválido.");
}

$profileUserId = validateId(
    $_GET["id"] ?? null,
    "Utilizador inválido."
);


/*
|--------------------------------------------------------------------------
| Obter utilizador
|--------------------------------------------------------------------------
*/

$profileUser = getUserById(
    $pdo,
    $profileUserId
);

if (!$profileUser) {
    die("Utilizador não encontrado.");
}


/*
|--------------------------------------------------------------------------
| Obter utilizadores
|--------------------------------------------------------------------------
*/

if ($type === "followers") {

    $sql = "SELECT
                users.id,
                users.username

            FROM follows

            INNER JOIN users
                ON users.id = follows.follower_id

            WHERE follows.following_id = :user_id

            ORDER BY users.username ASC";

} else {

    $sql = "SELECT
                users.id,
                users.username

            FROM follows

            INNER JOIN users
                ON users.id = follows.following_id

            WHERE follows.follower_id = :user_id

            ORDER BY users.username ASC";
}

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":user_id" => $profileUserId
]);

$users = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Dados para apresentação
|--------------------------------------------------------------------------
*/

$profileUsername = e(
    $profileUser["username"]
);

$pageTitle = (
    $type === "followers"
    ? "Seguidores"
    : "A seguir"
) . " - GameBacklog";


require_once "includes/header.php";

?>

<h1>

    <?php if ($type === "followers") { ?>

        Seguidores de

    <?php } else { ?>

        A seguir de

    <?php } ?>

    <?php echo $profileUsername; ?>

</h1>


<?php if (empty($users)) { ?>

    <p>

        <?php if ($type === "followers") { ?>

            Este utilizador ainda não tem seguidores.

        <?php } else { ?>

            Este utilizador ainda não segue ninguém.

        <?php } ?>

    </p>

<?php } else { ?>

    <?php foreach ($users as $user) { ?>

        <?php
        $userId = (int) $user["id"];
        ?>

        <div>

            <a href="profile.php?id=<?php echo $userId; ?>">

                <?php echo e($user["username"]); ?>

            </a>

        </div>

    <?php } ?>

<?php } ?>


<p>

    <a href="profile.php?id=<?php echo $profileUserId; ?>">
        Voltar ao perfil
    </a>

</p>


<?php require_once "includes/footer.php"; ?>