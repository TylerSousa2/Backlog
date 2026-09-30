<?php

require_once "includes/auth.php";
require_once "includes/db.php";
require_once "includes/functions.php";

requireLogin();

$userId = currentUserId();


/*
|--------------------------------------------------------------------------
| Marcar notificações como lidas
|--------------------------------------------------------------------------
*/

$sql = "UPDATE notifications
        SET is_read = 1
        WHERE user_id = :user_id
        AND is_read = 0";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":user_id" => $userId
]);


/*
|--------------------------------------------------------------------------
| Procurar notificações
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            notifications.id,
            notifications.type,
            notifications.created_at,

            users.id AS sender_id,
            users.username,

            games.rawg_id,
            games.title

        FROM notifications

        INNER JOIN users
            ON notifications.sender_id = users.id

        LEFT JOIN reviews
            ON notifications.review_id = reviews.id

        LEFT JOIN activities
            ON notifications.activity_id = activities.id

        LEFT JOIN games
            ON games.id = COALESCE(
                reviews.game_id,
                activities.game_id
            )

        WHERE notifications.user_id = :user_id

        ORDER BY notifications.created_at DESC";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":user_id" => $userId
]);

$notifications = $stmt->fetchAll();


$pageTitle = "Notificações - GameBacklog";

require_once "includes/header.php";

?>

<h1>
    Notificações
</h1>


<?php if (empty($notifications)) { ?>

    <p>
        Não tens notificações.
    </p>

<?php } else { ?>

    <?php foreach ($notifications as $notification) { ?>

        <?php

        $senderId = (int) $notification["sender_id"];

        $rawgId = !empty($notification["rawg_id"])
            ? (int) $notification["rawg_id"]
            : null;

        $type = $notification["type"];

        ?>


        <div>

            <?php if ($type === "follow") { ?>

                <p>

                    <a href="profile.php?id=<?php echo $senderId; ?>">

                        <strong>
                            <?php echo e($notification["username"]); ?>
                        </strong>

                    </a>

                    começou a seguir-te.

                </p>


            <?php } elseif ($type === "like_review") { ?>

                <p>

                    <a href="profile.php?id=<?php echo $senderId; ?>">

                        <strong>
                            <?php echo e($notification["username"]); ?>
                        </strong>

                    </a>

                    gostou da tua review de

                    <?php if (
                        $rawgId !== null &&
                        !empty($notification["title"])
                    ) { ?>

                        <a href="game.php?id=<?php echo $rawgId; ?>">

                            <strong>
                                <?php echo e($notification["title"]); ?>
                            </strong>

                        </a>

                    <?php } else { ?>

                        um jogo.

                    <?php } ?>

                </p>


            <?php } elseif ($type === "like_activity") { ?>

                <p>

                    <a href="profile.php?id=<?php echo $senderId; ?>">

                        <strong>
                            <?php echo e($notification["username"]); ?>
                        </strong>

                    </a>

                    gostou da tua atividade

                    <?php if (
                        $rawgId !== null &&
                        !empty($notification["title"])
                    ) { ?>

                        de

                        <a href="game.php?id=<?php echo $rawgId; ?>">

                            <strong>
                                <?php echo e($notification["title"]); ?>
                            </strong>

                        </a>

                    <?php } ?>

                    .

                </p>

            <?php } ?>


            <p>

                <small>
                    <?php echo e(
                        date(
                            "d/m/Y H:i",
                            strtotime($notification["created_at"])
                        )
                    ); ?>
                </small>

            </p>


            <hr>

        </div>

    <?php } ?>

<?php } ?>


<?php require_once "includes/footer.php"; ?>