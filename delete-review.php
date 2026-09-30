<?php

require_once "includes/csrf.php";
require_once "includes/db.php";
require_once "includes/functions.php";

requireLogin();

verifyCsrfToken();

$userId = currentUserId();

$reviewId = validateId(
    $_POST["review_id"] ?? null,
    "Review inválida."
);


/*
|--------------------------------------------------------------------------
| Procurar review
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            games.rawg_id

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
|--------------------------------------------------------------------------
| Apagar review
|--------------------------------------------------------------------------
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
|--------------------------------------------------------------------------
| Voltar para o jogo
|--------------------------------------------------------------------------
*/

redirect(
    "game.php?id=" . (int) $review["rawg_id"]
);