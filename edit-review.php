<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "includes/db.php";

$userId = $_SESSION["user_id"];

$reviewId = $_GET["id"] ?? null;

if (!$reviewId) {
    die("Review inválida.");
}


/*
 * Procurar a review
 */

$sql = "SELECT
            reviews.id,
            reviews.rating,
            reviews.review,
            games.rawg_id,
            games.title

        FROM reviews

        INNER JOIN games
            ON reviews.game_id = games.id

        WHERE reviews.id = :review_id
        AND reviews.user_id = :user_id";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":review_id" => $reviewId,
    ":user_id" => $userId
]);

$review = $stmt->fetch();


if (!$review) {
    die("Review não encontrada.");
}


/*
 * Atualizar review
 */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $rating = $_POST["rating"] ?? null;

    $reviewText = trim(
        $_POST["review"] ?? ""
    );


    if ($rating !== null && $rating !== "") {

        $rating = (float) $rating;

        if ($rating < 0 || $rating > 10) {
            die("O rating deve estar entre 0 e 10.");
        }

    } else {

        $rating = null;

    }


    if (empty($reviewText)) {
        die("A review não pode estar vazia.");
    }


    $sql = "UPDATE reviews

            SET
                rating = :rating,
                review = :review

            WHERE id = :review_id
            AND user_id = :user_id";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":rating" => $rating,
        ":review" => $reviewText,
        ":review_id" => $reviewId,
        ":user_id" => $userId
    ]);


    header(
        "Location: game.php?id=" . $review["rawg_id"]
    );

    exit;
}


$pageTitle = "Editar review - GameBacklog";

require_once "includes/header.php";

?>

<h1>
    Editar review
</h1>

<h2>
    <?php echo htmlspecialchars($review["title"]); ?>
</h2>

<form method="POST">

    <label for="rating">
        Rating:
    </label>

    <br>

    <input
        type="number"
        id="rating"
        name="rating"
        min="0"
        max="10"
        step="0.5"
        value="<?php echo $review["rating"] ?? ""; ?>"
    >

    <br><br>

    <label for="review">
        Review:
    </label>

    <br>

    <textarea
        id="review"
        name="review"
        rows="8"
        cols="60"
        required
    ><?php echo htmlspecialchars($review["review"]); ?></textarea>

    <br><br>

    <button type="submit">
        Guardar alterações
    </button>

</form>

<p>
    <a href="game.php?id=<?php echo $review["rawg_id"]; ?>">
        Voltar ao jogo
    </a>
</p>

<?php require_once "includes/footer.php"; ?>