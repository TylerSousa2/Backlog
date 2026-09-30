<?php

require_once "includes/rawg.php";
require_once "includes/functions.php";


/*
|--------------------------------------------------------------------------
| Variáveis
|--------------------------------------------------------------------------
*/

$results = [];
$search = trim($_GET["search"] ?? "");
$error = null;


/*
|--------------------------------------------------------------------------
| Pesquisar jogos
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "GET" && $search !== "") {

    $url = "https://api.rawg.io/api/games?key=" .
        urlencode($rawgApiKey) .
        "&search=" .
        urlencode($search) .
        "&search_precise=true" .
        "&page_size=40";


    /*
     * Contactar API RAWG
     */

    $response = @file_get_contents($url);

    if ($response === false) {

        $error = "Não foi possível contactar a API de jogos.";

    } else {

        $data = json_decode($response, true);

        if (!is_array($data)) {

            $error = "Resposta inválida da API.";

        } elseif (!empty($data["results"]) && is_array($data["results"])) {

            $searchLower = strtolower($search);

            $searchWords = preg_split(
                '/\s+/',
                $searchLower,
                -1,
                PREG_SPLIT_NO_EMPTY
            );


            /*
             * Calcular relevância dos resultados
             */

            foreach ($data["results"] as $game) {

                if (
                    !is_array($game) ||
                    empty($game["id"]) ||
                    empty($game["name"])
                ) {
                    continue;
                }


                $gameName = (string) $game["name"];
                $gameNameLower = strtolower($gameName);

                $score = 0;
                $matchedWords = 0;


                /*
                 * Verificar palavras pesquisadas
                 */

                foreach ($searchWords as $word) {

                    if (str_contains($gameNameLower, $word)) {
                        $matchedWords++;
                    }
                }


                /*
                 * Ignorar resultados sem correspondência
                 */

                if ($matchedWords === 0) {
                    continue;
                }


                /*
                 * Correspondência das palavras
                 */

                $score += $matchedWords * 3000;


                /*
                 * Todas as palavras correspondem
                 */

                if ($matchedWords === count($searchWords)) {
                    $score += 10000;
                }


                /*
                 * Nome começa exatamente pela pesquisa
                 */

                if (
                    str_starts_with(
                        $gameNameLower,
                        $searchLower
                    )
                ) {
                    $score += 10000;
                }


                /*
                 * Nome contém exatamente a pesquisa
                 */

                if (
                    str_contains(
                        $gameNameLower,
                        $searchLower
                    )
                ) {
                    $score += 5000;
                }


                /*
                 * Popularidade
                 */

                $ratingsCount = (int) (
                    $game["ratings_count"] ?? 0
                );

                $score += min($ratingsCount, 5000);


                /*
                 * Metacritic
                 */

                if (
                    isset($game["metacritic"]) &&
                    is_numeric($game["metacritic"])
                ) {
                    $score += (int) $game["metacritic"] * 10;
                }


                $game["_search_score"] = $score;

                $results[] = $game;
            }


            /*
             * Ordenar por relevância
             */

            usort(
                $results,
                function ($a, $b) {
                    return $b["_search_score"]
                        <=> $a["_search_score"];
                }
            );


            /*
             * Limitar resultados
             */

            $results = array_slice(
                $results,
                0,
                20
            );
        }
    }
}


$pageTitle = "Pesquisar - GameBacklog";

require_once "includes/header.php";

?>

<h1>
    Pesquisar jogos
</h1>


<form method="GET">

    <input type="text" name="search" placeholder="Nome do jogo..." value="<?php echo e($search); ?>" required>

    <button type="submit">
        Pesquisar
    </button>

</form>


<hr>


<?php if ($search !== "") { ?>

    <h2>
        Resultados para:
        <?php echo e($search); ?>
    </h2>

<?php } ?>


<?php if ($error !== null) { ?>

    <p>
        <?php echo e($error); ?>
    </p>


<?php } elseif (
    $search !== "" &&
    empty($results)
) { ?>

    <p>
        Não foram encontrados jogos.
    </p>

<?php } ?>


<?php foreach ($results as $game) { ?>

    <?php

    $rawgId = validateId(
        $game["id"] ?? null,
        "Jogo inválido."
    );

    $gameName = (string) (
        $game["name"] ?? "Jogo"
    );

    ?>


    <div>

        <a href="game.php?id=<?php echo $rawgId; ?>">

            <h2>
                <?php echo e($gameName); ?>
            </h2>


            <?php if (!empty($game["background_image"])) { ?>

                <img src="<?php echo e($game["background_image"]); ?>" width="250" alt="<?php echo e($gameName); ?>">

            <?php } ?>

        </a>


        <p>

            <strong>
                Data de lançamento:
            </strong>

            <?php echo e(
                $game["released"] ?? "Desconhecida"
            ); ?>

        </p>


        <p>

            <strong>
                Playtime:
            </strong>

            <?php echo (int) (
                $game["playtime"] ?? 0
            ); ?>

            horas

        </p>


        <p>

            <strong>
                Metacritic:
            </strong>


            <?php if (
                isset($game["metacritic"]) &&
                is_numeric($game["metacritic"])
            ) { ?>

                <?php echo (int) $game["metacritic"]; ?>

            <?php } else { ?>

                Sem pontuação

            <?php } ?>

        </p>


        <hr>

    </div>

<?php } ?>


<?php require_once "includes/footer.php"; ?>