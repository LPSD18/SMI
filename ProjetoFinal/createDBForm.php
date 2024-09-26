<!DOCTYPE html>
<?php
include_once("showAQLAccounts.php");
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
        
        <form 
            action="processCreateDB.php"
            method="post" >
            <table>
                <tr>
                    <td>Database:</td>
                    <td><input type="text" name="database" placeholder="Type your Database"></td>
                </tr>
                <tr>
                    <td>Username:</td>
                    <td><input type="text" name="username" placeholder="Type your username"></td>
                </tr>
                <tr>
                    <td>Password:</td>
                    <td><input type="password" name="password" placeholder="Type your password"></td>
                </tr>
                <tr>
                    <td><input type="submit" name="create" value="Create"></td>
                </tr>   
            </table>
        </form>
        <hr>
        <p><a href="../index.php">Back</a></p>

    </body>
</html>