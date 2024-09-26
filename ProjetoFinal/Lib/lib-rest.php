<?php

// Based on http://rest.elkstein.org/2008/02/using-rest-in-php.html

function httpRequest(
$hostName, $ssl, $portNumber, $path, $method, $contenType, $auth, $additionalHeaders, $params, $encodeParams, $fileName, $debug = FALSE) {

    $newLine = "\r\n";

    $request = array();

    $method = strtoupper($method);



    if ($params !== NULL) {
        if ($encodeParams === TRUE) {
            $isFirst = FALSE;

            // Params are a map from names to values
            $paramStr = "";

            foreach ($params as $paramRaw) {
                if ($isFirst == TRUE) {
                    $isFirst = FALSE;
                } else {
                    $paramStr .= "&";
                }
                $param = explode("=", $paramRaw);

                $name = $param[0];
                $val = $param[1];

                $paramStr .= $name . "=";
                $paramStr .= urlencode($val);
            }
        } else {
            $paramStr = "";
            foreach ($params as $param) {
                if (strlen($param) > 0) {
                    $paramStr .= $param . "\n";
                }
            }
        }
    }

    if ($debug === FALSE) {
        // Create the connection
        if ($ssl === TRUE) {
            $sock = fsockopen("ssl://" . $hostName, $portNumber);
        } else {
            $sock = fsockopen($hostName, $portNumber);
        }
    }

    if ($method == "GET") {
        if ($params != NULL && strlen($paramStr) > 0) {
            $path .= "?" . $paramStr;
        }
    }

    // Send request
    if ($ssl === TRUE) {
        $protocol = "https";
        if ($portNumber == 443) {
            $port = "";
        } else {
            $port = ":" . $portNumber;
        }
    } else {
        $protocol = "http";
        if ($portNumber == 80) {
            $port = "";
        } else {
            $port = ":" . $portNumber;
        }
    }

    $request[] = "$method $protocol://$hostName$port$path HTTP/1.1";
    if ($debug === FALSE) {
        fputs($sock, $request[count($request) - 1] . $newLine);
    }

    $request[] = "Host: $hostName";
    if ($debug === FALSE) {
        fputs($sock, $request[count($request) - 1] . $newLine);
    }

    if ($params !== NULL) {
        if (strlen($paramStr) > 0) {
            $request[] = "Content-type: $contenType";
            if ($debug === FALSE) {
                fputs($sock, $request[count($request) - 1] . $newLine);
            }

            if ($method == "POST") {
                $request[] = "Content-length: " . strlen($paramStr);
                if ($debug === FALSE) {
                    fputs($sock, $request[count($request) - 1] . $newLine);
                }
            }
        }
    }

    if ($auth !== NULL) {
        if ($debug === FALSE) {
            $request[] = $auth;
            fputs($sock, $request[count($request) - 1] . $newLine);
        }
    }

    foreach ($additionalHeaders as $additionalHeader) {
        if (strlen($additionalHeader) > 0) {
            $request[] = $additionalHeader;
            if ($debug === FALSE) {
                fputs($sock, $request[count($request) - 1] . $newLine);
            }
        }
    }

    // End request
    $request[] = "Connection: close";
    if ($debug === FALSE) {
        fputs($sock, $request[count($request) - 1] . $newLine);
    }

    // Header is done, send arguments if necessary
    if ($debug === FALSE) {
        fputs($sock, $newLine);
    }
    $request[] = $newLine;

    if ($method == "POST") {
        if ($params !== NULL) {
            $line = $paramStr;
            $request[] = $line;
            if ($debug === FALSE) {
                fputs($sock, $line);
            }
        }
    }

    $regContentLength = "/^(Content\-Length)/";
    $regContentType = "/^(Content\-Type)/";

    // Read response
    $responseHeader = array();

    $nbr = -1;

    $responseContentType = "";

    if ($debug === FALSE) {
        while (!feof($sock)) {
            $aux = fgets($sock, 4096);

            $responseHeader[] = trim($aux, $newLine);

            if (preg_match($regContentType, $aux)) {
                $responseContentType = trim(explode(":", $aux)[1]);
            }

            if (preg_match($regContentLength, $aux)) {
                //$len = trim(explode( ":", $aux )[1]);
                // Read the empty line
                $aux = fgets($sock, 1024 * 8);

                // Read the response body
                $hd = fopen($fileName, "wb");

                while (!feof($sock)) {
                    $aux = fread($sock, 1024 * 8);

                    $nbr += strlen($aux);

                    fwrite($hd, $aux);
                }
                fflush($hd);
                fclose($hd);
                break;
            }
        }
    }

    if ($debug === FALSE) {
        // Close connection
        fclose($sock);
    }

    // return response
    return array(
        "Request" => $request,
        "ResponseContentType" => $responseContentType,
        "ResponseHeader" => $responseHeader,
        "ResponseRealSize" => $nbr);
}

function showArray($array, $convert = FALSE) {
    $find = array("&", "<", ">", "\n");
    $replace = array('&amp;', "&lt;", "&gt;", "\n<br>");

    foreach ($array as $arrayElement) {
        if ($convert === TRUE) {
            $elem = str_replace($find, $replace, $arrayElement);
        } else {
            $elem = $arrayElement;
        }

        echo "$elem\n<br>";
    }
}

?>
