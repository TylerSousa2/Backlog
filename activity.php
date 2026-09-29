<?php

require_once "includes/auth.php";
require_once "includes/db.php";

requireLogin();

$userId = currentUserId();

/*
 * Procurar atividades das pessoas que seguimos
 */

$sql = "SELECT
            activities.id,
            activities.type,
            activities.game_id,
            activities.created_at,

            users.id AS user_id,
            users.username,

            games.rawg_id,
            games.title,
            games.cover,

            reviews.rating,
            reviews.review

        FROM activities

        INNER JOIN follows
            ON activities.user_id = follows.following_id

        INNER JOIN users
            ON activities.user_id = users.id

        LEFT JOIN games
            ON activities.game_id = games.id

        LEFT JOIN reviews
            ON activities.user_id = reviews.user_id
            AND activities.game_id = reviews.game_id
            AND activities.type = 'review'

        WHERE follows.follower_id = :user_id

        ORDER BY activities.created_at DESC";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":user_id" => $userId
]);

$activities = $stmt->fetchAll();


/*
 * Procurar Top 3 atual das pessoas
 * que têm atividades top_games
 */

$topGamesByUser = [];

foreach ($activities as $activity) {

    if ($activity["type"] !== "top_games") {
        continue;
    }


    if (isset($topGamesByUser[$activity["user_id"]])) {
        continue;
    }


    $sql = "SELECT
                user_top_games.position,
                games.rawg_id,
                games.title,
                games.cover

            FROM user_top_games

            INNER JOIN games
                ON user_top_games.game_id = games.id

            WHERE user_top_games.user_id = :user_id

            ORDER BY user_top_games.position ASC";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":user_id" => $activity["user_id"]
    ]);

    $topGamesByUser[$activity["user_id"]] = $stmt->fetchAll();
}


$pageTitle = "Atividade - GameBacklog";

require_once "includes/header.php";

?>

<h1>Atividade</h1>


<?php if (empty($activities)) { ?>

    <p>
        Ainda não existe atividade das pessoas que segues.
    </p>

    <p>
        <a href="users-search.php">
            Procurar utilizadores
        </a>
    </p>


<?php } else { ?>

    <?php foreach ($activities as $activity) { ?>

        <div>


            <!-- Utilizador -->

            <p>

                <a
                    href="profile.php?id=<?php echo $activity["user_id"]; ?>"
                >

                    <strong>
                        <?php echo htmlspecialchars($activity["username"]); ?>
                    </strong>

                </a>


                <?php if ($activity["type"] === "added_game") { ?>

                    adicionou um jogo à biblioteca


                <?php } elseif ($activity["type"] === "completed_game") { ?>

                    concluiu


                <?php } elseif ($activity["type"] === "favorite_game") { ?>

                    adicionou aos favoritos


                <?php } elseif ($activity["type"] === "review") { ?>

                    publicou uma review de


                <?php } elseif ($activity["type"] === "top_games") { ?>

                    atualizou o seu Top 3

                <?php } ?>

            </p>


            <?php if ($activity["type"] === "top_games") { ?>


                <!-- Top 3 -->

                <?php
                $topGames = $topGamesByUser[
                    $activity["user_id"]
                ] ?? [];
                ?>


                <?php if (empty($topGames)) { ?>

                    <p>
                        O Top 3 foi removido.
                    </p>

                <?php } else { ?>


                    <?php foreach ($topGames as $topGame) { ?>

                        <div>

                            <p>

                                <?php

                                if ($topGame["position"] == 1) {

                                    echo "🥇";

                                } elseif ($topGame["position"] == 2) {

                                    echo "🥈";

                                } else {

                                    echo "🥉";

                                }

                                ?>

                            </p>


                            <?php if (!empty($topGame["cover"])) { ?>

                                <a
                                    href="game.php?id=<?php echo $topGame["rawg_id"]; ?>"
                                >

                                    <img
                                        src="<?php echo htmlspecialchars($topGame["cover"]); ?>"
                                        width="120"
                                        alt="<?php echo htmlspecialchars($topGame["title"]); ?>"
                                    >

                                </a>

                            <?php } ?>


                            <p>

                                <a
                                    href="game.php?id=<?php echo $topGame["rawg_id"]; ?>"
                                >

                                    <?php echo htmlspecialchars($topGame["title"]); ?>

                                </a>

                            </p>

                        </div>

                    <?php } ?>


                <?php } ?>


            <?php } else { ?>


                <!-- Jogo da atividade -->

                <?php if (!empty($activity["game_id"])) { ?>

                    <div>

                        <?php if (!empty($activity["cover"])) { ?>

                            <a
                                href="game.php?id=<?php echo $activity["rawg_id"]; ?>"
                            >

                                <img
                                    src="<?php echo htmlspecialchars($activity["cover"]); ?>"
                                    width="150"
                                    alt="<?php echo htmlspecialchars($activity["title"]); ?>"
                                >

                            </a>

                        <?php } ?>


                        <h2>

                            <a
                                href="game.php?id=<?php echo $activity["rawg_id"]; ?>"
                            >

                                <?php echo htmlspecialchars($activity["title"]); ?>

                            </a>

                        </h2>

                    </div>

                <?php } ?>


                <!-- Rating da review -->

                <?php if (
                    $activity["type"] === "review" &&
                    $activity["rating"] !== null
                ) { ?>

                    <p>

                        <strong>Rating:</strong>

                        <?php echo $activity["rating"]; ?> / 10

                    </p>

                <?php } ?>


                <!-- Texto da review -->

                <?php if (
                    $activity["type"] === "review" &&
                    !empty($activity["review"])
                ) { ?>

                    <p>

                        <?php

                        echo nl2br(
                            htmlspecialchars($activity["review"])
                        );

                        ?>

                    </p>

                <?php } ?>

            <?php } ?>


            <!-- Data -->

            <p>

                <small>

                    <?php

                    echo date(
                        "d/m/Y H:i",
                        strtotime($activity["created_at"])
                    );

                    ?>

                </small>

            </p>


            <hr>

        </div>

    <?php } ?>

<?php } ?>


<?php require_once "includes/footer.php"; ?>