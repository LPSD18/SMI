<?php
require_once("Lib/lib.php");
require_once("Lib/db.php");
if (!isset($_SESSION)) {
    session_start();
}

$filterUserName = "/^([a-zA-Z0-9]{4,16})/";
$filterPassword = "/^([a-zA-Z0-9]{4,16})/";
$filterEmail = "/^([a-z0-9_\.\-])+\@(([a-z0-9\-])+\.)+([a-z0-9]{2,4})$/i";


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $day = $_POST['day'];
    $month = $_POST['month'];
    $year = $_POST['year'];
    $password = $_POST['password'];

    $type = 'default';


    $userCaptcha = $_POST['captcha'];

    $captchaValue = $_SESSION['captcha'];

    // Verificar se o valor do captcha inserido pelo usuário é igual ao valor armazenado na sessão
    if ($userCaptcha == $captchaValue) {
        // Captcha correto, continuar com o registro do usuário

        if (!preg_match($filterUserName, $name) || !preg_match($filterPassword, $password) || !preg_match($filterEmail, $email)) {
            // Entradas inválidas, exibir uma mensagem de erro ou redirecionar de volta ao formulário de registro
            echo "<p>Invalid input. Please try again.</p>";
            header('Location: register.php');
            //exit();
        }


        // Conectar à base de dados
        dbConnect(ConfigFile);
        $dataBaseName = $GLOBALS['configDataBase']->db;
        mysqli_select_db($GLOBALS['ligacao'], $dataBaseName);

        // Escapar entradas para evitar SQL Injection
        $name = mysqli_real_escape_string($GLOBALS['ligacao'], $name);
        $email = mysqli_real_escape_string($GLOBALS['ligacao'], $email);
        $password = mysqli_real_escape_string($GLOBALS['ligacao'], $password);



        // Criar data de nascimento
        $birthday = $year . '-' . $month . '-' . $day;

        $query = "SELECT * FROM `User` WHERE `email` = '$email' or `username` = '$name'";
        $result = mysqli_query($GLOBALS['ligacao'], $query);

        if (!$result) {
            echo "<p>Error: " . mysqli_error($GLOBALS['ligacao']) . "</p>";
            //exit();
        }

        $num_rows = mysqli_num_rows($result);
        if ($num_rows > 0) {
            echo "<p>Email already exists.</p>";
            // header('Location: login.php');
            // exit();
        } else {
            // Inserir novo usuário na base de dados
            $query = "INSERT INTO User (username, email, password, birthday, type, active) VALUES ('$name', '$email', '$password', '$birthday','$type',1 )";
            if (mysqli_query($GLOBALS['ligacao'], $query)) {
                // Registro bem-sucedido, redirecionar para a página de login
                $userId = mysqli_insert_id($GLOBALS['ligacao']);

                $_SESSION['user_id'] = $userId;
                $_SESSION['username'] = $name;

                header("Location: page.php");
                exit();
            } else {
                echo "<p>Error: " . mysqli_error($GLOBALS['ligacao']) . "</p>";
            }

            dbDisconnect();
        }
    } else {
        // Captcha incorreto, exibir uma mensagem de erro ou redirecionar de volta ao formulário de registro
        echo "<p>Captcha incorrect. Please try again.</p>";
        echo "<p>DEBUG Captcha do servidor: $captchaValue</p>";
        echo "<p>DEBUG Captcha inserido: $userCaptcha</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GymHub - Register</title>
    <link rel="stylesheet" href="css/register.css">
</head>

<body>
    <div class="register-container">
        <div class="register-box">
            <a href="page.php" class="close-button">&times;</a>
            <img src="images/GymHub.jpg" alt="GymHub Logo" class="logo">
            <form action="processFormRegister.php" method="post">
                <div class="input-group">
                    <input type="text" name="name" placeholder="Name" required>
                </div>
                <div class="input-group">
                    <input type="email" name="email" placeholder="Email" required>
                </div>
                <div class="input-group">
                    <select name="day" required>
                        <option value="" disabled selected>Day</option>
                        <!-- Options for days 1-31 -->
                        <?php for ($i = 1; $i <= 31; $i++) : ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                    <select name="month" required>
                        <option value="" disabled selected>Month</option>
                        <!-- Options for months 1-12 -->
                        <?php for ($i = 1; $i <= 12; $i++) : ?>
                            <option value="<?= $i ?>"><?= date("F", mktime(0, 0, 0, $i, 10)) ?></option>
                        <?php endfor; ?>
                    </select>
                    <select name="year" required>
                        <option value="" disabled selected>Year</option>
                        <!-- Options for years -->
                        <?php for ($i = 1900; $i <= date("Y"); $i++) : ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="input-group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <!-- Exibir a imagem captcha -->
                <div class="input-group">
                    <img src="captchaImage.php" alt="Captcha Image">
                </div>
                <div class="input-group">
                    <input type="text" name="captcha" placeholder="Enter Captcha" required>
                </div>
                <button type="submit" class="register-button">Register</button>
            </form>

            <div class="extra-links">
                <p>Already have an account? <a href="login.php" class="signin-link">Sign in</a></p>
            </div>
        </div>
    </div>
</body>

</html>