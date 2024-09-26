<?php

/* * ************************************ 
 * Title.........: HTML Mime Mail class 
 * Version.......: 1.1 
 * Author........: Richard Heyes <richard.heyes@heyes-computing.net> 
 * Filename......: HtmlMimeMail-class.php 
 * Last changed..: 15/4/2000 
 * Notes.........: Based upon mime_mail.class 
 *                 by Tobias Ratschiller <tobias@dnet.it> 
 *                 and Sascha Schumann <sascha@schumann.cx>. 
 *                 Thanks to Thomas Flemming for supplying a fix 
 *                 for Win32. 
 * ************************************* */

class HtmlMimeMail {

    var $headers;
    var $body;
    var $multipart;
    var $mime;
    var $html;
    var $html_text;
    var $html_images = array();
    var $cids = array();
    var $do_html;
    var $parts = array();

    /*     * ************************************ 
     * Constructor function. Sets the headers 
     * if supplied. 
     * ************************************ */

    function __construct($headers = '') {
        $this->headers = $headers;
    }

    /*     * ************************************* 
     * Adds a html part to the mail. 
     * Also replaces image names with 
     * content-id's. 
     * ************************************ */

    function add_html($html, $text) {
        $this->do_html = 1;
        $this->html = $html;
        $this->html_text = $text;
        if (is_array($this->html_images) AND count($this->html_images) > 0) {
            for ($i = 0; $i < count($this->html_images); $i++) {
                $this->html = @preg_replace($this->html_images[$i]['name'], 'cid:' . $this->html_images[$i]['cid'], $this->html);
            }
        }
    }

    /*     * ************************************* 
     * Builds html part of email. 
     * ************************************* */

    function build_html($orig_boundary) {
        $sec_boundary = '=_' . md5(uniqid(time()));
        $thr_boundary = '=_' . md5(uniqid(time()));

        if (!is_array($this->html_images)) {
            $this->multipart .= '--' . $orig_boundary . "\n";
            $this->multipart .= 'Content-Type: multipart/alternative; boundary = "' . $sec_boundary . "\"\n\n\n";

            $this->multipart .= '--' . $sec_boundary . "\n";
            $this->multipart .= 'Content-Type: text/plain' . "\n";
            $this->multipart .= 'Content-Transfer-Encoding: 7bit' . "\n\n";
            $this->multipart .= $this->html_text . "\n\n";

            $this->multipart .= '--' . $sec_boundary . "\n";
            $this->multipart .= 'Content-Type: text/html' . "\n";
            $this->multipart .= 'Content-Transfer-Encoding: 7bit' . "\n\n";
            $this->multipart .= $this->html . "\n\n";
            $this->multipart .= '--' . $sec_boundary . "--\n\n";
        } else {
            $this->multipart .= '--' . $orig_boundary . "\n";
            $this->multipart .= 'Content-Type: multipart/related; boundary = "' . $sec_boundary . "\"\n\n\n";

            $this->multipart .= '--' . $sec_boundary . "\n";
            $this->multipart .= 'Content-Type: multipart/alternative; boundary = "' . $thr_boundary . "\"\n\n\n";

            $this->multipart .= '--' . $thr_boundary . "\n";
            $this->multipart .= 'Content-Type: text/plain' . "\n";
            $this->multipart .= 'Content-Transfer-Encoding: 7bit' . "\n\n";
            $this->multipart .= $this->html_text . "\n\n";

            $this->multipart .= '--' . $thr_boundary . "\n";
            $this->multipart .= 'Content-Type: text/html' . "\n";
            $this->multipart .= 'Content-Transfer-Encoding: 7bit' . "\n\n";
            $this->multipart .= $this->html . "\n\n";
            $this->multipart .= '--' . $thr_boundary . "--\n\n";

            for ($i = 0; $i < count($this->html_images); $i++) {
                $this->multipart .= '--' . $sec_boundary . "\n";
                $this->build_html_image($i);
            }

            $this->multipart .= "--" . $sec_boundary . "--\n\n";
        }
    }

    /*     * ************************************ 
     * Adds an image to the list of embedded 
     * images. 
     * ************************************ */

    function add_html_image($file, $name = '', $c_type = 'application/octet-stream') {
        $this->html_images[] = array('body' => $file,
            'name' => $name,
            'c_type' => $c_type,
            'cid' => md5(uniqid(time())));
    }

    /*     * ************************************ 
     * Adds a file to the list of attachments. 
     * ************************************ */

    function add_attachment($file, $name = '', $c_type = 'application/octet-stream') {
        $this->parts[] = array('body' => $file,
            'name' => $name,
            'c_type' => $c_type);
    }

    /*     * ************************************ 
     * Builds an embedded image part of an 
     * html mail. 
     * ************************************ */

    function build_html_image($i) {
        $this->multipart .= 'Content-Type: ' . $this->html_images[$i]['c_type'];

        if ($this->html_images[$i]['name'] != '')
            $this->multipart .= '; name = "' . $this->html_images[$i]['name'] . "\"\n";
        else
            $this->multipart .= "\n";

        $this->multipart .= 'Content-ID: <' . $this->html_images[$i]['cid'] . ">\n";
        $this->multipart .= 'Content-Transfer-Encoding: base64' . "\n\n";
        $this->multipart .= chunk_split(base64_encode($this->html_images[$i]['body'])) . "\n";
    }

    /*     * ************************************ 
     * Builds a single part of a multipart 
     * message. 
     * ************************************ */

    function build_part($i) {
        $message_part = '';
        $message_part .= 'Content-Type: ' . $this->parts[$i]['c_type'];
        if ($this->parts[$i]['name'] != '')
            $message_part .= '; name = "' . $this->parts[$i]['name'] . "\"\n";
        else
            $message_part .= "\n";

        // Determine content encoding. 
        if ($this->parts[$i]['c_type'] == 'text/plain') {
            $message_part .= 'Content-Transfer-Encoding: 7bit' . "\n\n";
            $message_part .= $this->parts[$i]['body'] . "\n";
        } else {
            $message_part .= 'Content-Transfer-Encoding: base64' . "\n";
            $message_part .= 'Content-Disposition: attachment; filename = "' . $this->parts[$i]['name'] . "\"\n\n";
            $message_part .= chunk_split(base64_encode($this->parts[$i]['body'])) . "\n";
        }

        return $message_part;
    }

    /*     * ************************************ 
     * Builds the multipart message from the 
     * list ($this->parts). 
     * ************************************ */

    function build_message() {
        $boundary = '=_' . md5(uniqid(time()));

        $this->headers .= "MIME-Version: 1.0\n";
        $this->headers .= "Content-Type: multipart/mixed; boundary = \"" . $boundary . "\"\n";
        $this->multipart = '';
        $this->multipart .= "This is a MIME encoded message.\nCreated by html_mime_mail.class.\nSee http://www.heyes-computing.net/red.software/ for a copy.\n\n";

        if (isset($this->do_html) AND $this->do_html == 1)
            $this->build_html($boundary);
        if (isset($this->body) AND $this->body != '')
            $this->parts[] = array('body' => $this->body, 'name' => '', 'c_type' => 'text/plain');

        for ($i = (count($this->parts) - 1); $i >= 0; $i--) {
            $this->multipart .= '--' . $boundary . "\n" . $this->build_part($i);
        }

        $this->mime = $this->multipart . "--" . $boundary . "--\n";
    }

    /*     * ************************************* 
     * Sends the mail. 
     * ************************************* */

    function send(
    $smtpServer, $useSSL, $smtpPort, $loginName, $password, $toName, $toAddress, $fromName, $fromAddress, $subject = '', $adicionalHeaders = '') {

        $newLine = "\r\n";

        /*
          $contextOptions = array( 'ssl' => array(
          'verify_peer' => true,
          //'cafile' => "C:\\xampp\\certs\\cacert.pem",
          'cafile' => "C:\\xampp\\certs\\MailShield.pem",
          'CN_match' => $smtpServer, )
          );
         */
        $contextOptions = array('ssl' => array('verify_peer' => false));

        $context = stream_context_create($contextOptions);

        $flags = STREAM_CLIENT_CONNECT;

        if ($useSSL == 0) {
            $protocol = "";
        } else {
            $protocol = "ssl://";
        }

        // Connect to the SMTP Server on the specified port
        $location = $protocol . "$smtpServer:$smtpPort";

        $smtpConnect = stream_socket_client($location, $errno, $errstr, 30, $flags, $context);
        fgets($smtpConnect, 515);

        $host = gethostname();
        fputs($smtpConnect, "EHLO $host" . $newLine);

        // A ultima resposta é composta por 3 dígitos seguido de um espaço 
        $regExpLastResponse = "/^([0-9]{3}\s)/";

        // Saber se suporta TLS
        $regSupportsTLS = "/^([0-9]{3}\-STARTTLS)/";

        // Tipo de autenticação
        $regAuthType = "/^([0-9]{3}\-AUTH)/";

        $supportsTLS = FALSE;

        for ($id = 0;; ++$id) {
            $smtpResponseOptions = fgets($smtpConnect, 515);

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
            fputs($smtpConnect, "STARTTLS" . $newLine);
            $dummy = fgets($smtpConnect, 515);

            stream_socket_enable_crypto($smtpConnect, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

            fputs($smtpConnect, "EHLO $host" . $newLine);

            for ($id = 0;; ++$id) {
                $smtpResponseOptions = fgets($smtpConnect, 515);

                if (preg_match($regAuthType, $smtpResponseOptions)) {
                    $suportedAuthMethods = getAuthTypes($smtpResponseOptions);
                }

                if (preg_match($regExpLastResponse, $smtpResponseOptions)) {
                    break;
                }
            }
        }

        if ($suportedAuthMethods['CRAM-MD5'] === TRUE) {
            fputs($smtpConnect, "AUTH CRAM-MD5" . $newLine);
            $smtpResponseAuthRaw = fgets($smtpConnect, 515);
            $smtpResponseAuth = substr_replace($smtpResponseAuthRaw, "", -1);

            $rawAuthResponse = explode(" ", $smtpResponseAuth);
            $ticketDecode = base64_decode($rawAuthResponse[1]);

            $challenge = $ticketDecode;

            $sharedSecret = hash_hmac("md5", $challenge, $password);

            $autenticationEncoded = base64_encode("$loginName $sharedSecret");

            // Send autentication
            fputs($smtpConnect, $autenticationEncoded . $newLine);
            $dummy = fgets($smtpConnect, 515);
        } else {
            if ($suportedAuthMethods['LOGIN'] === TRUE) {
                fputs($smtpConnect, "AUTH LOGIN" . $newLine);

                $smtpResponseAuth = fgets($smtpConnect, 515);

                fputs($smtpConnect, base64_encode($loginName) . $newLine);

                $dummy = fgets($smtpConnect, 515);

                fputs($smtpConnect, base64_encode($password) . $newLine);

                $dummy = fgets($smtpConnect, 515);
            } else {
                if ($suportedAuthMethods['PLAIN'] === TRUE) {
                    // Fica como exercício
                    return FALSE;
                } else {
                    return FALSE;
                }
            }
        }

        //Email From
        fputs($smtpConnect, "MAIL FROM: $fromAddress" . $newLine);
        $dummy = fgets($smtpConnect, 515);

        $responseCode = substr($dummy, 0, 3);
        if ($responseCode == 555) {
            fputs($smtpConnect, "MAIL FROM: <$fromAddress>" . $newLine);
            $dummy = fgets($smtpConnect, 515);
        }

        // Google likes this way
        $mailTo = "RCPT TO: <$toAddress>";
        fputs($smtpConnect, $mailTo . $newLine);
        $dummy = fgets($smtpConnect, 515);

        fputs($smtpConnect, "DATA" . $newLine);
        $dummy = fgets($smtpConnect, 515);

        $fromAsArray['display'] = $fromName;
        $fromAsArray['e-mail'] = $fromAddress;
        $fromHeader = encodeHeaderEmailList('From', array($fromAsArray));

        $toAsArray['display'] = $toName;
        $toAsArray['e-mail'] = $toAddress;
        $toHeader = encodeHeaderEmailList('To', array($toAsArray));

        $subjectHeader = encodeHeader('Subject', $subject);

        $this->headers .= $fromHeader;
        $this->headers .= $toHeader;
        $this->headers .= $subjectHeader;
        $this->headers .= $adicionalHeaders . $newLine;
        $this->headers .= $this->mime . $newLine;
        $this->headers .= "." . $newLine;

        fputs($smtpConnect, $this->headers);
        $dummy = fgets($smtpConnect, 4096);

        fputs($smtpConnect, "QUIT" . $newLine);
        $dummy = fgets($smtpConnect, 515);

        fclose($smtpConnect);

        return TRUE;
    }

}

// End of class. 
?>
