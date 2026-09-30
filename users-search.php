<?php

require_once "includes/auth.php";
require_once "includes/db.php";
require_once "includes/functions.php";

requireLogin();

$currentUserId = currentUserId();

$search = trim(
    $_GET["search"] ?? ""
);

$users = [];


/*
|--------------------------------------------------------------------------
| Pesquisar utilizadores
|--------------------------------------------------------------------------
*/

if ($search !== "") {

    $sql = "SELECT
                id,
                username

            FROM users

            WHERE username LIKE :search
            AND id != :current_user_id

            ORDER BY username ASC

            LIMIT 20";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":search" => "%" . $search . "%",
        ":current_user_id" => $currentUserId
    ]);

    $users = $stmt->fetchAll();
}


$pageTitle = "Pesquisar utilizadores - GameBacklog";

require_once "includes/header.php";

?>

<h1>
    Pesquisar utilizadores
</h1>


<form method="GET">

    <input type="text" name="search" placeholder="Username..." value="<?php echo e($search); ?>" required>

    <button type="submit">
        Pesquisar
    </button>

</form>


<hr>


<?php if ($search !== "") { ?>

    <h2>

        Resultados para:

        <?php echo e($search); ?>

    </h2>


    <?php if (empty($users)) { ?>

        <p>
            Não foram encontrados utilizadores.
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

<?php } ?>


<?php require_once "includes/footer.php"; ?>