
<!DOCTYPE html>
<?php
    //MUDADO para selecionar lib.php de Teste
    require_once( "Lib/lib.php" );
      require_once( "Lib/db.php" );
    session_start();

    
    echo "SESSAO USER" . $_SESSION['phpUser'] . "</p>";
    echo "SESSAO PASS" . $_SESSION['phpPass'] . "</p>";
?>