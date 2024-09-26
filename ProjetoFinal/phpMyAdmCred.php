<!DOCTYPE html>
<?php
//MUDADO para selecionar lib.php de Teste
require_once( "Lib/lib.php" );
require_once( "Lib/db.php" );
include_once("phpMyAdmCred2.php");
?>

<html>
    <head>
        <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <link rel="stylesheet" href="css/login.css">
        <script type="text/javascript" src="scripts/validators.js">
        </script>
    </head>

    <body>
        <h1>phpMyAdmin Credentials</h1>
        <form 
            action=""
            method="post" >
            <table>
                <tr>
                    <td>User:</td>
                    <td><input type="text" name="phpUser" placeholder="User"></td>
                </tr>
                <tr>
                    <td>Password:</td>
                    <td><input type="text" name="phpPass" placeholder="Pass"></td>
                </tr>
                <tr>
                    <td><input type="submit" name="loginPHP" value="login"></td>
                </tr>   
            </table>
        </form>
        <hr>
        <p><a href="../index.php">Back</a></p>

    </body>
</html>