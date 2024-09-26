

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/emailSent.css">
    <title>Email Sent</title>
</head>

<body>
    <h1>Email Sent</h1>
    <p>An email was sent to verify your account. Please check your email and click on the verification link to activate your account.</p>
    <a href="login.php">Go to Login</a>
</body>

</html>
<!-- <?php
require_once("Lib/db.php");
require_once("Lib/lib.php");
require_once("Lib/lib-mail-v2.php");
require_once("Lib/HtmlMimeMail.php");


session_start();


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

    if ($userCaptcha != $captchaValue) {
        // Captcha incorreto, exibir uma mensagem de erro ou redirecionar de volta ao formulário de registro
        echo "<p>Captcha incorrect. Please try again.</p>";
        echo "<p>DEBUG Captcha do servidor: $captchaValue</p>";
        echo "<p>DEBUG Captcha inserido: $userCaptcha</p>";
        header('Location: register.php');
        //exit();
    }

    if (!preg_match($filterUserName, $name) || !preg_match($filterPassword, $password) || !preg_match($filterEmail, $email)) {
        // Entradas inválidas, exibir uma mensagem de erro ou redirecionar de volta ao formulário de registro
        echo "<p>Invalid input. Please try again.</p>";
        header('Location: register.php');
        exit();
    }

    // Conectar à base de dados
    dbConnect(ConfigFile);
    $dataBaseName = $GLOBALS['configDataBase']->db;
    mysqli_select_db($GLOBALS['ligacao'], $dataBaseName);

    // Escapar entradas para evitar SQL Injection
    $name = mysqli_real_escape_string($GLOBALS['ligacao'], $name);
    $email = mysqli_real_escape_string($GLOBALS['ligacao'], $email);
    $password = mysqli_real_escape_string($GLOBALS['ligacao'], $password);

    $birthday = $year . '-' . $month . '-' . $day;

    // Verificar se o email existe
    $query = "SELECT * FROM `User` WHERE `email` = '$email' or `username` = '$name'";
    $result = mysqli_query($GLOBALS['ligacao'], $query);

    if (!$result) {
        echo "<p>Error: " . mysqli_error($GLOBALS['ligacao']) . "</p>";
        // exit();
    }

    $num_rows = mysqli_num_rows($result);
    if ($num_rows > 0) {
        echo "<p>Email already exists.</p>";
        header('Location: register.php');
        exit();
    } else {
        $query = "INSERT INTO `User` (username, password, email, birthday, followers, type, active) 
                VALUES ('$name', '$password', '$email', '$birthday', 0, 'default', 0)";
        if (mysqli_query($GLOBALS['ligacao'], $query) === false) {
            echo "Insert has failed. Details: \n<br>";
            $errorMsg = mysqli_error($GLOBALS['ligacao']);
            $errorCode = mysqli_errno($GLOBALS['ligacao']);
            echo "Error $errorCode: $errorMsg";
        } else {
            $Account = 'GymHub';
            $ToName = $name;
            $ToEmail = $email;
            $Subject = 'Welcome to GymHub. Please verify your account';

            $token = md5(uniqid());
            $_SESSION['token'] = $token;


            $name = webAppName();

            $verificationLink = "http://" . $_SERVER['SERVER_NAME'] . ":" . $_SERVER['SERVER_PORT'] . "verifyMail.php?token=" . $token . "&email=" . urlencode($email);

            $Message = 'Click the link to verify your account: ' . $verificationLink;

            isset($_INPUT['Debug']) ? $Debug = TRUE : $Debug = FALSE;
            isset($_INPUT['SendAsHTML']) ? $SendAsHTML = TRUE : $SendAsHTML = FALSE;

            $query = "SELECT * FROM `email-accounts` WHERE `email` = 'g02smi2324@gmail.com'";
            $result = mysqli_query($GLOBALS['ligacao'], $query);

            if ($result && mysqli_num_rows($result) > 0) {
                $account = mysqli_fetch_array($result);

                $accountName = $account['accountName'];
                $smtpServer = $account['smtpServer'];
                $port = $account['port'];
                $useSSL = $account['useSLL'];
                $timeout = $account['timeout'];
                $loginName = $account['loginName'];
                $accountPassowrd = $account['password'];
                $accountEmail = $account['email'];
                $displayName = $account['displayName'];
            }

            if ($SendAsHTML == TRUE) {
                /*
                * Read the files to attach.
                */

                $files[0]['Name'] = 'Example.zip';
                $files[0]['Type'] = 'application/octet-stream';

                $files[1]['Name'] = 'Example.png';
                $files[1]['Type'] = 'image/png';

                $files[2]['Name'] = 'Example.pdf';
                $files[2]['Type'] = 'application/octet-stream';

                $AttachDirectory = "attachs";
                for ($i = 0; $i < count($files); ++$i) {
                    $fileName = $AttachDirectory . DIRECTORY_SEPARATOR . $files[$i]['Name'];

                    $fHandler = fopen($fileName, 'rb');
                    $files[$i]['Contents'] = fread($fHandler, filesize($fileName));
                    fclose($fHandler);
                }

                /*
                * Create the mail object.
                */
                $mail = new HtmlMimeMail();

                /*
                * HTML component of the e-mail
                */
                $MessageHTML = <<<EOD
                <html>
                    <body style="background: url('background.gif') repeat;">
                        <font face="Verdana, Arial" color="#FF0000">
                            $Message
                        </font>
                    </body>
                </html>
                EOD;
                /*
                * Add the text, html and embedded images.
                */
                $mail->add_html($MessageHTML, $Message);

                /*
                * Add the attachments to the email.
                */
                for ($i = 0; $i < count($files); ++$i) {
                    $mail->add_attachment(
                        $files[$i]['Contents'],
                        $files[$i]['Name'],
                        $files[$i]['Type']
                    );
                }

                /*
                * Builds the message.
                */
                $mail->build_message();

                /*
                * Sends the message.
                */
                $result = $mail->send(
                    $smtpServer,
                    $useSSL,
                    $port,
                    $loginName,
                    $password,
                    $ToName,
                    $ToEmail,
                    $fromName,
                    $fromEmail,
                    $Subject,
                    "X-Mailer: Html Mime Mail Class"
                );
            } else {
                $result = sendAuthEmail(
                    $smtpServer,
                    $useSSL,
                    $port,
                    $timeout,
                    $loginName,
                    $password,
                    $fromEmail,
                    $fromName,
                    $ToName . " <" . $ToEmail . ">",
                    NULL,
                    NULL,
                    $Subject,
                    $Message,
                    $Debug,  // set to true see debug messages
                    NULL
                );
            }

            if ($result == true) {
                $userMessage = "was";
            } else {
                $userMessage = "could not be";
            }
        }
        mysqli_close($linkIdentifier);
    }
    if ($result == true) {
        $userMessage = "was";
    } else {
        $userMessage = "could not be";
    }
    dbDisconnect();
}
?> -->
