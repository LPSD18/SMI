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
    <title>Reset Password</title>
    <link rel="stylesheet" href="css/resetPassword.css">
    <script src="script.js"></script>
</head>
<body>
    <div class="container">
        <h2>Reset Password</h2>
        <form action="reset_password.php" method="post" onsubmit="return checkPasswords(event)">
            <div class="input-group">
                <label for="password">Password:</label>
                <input type="password" name="password" id="password" required>
            </div>
            <div class="input-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" name="confirm_password" id="confirm_password" required>
            </div>
            <button type="submit" class="submit-button">Reset Password</button>
        </form>
    </div>
</body>
</html>

<script>
    function checkPasswords(event) {
        event.preventDefault();
        var password = document.getElementById("password").value;
        var confirmPassword = document.getElementById("confirm_password").value;
        if(password !== confirmPassword) {
            alert("Passwords do not match!");
            return false;
        }
        return true;
    }
</script>

<?php
    dbConnect(ConfigFile);
    
    $dataBaseName = $GLOBALS['configDataBase']->db;
    mysqli_select_db($GLOBALS['ligacao'], $dataBaseName);
    
    $queryString = "UPDATE `$dataBaseName`.`User` SET `password` = ? WHERE `email` = ?";
    
    $stmt = mysqli_prepare($GLOBALS['ligacao'], $queryString);
    
    $password = $_POST['password'];
    $email = $_GET['email'];

    mysqli_stmt_bind_param($stmt, 'ss', $password, $email);
    if(mysqli_stmt_execute($stmt)) {
        echo "Password changed successfully.";
    } else {
        echo "Error: " . mysqli_error($GLOBALS['ligacao']);
    }
    

?>