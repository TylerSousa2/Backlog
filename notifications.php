<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "includes/db.php";

$userId = $_SESSION["user_id"];


/*
 * Procurar notificações
 */

$sql = "SELECT
            notifications.id,
            notifications.type,
            notifications.is_read,
            notifications.created_at,

            users.id AS sender_id,
            users.username AS sender_username

        FROM notifications

        INNER JOIN users
            ON notifications.sender_id = users.id

        WHERE notifications.user_id = :user_id

        ORDER BY notifications.created_at DESC";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":user_id" => $userId
]);

$notifications = $stmt->fetchAll();


/*
 * Marcar notificações como lidas
 */

$sql = "UPDATE notifications
        SET is_read = 1
        WHERE user_id = :user_id
        AND is_read = 0";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":user_id" => $userId
]);


$pageTitle = "Notificações - GameBacklog";

require_once "includes/header.php";

?>

<h1>Notificações</h1>


<?php if (empty($notifications)) { ?>

    <p>
        Não tens notificações.
    </p>

<?php } else { ?>

    <?php foreach ($notifications as $notification) { ?>

        <div>

            <?php if ($notification["type"] === "follow") { ?>

                <p>

                    <a
                        href="profile.php?id=<?php echo $notification["sender_id"]; ?>"
                    >

                        <strong>
                            <?php echo htmlspecialchars(
                                $notification["sender_username"]
                            ); ?>
                        </strong>

                    </a>

                    começou a seguir-te.

                </p>

            <?php } ?>


            <p>

                <small>

                    <?php

                    echo date(
                        "d/m/Y H:i",
                        strtotime($notification["created_at"])
                    );

                    ?>

                </small>

            </p>


            <hr>

        </div>

    <?php } ?>

<?php } ?>


<?php require_once "includes/footer.php"; ?>