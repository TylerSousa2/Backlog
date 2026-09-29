<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "includes/db.php";

$userId = $_SESSION["user_id"];

$reviewId = $_GET["id"] ?? null;

if (!$reviewId) {
    die("Review inválida.");
}


/*
 * Procurar o jogo associado à review
 */

$sql = "SELECT games.rawg_id

        FROM reviews

        INNER JOIN games
            ON reviews.game_id = games.id

        WHERE reviews.id = :review_id
        AND reviews.user_id = :user_id";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":review_id" => $reviewId,
    ":user_id" => $userId
]);

$review = $stmt->fetch();


if (!$review) {
    die("Review não encontrada.");
}


/*
 * Apagar review
 */

$sql = "DELETE FROM reviews

        WHERE id = :review_id
        AND user_id = :user_id";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":review_id" => $reviewId,
    ":user_id" => $userId
]);


/*
 * Voltar para o jogo
 */

header(
    "Location: game.php?id=" . $review["rawg_id"]
);

exit;

?>