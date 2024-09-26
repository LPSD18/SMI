<!DOCTYPE html>
<?php
require_once( "Lib/lib.php");
require_once( "Lib/db.php" );

session_start();

$token = $_GET['token'];
$email = $_GET['email'];

$sessionToken = $_SESSION['token'];

if( $token !== $sessionToken ) {
    echo "Invalid token";
    exit;
}

// Conectar à base de dados
dbConnect(ConfigFile);
$dataBaseName = $GLOBALS['configDataBase']->db;
mysqli_select_db($GLOBALS['ligacao'], $dataBaseName);

$query = "UPDATE `User` SET `active` = 1 WHERE `email` = '$email'";
$result = mysqli_query($GLOBALS['ligacao'], $query);

$query = "SELECT * FROM `User` WHERE `email` = '$email'";
$user_query = mysqli_query($GLOBALS['ligacao'], $query);
$user_query_values = mysqli_fetch_assoc($user_query);
$userId = $user_query_values['id'];
$userName = $user_query_values['username'];

if($result) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $userName;
    header("Location: page.php");
    exit();
}
dbDisconnect();