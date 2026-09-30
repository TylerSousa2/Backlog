<?php

require_once "includes/csrf.php";
require_once "includes/db.php";
require_once "includes/functions.php";

requireLogin();

$userId = currentUserId();

$statusFilter = $_GET["status"] ?? "all";

$allowedFilters = [
    "all",
    "planned",
    "playing",
    "completed",
    "dropped",
    "favorite"
];

if (!in_array($statusFilter, $allowedFilters, true)) {
    $statusFilter = "all";
}

$statusLabels = getGameStatusLabels();


/*
|--------------------------------------------------------------------------
| Obter jogos da biblioteca
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            games.id,
            games.rawg_id,
            games.title,
            games.cover,
            games.release_date,
            games.genre,
            games.platform,
            user_games.status,
            user_games.rating,
            user_games.hours_played,
            user_games.favorite,
            user_games.started_at,
            user_games.finished_at,
            user_games.created_at

        FROM user_games

        INNER JOIN games
            ON games.id = user_games.game_id

        WHERE user_games.user_id = :user_id";

$params = [
    ":user_id" => $userId
];


if (
    in_array(
        $statusFilter,
        [
            "planned",
            "playing",
            "completed",
            "dropped"
        ],
        true
    )
) {

    $sql .= " AND user_games.status = :status";

    $params[":status"] = $statusFilter;

} elseif ($statusFilter === "favorite") {

    $sql .= " AND user_games.favorite = 1";
}


$sql .= " ORDER BY user_games.created_at DESC";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$games = $stmt->fetchAll();


$pageTitle = "Biblioteca - GameBacklog";

require_once "includes/header.php";

?>

<h1>
    A minha biblioteca
</h1>


<nav>

    <a href="library.php?status=all">
        Todos
    </a>

    |

    <a href="library.php?status=planned">
        Planeados
    </a>

    |

    <a href="library.php?status=playing">
        A jogar
    </a>

    |

    <a href="library.php?status=completed">
        Completados
    </a>

    |

    <a href="library.php?status=dropped">
        Abandonados
    </a>

    |

    <a href="library.php?status=favorite">
        Favoritos
    </a>

</nav>


<hr>


<?php if (empty($games)) { ?>

    <p>
        Não tens jogos nesta categoria.
    </p>

<?php } else { ?>

    <?php foreach ($games as $game) { ?>

        <?php

        $gameDbId = (int) $game["id"];
        $rawgId = (int) $game["rawg_id"];

        $gameStatus = $game["status"];

        $rating = (string) (
            $game["rating"] ?? ""
        );

        $hoursPlayed = (string) (
            $game["hours_played"] ?? 0
        );

        $startedAt = (string) (
            $game["started_at"] ?? ""
        );

        $finishedAt = (string) (
            $game["finished_at"] ?? ""
        );

        $favorite = (int) $game["favorite"] === 1;

        ?>


        <div>

            <a href="game.php?id=<?php echo $rawgId; ?>">

                <?php if (!empty($game["cover"])) { ?>

                    <img src="<?php echo e($game["cover"]); ?>" width="150" alt="<?php echo e($game["title"]); ?>">

                <?php } ?>


                <h2>
                    <?php echo e($game["title"]); ?>
                </h2>

            </a>


            <p>

                <strong>
                    Estado:
                </strong>

                <?php echo e(
                    $statusLabels[$gameStatus]
                    ?? $gameStatus
                ); ?>

            </p>


            <form method="POST" action="update-game.php">

                <?php echo csrfField(); ?>


                <input type="hidden" name="game_id" value="<?php echo $gameDbId; ?>">


                <label for="status_<?php echo $gameDbId; ?>">
                    Estado:
                </label>

                <select id="status_<?php echo $gameDbId; ?>" name="status">

                    <?php foreach (
                        $statusLabels as $status => $label
                    ) { ?>

                        <option value="<?php echo e($status); ?>" <?php echo $gameStatus === $status
                               ? "selected"
                               : ""; ?>>
                            <?php echo e($label); ?>
                        </option>

                    <?php } ?>

                </select>


                <br><br>


                <label for="rating_<?php echo $gameDbId; ?>">
                    Rating:
                </label>

                <input type="number" id="rating_<?php echo $gameDbId; ?>" name="rating" min="0" max="10" step="0.5"
                    value="<?php echo e($rating); ?>">


                <br><br>


                <label for="hours_<?php echo $gameDbId; ?>">
                    Horas jogadas:
                </label>

                <input type="number" id="hours_<?php echo $gameDbId; ?>" name="hours_played" min="0" step="0.1"
                    value="<?php echo e($hoursPlayed); ?>">


                <br><br>


                <label for="started_<?php echo $gameDbId; ?>">
                    Data de início:
                </label>

                <input type="date" id="started_<?php echo $gameDbId; ?>" name="started_at" value="<?php echo e($startedAt); ?>">


                <br><br>


                <label for="finished_<?php echo $gameDbId; ?>">
                    Data de conclusão:
                </label>

                <input type="date" id="finished_<?php echo $gameDbId; ?>" name="finished_at"
                    value="<?php echo e($finishedAt); ?>">


                <br><br>


                <label>

                    <input type="checkbox" name="favorite" <?php echo $favorite
                        ? "checked"
                        : ""; ?>>

                    Favorito

                </label>


                <br><br>


                <button type="submit">
                    Guardar
                </button>

            </form>


            <hr>

        </div>

    <?php } ?>

<?php } ?>


<?php require_once "includes/footer.php"; ?>