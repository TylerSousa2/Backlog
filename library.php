<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "includes/db.php";

$userId = $_SESSION["user_id"];

$statusFilter = $_GET["status"] ?? "all";

$allowedFilters = [
    "all",
    "planned",
    "playing",
    "completed",
    "dropped",
    "favorite"
];

if (!in_array($statusFilter, $allowedFilters)) {
    $statusFilter = "all";
}


/*
 * Buscar jogos da biblioteca
 */

$sql = "SELECT
            games.id,
            games.rawg_id,
            games.title,
            games.cover,
            games.release_date,
            user_games.status,
            user_games.rating,
            user_games.hours_played,
            user_games.favorite,
            user_games.started_at,
            user_games.finished_at
        FROM user_games
        INNER JOIN games
            ON user_games.game_id = games.id
        WHERE user_games.user_id = :user_id";


$params = [
    ":user_id" => $userId
];


if ($statusFilter !== "all" && $statusFilter !== "favorite") {

    $sql .= " AND user_games.status = :status";

    $params[":status"] = $statusFilter;

}


if ($statusFilter === "favorite") {

    $sql .= " AND user_games.favorite = 1";

}


$sql .= " ORDER BY user_games.created_at DESC";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$games = $stmt->fetchAll();


/*
 * Título da página
 */

$pageTitle = "Minha Biblioteca - GameBacklog";

require_once "includes/header.php";

?>


<h1>Minha Biblioteca</h1>


<div>

    <a href="library.php">
        Todos
    </a>

    <a href="library.php?status=planned">
        Planeados
    </a>

    <a href="library.php?status=playing">
        A jogar
    </a>

    <a href="library.php?status=completed">
        Concluídos
    </a>

    <a href="library.php?status=dropped">
        Abandonados
    </a>

    <a href="library.php?status=favorite">
        ❤️ Favoritos
    </a>

</div>


<hr>


<p>
    Olá, <?php echo htmlspecialchars($_SESSION["username"]); ?>!
</p>


<?php if (empty($games)) { ?>

    <p>
        Ainda não tens jogos nesta categoria.
    </p>

<?php } else { ?>


    <?php foreach ($games as $game) { ?>

        <div>

            <?php if (!empty($game["cover"])) { ?>

                <img
                    src="<?php echo htmlspecialchars($game["cover"]); ?>"
                    width="200"
                    alt="<?php echo htmlspecialchars($game["title"]); ?>"
                >

            <?php } ?>


            <h2>
                <?php echo htmlspecialchars($game["title"]); ?>
            </h2>


            <form method="POST" action="update-game.php">

                <input
                    type="hidden"
                    name="game_id"
                    value="<?php echo $game["id"]; ?>"
                >


                <label for="status-<?php echo $game["id"]; ?>">
                    Estado:
                </label>


                <select
                    id="status-<?php echo $game["id"]; ?>"
                    name="status"
                >

                    <option
                        value="planned"
                        <?php echo $game["status"] === "planned" ? "selected" : ""; ?>
                    >
                        Planeado
                    </option>

                    <option
                        value="playing"
                        <?php echo $game["status"] === "playing" ? "selected" : ""; ?>
                    >
                        A jogar
                    </option>

                    <option
                        value="completed"
                        <?php echo $game["status"] === "completed" ? "selected" : ""; ?>
                    >
                        Concluído
                    </option>

                    <option
                        value="dropped"
                        <?php echo $game["status"] === "dropped" ? "selected" : ""; ?>
                    >
                        Abandonado
                    </option>

                </select>


                <br><br>


                <label for="rating-<?php echo $game["id"]; ?>">
                    Rating:
                </label>


                <input
                    type="number"
                    id="rating-<?php echo $game["id"]; ?>"
                    name="rating"
                    min="0"
                    max="10"
                    step="0.5"
                    value="<?php echo $game["rating"] ?? ""; ?>"
                    placeholder="0 - 10"
                >


                <br><br>


                <label for="hours-<?php echo $game["id"]; ?>">
                    Horas jogadas:
                </label>


                <input
                    type="number"
                    id="hours-<?php echo $game["id"]; ?>"
                    name="hours_played"
                    min="0"
                    step="0.5"
                    value="<?php echo $game["hours_played"]; ?>"
                >


                <br><br>


                <label for="started-<?php echo $game["id"]; ?>">
                    Data de início:
                </label>


                <input
                    type="date"
                    id="started-<?php echo $game["id"]; ?>"
                    name="started_at"
                    value="<?php echo $game["started_at"] ?? ""; ?>"
                >


                <br><br>


                <label for="finished-<?php echo $game["id"]; ?>">
                    Data de conclusão:
                </label>


                <input
                    type="date"
                    id="finished-<?php echo $game["id"]; ?>"
                    name="finished_at"
                    value="<?php echo $game["finished_at"] ?? ""; ?>"
                >


                <br><br>


                <label>

                    <input
                        type="checkbox"
                        name="favorite"
                        value="1"
                        <?php echo $game["favorite"] ? "checked" : ""; ?>
                    >

                    Favorito ❤️

                </label>


                <br><br>


                <button type="submit">
                    Guardar
                </button>

            </form>


            <p>

                <strong>Rating:</strong>

                <?php

                if ($game["rating"] !== null) {
                    echo $game["rating"] . " / 10";
                } else {
                    echo "Sem rating";
                }

                ?>

            </p>


            <p>

                <strong>Horas jogadas:</strong>

                <?php echo $game["hours_played"]; ?>

            </p>


            <?php if ($game["favorite"]) { ?>

                <p>
                    ❤️ Favorito
                </p>

            <?php } ?>


            <?php if (!empty($game["started_at"])) { ?>

                <p>

                    <strong>Data de início:</strong>

                    <?php echo htmlspecialchars($game["started_at"]); ?>

                </p>

            <?php } ?>


            <?php if (!empty($game["finished_at"])) { ?>

                <p>

                    <strong>Data de conclusão:</strong>

                    <?php echo htmlspecialchars($game["finished_at"]); ?>

                </p>

            <?php } ?>


            <a href="game.php?id=<?php echo $game["rawg_id"]; ?>">
                Ver jogo
            </a>


            <hr>

        </div>

    <?php } ?>


<?php } ?>


<?php require_once "includes/footer.php"; ?>