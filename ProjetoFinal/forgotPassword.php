<!DOCTYPE html>
<?php
    require_once("Lib/lib.php");
    require_once("Lib/lib-mail-v2.php");

    session_start()
?>
<html>  
<head>  
<title>Forgot Password</title>  
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">  
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>  
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>  
</head>  
<body>  
<div class="container">  
    <h2>Forgot Password</h2>  
    <form method="post" action="forgotPassword.php">  
        <div class="form-group">  
            <label for="email">Email:</label>  
            <input type="email" class="form-control" id="email" placeholder="Enter email" name="email">  
        </div>  
        <button type="submit" class="btn btn-default" onclick="forgotPassword()">Submit</button>  
    </form>
    <script>
        function forgotPassword() {
            var email = document.getElementById('email').value;
            $.post('forgotPassword.php', {'email': email}, function(data) {
                alert(data);
            });
        }
    </script>
</html>

<?php
    function forgotPassword() {
        $flags[] = FILTER_NULL_ON_FAILURE;

        $method = filter_input( INPUT_SERVER, 'REQUEST_METHOD', FILTER_SANITIZE_STRING, $flags);
        $referer = filter_input( INPUT_SERVER, 'HTTP_REFERER', FILTER_SANITIZE_URL, $flags);

        if( $referer == NULL) {
            echo "Invalid HTTP REFERER";
            exit();
        }

        if( $method=='POST') {
            $_INPUT_METHOD = INPUT_POST;
        }
        else if( $method=='GET' ) {
            $_INPUT_METHOD = INPUT_GET;
        }
        else {
            echo "Invalid HTTP method (" . $method . ")";
            exit();
        }

        dbConnect(ConfigFile);

        $dataBaseName = $GLOBALS['configDataBase']->db;
        mysqli_select_db($GLOBALS['ligacao'], $dataBaseName);

        $queryString = "SELECT * FROM `User` WHERE `email` = '$_POST[email]'";
        $result = mysqli_query($GLOBALS['ligacao'], $queryString);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_array($result);

            $ToName = $user['name'];
            $ToEmail = $user['email'];
            }
        $Subject = "GymHub - Password Recovery";
        $verificationLink = "https://" . $_SERVER['SERVER_NAME'] . "/forgotPassword.php?email=" . urlencode($ToEmail);
        $Message = "Press the following link to reset your password: " . $verificationLink;

        $newLine = "\r\n";

        $senderEmail = "g02smi2324@gmail.com";
        $senderName = "GymHub";

        $from = $senderName . " <" . $senderEmail . ">";
        $replyTo = $from;

        $to = $ToName . " <" . $ToEmail . ">";
        $subject = $Subject;
        $message = $Message;

        $headers = "MIMI-Version: 1.0" . $newLine;
        $headers .= "Content-type: text/plain; charset=UTF-8" . $newLine;

        $headers .= encodeHeaderEmail(
            "From",
            $senderName,
            $senderEmail
        );

        $headers .= encodeHeaderEmail(
            "Reply-To",
            $senderName,
            $senderEmail
        );

        $preferences = array(
            "input-charset" => "UTF-8",
            "output-charset" => "ISO-8859-1",
            "scheme" => "Q"
        );

        $result = mail($to, $subject, $message, $headers);

        if ( $result==true ) {
            echo "Email sent successfully";
        }
        else {
            echo "Email not sent";
        }
    }
?>

