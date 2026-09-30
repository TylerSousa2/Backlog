<?php

require_once "includes/csrf.php";
require_once "includes/db.php";

requireLogin();

verifyCsrfToken();

$userId = currentUserId();

$reviewId = $_POST["review_id"] ?? null;
$activityId = $_POST["activity_id"] ?? null;

if (
    (!$reviewId && !$activityId) ||
    ($reviewId && $activityId)
) {
    die("Like inválido.");
}


/*
|--------------------------------------------------------------------------
| LIKE EM REVIEW
|--------------------------------------------------------------------------
*/

if ($reviewId) {

    if (!filter_var($reviewId, FILTER_VALIDATE_INT)) {
        die("Review inválida.");
    }

    $reviewId = (int) $reviewId;


    /*
     * Procurar review
     */

    $sql = "SELECT
                id,
                user_id
            FROM reviews
            WHERE id = :review_id";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":review_id" => $reviewId
    ]);

    $review = $stmt->fetch();

    if (!$review) {
        die("Review não encontrada.");
    }


    /*
     * Não permitir like na própria review
     */

    if ((int) $review["user_id"] === $userId) {
        die("Não podes dar like na tua própria review.");
    }


    /*
     * Verificar se já existe like
     */

    $sql = "SELECT id
            FROM likes
            WHERE user_id = :user_id
            AND review_id = :review_id";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":user_id" => $userId,
        ":review_id" => $reviewId
    ]);

    $like = $stmt->fetch();


    /*
     * Remover like
     */

    if ($like) {

        $sql = "DELETE FROM likes
                WHERE user_id = :user_id
                AND review_id = :review_id";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ":user_id" => $userId,
            ":review_id" => $reviewId
        ]);


    /*
     * Adicionar like
     */

    } else {

        $sql = "INSERT INTO likes (
                    user_id,
                    review_id
                )
                VALUES (
                    :user_id,
                    :review_id
                )";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ":user_id" => $userId,
            ":review_id" => $reviewId
        ]);


        /*
         * Criar notificação
         */

$sql = "INSERT INTO notifications (
            user_id,
            sender_id,
            review_id,
            type
        )
        VALUES (
            :user_id,
            :sender_id,
            :review_id,
            'like_review'
        )";

        $stmt = $pdo->prepare($sql);

$stmt->execute([
    ":user_id" => $review["user_id"],
    ":sender_id" => $userId,
    ":review_id" => $reviewId
]);
    }


    /*
     * Voltar para o jogo
     */

    $sql = "SELECT games.rawg_id
            FROM reviews

            INNER JOIN games
                ON reviews.game_id = games.id

            WHERE reviews.id = :review_id";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":review_id" => $reviewId
    ]);

    $game = $stmt->fetch();

    header(
        "Location: game.php?id=" . $game["rawg_id"]
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| LIKE EM ATIVIDADE
|--------------------------------------------------------------------------
*/

if ($activityId) {

    if (!filter_var($activityId, FILTER_VALIDATE_INT)) {
        die("Atividade inválida.");
    }

    $activityId = (int) $activityId;


    /*
     * Procurar atividade
     */

    $sql = "SELECT
                id,
                user_id
            FROM activities
            WHERE id = :activity_id";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":activity_id" => $activityId
    ]);

    $activity = $stmt->fetch();

    if (!$activity) {
        die("Atividade não encontrada.");
    }


    /*
     * Não permitir like na própria atividade
     */

    if ((int) $activity["user_id"] === $userId) {
        die("Não podes dar like na tua própria atividade.");
    }


    /*
     * Verificar se já existe like
     */

    $sql = "SELECT id
            FROM likes
            WHERE user_id = :user_id
            AND activity_id = :activity_id";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":user_id" => $userId,
        ":activity_id" => $activityId
    ]);

    $like = $stmt->fetch();


    /*
     * Remover like
     */

    if ($like) {

        $sql = "DELETE FROM likes
                WHERE user_id = :user_id
                AND activity_id = :activity_id";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ":user_id" => $userId,
            ":activity_id" => $activityId
        ]);


    /*
     * Adicionar like
     */

    } else {

        $sql = "INSERT INTO likes (
                    user_id,
                    activity_id
                )
                VALUES (
                    :user_id,
                    :activity_id
                )";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ":user_id" => $userId,
            ":activity_id" => $activityId
        ]);


        /*
         * Criar notificação
         */

$sql = "INSERT INTO notifications (
            user_id,
            sender_id,
            activity_id,
            type
        )
        VALUES (
            :user_id,
            :sender_id,
            :activity_id,
            'like_activity'
        )";

        $stmt = $pdo->prepare($sql);

$stmt->execute([
    ":user_id" => $activity["user_id"],
    ":sender_id" => $userId,
    ":activity_id" => $activityId
]);
    }


    /*
     * Voltar para atividade
     */

    header("Location: activity.php");

    exit;
}