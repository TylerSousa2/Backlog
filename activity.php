<?php

require_once "includes/csrf.php";
require_once "includes/db.php";
require_once "includes/functions.php";

requireLogin();

$userId = currentUserId();


/*
|--------------------------------------------------------------------------
| Textos das atividades
|--------------------------------------------------------------------------
*/

$activityLabels = [
    "added_game" => "adicionou um jogo à biblioteca",
    "completed_game" => "concluiu",
    "favorite_game" => "adicionou aos favoritos",
    "review" => "publicou uma review de",
    "top_games" => "atualizou o seu Top 3"
];


/*
|--------------------------------------------------------------------------
| Procurar atividades das pessoas que seguimos
|--------------------------------------------------------------------------
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
|--------------------------------------------------------------------------
| Likes das atividades
|--------------------------------------------------------------------------
*/

$activityLikes = [];
$userActivityLikes = [];

if (!empty($activities)) {

    $activityIds = [];

    foreach ($activities as $activity) {

        $activityId = validateId(
            $activity["id"],
            "Atividade inválida."
        );

        $activityIds[] = $activityId;
    }

    $activityIds = array_values(
        array_unique($activityIds)
    );


    /*
     * Criar placeholders para o IN
     */

    $placeholders = [];

    foreach ($activityIds as $index => $activityId) {

        $placeholders[] = ":activity_" . $index;
    }

    $inClause = implode(", ", $placeholders);


    /*
     * Contar todos os likes de uma vez
     */

    $sql = "SELECT
                activity_id,
                COUNT(*) AS total_likes

            FROM likes

            WHERE activity_id IN ($inClause)

            GROUP BY activity_id";

    $stmt = $pdo->prepare($sql);

    foreach ($activityIds as $index => $activityId) {

        $stmt->bindValue(
            ":activity_" . $index,
            $activityId,
            PDO::PARAM_INT
        );
    }

    $stmt->execute();

    foreach ($stmt->fetchAll() as $like) {

        $activityId = (int) $like["activity_id"];

        $activityLikes[$activityId] =
            (int) $like["total_likes"];
    }


    /*
     * Verificar quais atividades receberam
     * like do utilizador atual
     */

    $sql = "SELECT activity_id

            FROM likes

            WHERE activity_id IN ($inClause)

            AND user_id = :user_id";

    $stmt = $pdo->prepare($sql);

    foreach ($activityIds as $index => $activityId) {

        $stmt->bindValue(
            ":activity_" . $index,
            $activityId,
            PDO::PARAM_INT
        );
    }

    $stmt->bindValue(
        ":user_id",
        $userId,
        PDO::PARAM_INT
    );

    $stmt->execute();

    foreach ($stmt->fetchAll() as $like) {

        $activityId = (int) $like["activity_id"];

        $userActivityLikes[$activityId] = true;
    }
}


/*
|--------------------------------------------------------------------------
| Procurar Top 3 das pessoas com atividades top_games
|--------------------------------------------------------------------------
*/

$topGamesByUser = [];

$topGameUserIds = [];

foreach ($activities as $activity) {

    if ($activity["type"] !== "top_games") {
        continue;
    }

    $topGameUserIds[] = validateId(
        $activity["user_id"],
        "Utilizador inválido."
    );
}

$topGameUserIds = array_values(
    array_unique($topGameUserIds)
);


if (!empty($topGameUserIds)) {

    $placeholders = [];

    foreach ($topGameUserIds as $index => $topUserId) {

        $placeholders[] = ":top_user_" . $index;
    }

    $inClause = implode(", ", $placeholders);


    $sql = "SELECT
                user_top_games.user_id,
                user_top_games.position,
                games.rawg_id,
                games.title,
                games.cover

            FROM user_top_games

            INNER JOIN games
                ON user_top_games.game_id = games.id

            WHERE user_top_games.user_id IN ($inClause)

            ORDER BY
                user_top_games.user_id ASC,
                user_top_games.position ASC";

    $stmt = $pdo->prepare($sql);

    foreach ($topGameUserIds as $index => $topUserId) {

        $stmt->bindValue(
            ":top_user_" . $index,
            $topUserId,
            PDO::PARAM_INT
        );
    }

    $stmt->execute();

    foreach ($stmt->fetchAll() as $topGame) {

        $topUserId = (int) $topGame["user_id"];

        if (!isset($topGamesByUser[$topUserId])) {
            $topGamesByUser[$topUserId] = [];
        }

        $topGamesByUser[$topUserId][] = $topGame;
    }
}


$pageTitle = "Atividade - GameBacklog";

require_once "includes/header.php";

?>

<h1>
    Atividade
</h1>


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

        <?php

        $activityId = (int) $activity["id"];
        $activityUserId = (int) $activity["user_id"];

        $activityType = $activity["type"];

        $likeCount = $activityLikes[$activityId] ?? 0;
        $userLiked = $userActivityLikes[$activityId] ?? false;

        ?>


        <div>


            <!-- Utilizador -->

            <p>

                <a href="profile.php?id=<?php echo $activityUserId; ?>">

                    <strong>
                        <?php echo e($activity["username"]); ?>
                    </strong>

                </a>


                <?php echo e(
                    $activityLabels[$activityType] ?? ""
                ); ?>

            </p>


            <?php if ($activityType === "top_games") { ?>


                <!-- Top 3 -->

                <?php
                $topGames = $topGamesByUser[
                    $activityUserId
                ] ?? [];
                ?>


                <?php if (empty($topGames)) { ?>

                    <p>
                        O Top 3 foi removido.
                    </p>

                <?php } else { ?>


                    <?php foreach ($topGames as $topGame) { ?>

                        <?php

                        $topGamePosition =
                            (int) $topGame["position"];

                        $topGameRawgId =
                            (int) $topGame["rawg_id"];

                        ?>


                        <div>

                            <p>

                                <?php

                                if ($topGamePosition === 1) {

                                    echo "🥇";

                                } elseif ($topGamePosition === 2) {

                                    echo "🥈";

                                } else {

                                    echo "🥉";

                                }

                                ?>

                            </p>


                            <?php if (!empty($topGame["cover"])) { ?>

                                <a href="game.php?id=<?php echo $topGameRawgId; ?>">

                                    <img src="<?php echo e($topGame["cover"]); ?>" width="120" alt="<?php echo e($topGame["title"]); ?>">

                                </a>

                            <?php } ?>


                            <p>

                                <a href="game.php?id=<?php echo $topGameRawgId; ?>">

                                    <?php echo e($topGame["title"]); ?>

                                </a>

                            </p>

                        </div>

                    <?php } ?>


                <?php } ?>


            <?php } else { ?>


                <!-- Jogo da atividade -->

                <?php if (!empty($activity["game_id"])) { ?>

                    <?php
                    $activityRawgId =
                        (int) $activity["rawg_id"];
                    ?>


                    <div>

                        <?php if (!empty($activity["cover"])) { ?>

                            <a href="game.php?id=<?php echo $activityRawgId; ?>">

                                <img src="<?php echo e($activity["cover"]); ?>" width="150" alt="<?php echo e($activity["title"]); ?>">

                            </a>

                        <?php } ?>


                        <h2>

                            <a href="game.php?id=<?php echo $activityRawgId; ?>">

                                <?php echo e($activity["title"]); ?>

                            </a>

                        </h2>

                    </div>

                <?php } ?>


                <!-- Rating da review -->

                <?php if (
                    $activityType === "review" &&
                    $activity["rating"] !== null
                ) { ?>

                    <p>

                        <strong>
                            Rating:
                        </strong>

                        <?php echo e(
                            (string) $activity["rating"]
                        ); ?>

                        / 10

                    </p>

                <?php } ?>


                <!-- Texto da review -->

                <?php if (
                    $activityType === "review" &&
                    !empty($activity["review"])
                ) { ?>

                    <p>

                        <?php echo nl2br(
                            e($activity["review"])
                        ); ?>

                    </p>

                <?php } ?>

            <?php } ?>


            <!-- Like -->

            <?php if ($activityUserId !== $userId) { ?>

                <form method="POST" action="like.php">

                    <?php echo csrfField(); ?>

                    <input type="hidden" name="activity_id" value="<?php echo $activityId; ?>">

                    <button type="submit">

                        <?php echo $userLiked
                            ? "❤️"
                            : "🤍"; ?>

                        <?php echo $likeCount; ?>

                    </button>

                </form>

            <?php } else { ?>

                <p>
                    ❤️ <?php echo $likeCount; ?>
                </p>

            <?php } ?>


            <!-- Data -->

            <p>

                <small>

                    <?php echo e(
                        date(
                            "d/m/Y H:i",
                            strtotime($activity["created_at"])
                        )
                    ); ?>

                </small>

            </p>


            <hr>

        </div>

    <?php } ?>

<?php } ?>


<?php require_once "includes/footer.php"; ?>