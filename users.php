<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "includes/db.php";

$currentUserId = $_SESSION["user_id"];

$type = $_GET["type"] ?? null;
$userId = $_GET["id"] ?? null;

if (!$userId || !in_array($type, ["followers", "following"])) {
    die("Pedido inválido.");
}


/*
 * Procurar utilizador
 */

$sql = "SELECT id, username
        FROM users
        WHERE id = :user_id";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":user_id" => $userId
]);

$user = $stmt->fetch();

if (!$user) {
    die("Utilizador não encontrado.");
}


/*
 * Seguidores
 */

if ($type === "followers") {

    $sql = "SELECT
                users.id,
                users.username
            FROM follows

            INNER JOIN users
                ON follows.follower_id = users.id

            WHERE follows.following_id = :user_id

            ORDER BY users.username ASC";

    $title = "Seguidores de " . $user["username"];

}


/*
 * A seguir
 */

if ($type === "following") {

    $sql = "SELECT
                users.id,
                users.username
            FROM follows

            INNER JOIN users
                ON follows.following_id = users.id

            WHERE follows.follower_id = :user_id

            ORDER BY users.username ASC";

    $title = "A seguir " . $user["username"];

}


$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":user_id" => $userId
]);

$users = $stmt->fetchAll();


$pageTitle = $title . " - GameBacklog";

require_once "includes/header.php";

?>

<h1>
    <?php echo htmlspecialchars($title); ?>
</h1>


<?php if (empty($users)) { ?>

    <?php if ($type === "followers") { ?>

        <p>
            Este utilizador ainda não tem seguidores.
        </p>

    <?php } else { ?>

        <p>
            Este utilizador ainda não segue ninguém.
        </p>

    <?php } ?>


<?php } else { ?>

    <?php foreach ($users as $listedUser) { ?>

        <div>

            <h2>
                <a
                    href="profile.php?id=<?php echo $listedUser["id"]; ?>"
                >
                    <?php echo htmlspecialchars($listedUser["username"]); ?>
                </a>
            </h2>

        </div>

        <hr>

    <?php } ?>

<?php } ?>


<p>

    <a href="profile.php?id=<?php echo $user["id"]; ?>">
        Voltar ao perfil
    </a>

</p>


<?php require_once "includes/footer.php"; ?>