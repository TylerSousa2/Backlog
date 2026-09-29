<?php

session_start();

require_once "includes/db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $sql = "SELECT id, username, password
            FROM users
            WHERE email = :email";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":email" => $email
    ]);

    $user = $stmt->fetch();

    if (!$user) {

        $error = "Email ou password incorretos.";

    } elseif (!password_verify($password, $user["password"])) {

        $error = "Email ou password incorretos.";

    } else {

        $_SESSION["user_id"] = $user["id"];
        $_SESSION["username"] = $user["username"];

        header("Location: dashboard.php");
        exit;
    }
}

$pageTitle = "Entrar - GameBacklog";

require_once "includes/header.php";

?>

<h1>Iniciar sessão</h1>

<?php if (isset($error)) { ?>

    <p>
        <?php echo htmlspecialchars($error); ?>
    </p>

<?php } ?>

<form method="POST">

    <label for="email">
        Email:
    </label>

    <input
        type="email"
        id="email"
        name="email"
        required
    >

    <br><br>

    <label for="password">
        Password:
    </label>

    <input
        type="password"
        id="password"
        name="password"
        required
    >

    <br><br>

    <button type="submit">
        Entrar
    </button>

</form>

<p>
    Ainda não tens conta?
    <a href="register.php">
        Criar conta
    </a>
</p>

<?php require_once "includes/footer.php"; ?>