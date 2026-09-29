<?php

require_once "includes/db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (empty($username) || empty($email) || empty($password)) {

        $error = "Preenche todos os campos.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Introduz um email válido.";

    } elseif (strlen($password) < 8) {

        $error = "A password deve ter pelo menos 8 caracteres.";

    } else {

        /*
         * Verificar username
         */

        $sql = "SELECT id
                FROM users
                WHERE username = :username";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ":username" => $username
        ]);

        $existingUser = $stmt->fetch();


        if ($existingUser) {

            $error = "Este username já está a ser utilizado.";

        } else {

            /*
             * Verificar email
             */

            $sql = "SELECT id
                    FROM users
                    WHERE email = :email";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":email" => $email
            ]);

            $existingUser = $stmt->fetch();


            if ($existingUser) {

                $error = "Este email já está registado.";

            } else {

                /*
                 * Criar password segura
                 */

                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


                /*
                 * Criar utilizador
                 */

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


                $success = "Conta criada com sucesso!";
            }
        }
    }
}

$pageTitle = "Criar conta - GameBacklog";

require_once "includes/header.php";

?>

<h1>Criar conta</h1>

<?php if (isset($error)) { ?>

    <p>
        <?php echo htmlspecialchars($error); ?>
    </p>

<?php } ?>

<?php if (isset($success)) { ?>

    <p>
        <?php echo htmlspecialchars($success); ?>
    </p>

    <p>
        <a href="login.php">
            Iniciar sessão
        </a>
    </p>

<?php } else { ?>

    <form method="POST">

        <label for="username">
            Username:
        </label>

        <input
            type="text"
            id="username"
            name="username"
            required
        >

        <br><br>

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
            minlength="8"
            required
        >

        <br><br>

        <button type="submit">
            Criar conta
        </button>

    </form>

    <p>
        Já tens uma conta?
        <a href="login.php">
            Iniciar sessão
        </a>
    </p>

<?php } ?>

<?php require_once "includes/footer.php"; ?>