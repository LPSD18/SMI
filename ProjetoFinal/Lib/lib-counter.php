<?php

require_once( "db.php" );

########################################################################
#   counter.php
#
#   Module to generate a PNG image intended to display the 
#   number of times that a page has been accessed.
#
#   Original code written by: Guy N. Hurst
#
#   Modified by Luis Sousa and Carlos Gon�alves to:
#     * Use PNG instead of GIF
#
#   Pre requisites:
#     * PHP
#     * GD
#     * PNG image(s) containing digits 0 through 9 equally spaced
########################################################################
# Images directory
define("imagePath", "../08-Images/images");

define("imageStrip", "digits.png");

function sendCounter($counterImage) {
    Header("Expires: Sat, 1 Jan 2000 00:00:01 GMT");
    Header("Content-type: image/png");
    ImagePNG($counterImage);
    ImageDestroy($counterImage);
}

function getCounterValueAndIncrement($counterId) {
    dbConnect(ConfigFile);
    mysqli_select_db($GLOBALS['ligacao'], $GLOBALS['configDataBase']->db);

    $queryIncrement = "UPDATE `images-counters` SET `counterValue`=`counterValue`+1 WHERE `id`='$counterId'";
    mysqli_query($GLOBALS['ligacao'], $queryIncrement);

    $queryGet = "SELECT `counterValue` FROM `images-counters` WHERE `id`='$counterId'";
    $resultGet = mysqli_query($GLOBALS['ligacao'], $queryGet);
    $counterData = mysqli_fetch_array($resultGet);
    mysqli_free_result($resultGet);

    dbDisconnect();

    return $counterData['counterValue'];
}

function buildCounterImage($counterValue) {
    # How many digits long is the counter? (will be at least 1)
    $numDigits = strlen("$counterValue");

    $imageStripPath = imagePath . "/" . imageStrip;

    # Load strip image from file
    $src = ImageCreateFromPNG($imageStripPath);

    $srcx = ImageSX($src);                              # src is the image strip
    $srcy = ImageSY($src);
    $cutx = $srcx / 10;                                 # assumes 10 evenly spaced digits in image strip
    $newx = $cutx * $numDigits;                         # determine width of image counter
    $newy = $srcy;
    $new = ImageCreateTrueColor($newx, $newy);          # new = image counter
    $alphaChannel = ImageColorAllocateAlpha($new, 0, 0, 0, 127); 
    imageColorTransparent( $new, $alphaChannel);
    ImageFill( $new, 0, 0, $alphaChannel);
    
    $i = 0;
    while ($i < $numDigits) {
        $c = substr("$counterValue", $i, 1);              # get next counter digit
        $nx = $i * $cutx;                                 # nx is x position in image counter to copy to
        $sx = $c * $cutx;                                 # sx is x position in GIF strip to copy from
        # segments copied at same size
        ImageCopyResized(
                $new, 
                $src, 
                $nx, 
                0, 
                $sx, 
                0, 
                $cutx, 
                $srcy, 
                $cutx, 
                $srcy);
        $i += 1;
    }
    ImageSaveAlpha($new, true);
    
    sendCounter( $new );
    exit;
}

$counterValue = getCounterValueAndIncrement( $_GET['counterId'] );

buildCounterImage($counterValue);
?>
