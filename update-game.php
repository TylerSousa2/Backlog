<?php

require_once "includes/csrf.php";
require_once "includes/db.php";
require_once "includes/functions.php";

requireLogin();

$userId = currentUserId();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect("library.php");
}

verifyCsrfToken();

$gameId = validateId(
    $_POST["game_id"] ?? null,
    "Jogo inválido."
);

$status = validateStatus(
    $_POST["status"] ?? null
);

$rating = validateRating(
    $_POST["rating"] ?? null
);

$hoursPlayed = $_POST["hours_played"] ?? null;

$favorite = isset($_POST["favorite"]) ? 1 : 0;

$startedAt = validateDate(
    $_POST["started_at"] ?? null
);

$finishedAt = validateDate(
    $_POST["finished_at"] ?? null
);


/*
|--------------------------------------------------------------------------
| Validar horas jogadas
|--------------------------------------------------------------------------
*/

if ($hoursPlayed !== null && $hoursPlayed !== "") {

    if (!is_numeric($hoursPlayed)) {
        die("Horas jogadas inválidas.");
    }

    $hoursPlayed = (float) $hoursPlayed;

    if ($hoursPlayed < 0) {
        die("As horas jogadas não podem ser negativas.");
    }

} else {

    $hoursPlayed = 0;
}


/*
|--------------------------------------------------------------------------
| Validar datas
|--------------------------------------------------------------------------
*/

if (
    $startedAt !== null &&
    $finishedAt !== null &&
    $finishedAt < $startedAt
) {
    die("A data de conclusão não pode ser anterior à data de início.");
}


/*
|--------------------------------------------------------------------------
| Verificar jogo na biblioteca
|--------------------------------------------------------------------------
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

$oldStatus = $currentGame["status"];
$oldFavorite = (int) $currentGame["favorite"];


/*
|--------------------------------------------------------------------------
| Atualizar jogo
|--------------------------------------------------------------------------
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
|--------------------------------------------------------------------------
| Activity: jogo concluído
|--------------------------------------------------------------------------
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
|--------------------------------------------------------------------------
| Activity: jogo favorito
|--------------------------------------------------------------------------
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


redirect("library.php");