<!DOCTYPE html>
<?php
require_once("Lib/lib.php");
require_once("Lib/db.php");

session_start();
?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GymHub - Login</title>
    <link rel="stylesheet" href="css/login.css">
    <script src="scripts/validators.js"></script>
</head>

<body>
    <div class="login-container">
        <div class="login-box">
            <a href="page.php" class="close-button">&times;</a>
            <img src="images/GymHub.jpg" alt="GymHub Logo" class="logo">

            <?php


            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                $username = $_POST['username'];
                $password = $_POST['password'];

                // Conectar à base de dados
                dbConnect(ConfigFile);

                $dataBaseName = $GLOBALS['configDataBase']->db;
                mysqli_select_db($GLOBALS['ligacao'], $dataBaseName);

                // Escapar entradas para evitar SQL Injection
                $username = mysqli_real_escape_string($GLOBALS['ligacao'], $username);
                $password = mysqli_real_escape_string($GLOBALS['ligacao'], $password);

                // Verificar se o utilizador existe e obter os seus dados
                $query = "SELECT * FROM `User` WHERE `username`='$username'";
                $result = mysqli_query($GLOBALS['ligacao'], $query);

                if ($result && mysqli_num_rows($result) > 0) {
                    $user = mysqli_fetch_assoc($result);

                    // Verificar se a password corresponde
                    if ($password === $user['password']&& $user['active'] === '1') {
                        // Password correta, iniciar sessão
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['userType'] = $user['type'];


                        // Redirecionar para a página principal ou painel de utilizador
                        header("Location: page.php");
                        exit();
                    } else {
                        // Password incorreta

                    }
                } else {
                    // Utilizador não encontrado
                    echo "<p>Invalid username or password.</p>";
                }

                mysqli_free_result($result);
                dbDisconnect();
            }
            ?>

            <form action="login.php" method="post" onsubmit="return GuestUserAndPasswordValidator(this)" name="TesteLogin">

                <div class="input-group">
                    <input type="text" name="username" placeholder="Email, or username" required>
                </div>

                <div class="input-group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>

                <button type="submit" class="login-button" name="login">Login</button>

            </form>

            <div class="extra-links">
                <a href="forgotPassword.php" class="forgot-password">Forgot password?</a>
                <p>Don't have an account? <a href="register.php" class="signup-link">Sign up</a></p>
            </div>
        </div>
    </div>
</body>

</html>