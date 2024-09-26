<?php
define(
    "ConfigFile",
    "./Config/.htconfig.xml"
);

$ligacao;
$configDataBase;

function loadConfigurationDataBase($configFile) {
    global $configDataBase;

    if ( $configDataBase==NULL ) {
        $aux = simplexml_load_file( $configFile )
                or die( "Can't read data base configuration file ($configFile).");

        $configDataBase = $aux->DataBase[0];
    }
}

function dbConnect($configFile, $setCharSet = true) {
    global $configDataBase;
    global $ligacao;

    loadConfigurationDataBase( $configFile );

    $host = strval( $configDataBase->host );
    $port = intval( $configDataBase->port );
    $username = strval( $configDataBase->username );
    $password = strval( $configDataBase->password );

    $hostFQN = "$host:$port";

    $ligacao = mysqli_connect( $hostFQN, $username, $password )
            or die( "Could not connect to data base server ($hostFQN)" );

    if ( $setCharSet==true ) {
        mysqli_set_charset( $ligacao, 'utf8' );
    }
}

function dbDisconnect() {
    global $ligacao;

    mysqli_close($ligacao);
}

function dbGetLastError() {
    global $ligacao;
    
    $errorMsg = mysqli_error( $ligacao );
    $errorCode = mysqli_errno( $ligacao );

    return "[$errorCode] $errorMsg";
}
?>