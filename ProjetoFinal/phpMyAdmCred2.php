<!DOCTYPE html>
<?php
//MUDADO para selecionar lib.php de Teste
require_once( "Lib/lib.php" );
require_once( "Lib/db.php" );
session_start();

if(isset($_POST['loginPHP']) && isset($_POST['phpPass']) && isset($_POST['phpUser'])){
    if($_POST['phpUser'] != NULL){
        $_SESSION['phpUser'] = $_POST['phpUser']; 
        $_SESSION['phpPass'] = $_POST['phpPass'];
        header( 'Location: createDBForm.php' );
    }
}
?>