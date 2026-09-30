<?php

require_once "includes/csrf.php";
require_once "includes/rawg.php";
require_once "includes/db.php";
require_once "includes/functions.php";


/*
|--------------------------------------------------------------------------
| Validar ID do jogo
|--------------------------------------------------------------------------
*/

$gameId = validateId(
    $_GET["id"] ?? null,
    "Jogo inválido."
);


/*
|--------------------------------------------------------------------------
| Obter jogo através da API RAWG
|--------------------------------------------------------------------------
*/

$game = rawgRequest(
    "games/" . $gameId
);

if (
    !is_array($game) ||
    empty($game["id"]) ||
    !isset($game["name"])
) {
    die("Não foi possível obter os dados do jogo.");
}

$rawgId = (int) $game["id"];


/*
|--------------------------------------------------------------------------
| Verificar se o jogo está na biblioteca
|--------------------------------------------------------------------------
*/

$inLibrary = false;

if (isLoggedIn()) {

    $userId = currentUserId();

    $inLibrary = isGameInLibrary(
        $pdo,
        $userId,
        $rawgId
    );
}


/*
|--------------------------------------------------------------------------
| Procurar review do utilizador
|--------------------------------------------------------------------------
*/

$userReview = null;

if (isLoggedIn()) {

    $userId = currentUserId();

    $userReview = getUserReview(
        $pdo,
        $userId,
        $rawgId
    );
}


/*
|--------------------------------------------------------------------------
| Buscar reviews de todos os utilizadores
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            reviews.id,
            reviews.user_id,
            reviews.rating,
            reviews.review,
            reviews.created_at,
            reviews.updated_at,
            users.username
        FROM reviews
        INNER JOIN users
            ON reviews.user_id = users.id
        INNER JOIN games
            ON reviews.game_id = games.id
        WHERE games.rawg_id = :rawg_id
        ORDER BY reviews.created_at DESC";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":rawg_id" => $rawgId
]);

$reviews = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Likes das reviews
|--------------------------------------------------------------------------
*/

$reviewLikes = [];
$userReviewLikes = [];

if (!empty($reviews)) {

    $reviewIds = array_map(
        fn($review) => (int) $review["id"],
        $reviews
    );

    $placeholders = implode(
        ",",
        array_fill(0, count($reviewIds), "?")
    );


    /*
     * Obter número de likes de todas as reviews
     * numa única query
     */

    $sql = "SELECT
                review_id,
                COUNT(*) AS total_likes
            FROM likes
            WHERE review_id IN ($placeholders)
            GROUP BY review_id";

    $stmt = $pdo->prepare($sql);

    $stmt->execute($reviewIds);

    $likeCounts = $stmt->fetchAll();


    foreach ($likeCounts as $likeCount) {

        $reviewId = (int) $likeCount["review_id"];

        $reviewLikes[$reviewId] =
            (int) $likeCount["total_likes"];
    }


    /*
     * Garantir que reviews sem likes
     * ficam com 0
     */

    foreach ($reviewIds as $reviewId) {

        if (!isset($reviewLikes[$reviewId])) {
            $reviewLikes[$reviewId] = 0;
        }
    }


    /*
     * Verificar quais reviews foram
     * liked pelo utilizador atual
     */

    if (isLoggedIn()) {

        $userId = currentUserId();

        $sql = "SELECT review_id
                FROM likes
                WHERE user_id = ?
                AND review_id IN ($placeholders)";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $userId,
            ...$reviewIds
        ]);

        $likedReviews = $stmt->fetchAll();


        foreach ($likedReviews as $likedReview) {

            $reviewId = (int) $likedReview["review_id"];

            $userReviewLikes[$reviewId] = true;
        }
    }


    /*
     * Garantir que todas as reviews
     * têm um valor definido
     */

    foreach ($reviewIds as $reviewId) {

        if (!isset($userReviewLikes[$reviewId])) {
            $userReviewLikes[$reviewId] = false;
        }
    }
}


/*
|--------------------------------------------------------------------------
| Processar POST
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    requireLogin();

    verifyCsrfToken();

    $userId = currentUserId();


    /*
    |--------------------------------------------------------------------------
    | Guardar review
    |--------------------------------------------------------------------------
    */

    if (isset($_POST["save_review"])) {

        $rating = validateRating(
            $_POST["rating"] ?? null
        );

        $reviewText = trim(
            $_POST["review"] ?? ""
        );


        /*
         * Validar texto
         */

        if ($reviewText === "") {
            die("A review não pode estar vazia.");
        }


        /*
         * Encontrar o jogo na nossa base de dados
         */

        $existingGame = getGameByRawgId(
            $pdo,
            $rawgId
        );

        if (!$existingGame) {
            die("Este jogo ainda não está na biblioteca.");
        }

        $gameDbId = (int) $existingGame["id"];


        /*
         * Verificar se o utilizador tem o jogo
         */

        $sql = "SELECT id
                FROM user_games
                WHERE user_id = :user_id
                AND game_id = :game_id";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ":user_id" => $userId,
            ":game_id" => $gameDbId
        ]);

        $userGame = $stmt->fetch();

        if (!$userGame) {
            die(
                "Tens de adicionar o jogo à tua biblioteca primeiro."
            );
        }


        /*
         * Verificar se já existe uma review
         */

        $sql = "SELECT id
                FROM reviews
                WHERE user_id = :user_id
                AND game_id = :game_id";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ":user_id" => $userId,
            ":game_id" => $gameDbId
        ]);

        $existingReview = $stmt->fetch();


        if ($existingReview) {

            /*
             * Atualizar review existente
             */

            $sql = "UPDATE reviews
                    SET
                        rating = :rating,
                        review = :review
                    WHERE id = :id
                    AND user_id = :user_id";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":rating" => $rating,
                ":review" => $reviewText,
                ":id" => (int) $existingReview["id"],
                ":user_id" => $userId
            ]);

        } else {

            /*
             * Criar nova review + atividade
             */

            $pdo->beginTransaction();

            try {

                $sql = "INSERT INTO reviews (
                            user_id,
                            game_id,
                            rating,
                            review
                        )
                        VALUES (
                            :user_id,
                            :game_id,
                            :rating,
                            :review
                        )";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ":user_id" => $userId,
                    ":game_id" => $gameDbId,
                    ":rating" => $rating,
                    ":review" => $reviewText
                ]);


                $sql = "INSERT INTO activities (
                            user_id,
                            game_id,
                            type
                        )
                        VALUES (
                            :user_id,
                            :game_id,
                            'review'
                        )";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ":user_id" => $userId,
                    ":game_id" => $gameDbId
                ]);

                $pdo->commit();

            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                error_log($e->getMessage());

                die(
                    "Não foi possível publicar a review."
                );
            }
        }


        redirect(
            "game.php?id=" . $rawgId
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Adicionar à biblioteca
    |--------------------------------------------------------------------------
    */

    if (isset($_POST["add_to_library"])) {

        $pdo->beginTransaction();

        try {

            /*
             * Verificar se o jogo já existe
             */

            $existingGame = getGameByRawgId(
                $pdo,
                $rawgId
            );


            if ($existingGame) {

                $gameDbId = (int) $existingGame["id"];

            } else {

                /*
                 * Guardar jogo na nossa base de dados
                 */

                $sql = "INSERT INTO games (
                            rawg_id,
                            title,
                            description,
                            cover,
                            release_date,
                            genre,
                            platform
                        )
                        VALUES (
                            :rawg_id,
                            :title,
                            :description,
                            :cover,
                            :release_date,
                            :genre,
                            :platform
                        )";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ":rawg_id" => $rawgId,
                    ":title" => $game["name"],
                    ":description" => $game["description_raw"] ?? null,
                    ":cover" => $game["background_image"] ?? null,
                    ":release_date" => $game["released"] ?? null,
                    ":genre" => !empty($game["genres"])
                        ? ($game["genres"][0]["name"] ?? null)
                        : null,
                    ":platform" => !empty($game["platforms"])
                        ? ($game["platforms"][0]["platform"]["name"] ?? null)
                        : null
                ]);

                $gameDbId = (int) $pdo->lastInsertId();
            }


            /*
             * Verificar se a relação já existe
             */

            $sql = "SELECT id
                    FROM user_games
                    WHERE user_id = :user_id
                    AND game_id = :game_id";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":user_id" => $userId,
                ":game_id" => $gameDbId
            ]);

            $existingUserGame = $stmt->fetch();


            /*
             * Criar relação entre utilizador e jogo
             */

            if (!$existingUserGame) {

                $sql = "INSERT INTO user_games (
                            user_id,
                            game_id
                        )
                        VALUES (
                            :user_id,
                            :game_id
                        )";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ":user_id" => $userId,
                    ":game_id" => $gameDbId
                ]);


                /*
                 * Registar atividade
                 */

                $sql = "INSERT INTO activities (
                            user_id,
                            game_id,
                            type
                        )
                        VALUES (
                            :user_id,
                            :game_id,
                            'added_game'
                        )";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ":user_id" => $userId,
                    ":game_id" => $gameDbId
                ]);
            }


            $pdo->commit();

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());

            die(
                "Não foi possível adicionar o jogo à biblioteca."
            );
        }


        redirect(
            "game.php?id=" . $rawgId
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Remover da biblioteca
    |--------------------------------------------------------------------------
    */

    if (isset($_POST["remove_from_library"])) {

        $existingGame = getGameByRawgId(
            $pdo,
            $rawgId
        );

        if ($existingGame) {

            $gameDbId = (int) $existingGame["id"];


            $sql = "DELETE FROM user_games
                    WHERE user_id = :user_id
                    AND game_id = :game_id";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":user_id" => $userId,
                ":game_id" => $gameDbId
            ]);
        }


        redirect(
            "game.php?id=" . $rawgId
        );
    }
}


$pageTitle = ($game["name"] ?? "Jogo") . " - GameBacklog";

require_once "includes/header.php";

?>

<h1>
    <?php echo e($game["name"]); ?>
</h1>


<?php if (!empty($game["background_image"])) { ?>

    <img src="<?php echo e($game["background_image"]); ?>" width="400" alt="<?php echo e($game["name"]); ?>">

<?php } ?>


<h2>
    Descrição
</h2>

<p>
    <?php echo e(
        $game["description_raw"] ?? "Sem descrição disponível."
    ); ?>
</p>


<h2>
    Informações
</h2>

<p>
    <strong>Data de lançamento:</strong>

    <?php echo e(
        $game["released"] ?? "Desconhecida"
    ); ?>
</p>


<p>
    <strong>Rating:</strong>

    <?php

    if (
        isset($game["rating"]) &&
        is_numeric($game["rating"])
    ) {

        echo e(
            (string) ($game["rating"] * 2)
        ) . " / 10";

    } else {

        echo "Sem rating";
    }

    ?>

</p>


<p>
    <strong>Playtime:</strong>

    <?php echo e(
        (string) ($game["playtime"] ?? 0)
    ); ?>

    horas
</p>


<p>
    <strong>Metacritic:</strong>

    <?php echo e(
        (string) ($game["metacritic"] ?? "Sem pontuação")
    ); ?>
</p>


<h2>
    Géneros
</h2>

<?php if (!empty($game["genres"])) { ?>

    <ul>

        <?php foreach ($game["genres"] as $genre) { ?>

            <?php if (!empty($genre["name"])) { ?>

                <li>
                    <?php echo e($genre["name"]); ?>
                </li>

            <?php } ?>

        <?php } ?>

    </ul>

<?php } else { ?>

    <p>
        Sem géneros disponíveis.
    </p>

<?php } ?>


<h2>
    Plataformas
</h2>

<?php if (!empty($game["platforms"])) { ?>

    <ul>

        <?php foreach ($game["platforms"] as $platform) { ?>

            <?php
            $platformName =
                $platform["platform"]["name"] ?? null;
            ?>

            <?php if ($platformName) { ?>

                <li>
                    <?php echo e($platformName); ?>
                </li>

            <?php } ?>

        <?php } ?>

    </ul>

<?php } else { ?>

    <p>
        Sem plataformas disponíveis.
    </p>

<?php } ?>


<h2>
    Desenvolvedora
</h2>

<?php if (!empty($game["developers"])) { ?>

    <?php foreach ($game["developers"] as $developer) { ?>

        <?php if (!empty($developer["name"])) { ?>

            <p>
                <?php echo e($developer["name"]); ?>
            </p>

        <?php } ?>

    <?php } ?>

<?php } else { ?>

    <p>
        Desconhecida
    </p>

<?php } ?>


<h2>
    Publisher
</h2>

<?php if (!empty($game["publishers"])) { ?>

    <?php foreach ($game["publishers"] as $publisher) { ?>

        <?php if (!empty($publisher["name"])) { ?>

            <p>
                <?php echo e($publisher["name"]); ?>
            </p>

        <?php } ?>

    <?php } ?>

<?php } else { ?>

    <p>
        Desconhecida
    </p>

<?php } ?>


<h2>
    Minha biblioteca
</h2>


<?php if (!isLoggedIn()) { ?>

    <p>
        Inicia sessão para adicionar este jogo à tua biblioteca.
    </p>

<?php } else { ?>

    <form method="POST">

        <?php echo csrfField(); ?>


        <?php if ($inLibrary) { ?>

            <button type="submit" name="remove_from_library">
                Remover da minha biblioteca
            </button>

        <?php } else { ?>

            <button type="submit" name="add_to_library">
                Adicionar à minha biblioteca
            </button>

        <?php } ?>

    </form>

<?php } ?>


<h2>
    Minha review
</h2>


<?php if (!isLoggedIn()) { ?>

    <p>
        Inicia sessão para escrever uma review.
    </p>

<?php } elseif (!$inLibrary) { ?>

    <p>
        Adiciona este jogo à tua biblioteca para poderes escrever uma review.
    </p>

<?php } else { ?>

    <form method="POST">

        <?php echo csrfField(); ?>


        <label for="rating">
            Rating:
        </label>

        <br>

        <input type="number" id="rating" name="rating" min="0" max="10" step="0.5" value="<?php echo e(
            (string) ($userReview["rating"] ?? "")
        ); ?>" placeholder="0 - 10">


        <br><br>


        <label for="review">
            Review:
        </label>

        <br>

        <textarea id="review" name="review" rows="8" cols="60" placeholder="Escreve a tua opinião sobre este jogo..."
            required><?php echo e(
                $userReview["review"] ?? ""
            ); ?></textarea>


        <br><br>


        <button type="submit" name="save_review">
            <?php echo $userReview
                ? "Atualizar review"
                : "Publicar review"; ?>
        </button>

    </form>

<?php } ?>


<h2>
    Reviews dos utilizadores
</h2>


<?php if (empty($reviews)) { ?>

    <p>
        Ainda não existem reviews para este jogo.
    </p>

<?php } else { ?>

    <?php foreach ($reviews as $review) { ?>

        <?php $reviewId = (int) $review["id"]; ?>


        <div>

            <h3>
                <?php echo e($review["username"]); ?>
            </h3>


            <?php if ($review["rating"] !== null) { ?>

                <p>

                    <strong>Rating:</strong>

                    <?php echo e(
                        (string) $review["rating"]
                    ); ?>

                    / 10

                </p>

            <?php } ?>


            <p>
                <?php echo nl2br(
                    e($review["review"])
                ); ?>
            </p>


            <p>

                <small>

                    Publicado em

                    <?php echo e(
                        date(
                            "d/m/Y",
                            strtotime($review["created_at"])
                        )
                    ); ?>

                </small>

            </p>


            <?php if (isLoggedIn()) { ?>

                <?php if (
                    (int) $review["user_id"] !== currentUserId()
                ) { ?>

                    <form method="POST" action="like.php">

                        <?php echo csrfField(); ?>


                        <input type="hidden" name="review_id" value="<?php echo $reviewId; ?>">


                        <button type="submit">

                            <?php echo $userReviewLikes[$reviewId]
                                ? "❤️"
                                : "🤍"; ?>

                            <?php echo $reviewLikes[$reviewId]; ?>

                        </button>

                    </form>

                <?php } else { ?>

                    <p>
                        ❤️ <?php echo $reviewLikes[$reviewId]; ?>
                    </p>

                <?php } ?>

            <?php } else { ?>

                <p>
                    ❤️ <?php echo $reviewLikes[$reviewId]; ?>
                </p>

            <?php } ?>


            <?php if (
                isLoggedIn() &&
                (int) $review["user_id"] === currentUserId()
            ) { ?>

                <p>

                    <a href="edit-review.php?id=<?php echo $reviewId; ?>">
                        Editar
                    </a>

                </p>


                <form method="POST" action="delete-review.php"
                    onsubmit="return confirm('Tens a certeza que queres apagar esta review?');">

                    <?php echo csrfField(); ?>


                    <input type="hidden" name="review_id" value="<?php echo $reviewId; ?>">


                    <button type="submit">
                        Apagar
                    </button>

                </form>

            <?php } ?>


            <hr>

        </div>

    <?php } ?>

<?php } ?>


<?php require_once "includes/footer.php"; ?>