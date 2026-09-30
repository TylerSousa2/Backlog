<?php

require_once "includes/csrf.php";
require_once "includes/db.php";
require_once "includes/functions.php";

requireLogin();

verifyCsrfToken();

$currentUserId = currentUserId();

$profileUserId = validateId(
    $_POST["user_id"] ?? null,
    "Utilizador inválido."
);


/*
|--------------------------------------------------------------------------
| Impedir seguir a própria conta
|--------------------------------------------------------------------------
*/

if ($currentUserId === $profileUserId) {
    die("Não podes seguir a tua própria conta.");
}


/*
|--------------------------------------------------------------------------
| Verificar se o utilizador existe
|--------------------------------------------------------------------------
*/

$sql = "SELECT id
        FROM users
        WHERE id = :user_id";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":user_id" => $profileUserId
]);

$user = $stmt->fetch();

if (!$user) {
    die("Utilizador não encontrado.");
}


/*
|--------------------------------------------------------------------------
| Verificar se já existe follow
|--------------------------------------------------------------------------
*/

$sql = "SELECT id
        FROM follows
        WHERE follower_id = :follower_id
        AND following_id = :following_id";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":follower_id" => $currentUserId,
    ":following_id" => $profileUserId
]);

$follow = $stmt->fetch();


/*
|--------------------------------------------------------------------------
| Deixar de seguir
|--------------------------------------------------------------------------
*/

if ($follow) {

    $sql = "DELETE FROM follows
            WHERE follower_id = :follower_id
            AND following_id = :following_id";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":follower_id" => $currentUserId,
        ":following_id" => $profileUserId
    ]);


    /*
    |--------------------------------------------------------------------------
    | Seguir
    |--------------------------------------------------------------------------
    */

} else {

    $pdo->beginTransaction();

    try {

        /*
         * Criar follow
         */

        $sql = "INSERT INTO follows (
                    follower_id,
                    following_id
                )
                VALUES (
                    :follower_id,
                    :following_id
                )";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ":follower_id" => $currentUserId,
            ":following_id" => $profileUserId
        ]);


        /*
         * Criar notificação
         */

        $sql = "INSERT INTO notifications (
                    user_id,
                    sender_id,
                    type
                )
                VALUES (
                    :user_id,
                    :sender_id,
                    'follow'
                )";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ":user_id" => $profileUserId,
            ":sender_id" => $currentUserId
        ]);


        $pdo->commit();

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log($e->getMessage());

        die("Não foi possível seguir este utilizador.");
    }
}


redirect(
    "profile.php?id=" . $profileUserId
);