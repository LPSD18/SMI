<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once( "Lib/lib.php" );
require_once( "Lib/db.php" );

if (isset($_POST["submit"]) && isset($_FILES["fileToUpload"])) {
    $target_file = $_FILES["fileToUpload"]["tmp_name"];
    $imageFileType = strtolower(pathinfo($_FILES["fileToUpload"]["name"], PATHINFO_EXTENSION));

    // Verifica se o arquivo é uma imagem
    $check = getimagesize($target_file);
    if ($check === false) {
        echo "O arquivo não é uma imagem.";
        exit();
    }

    // Conectar à base de dados
    dbConnect(ConfigFile);
    $dataBaseName = $GLOBALS['configDataBase']->db;
    mysqli_select_db($GLOBALS['ligacao'], $dataBaseName);

    // Escapar entradas para evitar SQL Injection
    $username = mysqli_real_escape_string($GLOBALS['ligacao'], $_SESSION['username']);
    $image = addslashes(file_get_contents($target_file));

    // Atualizar a imagem do usuário
    $query = "UPDATE `User` SET `photo`='$image' WHERE `username`='$username'";
    if (mysqli_query($GLOBALS['ligacao'], $query)) {
        echo "A imagem foi enviada com sucesso.";
        header("Location: page.php");
    } else {
        echo "Erro ao enviar a imagem: " . mysqli_error($GLOBALS['ligacao']);
        header("Location: page.php");
    }

    dbDisconnect();
} else {
    echo "Nenhuma imagem foi enviada.";
}
?>