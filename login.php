<?php

require_once "includes/csrf.php";
require_once "includes/db.php";
require_once "includes/functions.php";


/*
|--------------------------------------------------------------------------
| Se já estiver autenticado
|--------------------------------------------------------------------------
*/

if (isLoggedIn()) {
    redirect("dashboard.php");
}


/*
|--------------------------------------------------------------------------
| Login
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    verifyCsrfToken();

    $email = trim(
        $_POST["email"] ?? ""
    );

    $password = $_POST["password"] ?? "";


    /*
     * Validar campos
     */

    if ($email === "" || $password === "") {

        $error = "Preenche todos os campos.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Introduz um email válido.";

    } else {


        /*
         * Procurar utilizador
         */

        $sql = "SELECT
                    id,
                    username,
                    password

                FROM users

                WHERE email = :email";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ":email" => $email
        ]);

        $user = $stmt->fetch();


        /*
         * Verificar credenciais
         */

        if (
            !$user ||
            !password_verify(
                $password,
                $user["password"]
            )
        ) {

            $error = "Email ou password incorretos.";

        } else {


            /*
             * Regenerar o ID da sessão
             *
             * Ajuda a prevenir session fixation.
             */

            session_regenerate_id(true);

            $_SESSION["user_id"] =
                (int) $user["id"];

            $_SESSION["username"] =
                $user["username"];


            /*
             * Entrar no dashboard
             */

            redirect("dashboard.php");
        }
    }
}


$pageTitle = "Entrar - GameBacklog";

require_once "includes/header.php";

?>

<h1>
    Iniciar sessão
</h1>


<?php if (isset($error)) { ?>

    <p>
        <?php echo e($error); ?>
    </p>

<?php } ?>


<form method="POST">

    <?php echo csrfField(); ?>


    <label for="email">
        Email:
    </label>

    <input type="email" id="email" name="email" value="<?php echo e(
        $_POST["email"] ?? ""
    ); ?>" autocomplete="email" required>


    <br><br>


    <label for="password">
        Password:
    </label>

    <input type="password" id="password" name="password" autocomplete="current-password" required>


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