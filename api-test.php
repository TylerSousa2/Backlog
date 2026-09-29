<?php

require_once "includes/rawg.php";

$url = "https://api.rawg.io/api/games?key=" . $rawgApiKey . "&search=Red%20Dead%20Redemption%202";

$response = file_get_contents($url);

$data = json_decode($response, true);

?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste RAWG</title>
</head>

<body>

    <h1>Resultados</h1>

    <?php foreach ($data["results"] as $game) { ?>

        <h2><?php echo $game["name"]; ?></h2>

        <p>ID: <?php echo $game["id"]; ?></p>

        <p>Data de lançamento: <?php echo $game["released"]; ?></p>

        <p>Playtime: <?php echo $game["playtime"]; ?> horas</p>

        <img src="<?php echo $game["background_image"]; ?>" width="300">

        <hr>

    <?php } ?>

</body>
</html>