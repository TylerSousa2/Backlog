<?php

require_once "includes/csrf.php";
require_once "includes/db.php";

requireLogin();

$currentUserId = currentUserId();

$profileUserId = $_GET["id"] ?? $currentUserId;


/*
 * Informações do utilizador
 */

$sql = "SELECT
            id,
            username,
            email,
            created_at
        FROM users
        WHERE id = :user_id";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":user_id" => $profileUserId
]);

$user = $stmt->fetch();

if (!$user) {
    die("Utilizador não encontrado.");
}


/*
 * Estatísticas da biblioteca
 */

$sql = "SELECT
            COUNT(*) AS total_games,
            SUM(status = 'planned') AS planned_games,
            SUM(status = 'playing') AS playing_games,
            SUM(status = 'completed') AS completed_games,
            SUM(status = 'dropped') AS dropped_games,
            SUM(favorite = 1) AS favorite_games,
            AVG(rating) AS average_rating
        FROM user_games
        WHERE user_id = :user_id";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":user_id" => $profileUserId
]);

$stats = $stmt->fetch();


/*
 * Verificar se estamos a ver o nosso próprio perfil
 */

$isOwnProfile = ($currentUserId == $profileUserId);

$isFollowing = false;

if (!$isOwnProfile) {

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
 * Número de seguidores
 */

$sql = "SELECT COUNT(*)
        FROM follows
        WHERE following_id = :user_id";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":user_id" => $profileUserId
]);

$followersCount = $stmt->fetchColumn();


/*
 * Número de pessoas que segue
 */

$sql = "SELECT COUNT(*)
        FROM follows
        WHERE follower_id = :user_id";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":user_id" => $profileUserId
]);

$followingCount = $stmt->fetchColumn();


/*
 * Jogos favoritos
 */

$sql = "SELECT
            games.id,
            games.rawg_id,
            games.title,
            games.cover,
            user_games.rating
        FROM user_games

        INNER JOIN games
            ON user_games.game_id = games.id

        WHERE user_games.user_id = :user_id
        AND user_games.favorite = 1

        ORDER BY
            user_games.rating DESC,
            user_games.created_at DESC

        LIMIT 5";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":user_id" => $profileUserId
]);

$favoriteGames = $stmt->fetchAll();


/*
 * Top 3 do utilizador
 */

$sql = "SELECT
            user_top_games.position,
            games.id,
            games.rawg_id,
            games.title,
            games.cover,
            user_games.rating

        FROM user_top_games

        INNER JOIN games
            ON user_top_games.game_id = games.id

        LEFT JOIN user_games
            ON user_top_games.game_id = user_games.game_id
            AND user_top_games.user_id = user_games.user_id

        WHERE user_top_games.user_id = :user_id

        ORDER BY user_top_games.position ASC";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":user_id" => $profileUserId
]);

$topGames = $stmt->fetchAll();


$pageTitle = "Perfil de " . $user["username"] . " - GameBacklog";

require_once "includes/header.php";

?>

<h1>
    Perfil de <?php echo htmlspecialchars($user["username"]); ?>
</h1>


<h2>Informações</h2>

<p>

    <strong>Username:</strong>

    <?php echo htmlspecialchars($user["username"]); ?>

</p>


<?php if ($isOwnProfile) { ?>

    <p>

        <strong>Email:</strong>

        <?php echo htmlspecialchars($user["email"]); ?>

    </p>

<?php } ?>


<p>

    <strong>Membro desde:</strong>

    <?php

    echo date(
        "d/m/Y",
        strtotime($user["created_at"])
    );

    ?>

</p>


<h2>Seguidores</h2>

<p>

    <a
        href="users.php?type=followers&id=<?php echo $user["id"]; ?>"
    >

        <strong>Seguidores:</strong>

        <?php echo $followersCount; ?>

    </a>

</p>


<p>

    <a
        href="users.php?type=following&id=<?php echo $user["id"]; ?>"
    >

        <strong>A seguir:</strong>

        <?php echo $followingCount; ?>

    </a>

</p>


<h2>Estatísticas</h2>

<p>

    <strong>Total de jogos:</strong>

    <?php echo $stats["total_games"] ?? 0; ?>

</p>


<p>

    <strong>Planeados:</strong>

    <?php echo $stats["planned_games"] ?? 0; ?>

</p>


<p>

    <strong>A jogar:</strong>

    <?php echo $stats["playing_games"] ?? 0; ?>

</p>


<p>

    <strong>Concluídos:</strong>

    <?php echo $stats["completed_games"] ?? 0; ?>

</p>


<p>

    <strong>Abandonados:</strong>

    <?php echo $stats["dropped_games"] ?? 0; ?>

</p>


<p>

    <strong>Favoritos:</strong>

    <?php echo $stats["favorite_games"] ?? 0; ?>

</p>


<p>

    <strong>Rating médio:</strong>

    <?php

    if ($stats["average_rating"] !== null) {

        echo number_format(
            $stats["average_rating"],
            1
        ) . " / 10";

    } else {

        echo "Sem ratings";

    }

    ?>

</p>


<h2>🏆 Top 3</h2>


<?php if (empty($topGames)) { ?>

    <p>
        Este utilizador ainda não definiu o seu Top 3.
    </p>

<?php } else { ?>

    <?php foreach ($topGames as $topGame) { ?>

        <div>

            <h3>

                <?php

                if ($topGame["position"] == 1) {

                    echo "🥇 1.º lugar";

                } elseif ($topGame["position"] == 2) {

                    echo "🥈 2.º lugar";

                } else {

                    echo "🥉 3.º lugar";

                }

                ?>

            </h3>


            <?php if (!empty($topGame["cover"])) { ?>

                <a
                    href="game.php?id=<?php echo $topGame["rawg_id"]; ?>"
                >

                    <img
                        src="<?php echo htmlspecialchars($topGame["cover"]); ?>"
                        width="200"
                        alt="<?php echo htmlspecialchars($topGame["title"]); ?>"
                    >

                </a>

            <?php } ?>


            <h3>

                <a
                    href="game.php?id=<?php echo $topGame["rawg_id"]; ?>"
                >

                    <?php echo htmlspecialchars($topGame["title"]); ?>

                </a>

            </h3>


            <?php if ($topGame["rating"] !== null) { ?>

                <p>

                    <strong>Rating:</strong>

                    <?php echo $topGame["rating"]; ?> / 10

                </p>

            <?php } ?>


            <hr>

        </div>

    <?php } ?>

<?php } ?>


<h2>Jogos favoritos</h2>


<?php if (empty($favoriteGames)) { ?>

    <p>
        Este utilizador ainda não tem jogos favoritos.
    </p>

<?php } else { ?>

    <?php foreach ($favoriteGames as $favoriteGame) { ?>

        <div>

            <?php if (!empty($favoriteGame["cover"])) { ?>

                <a
                    href="game.php?id=<?php echo $favoriteGame["rawg_id"]; ?>"
                >

                    <img
                        src="<?php echo htmlspecialchars($favoriteGame["cover"]); ?>"
                        width="150"
                        alt="<?php echo htmlspecialchars($favoriteGame["title"]); ?>"
                    >

                </a>

            <?php } ?>


            <h3>

                <a
                    href="game.php?id=<?php echo $favoriteGame["rawg_id"]; ?>"
                >

                    <?php echo htmlspecialchars($favoriteGame["title"]); ?>

                </a>

            </h3>


            <?php if ($favoriteGame["rating"] !== null) { ?>

                <p>

                    <strong>Rating:</strong>

                    <?php echo $favoriteGame["rating"]; ?> / 10

                </p>

            <?php } ?>


            <p>
                ❤️ Favorito
            </p>

            <hr>

        </div>

    <?php } ?>

<?php } ?>


<?php if ($isOwnProfile) { ?>

    <p>

        <a href="library.php">
            Ver a minha biblioteca
        </a>

    </p>


    <p>

        <a href="edit-top.php">
            Editar Top 3
        </a>

    </p>

<?php } else { ?>

<form method="POST" action="follow.php">

    <?php echo csrfField(); ?>

    <input
        type="hidden"
        name="user_id"
        value="<?php echo $user["id"]; ?>"
    >

    <button type="submit">

        <?php if ($isFollowing) { ?>

            Deixar de seguir

        <?php } else { ?>

            Seguir

        <?php } ?>

    </button>

</form>

<?php } ?>


<?php require_once "includes/footer.php"; ?>