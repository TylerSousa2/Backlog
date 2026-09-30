<?php

require_once "includes/csrf.php";
require_once "includes/db.php";
require_once "includes/functions.php";

requireLogin();

$userId = currentUserId();

$reviewId = validateId(
    $_GET["id"] ?? null,
    "Review inválida."
);


/*
|--------------------------------------------------------------------------
| Obter review
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            reviews.id,
            reviews.game_id,
            reviews.rating,
            reviews.review,
            games.rawg_id,
            games.title
        FROM reviews
        INNER JOIN games
            ON games.id = reviews.game_id
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
|--------------------------------------------------------------------------
| Atualizar review
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    verifyCsrfToken();

    $rating = validateRating(
        $_POST["rating"] ?? null
    );

    $reviewText = trim(
        $_POST["review"] ?? ""
    );

    if ($reviewText === "") {
        $error = "A review não pode estar vazia.";
    }

    if (!isset($error)) {

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

        redirect(
            "game.php?id=" . (int) $review["rawg_id"]
        );
    }
}


$pageTitle = "Editar review - GameBacklog";

require_once "includes/header.php";

?>

<h1>
    Editar review
</h1>

<h2>
    <?php echo e($review["title"]); ?>
</h2>

<?php if (isset($error)) { ?>

    <p>
        <?php echo e($error); ?>
    </p>

<?php } ?>

<form method="POST">

    <?php echo csrfField(); ?>

    <label for="rating">
        Rating:
    </label>

    <input type="number" id="rating" name="rating" min="0" max="10" step="0.5" value="<?php echo e(
        $_POST["rating"] ?? $review["rating"] ?? ""
    ); ?>">

    <br><br>

    <label for="review">
        Review:
    </label>

    <br>

    <textarea id="review" name="review" rows="8" cols="60" required><?php echo e(
        $_POST["review"] ?? $review["review"]
    ); ?></textarea>

    <br><br>

    <button type="submit">
        Guardar alterações
    </button>

</form>

<p>
    <a href="game.php?id=<?php echo (int) $review["rawg_id"]; ?>">
        Cancelar
    </a>
</p>

<?php require_once "includes/footer.php"; ?>