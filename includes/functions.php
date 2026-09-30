<?php


/*
|--------------------------------------------------------------------------
| Escapar texto para HTML
|--------------------------------------------------------------------------
*/

function e(?string $value): string
{
    return htmlspecialchars(
        $value ?? "",
        ENT_QUOTES,
        "UTF-8"
    );
}

/*
|--------------------------------------------------------------------------
| Validar ID
|--------------------------------------------------------------------------
*/

function validateId($id, string $errorMessage = "ID inválido."): int
{
    if (
        $id === null ||
        $id === "" ||
        !filter_var($id, FILTER_VALIDATE_INT)
    ) {
        die($errorMessage);
    }

    $id = (int) $id;

    if ($id <= 0) {
        die($errorMessage);
    }

    return $id;
}


/*
|--------------------------------------------------------------------------
| Validar data
|--------------------------------------------------------------------------
*/

function validateDate(?string $date): ?string
{
    if ($date === null || $date === "") {
        return null;
    }

    $dateObject = DateTime::createFromFormat(
        "Y-m-d",
        $date
    );

    if (
        !$dateObject ||
        $dateObject->format("Y-m-d") !== $date
    ) {
        die("Data inválida.");
    }

    return $date;
}


/*
|--------------------------------------------------------------------------
| Validar rating
|--------------------------------------------------------------------------
*/

function validateRating($rating): ?float
{
    if ($rating === null || $rating === "") {
        return null;
    }

    if (!is_numeric($rating)) {
        die("Rating inválido.");
    }

    $rating = (float) $rating;

    if ($rating < 0 || $rating > 10) {
        die("O rating deve estar entre 0 e 10.");
    }

    if (fmod($rating * 2, 1) !== 0.0) {
        die("O rating deve ser em incrementos de 0.5.");
    }

    return $rating;
}


/*
|--------------------------------------------------------------------------
| Validar estado do jogo
|--------------------------------------------------------------------------
*/

function validateStatus(?string $status): string
{
    $allowedStatuses = [
        "planned",
        "playing",
        "completed",
        "dropped"
    ];

    if (!in_array($status, $allowedStatuses, true)) {
        die("Estado inválido.");
    }

    return $status;
}


/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/

function redirect(string $url): never
{
    header("Location: " . $url);
    exit;
}


/*
|--------------------------------------------------------------------------
| Obter jogo pela ID interna
|--------------------------------------------------------------------------
*/

function getGameById(PDO $pdo, int $gameId): ?array
{
    $sql = "SELECT
                id,
                rawg_id,
                title,
                description,
                cover,
                release_date,
                genre,
                platform
            FROM games
            WHERE id = :game_id";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":game_id" => $gameId
    ]);

    $game = $stmt->fetch();

    return $game ?: null;
}


/*
|--------------------------------------------------------------------------
| Obter utilizador pela ID
|--------------------------------------------------------------------------
*/

function getUserById(PDO $pdo, int $userId): ?array
{
    $sql = "SELECT
                id,
                username,
                email,
                created_at
            FROM users
            WHERE id = :user_id";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":user_id" => $userId
    ]);

    $user = $stmt->fetch();

    return $user ?: null;
}

/*
|--------------------------------------------------------------------------
| Obter jogo através do RAWG ID
|--------------------------------------------------------------------------
*/

function getGameByRawgId(PDO $pdo, int $rawgId): ?array
{
    $sql = "SELECT
                id,
                rawg_id,
                title,
                description,
                cover,
                release_date,
                genre,
                platform
            FROM games
            WHERE rawg_id = :rawg_id";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":rawg_id" => $rawgId
    ]);

    $game = $stmt->fetch();

    return $game ?: null;
}


/*
|--------------------------------------------------------------------------
| Verificar se o jogo está na biblioteca
|--------------------------------------------------------------------------
*/

function isGameInLibrary(
    PDO $pdo,
    int $userId,
    int $rawgId
): bool {
    $sql = "SELECT user_games.id
            FROM user_games
            INNER JOIN games
                ON games.id = user_games.game_id
            WHERE user_games.user_id = :user_id
            AND games.rawg_id = :rawg_id";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":user_id" => $userId,
        ":rawg_id" => $rawgId
    ]);

    return (bool) $stmt->fetch();
}


/*
|--------------------------------------------------------------------------
| Obter review de um utilizador para um jogo
|--------------------------------------------------------------------------
*/

function getUserReview(
    PDO $pdo,
    int $userId,
    int $rawgId
): ?array {
    $sql = "SELECT
                reviews.id,
                reviews.rating,
                reviews.review,
                reviews.created_at,
                reviews.updated_at
            FROM reviews
            INNER JOIN games
                ON reviews.game_id = games.id
            WHERE reviews.user_id = :user_id
            AND games.rawg_id = :rawg_id";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":user_id" => $userId,
        ":rawg_id" => $rawgId
    ]);

    $review = $stmt->fetch();

    return $review ?: null;
}

/*
|--------------------------------------------------------------------------
| Estados dos jogos
|--------------------------------------------------------------------------
*/

function getGameStatusLabels(): array
{
    return [
        "planned" => "Planeado",
        "playing" => "A jogar",
        "completed" => "Completado",
        "dropped" => "Abandonado"
    ];
}