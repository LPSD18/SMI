<!DOCTYPE html>
    <?php
      require_once( "Lib/lib.php" );
      require_once( "Lib/db.php" );
      session_start();
      
      try{
        //tenta conectar-se à base de dados
        dbConnect(ConfigFile);
        
        //vai para a pagina de register
        header( 'Location:page.php' );
      }catch (Exception $e) {
        header( 'Location:phpMyAdmCred.php' );
        //header( 'Location: createDBForm.php' );
      }
    ?>