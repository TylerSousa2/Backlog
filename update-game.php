<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "includes/db.php";

$userId = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $gameId = $_POST["game_id"] ?? null;
    $status = $_POST["status"] ?? null;
    $rating = $_POST["rating"] ?? null;
    $hoursPlayed = $_POST["hours_played"] ?? null;
    $favorite = isset($_POST["favorite"]) ? 1 : 0;

    $startedAt = $_POST["started_at"] ?? null;
    $finishedAt = $_POST["finished_at"] ?? null;


    /*
     * Verificar se o jogo foi enviado
     */

    if (!$gameId) {
        die("Jogo inválido.");
    }


    /*
     * Verificar se o estado é válido
     */

    $allowedStatuses = [
        "planned",
        "playing",
        "completed",
        "dropped"
    ];

    if (!in_array($status, $allowedStatuses)) {
        die("Estado inválido.");
    }


    /*
     * Validar rating
     */

    if ($rating !== null && $rating !== "") {

        $rating = (float) $rating;

        if ($rating < 0 || $rating > 10) {
            die("O rating deve estar entre 0 e 10.");
        }

    } else {

        $rating = null;

    }


    /*
     * Validar horas jogadas
     */

    if ($hoursPlayed !== null && $hoursPlayed !== "") {

        $hoursPlayed = (float) $hoursPlayed;

        if ($hoursPlayed < 0) {
            die("As horas jogadas não podem ser negativas.");
        }

    } else {

        $hoursPlayed = 0;

    }


    /*
     * Procurar o estado atual do jogo
     */

    $sql = "SELECT
                status,
                favorite
            FROM user_games
            WHERE user_id = :user_id
            AND game_id = :game_id";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":user_id" => $userId,
        ":game_id" => $gameId
    ]);

    $currentGame = $stmt->fetch();


    if (!$currentGame) {
        die("Este jogo não está na tua biblioteca.");
    }


    /*
     * Guardar valores anteriores
     */

    $oldStatus = $currentGame["status"];
    $oldFavorite = (int) $currentGame["favorite"];


    /*
     * Atualizar jogo
     */

    $sql = "UPDATE user_games
            SET
                status = :status,
                rating = :rating,
                hours_played = :hours_played,
                favorite = :favorite,
                started_at = :started_at,
                finished_at = :finished_at

            WHERE user_id = :user_id
            AND game_id = :game_id";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":status" => $status,
        ":rating" => $rating,
        ":hours_played" => $hoursPlayed,
        ":favorite" => $favorite,
        ":started_at" => $startedAt,
        ":finished_at" => $finishedAt,
        ":user_id" => $userId,
        ":game_id" => $gameId
    ]);


    /*
     * Atividade: jogo concluído
     *
     * Só cria atividade quando o estado
     * muda para "completed".
     */

    if (
        $oldStatus !== "completed" &&
        $status === "completed"
    ) {

        $sql = "INSERT INTO activities (
                    user_id,
                    game_id,
                    type
                )
                VALUES (
                    :user_id,
                    :game_id,
                    'completed_game'
                )";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ":user_id" => $userId,
            ":game_id" => $gameId
        ]);
    }


    /*
     * Atividade: jogo favorito
     *
     * Só cria atividade quando passa
     * de não favorito para favorito.
     */

    if (
        $oldFavorite === 0 &&
        $favorite === 1
    ) {

        $sql = "INSERT INTO activities (
                    user_id,
                    game_id,
                    type
                )
                VALUES (
                    :user_id,
                    :game_id,
                    'favorite_game'
                )";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ":user_id" => $userId,
            ":game_id" => $gameId
        ]);
    }
}


header("Location: library.php");

exit;
?>