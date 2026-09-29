<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "includes/db.php";

$userId = $_SESSION["user_id"];


/*
 * Procurar jogos da biblioteca
 */

$sql = "SELECT
            games.id,
            games.rawg_id,
            games.title,
            games.cover

        FROM user_games

        INNER JOIN games
            ON user_games.game_id = games.id

        WHERE user_games.user_id = :user_id

        ORDER BY games.title ASC";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":user_id" => $userId
]);

$libraryGames = $stmt->fetchAll();


/*
 * Procurar Top 3 atual
 */

$sql = "SELECT
            position,
            game_id

        FROM user_top_games

        WHERE user_id = :user_id

        ORDER BY position ASC";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":user_id" => $userId
]);

$topGames = $stmt->fetchAll();


/*
 * Organizar Top 3 atual
 */

$currentTop = [
    1 => null,
    2 => null,
    3 => null
];

foreach ($topGames as $topGame) {

    $currentTop[
        (int) $topGame["position"]
    ] = (int) $topGame["game_id"];
}


/*
 * Guardar alterações
 */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $position1 = $_POST["position1"] ?? "";
    $position2 = $_POST["position2"] ?? "";
    $position3 = $_POST["position3"] ?? "";


    /*
     * Novo Top 3
     */

    $newTop = [
        1 => $position1 !== "" ? (int) $position1 : null,
        2 => $position2 !== "" ? (int) $position2 : null,
        3 => $position3 !== "" ? (int) $position3 : null
    ];


    /*
     * Verificar jogos repetidos
     */

    $selectedIds = array_filter($newTop);

    if (
        count($selectedIds)
        !==
        count(array_unique($selectedIds))
    ) {

        die("Não podes escolher o mesmo jogo mais do que uma vez.");

    }


    /*
     * Verificar se os jogos pertencem à biblioteca
     */

    foreach ($selectedIds as $gameId) {

        $sql = "SELECT id
                FROM user_games
                WHERE user_id = :user_id
                AND game_id = :game_id";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ":user_id" => $userId,
            ":game_id" => $gameId
        ]);

        if (!$stmt->fetch()) {

            die(
                "Um dos jogos selecionados não pertence à tua biblioteca."
            );

        }
    }


    /*
     * Verificar se o Top 3 realmente mudou
     */

    $topChanged = (
        $currentTop[1] !== $newTop[1] ||
        $currentTop[2] !== $newTop[2] ||
        $currentTop[3] !== $newTop[3]
    );


    /*
     * Apagar Top 3 atual
     */

    $sql = "DELETE FROM user_top_games
            WHERE user_id = :user_id";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":user_id" => $userId
    ]);


    /*
     * Inserir novo Top 3
     */

    foreach ($newTop as $position => $gameId) {

        if ($gameId === null) {
            continue;
        }

        $sql = "INSERT INTO user_top_games (
                    user_id,
                    game_id,
                    position
                )
                VALUES (
                    :user_id,
                    :game_id,
                    :position
                )";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ":user_id" => $userId,
            ":game_id" => $gameId,
            ":position" => $position
        ]);
    }


    /*
     * Criar atividade apenas se o Top 3 mudou
     */

    if ($topChanged) {

        /*
         * Para esta atividade usamos o primeiro jogo
         * do Top 3 como referência.
         *
         * O activity.php vai depois buscar
         * os três jogos diretamente à tabela user_top_games.
         */

        $activityGameId = $newTop[1];

        $sql = "INSERT INTO activities (
                    user_id,
                    game_id,
                    type
                )
                VALUES (
                    :user_id,
                    :game_id,
                    'top_games'
                )";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ":user_id" => $userId,
            ":game_id" => $activityGameId
        ]);
    }


    /*
     * Voltar ao perfil
     */

    header("Location: profile.php");

    exit;
}


$pageTitle = "Editar Top 3 - GameBacklog";

require_once "includes/header.php";

?>

<h1>Editar Top 3</h1>

<p>
    Escolhe os teus três jogos favoritos de sempre.
</p>


<form method="POST">


    <h2>🥇 1.º lugar</h2>

    <select name="position1">

        <option value="">
            Nenhum
        </option>

        <?php foreach ($libraryGames as $game) { ?>

            <option
                value="<?php echo $game["id"]; ?>"
                <?php
                echo $currentTop[1] == $game["id"]
                    ? "selected"
                    : "";
                ?>
            >

                <?php echo htmlspecialchars($game["title"]); ?>

            </option>

        <?php } ?>

    </select>


    <br><br>


    <h2>🥈 2.º lugar</h2>

    <select name="position2">

        <option value="">
            Nenhum
        </option>

        <?php foreach ($libraryGames as $game) { ?>

            <option
                value="<?php echo $game["id"]; ?>"
                <?php
                echo $currentTop[2] == $game["id"]
                    ? "selected"
                    : "";
                ?>
            >

                <?php echo htmlspecialchars($game["title"]); ?>

            </option>

        <?php } ?>

    </select>


    <br><br>


    <h2>🥉 3.º lugar</h2>

    <select name="position3">

        <option value="">
            Nenhum
        </option>

        <?php foreach ($libraryGames as $game) { ?>

            <option
                value="<?php echo $game["id"]; ?>"
                <?php
                echo $currentTop[3] == $game["id"]
                    ? "selected"
                    : "";
                ?>
            >

                <?php echo htmlspecialchars($game["title"]); ?>

            </option>

        <?php } ?>

    </select>


    <br><br>


    <button type="submit">
        Guardar Top 3
    </button>

</form>


<p>

    <a href="profile.php">
        Cancelar
    </a>

</p>


<?php require_once "includes/footer.php"; ?>