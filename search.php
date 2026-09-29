<?php

require_once "includes/rawg.php";

$results = [];

if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["search"])) {

    $search = trim($_GET["search"]);

    if (!empty($search)) {

        $url = "https://api.rawg.io/api/games?key=" . $rawgApiKey
             . "&search=" . urlencode($search)
             . "&search_precise=true"
             . "&page_size=40";

        $response = file_get_contents($url);

        $data = json_decode($response, true);

        if (!empty($data["results"])) {

            $searchLower = strtolower($search);

            $searchWords = preg_split('/\s+/', $searchLower);

            foreach ($data["results"] as $game) {

                $gameName = $game["name"] ?? "";
                $gameNameLower = strtolower($gameName);

                $score = 0;

                $matchedWords = 0;

                foreach ($searchWords as $word) {

                    if ($word !== "" && str_contains($gameNameLower, $word)) {
                        $matchedWords++;
                    }

                }

                if ($matchedWords === 0) {
                    continue;
                }

                $score += $matchedWords * 3000;

                if ($matchedWords === count($searchWords)) {
                    $score += 10000;
                }

                if (str_starts_with($gameNameLower, $searchLower)) {
                    $score += 10000;
                }

                if (str_contains($gameNameLower, $searchLower)) {
                    $score += 5000;
                }

                $ratingsCount = $game["ratings_count"] ?? 0;

                $score += min($ratingsCount, 5000);

                if (
                    isset($game["metacritic"]) &&
                    $game["metacritic"] !== null
                ) {
                    $score += $game["metacritic"] * 10;
                }

                $game["_search_score"] = $score;

                $results[] = $game;
            }

            usort($results, function ($a, $b) {
                return $b["_search_score"] <=> $a["_search_score"];
            });

            $results = array_slice($results, 0, 20);
        }
    }
}

$pageTitle = "Pesquisar - GameBacklog";

require_once "includes/header.php";

?>

<h1>Pesquisar jogos</h1>

<form method="GET">

    <input
        type="text"
        name="search"
        placeholder="Nome do jogo..."
        value="<?php echo htmlspecialchars($_GET["search"] ?? ""); ?>"
        required
    >

    <button type="submit">
        Pesquisar
    </button>

</form>

<hr>

<?php if (!empty($_GET["search"])) { ?>

    <h2>
        Resultados para:
        <?php echo htmlspecialchars($_GET["search"]); ?>
    </h2>

<?php } ?>

<?php if (empty($results) && !empty($_GET["search"])) { ?>

    <p>
        Não foram encontrados jogos.
    </p>

<?php } ?>

<?php foreach ($results as $game) { ?>

    <div>

        <a href="game.php?id=<?php echo $game["id"]; ?>">

            <h2>
                <?php echo htmlspecialchars($game["name"]); ?>
            </h2>

            <?php if (!empty($game["background_image"])) { ?>

                <img
                    src="<?php echo htmlspecialchars($game["background_image"]); ?>"
                    width="250"
                    alt="<?php echo htmlspecialchars($game["name"]); ?>"
                >

            <?php } ?>

        </a>

        <p>
            <strong>Data de lançamento:</strong>
            <?php echo $game["released"] ?? "Desconhecida"; ?>
        </p>

        <p>
            <strong>Playtime:</strong>
            <?php echo $game["playtime"] ?? "0"; ?> horas
        </p>

        <p>
            <strong>Metacritic:</strong>

            <?php

            if (
                isset($game["metacritic"]) &&
                $game["metacritic"] !== null
            ) {
                echo $game["metacritic"];
            } else {
                echo "Sem pontuação";
            }

            ?>

        </p>

        <hr>

    </div>

<?php } ?>

<?php require_once "includes/footer.php"; ?>