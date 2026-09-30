<?php

require_once "includes/csrf.php";
require_once "includes/db.php";
require_once "includes/functions.php";

requireLogin();

$userId = currentUserId();


/*
|--------------------------------------------------------------------------
| Guardar Top 3
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    verifyCsrfToken();

    $topGames = $_POST["top_games"] ?? [];

    if (!is_array($topGames)) {
        die("Dados inválidos.");
    }

    $topGames = array_values(
        array_filter(
            $topGames,
            fn($value) => $value !== ""
        )
    );

    if (count($topGames) > 3) {
        die("Podes selecionar no máximo 3 jogos.");
    }


    /*
    |--------------------------------------------------------------------------
    | Validar IDs
    |--------------------------------------------------------------------------
    */

    $topGameIds = [];

    foreach ($topGames as $gameId) {

        $gameId = validateId(
            $gameId,
            "Jogo inválido."
        );

        $topGameIds[] = $gameId;
    }


    /*
    |--------------------------------------------------------------------------
    | Impedir jogos duplicados
    |--------------------------------------------------------------------------
    */

    if (
        count($topGameIds) !==
        count(array_unique($topGameIds))
    ) {
        die(
            "Não podes selecionar o mesmo jogo mais do que uma vez."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Obter jogos da biblioteca
    |--------------------------------------------------------------------------
    */

    $libraryGames = [];

    $sql = "SELECT game_id
            FROM user_games
            WHERE user_id = :user_id";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":user_id" => $userId
    ]);

    foreach ($stmt->fetchAll() as $game) {
        $libraryGames[] = (int) $game["game_id"];
    }


    /*
    |--------------------------------------------------------------------------
    | Verificar se os jogos pertencem à biblioteca
    |--------------------------------------------------------------------------
    */

    foreach ($topGameIds as $gameId) {

        if (
            !in_array(
                $gameId,
                $libraryGames,
                true
            )
        ) {
            die(
                "Só podes colocar no Top 3 jogos que estão na tua biblioteca."
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Obter Top 3 atual
    |--------------------------------------------------------------------------
    */

    $sql = "SELECT
                position,
                game_id
            FROM user_top_games
            WHERE user_id = :user_id
            ORDER BY position ASC";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":user_id" => $userId
    ]);

    $currentTopGames = $stmt->fetchAll();

    $currentTopIds = [];

    foreach ($currentTopGames as $game) {
        $currentTopIds[] = (int) $game["game_id"];
    }

    $topChanged = $currentTopIds !== $topGameIds;


    /*
    |--------------------------------------------------------------------------
    | Atualizar Top 3
    |--------------------------------------------------------------------------
    */

    $pdo->beginTransaction();

    try {

        $sql = "DELETE FROM user_top_games
                WHERE user_id = :user_id";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ":user_id" => $userId
        ]);


        foreach ($topGameIds as $position => $gameId) {

            $sql = "INSERT INTO user_top_games (
                        user_id,
                        game_id,
                        position
                    )
                    VALUES (
                        :user_id,
                        :game_id,
                        :position
                    )";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":user_id" => $userId,
                ":game_id" => $gameId,
                ":position" => $position + 1
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Activity
        |--------------------------------------------------------------------------
        */

        if ($topChanged) {

            $sql = "INSERT INTO activities (
                        user_id,
                        type
                    )
                    VALUES (
                        :user_id,
                        'top_games'
                    )";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":user_id" => $userId
            ]);
        }


        $pdo->commit();

    } catch (Throwable $e) {

        $pdo->rollBack();

        error_log($e->getMessage());

        die(
            "Não foi possível atualizar o Top 3."
        );
    }


    redirect("profile.php");
}


/*
|--------------------------------------------------------------------------
| Obter jogos da biblioteca
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            games.id,
            games.title,
            games.cover
        FROM user_games
        INNER JOIN games
            ON games.id = user_games.game_id
        WHERE user_games.user_id = :user_id
        ORDER BY games.title ASC";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":user_id" => $userId
]);

$libraryGames = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Obter Top 3 atual
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            position,
            game_id
        FROM user_top_games
        WHERE user_id = :user_id
        ORDER BY position ASC";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":user_id" => $userId
]);

$currentTopGames = $stmt->fetchAll();

$currentTopByPosition = [];

foreach ($currentTopGames as $game) {

    $currentTopByPosition[
        (int) $game["position"]
    ] = (int) $game["game_id"];
}


$pageTitle = "Editar Top 3 - GameBacklog";

require_once "includes/header.php";

?>

<h1>
    Editar Top 3
</h1>

<form method="POST">

    <?php echo csrfField(); ?>

    <?php for (
        $position = 1;
        $position <= 3;
        $position++
    ) { ?>

        <label for="top_game_<?php echo $position; ?>">

            <?php echo $position; ?>º jogo:

        </label>

        <select id="top_game_<?php echo $position; ?>" name="top_games[]">

            <option value="">
                Nenhum
            </option>

            <?php foreach (
                $libraryGames as $game
            ) { ?>

                <option value="<?php echo (int) $game["id"]; ?>" <?php

                    if (
                        isset(
                        $currentTopByPosition[$position]
                    ) &&
                        $currentTopByPosition[$position] ===
                        (int) $game["id"]
                    ) {
                        echo "selected";
                    }

                    ?>>

                    <?php echo e($game["title"]); ?>

                </option>

            <?php } ?>

        </select>

        <br><br>

    <?php } ?>

    <button type="submit">
        Guardar Top 3
    </button>

</form>

<p>
    <a href="profile.php">
        Cancelar
    </a>
</p>

<?php require_once "includes/footer.php"; ?>