<?php

session_start();

require_once "includes/rawg.php";
require_once "includes/db.php";

if (isset($_GET["id"])) {

    $gameId = $_GET["id"];

    $url = "https://api.rawg.io/api/games/" . $gameId . "?key=" . $rawgApiKey;

    $response = file_get_contents($url);

    $game = json_decode($response, true);

} else {

    die("Nenhum jogo selecionado.");

}


/*
 * Verificar se o jogo está na biblioteca
 */

$inLibrary = false;

if (isset($_SESSION["user_id"])) {

    $userId = $_SESSION["user_id"];

    $sql = "SELECT user_games.id
            FROM user_games
            INNER JOIN games
                ON user_games.game_id = games.id
            WHERE user_games.user_id = :user_id
            AND games.rawg_id = :rawg_id";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":user_id" => $userId,
        ":rawg_id" => $game["id"]
    ]);

    if ($stmt->fetch()) {
        $inLibrary = true;
    }
}


/*
 * Procurar review do utilizador
 */

$userReview = null;

if (isset($_SESSION["user_id"])) {

    $userId = $_SESSION["user_id"];

    $sql = "SELECT
                reviews.id,
                reviews.rating,
                reviews.review,
                reviews.created_at,
                reviews.updated_at

            FROM reviews

            INNER JOIN games
                ON reviews.game_id = games.id

            WHERE reviews.user_id = :user_id
            AND games.rawg_id = :rawg_id";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":user_id" => $userId,
        ":rawg_id" => $game["id"]
    ]);

    $userReview = $stmt->fetch();
}


/*
 * Buscar reviews de todos os utilizadores
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
    ":rawg_id" => $game["id"]
]);

$reviews = $stmt->fetchAll();

$reviewLikes = [];
$userReviewLikes = [];

if (!empty($reviews)) {

    foreach ($reviews as $review) {

        $sql = "SELECT COUNT(*)
                FROM likes
                WHERE review_id = :review_id";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ":review_id" => $review["id"]
        ]);

        $reviewLikes[
            $review["id"]
        ] = $stmt->fetchColumn();


        /*
         * Verificar se o utilizador atual
         * deu like
         */

        if (isset($_SESSION["user_id"])) {

            $sql = "SELECT id
                    FROM likes
                    WHERE review_id = :review_id
                    AND user_id = :user_id";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":review_id" => $review["id"],
                ":user_id" => $_SESSION["user_id"]
            ]);

            $userReviewLikes[
                $review["id"]
            ] = (bool) $stmt->fetch();

        } else {

            $userReviewLikes[
                $review["id"]
            ] = false;

        }
    }
}

/*
 * Adicionar ou remover da biblioteca
 */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!isset($_SESSION["user_id"])) {

        echo "Tens de iniciar sessão para adicionar jogos à tua biblioteca.";

    } else {

        $userId = $_SESSION["user_id"];
        $rawgId = $game["id"];


        /*
         * Guardar review
         */

        if (isset($_POST["save_review"])) {

            $rating = $_POST["rating"] ?? null;
            $reviewText = trim($_POST["review"] ?? "");


            if ($rating !== null && $rating !== "") {

                $rating = (float) $rating;

                if ($rating < 0 || $rating > 10) {
                    die("O rating deve estar entre 0 e 10.");
                }

            } else {

                $rating = null;

            }


            if (empty($reviewText)) {

                die("A review não pode estar vazia.");

            }


            /*
             * Encontrar o jogo na nossa base de dados
             */

            $sql = "SELECT id
                    FROM games
                    WHERE rawg_id = :rawg_id";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":rawg_id" => $game["id"]
            ]);

            $existingGame = $stmt->fetch();


            if (!$existingGame) {

                die("Este jogo ainda não está na biblioteca.");

            }


            $gameDbId = $existingGame["id"];


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

                die("Tens de adicionar o jogo à tua biblioteca primeiro.");

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
                    ":id" => $existingReview["id"],
                    ":user_id" => $userId
                ]);

            } else {

                /*
                 * Criar nova review
                 */

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


                /*
                 * Registar atividade da nova review
                 */

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
            }


            /*
             * Atualizar rating da variável
             */

            $userReview = [
                "rating" => $rating,
                "review" => $reviewText
            ];
        }


        /*
         * Adicionar à biblioteca
         */

        if (isset($_POST["add_to_library"])) {

            $sql = "SELECT id
                    FROM games
                    WHERE rawg_id = :rawg_id";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":rawg_id" => $rawgId
            ]);

            $existingGame = $stmt->fetch();


            if ($existingGame) {

                $gameDbId = $existingGame["id"];

            } else {

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
                    ":rawg_id" => $game["id"],
                    ":title" => $game["name"],
                    ":description" => $game["description_raw"] ?? null,
                    ":cover" => $game["background_image"] ?? null,
                    ":release_date" => $game["released"] ?? null,
                    ":genre" => !empty($game["genres"])
                        ? $game["genres"][0]["name"]
                        : null,
                    ":platform" => !empty($game["platforms"])
                        ? $game["platforms"][0]["platform"]["name"]
                        : null
                ]);

                $gameDbId = $pdo->lastInsertId();
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
                 * Registar atividade de adicionar jogo
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

            $inLibrary = true;
        }


        /*
         * Remover da biblioteca
         */

        if (isset($_POST["remove_from_library"])) {

            $sql = "SELECT id
                    FROM games
                    WHERE rawg_id = :rawg_id";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":rawg_id" => $rawgId
            ]);

            $existingGame = $stmt->fetch();


            if ($existingGame) {

                $gameDbId = $existingGame["id"];

                $sql = "DELETE FROM user_games
                        WHERE user_id = :user_id
                        AND game_id = :game_id";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ":user_id" => $userId,
                    ":game_id" => $gameDbId
                ]);
            }

            $inLibrary = false;
        }
    }
}


$pageTitle = ($game["name"] ?? "Jogo") . " - GameBacklog";

require_once "includes/header.php";

?>

<h1>
    <?php echo htmlspecialchars($game["name"]); ?>
</h1>

<?php if (!empty($game["background_image"])) { ?>

    <img
        src="<?php echo htmlspecialchars($game["background_image"]); ?>"
        width="400"
        alt="<?php echo htmlspecialchars($game["name"]); ?>"
    >

<?php } ?>


<h2>Descrição</h2>

<p>
    <?php echo $game["description_raw"] ?? "Sem descrição disponível."; ?>
</p>


<h2>Informações</h2>

<p>
    <strong>Data de lançamento:</strong>
    <?php echo $game["released"] ?? "Desconhecida"; ?>
</p>

<p>
    <strong>Rating:</strong>

    <?php

    if (isset($game["rating"])) {

        echo $game["rating"] * 2 . " / 10";

    } else {

        echo "Sem rating";

    }

    ?>

</p>

<p>
    <strong>Playtime:</strong>
    <?php echo $game["playtime"] ?? "0"; ?> horas
</p>

<p>
    <strong>Metacritic:</strong>
    <?php echo $game["metacritic"] ?? "Sem pontuação"; ?>
</p>


<h2>Géneros</h2>

<?php if (!empty($game["genres"])) { ?>

    <ul>

        <?php foreach ($game["genres"] as $genre) { ?>

            <li>
                <?php echo htmlspecialchars($genre["name"]); ?>
            </li>

        <?php } ?>

    </ul>

<?php } else { ?>

    <p>
        Sem géneros disponíveis.
    </p>

<?php } ?>


<h2>Plataformas</h2>

<?php if (!empty($game["platforms"])) { ?>

    <ul>

        <?php foreach ($game["platforms"] as $platform) { ?>

            <li>
                <?php echo htmlspecialchars($platform["platform"]["name"]); ?>
            </li>

        <?php } ?>

    </ul>

<?php } else { ?>

    <p>
        Sem plataformas disponíveis.
    </p>

<?php } ?>


<h2>Desenvolvedora</h2>

<?php if (!empty($game["developers"])) { ?>

    <?php foreach ($game["developers"] as $developer) { ?>

        <p>
            <?php echo htmlspecialchars($developer["name"]); ?>
        </p>

    <?php } ?>

<?php } else { ?>

    <p>
        Desconhecida
    </p>

<?php } ?>


<h2>Publisher</h2>

<?php if (!empty($game["publishers"])) { ?>

    <?php foreach ($game["publishers"] as $publisher) { ?>

        <p>
            <?php echo htmlspecialchars($publisher["name"]); ?>
        </p>

    <?php } ?>

<?php } else { ?>

    <p>
        Desconhecida
    </p>

<?php } ?>


<h2>Minha biblioteca</h2>

<?php if (!isset($_SESSION["user_id"])) { ?>

    <p>
        Inicia sessão para adicionar este jogo à tua biblioteca.
    </p>

<?php } else { ?>

    <form method="POST">

        <?php if ($inLibrary) { ?>

            <button
                type="submit"
                name="remove_from_library"
            >
                Remover da minha biblioteca
            </button>

        <?php } else { ?>

            <button
                type="submit"
                name="add_to_library"
            >
                Adicionar à minha biblioteca
            </button>

        <?php } ?>

    </form>

<?php } ?>


<h2>Minha review</h2>

<?php if (!isset($_SESSION["user_id"])) { ?>

    <p>
        Inicia sessão para escrever uma review.
    </p>

<?php } elseif (!$inLibrary) { ?>

    <p>
        Adiciona este jogo à tua biblioteca para poderes escrever uma review.
    </p>

<?php } else { ?>

    <form method="POST">

        <label for="rating">
            Rating:
        </label>

        <br>

        <input
            type="number"
            id="rating"
            name="rating"
            min="0"
            max="10"
            step="0.5"
            value="<?php echo $userReview["rating"] ?? ""; ?>"
            placeholder="0 - 10"
        >

        <br><br>

        <label for="review">
            Review:
        </label>

        <br>

        <textarea
            id="review"
            name="review"
            rows="8"
            cols="60"
            placeholder="Escreve a tua opinião sobre este jogo..."
            required
        ><?php echo htmlspecialchars($userReview["review"] ?? ""); ?></textarea>

        <br><br>

        <button
            type="submit"
            name="save_review"
        >
            <?php echo $userReview ? "Atualizar review" : "Publicar review"; ?>
        </button>

    </form>

<?php } ?>


<h2>Reviews dos utilizadores</h2>

<?php if (empty($reviews)) { ?>

    <p>
        Ainda não existem reviews para este jogo.
    </p>

<?php } else { ?>

    <?php foreach ($reviews as $review) { ?>

        <div>

            <h3>
                <?php echo htmlspecialchars($review["username"]); ?>
            </h3>

            <?php if ($review["rating"] !== null) { ?>

                <p>
                    <strong>Rating:</strong>
                    <?php echo $review["rating"]; ?> / 10
                </p>

            <?php } ?>

            <p>
                <?php echo nl2br(htmlspecialchars($review["review"])); ?>
            </p>

            <p>

                <small>

                    Publicado em

                    <?php

                    echo date(
                        "d/m/Y",
                        strtotime($review["created_at"])
                    );

                    ?>

                </small>

            </p>

            <?php if (isset($_SESSION["user_id"])) { ?>

    <?php if ($review["user_id"] != $_SESSION["user_id"]) { ?>

        <form method="POST" action="like.php">

            <input
                type="hidden"
                name="review_id"
                value="<?php echo $review["id"]; ?>"
            >

            <button type="submit">

                <?php
                echo $userReviewLikes[$review["id"]]
                    ? "❤️"
                    : "🤍";
                ?>

                <?php echo $reviewLikes[$review["id"]]; ?>

            </button>

        </form>

            <?php } else { ?>

                <p>
                    ❤️ <?php echo $reviewLikes[$review["id"]]; ?>
                </p>

                <?php } ?>

            <?php } else { ?>

                <p>
                    ❤️ <?php echo $reviewLikes[$review["id"]]; ?>
                </p>

            <?php } ?>

            <?php if (
                isset($_SESSION["user_id"]) &&
                $review["user_id"] == $_SESSION["user_id"]
            ) { ?>

                <p>

                    <a
                        href="edit-review.php?id=<?php echo $review["id"]; ?>"
                    >
                        Editar
                    </a>

                    |

                    <a
                        href="delete-review.php?id=<?php echo $review["id"]; ?>"
                        onclick="return confirm('Tens a certeza que queres apagar esta review?');"
                    >
                        Apagar
                    </a>

                </p>

            <?php } ?>

            <hr>

        </div>

    <?php } ?>

<?php } ?>


<?php require_once "includes/footer.php"; ?>