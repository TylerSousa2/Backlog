<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "includes/db.php";

$currentUserId = $_SESSION["user_id"];

$profileUserId = $_POST["user_id"] ?? null;

if (!$profileUserId) {
    die("Utilizador inválido.");
}

if ($currentUserId == $profileUserId) {
    die("Não podes seguir a tua própria conta.");
}


/*
 * Verificar se o utilizador existe
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
 * Verificar se já existe follow
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
 * Deixar de seguir
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
 * Seguir
 */

} else {

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
}


header(
    "Location: profile.php?id=" . $profileUserId
);

exit;