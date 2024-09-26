<?php

function sendAuthEmail(
    // The SMTP server name
    $smtpServer,
    // Use SSL
    $useSSL,
    // The port where the SMTP server is listening
    $port,
    // Timeout for the connection
    $timeout,
    // User name to be used in the authentication
    $loginName,
    // Password
    $password,
    // The e-mail of the sender
    $fromEmail,
    // The friendly name that corresponds to the name used in the authentication
    $fromName,
    // The to list
    $toList,
    // The cc list
    $ccList,
    // The bcc list
    $bccList,
    // The subject of the message
    $subject,
    // The e-mail message
    $message,
    // Is to show the SMTP messages?
    $showProtocol,
    // CA file
    $caFileName = NULL) {

    $toListAsArray = parseEmailList($toList);
    if ($ccList != NULL) {
        $ccListAsArray = parseEmailList($ccList);
    } else {
        $ccListAsArray = NULL;
    }
    if ($bccList != NULL) {
        $bccListAsArray = parseEmailList($bccList);
    } else {
        $bccListAsArray = NULL;
    }

    $newLine = "\r\n";

    if ($caFileName == NULL) {
        # Without validation of the certificates
        $contextOptions = array(
            'ssl' => array('verify_peer' => false)
        );
    } else {
        # With validation of the certificates
        $contextOptions = array(
            'ssl' => array(
                'verify_peer' => true,
                'cafile' => $caFileName, 'CN_match' => $smtpServer,)
        );
    }
    $logArray['contextOptions'] = $contextOptions;

    $context = stream_context_create($contextOptions);

    $flags = STREAM_CLIENT_CONNECT;

    if ($useSSL == 0) {
        $protocol = "";
    } else {
        $protocol = "ssl://";
    }

    // Connect to the SMTP Server on the specified port
    $location = $protocol . "$smtpServer:$port";

    $smtpConnect = stream_socket_client(
            $location, $errno, $errstr, $timeout, $flags, $context);
    $smtpResponseConnect = fgets($smtpConnect, 515);
    if (empty($smtpConnect)) {
        $logArray['status'] = "Failed to connect to $location ($errno, $errstr): $smtpResponseConnect";
        echo $logArray['status'];

        if ($showProtocol) {
            echo "<pre>\n";
            print_r($logArray);
            echo "<pre>\n";
        }

        return FALSE;
    }

    $aux = "Connected to $location. Response: $smtpResponseConnect";
    $logArray['connection'] = substr_replace($aux, "", -1);

    // Say HELO(EHLO) to SMTP
    $helloCommand = 'EHLO';

    $host = gethostname();
    $logArray['helo'] = "$helloCommand $host";
    fputs($smtpConnect, $logArray['helo'] . $newLine);

    // A ultima resposta é composta por 3 dígitos seguido de um espaço 
    $regExpLastResponse = "/^([0-9]{3}\s)/";

    // Saber se suporta TLS
    $regSupportsTLS = "/^([0-9]{3}\-STARTTLS)/";

    // Tipo de autenticação
    $regAuthType = "/^([0-9]{3}\-AUTH)/";

    $supportsTLS = FALSE;

    for ($id = 0;; ++$id) {
        $smtpResponseOptions = fgets($smtpConnect, 515);

        $logArray['heloResponse'][$id] = substr_replace($smtpResponseOptions, "", -1);

        if (preg_match($regAuthType, $smtpResponseOptions)) {
            $suportedAuthMethods = getAuthTypes($smtpResponseOptions);
        }

        if (preg_match($regSupportsTLS, $smtpResponseOptions)) {
            $supportsTLS = TRUE;
        }

        if (preg_match($regExpLastResponse, $smtpResponseOptions)) {
            break;
        }
    }

    if ($supportsTLS == TRUE) {
        $logArray['STARTTLS'] = "STARTTLS";
        fputs($smtpConnect, $logArray['STARTTLS'] . $newLine);
        $smtpResponseTLS = fgets($smtpConnect, 515);
        $logArray['STARTTLSresponse'] = $smtpResponseTLS;

        $fp = @stream_socket_enable_crypto($smtpConnect, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        if (!$fp) {
            $lastError = error_get_last();
            $logArray['enableCrypto'] = "Failed to enable crypto to $location: " . $lastError['message'];
            echo $logArray['enableCrypto'];

            if ($showProtocol) {
                echo "<pre>\n";
                print_r($logArray);
                echo "<pre>\n";
            }
            return FALSE;
        }

        $logArray['heloSTARTTLS'] = "$helloCommand $host";
        fputs($smtpConnect, $logArray['heloSTARTTLS'] . $newLine);

        for ($id = 0;; ++$id) {
            $smtpResponseOptions = fgets($smtpConnect, 515);

            $logArray['heloResponseSTARTTLS'][$id] = substr_replace($smtpResponseOptions, "", -1);

            if (preg_match($regAuthType, $smtpResponseOptions)) {
                $suportedAuthMethods = getAuthTypes($smtpResponseOptions);
            }

            if (preg_match($regExpLastResponse, $smtpResponseOptions)) {
                break;
            }
        }
    }

    if ($suportedAuthMethods['CRAM-MD5'] === TRUE) {
        $logArray['AUTH'] = "AUTH CRAM-MD5";
        fputs($smtpConnect, $logArray['AUTH'] . $newLine);
        $smtpResponseAuth = fgets($smtpConnect, 515);
        $logArray['authResponse '] = substr_replace($smtpResponseAuth, "", -1);

        $rawAuthResponse = explode(" ", $logArray['authResponse ']);
        $logArray['ticketEncode'] = $rawAuthResponse[1];
        $logArray['ticketDecode'] = base64_decode($rawAuthResponse[1]);

        $challenge = $logArray['ticketDecode'];

        $logArray['challenge'] = $challenge;

        $sharedSecret = hash_hmac("md5", $challenge, $password);

        $logArray['sharedSecret'] = $sharedSecret;
        $logArray['autentication'] = "$loginName $sharedSecret";
        $logArray['autenticationEncoded'] = base64_encode($logArray['autentication']);

        // Send autentication
        fputs($smtpConnect, $logArray['autenticationEncoded'] . $newLine);
        $smtpResponseAuthEncoded = fgets($smtpConnect, 515);
        $logArray['autenticationResponse'] = substr_replace($smtpResponseAuthEncoded, "", -1);
    } else {
        if ($suportedAuthMethods['LOGIN'] === TRUE) {
            $logArray['AUTH'] = "AUTH LOGIN";
            fputs($smtpConnect, $logArray['AUTH'] . $newLine);

            $smtpResponseAuth = fgets($smtpConnect, 515);
            $logArray['authResponse'] = substr_replace($smtpResponseAuth, "", -1);

            $rawAuthResponse = explode(" ", $logArray['authResponse']);
            $logArray['UsernamePromptEncoded'] = $rawAuthResponse[1];
            $logArray['UsernamePrompt'] = base64_decode($rawAuthResponse[1]);

            $logArray['Username'] = $loginName;
            $logArray['UsernameEncoded'] = base64_encode($loginName);
            fputs($smtpConnect, $logArray['UsernameEncoded'] . $newLine);

            $smtpResponseUsername = fgets($smtpConnect, 515);
            $logArray['usernameResponse'] = substr_replace($smtpResponseUsername, "", -1);

            $rawUserNameResponse = explode(" ", $logArray['usernameResponse']);
            $logArray['PasswordPromptEncoded'] = $rawUserNameResponse[1];
            $logArray['PasswordPrompt'] = base64_decode($rawUserNameResponse[1]);

            fputs($smtpConnect, base64_encode($password) . $newLine);
            $smtpResponsePassword = fgets($smtpConnect, 515);
            $logArray['passwordResponse'] = substr_replace($smtpResponsePassword, "", -1);
        } else {
            if ($suportedAuthMethods['PLAIN'] === TRUE) {
                // Fica como exercício
            } else {
                if ($showProtocol) {
                    echo "<pre>\n";
                    print_r($logArray);
                    echo "<pre>\n";
                }

                return FALSE;
            }
        }
    }

    //Email From
    fputs($smtpConnect, "MAIL FROM: $fromEmail" . $newLine);
    $smtpResponseFrom = fgets($smtpConnect, 515);

    $responseCode = substr($smtpResponseFrom, 0, 3);
    if ($responseCode == 555) {
        fputs($smtpConnect, "MAIL FROM: <$fromEmail>" . $newLine);
        $smtpResponseFrom = fgets($smtpConnect, 515);
    }

    $logArray['mailFromResponse'] = substr_replace($smtpResponseFrom, "", -1);

    // Email To
    $idx = 0;
    foreach ($toListAsArray as $contact) {
        $to = $contact['e-mail'];
        $mailTo = "RCPT TO: <$to>"; // Google likes this way

        fputs($smtpConnect, $mailTo . $newLine);
        $smtpResponseTo = fgets($smtpConnect, 515);
        $logArray['mailTo'][$idx]['request'] = $mailTo;
        $logArray['mailTo'][$idx]['response'] = substr_replace($smtpResponseTo, "", -1);
        ++$idx;
    }

    // E-mail Cc
    if ($ccListAsArray != NULL) {
        $idx = 0;
        foreach ($ccListAsArray as $contact) {
            $to = $contact['e-mail'];
            $mailTo = "RCPT TO: $to";
            fputs($smtpConnect, $mailTo . $newLine);
            $smtpResponseCC = fgets($smtpConnect, 515);
            $logArray['mailCc'][$idx]['request'] = $mailTo;
            $logArray['mailCc'][$idx]['response'] = substr_replace($smtpResponseCC, "", -1);
            ++$idx;
        }
    }

    // E-mail Bcc
    if ($bccListAsArray != NULL) {
        $idx = 0;
        foreach ($bccListAsArray as $contact) {
            $to = $contact['e-mail'];
            $mailTo = "RCPT TO: $to";
            fputs($smtpConnect, $mailTo . $newLine);
            $smtpResponseBCC = fgets($smtpConnect, 515);
            $logArray['mailBcc'][$idx]['request'] = $mailTo;
            $logArray['mailBcc'][$idx]['response'] = substr_replace($smtpResponseBCC, "", -1);
            ++$idx;
        }
    }

    //The Email
    fputs($smtpConnect, "DATA" . $newLine);
    $smtpResponseData = fgets($smtpConnect, 515);
    $logArray['data']['request'] = "DATA";
    $logArray['data']['response'] = substr_replace($smtpResponseData, "", -1);

    //Construct Headers
    // MIME and type
    $headers = "MIME-Version: 1.0" . $newLine;
    $headers .= "Content-Type: text/plain; charset=UTF-8" . $newLine;

    $fromAsArray['display'] = $fromName;
    $fromAsArray['e-mail'] = $fromEmail;
    $headers .= encodeHeaderEmailList('From', array($fromAsArray));

    $headers .= encodeHeaderEmailList('To', $toListAsArray);

    if ($ccList) {
        $headers .= encodeHeaderEmailList('Cc', $ccListAsArray);
    }

    if ($bccList) {
        $headers .= encodeHeaderEmailList('Bcc', $bccListAsArray);
    }

    $headers .= encodeHeaderEmailList('Reply-To', array($fromAsArray));

    $preferences = array(
        "input-charset" => "UTF-8",
        "output-charset" => "ISO-8859-1",
        "scheme" => "Q");

    // Subject
    $headers .= iconv_mime_encode("Subject", $subject, $preferences) . $newLine;

    // Message
    $headers .= $newLine . $message . $newLine;

    // Last line of email
    $headers .= "." . $newLine;

    fputs($smtpConnect, $headers);
    $smtpResponseHeaders = fgets($smtpConnect, 2048);
    $logArray['headers']['request'] = $headers;
    $logArray['headers']['response'] = substr_replace($smtpResponseHeaders, "", -1);

    // Say Bye to SMTP
    fputs($smtpConnect, "QUIT" . $newLine);
    $smtpResponseQuit = fgets($smtpConnect, 515);
    $logArray['quitresponse'] = substr_replace($smtpResponseQuit, "", -1);

    if ($showProtocol) {
        echo "<pre>\n";
        print_r($logArray);
        echo "<pre>\n";
    }

    return TRUE;
}

function parseEmailList($emailListRaw) {

    $aux = explode(";", $emailListRaw);

    foreach ($aux as $contact) {
        $pos = strpos($contact, "<");
        if ($pos === FALSE) {
            // only e-mail address
            $emailList[] = array(
                "e-mail" => $contact,
                "display" => $contact);
        } else {
            $emailList[] = array(
                "e-mail" => substr($contact, $pos + 1, strlen($contact) - ($pos + 2)),
                "display" => trim(substr($contact, 0, $pos - 1)));
        }
    }

    return $emailList;
}

function encodeHeaderEmailList($headerName, $emailList, $srcEncoding = 'UTF-8', $dstEncoding = 'ISO-8859-1') {

    $newLine = "\r\n";

    $encodeStart = "=?" . $dstEncoding . "?Q?";
    $encodeEnd = "?=";

    $isFirstTo = TRUE;
    $headers = "$headerName: ";

    foreach ($emailList as $email) {
        if ($isFirstTo == FALSE) {
            $headers .= ";";
        } else {
            $isFirstTo = FALSE;
        }
        $_emailDisplay = utf8_encode($email['display']);
        $_email = utf8_encode($email['e-mail']);

        //$headers .= $encodeStart . iconv($srcEncoding, $dstEncoding . "//IGNORE", $_emailDisplay) . $encodeEnd . " <" . $_email . ">";
        $headers .= $encodeStart . iconv($srcEncoding, $dstEncoding . "//TRANSLIT", $_emailDisplay) . $encodeEnd . " <" . $_email . ">";
        //$headers .= $encodeStart . iconv($srcEncoding, $dstEncoding . "", $_emailDisplay) . $encodeEnd . " <" . $_email . ">";
    }

    $headers .= $newLine;

    return $headers;
}

function encodeHeaderEmail($headerName, $emailName, $emailAddress, $srcEncoding = 'UTF-8', $dstEncoding = 'ISO-8859-1') {

    $newLine = "\r\n";

    $encodeStart = "=?" . $dstEncoding . "?Q?";
    $encodeEnd = "?=";
    
    $_emailName = utf8_encode( $emailName );
    $_emailAddress = utf8_encode( $emailAddress );

    $headers = "$headerName: ";    
    $headers .= $encodeStart . iconv($srcEncoding, $dstEncoding . "//TRANSLIT", $_emailName) . $encodeEnd . " <" . $_emailAddress . ">";
    $headers .= $newLine;
    
    return $headers;
}

function encodeHeader($headerName, $text, $srcEncoding = 'UTF-8', $dstEncoding = 'ISO-8859-1') {

    $newLine = "\r\n";

    $encodeStart = "=?" . $dstEncoding . "?Q?";
    $encodeEnd = "?=";

    $headers = "$headerName: ";

    $headers .= $encodeStart . iconv($srcEncoding, $dstEncoding . "//IGNORE", $text) . $encodeEnd;
    $headers .= $newLine;

    return $headers;
}

function getAuthTypes($authProtocolsRaw) {
    $authProtocols = trim($authProtocolsRaw, " \n\r");

    $authTypesRaw = substr($authProtocols, strpos($authProtocols, " ") + 1);

    $authTypes = explode(" ", $authTypesRaw);

    $result = array();

    $result['PLAIN'] = FALSE;
    $result['LOGIN'] = FALSE;
    $result['CRAM-MD5'] = FALSE;

    foreach ($authTypes as $currentAuthType) {
        switch ($currentAuthType) {
            case 'PLAIN':
                $result['PLAIN'] = TRUE;
            case 'LOGIN':
                $result['LOGIN'] = TRUE;
                break;
            case 'CRAM-MD5':
                $result['CRAM-MD5'] = TRUE;
                break;
        }
    }

    return $result;
}

?>