<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "includes/db.php";

$currentUserId = $_SESSION["user_id"];

$search = trim($_GET["search"] ?? "");

$users = [];


if ($search !== "") {

    $sql = "SELECT
                id,
                username,
                created_at

            FROM users

            WHERE username LIKE :search
            AND id != :current_user_id

            ORDER BY username ASC

            LIMIT 20";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":search" => "%" . $search . "%",
        ":current_user_id" => $currentUserId
    ]);

    $users = $stmt->fetchAll();
}


$pageTitle = "Pesquisar utilizadores - GameBacklog";

require_once "includes/header.php";

?>

<h1>Pesquisar utilizadores</h1>


<form method="GET">

    <input
        type="text"
        name="search"
        placeholder="Username..."
        value="<?php echo htmlspecialchars($search); ?>"
        required
    >

    <button type="submit">
        Pesquisar
    </button>

</form>


<hr>


<?php if ($search !== "") { ?>

    <h2>
        Resultados para:
        <?php echo htmlspecialchars($search); ?>
    </h2>


    <?php if (empty($users)) { ?>

        <p>
            Não foram encontrados utilizadores.
        </p>

    <?php } else { ?>

        <?php foreach ($users as $user) { ?>

            <div>

                <h3>

                    <a
                        href="profile.php?id=<?php echo $user["id"]; ?>"
                    >

                        <?php echo htmlspecialchars($user["username"]); ?>

                    </a>

                </h3>


                <p>

                    Membro desde:

                    <?php

                    echo date(
                        "d/m/Y",
                        strtotime($user["created_at"])
                    );

                    ?>

                </p>


                <p>

                    <a
                        href="profile.php?id=<?php echo $user["id"]; ?>"
                    >
                        Ver perfil
                    </a>

                </p>


                <hr>

            </div>

        <?php } ?>

    <?php } ?>

<?php } else { ?>

    <p>
        Introduz um username para procurar utilizadores.
    </p>

<?php } ?>


<?php require_once "includes/footer.php"; ?>