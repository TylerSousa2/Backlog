<?php

require_once "includes/csrf.php";
require_once "includes/db.php";
require_once "includes/functions.php";

requireLogin();

$currentUserId = currentUserId();

$profileUserId = validateId(
    $_GET["id"] ?? $currentUserId,
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
| Verificar se está a seguir
|--------------------------------------------------------------------------
*/

$isFollowing = false;

if ($profileUserId !== $currentUserId) {

    $sql = "SELECT id
            FROM follows
            WHERE follower_id = :follower_id
            AND following_id = :following_id";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":follower_id" => $currentUserId,
        ":following_id" => $profileUserId
    ]);

    $isFollowing = (bool) $stmt->fetch();
}


/*
|--------------------------------------------------------------------------
| Contar followers
|--------------------------------------------------------------------------
*/

$sql = "SELECT COUNT(*)
        FROM follows
        WHERE following_id = :user_id";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":user_id" => $profileUserId
]);

$followersCount = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| Contar following
|--------------------------------------------------------------------------
*/

$sql = "SELECT COUNT(*)
        FROM follows
        WHERE follower_id = :user_id";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":user_id" => $profileUserId
]);

$followingCount = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| Top 3
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            user_top_games.position,
            games.id,
            games.rawg_id,
            games.title,
            games.cover

        FROM user_top_games

        INNER JOIN games
            ON games.id = user_top_games.game_id

        WHERE user_top_games.user_id = :user_id

        ORDER BY user_top_games.position ASC";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":user_id" => $profileUserId
]);

$topGames = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Estatísticas da biblioteca
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            COUNT(*) AS total_games,
            SUM(status = 'planned') AS planned,
            SUM(status = 'playing') AS playing,
            SUM(status = 'completed') AS completed,
            SUM(status = 'dropped') AS dropped,
            SUM(favorite = 1) AS favorites

        FROM user_games

        WHERE user_id = :user_id";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":user_id" => $profileUserId
]);

$libraryStats = $stmt->fetch();


/*
|--------------------------------------------------------------------------
| Dados para apresentação
|--------------------------------------------------------------------------
*/

$profileUsername = e(
    $profileUser["username"]
);


$pageTitle =
    $profileUsername . " - GameBacklog";


require_once "includes/header.php";

?>

<h1>
    <?php echo $profileUsername; ?>
</h1>


<?php if ($profileUserId === $currentUserId) { ?>

    <p>
        Este é o teu perfil.
    </p>


    <p>
        <a href="edit-top.php">
            Editar Top 3
        </a>
    </p>

<?php } else { ?>

    <form method="POST" action="follow.php">

        <?php echo csrfField(); ?>

        <input type="hidden" name="user_id" value="<?php echo $profileUserId; ?>">

        <button type="submit">

            <?php echo $isFollowing
                ? "Deixar de seguir"
                : "Seguir"; ?>

        </button>

    </form>

<?php } ?>


<h2>
    Top 3
</h2>


<?php if (empty($topGames)) { ?>

    <p>
        Ainda não definiu o Top 3.
    </p>

<?php } else { ?>

    <?php foreach ($topGames as $game) { ?>

        <?php

        $gameRawgId = (int) $game["rawg_id"];
        $gamePosition = (int) $game["position"];

        ?>

        <div>

            <p>
                <strong>
                    <?php echo $gamePosition; ?>º
                </strong>
            </p>


            <a href="game.php?id=<?php echo $gameRawgId; ?>">

                <?php if (!empty($game["cover"])) { ?>

                    <img src="<?php echo e($game["cover"]); ?>" width="150" alt="<?php echo e($game["title"]); ?>">

                <?php } ?>


                <h3>
                    <?php echo e($game["title"]); ?>
                </h3>

            </a>

        </div>

    <?php } ?>

<?php } ?>


<h2>
    Biblioteca
</h2>

<p>
    Total de jogos:
    <strong>
        <?php echo (int) (
            $libraryStats["total_games"] ?? 0
        ); ?>
    </strong>
</p>

<p>
    Planeados:
    <?php echo (int) (
        $libraryStats["planned"] ?? 0
    ); ?>
</p>

<p>
    A jogar:
    <?php echo (int) (
        $libraryStats["playing"] ?? 0
    ); ?>
</p>

<p>
    Completados:
    <?php echo (int) (
        $libraryStats["completed"] ?? 0
    ); ?>
</p>

<p>
    Abandonados:
    <?php echo (int) (
        $libraryStats["dropped"] ?? 0
    ); ?>
</p>

<p>
    Favoritos:
    <?php echo (int) (
        $libraryStats["favorites"] ?? 0
    ); ?>
</p>


<h2>
    Seguidores
</h2>

<p>
    <a href="users.php?type=followers&id=<?php echo $profileUserId; ?>">
        <?php echo $followersCount; ?> seguidores
    </a>
</p>


<h2>
    A seguir
</h2>

<p>
    <a href="users.php?type=following&id=<?php echo $profileUserId; ?>">
        <?php echo $followingCount; ?> a seguir
    </a>
</p>


<?php if ($profileUserId === $currentUserId) { ?>

    <h2>
        Informações
    </h2>

    <p>
        Email:
        <?php echo e($profileUser["email"]); ?>
    </p>

<?php } ?>


<?php require_once "includes/footer.php"; ?>