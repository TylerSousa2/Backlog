<?php

require_once "includes/csrf.php";
require_once "includes/db.php";
require_once "includes/functions.php";

requireLogin();

verifyCsrfToken();

$userId = currentUserId();

$reviewId = $_POST["review_id"] ?? null;
$activityId = $_POST["activity_id"] ?? null;


/*
|--------------------------------------------------------------------------
| Validar tipo de like
|--------------------------------------------------------------------------
*/

if (
    ($reviewId === null || $reviewId === "") &&
    ($activityId === null || $activityId === "")
) {
    die("Like inválido.");
}

if (
    $reviewId !== null &&
    $reviewId !== "" &&
    $activityId !== null &&
    $activityId !== ""
) {
    die("Like inválido.");
}


/*
|--------------------------------------------------------------------------
| LIKE EM REVIEW
|--------------------------------------------------------------------------
*/

if ($reviewId !== null && $reviewId !== "") {

    $reviewId = validateId(
        $reviewId,
        "Review inválida."
    );


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


    $reviewUserId = (int) $review["user_id"];


    /*
     * Não permitir like na própria review
     */

    if ($reviewUserId === $userId) {
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
         * Adicionar like + notificação
         */

    } else {

        $pdo->beginTransaction();

        try {

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
                ":user_id" => $reviewUserId,
                ":sender_id" => $userId,
                ":review_id" => $reviewId
            ]);


            $pdo->commit();

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());

            die("Não foi possível dar like.");
        }
    }


    /*
     * Procurar jogo da review
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

    if (!$game) {
        die("Jogo não encontrado.");
    }


    /*
     * Voltar para o jogo
     */

    redirect(
        "game.php?id=" . (int) $game["rawg_id"]
    );
}


/*
|--------------------------------------------------------------------------
| LIKE EM ATIVIDADE
|--------------------------------------------------------------------------
*/

if ($activityId !== null && $activityId !== "") {

    $activityId = validateId(
        $activityId,
        "Atividade inválida."
    );


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


    $activityUserId = (int) $activity["user_id"];


    /*
     * Não permitir like na própria atividade
     */

    if ($activityUserId === $userId) {
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
         * Adicionar like + notificação
         */

    } else {

        $pdo->beginTransaction();

        try {

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
                ":user_id" => $activityUserId,
                ":sender_id" => $userId,
                ":activity_id" => $activityId
            ]);


            $pdo->commit();

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());

            die("Não foi possível dar like.");
        }
    }


    /*
     * Voltar para atividade
     */

    redirect("activity.php");
}