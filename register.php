<?php

require_once "includes/csrf.php";
require_once "includes/db.php";
require_once "includes/functions.php";


/*
|--------------------------------------------------------------------------
| Utilizador já autenticado
|--------------------------------------------------------------------------
*/

if (isLoggedIn()) {
    redirect("dashboard.php");
}


/*
|--------------------------------------------------------------------------
| Processar registo
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    verifyCsrfToken();

    $username = trim(
        $_POST["username"] ?? ""
    );

    $email = trim(
        $_POST["email"] ?? ""
    );

    $password = $_POST["password"] ?? "";

    $passwordConfirm =
        $_POST["password_confirm"] ?? "";


    /*
    |--------------------------------------------------------------------------
    | Validações
    |--------------------------------------------------------------------------
    */

    if (
        $username === "" ||
        $email === "" ||
        $password === "" ||
        $passwordConfirm === ""
    ) {

        $error = "Preenche todos os campos.";

    } elseif (
        strlen($username) < 3 ||
        strlen($username) > 50
    ) {

        $error =
            "O username deve ter entre 3 e 50 caracteres.";

    } elseif (
        !preg_match(
            "/^[a-zA-Z0-9_]+$/",
            $username
        )
    ) {

        $error =
            "O username só pode conter letras, números e underscores.";

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error = "Introduz um email válido.";

    } elseif (strlen($password) < 8) {

        $error =
            "A password deve ter pelo menos 8 caracteres.";

    } elseif ($password !== $passwordConfirm) {

        $error =
            "As passwords não coincidem.";

    } else {


        /*
        |--------------------------------------------------------------------------
        | Verificar se username/email já existem
        |--------------------------------------------------------------------------
        */

        $sql = "SELECT
                    id
                FROM users
                WHERE username = :username
                OR email = :email
                LIMIT 1";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ":username" => $username,
            ":email" => $email
        ]);

        $existingUser = $stmt->fetch();


        if ($existingUser) {

            $error =
                "O username ou email já está a ser utilizado.";

        } else {


            /*
            |--------------------------------------------------------------------------
            | Criar utilizador
            |--------------------------------------------------------------------------
            */

            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $sql = "INSERT INTO users (
                        username,
                        email,
                        password
                    )
                    VALUES (
                        :username,
                        :email,
                        :password
                    )";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":username" => $username,
                ":email" => $email,
                ":password" => $hashedPassword
            ]);


            /*
            |--------------------------------------------------------------------------
            | Login automático
            |--------------------------------------------------------------------------
            */

            session_regenerate_id(true);

            $_SESSION["user_id"] =
                (int) $pdo->lastInsertId();

            $_SESSION["username"] =
                $username;


            /*
            |--------------------------------------------------------------------------
            | Entrar no dashboard
            |--------------------------------------------------------------------------
            */

            redirect("dashboard.php");
        }
    }
}


$pageTitle = "Criar conta - GameBacklog";

require_once "includes/header.php";

?>

<h1>
    Criar conta
</h1>


<?php if (isset($error)) { ?>

    <p>
        <?php echo e($error); ?>
    </p>

<?php } ?>


<form method="POST">

    <?php echo csrfField(); ?>


    <label for="username">
        Username:
    </label>

    <input type="text" id="username" name="username" value="<?php echo e(
        $_POST["username"] ?? ""
    ); ?>" minlength="3" maxlength="50" autocomplete="username" required>


    <br><br>


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

    <input type="password" id="password" name="password" minlength="8" autocomplete="new-password" required>


    <br><br>


    <label for="password_confirm">
        Confirmar password:
    </label>

    <input type="password" id="password_confirm" name="password_confirm" minlength="8" autocomplete="new-password"
        required>


    <br><br>


    <button type="submit">
        Criar conta
    </button>

</form>


<p>

    Já tens conta?

    <a href="login.php">
        Entrar
    </a>

</p>


<?php require_once "includes/footer.php"; ?>