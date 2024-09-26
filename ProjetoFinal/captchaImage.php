<?php
	//include_once( "config.php" );
	//include_once( "configDebug.php" );

	if ( !isset( $_SESSION ) ) {
		session_start();
	}

	
	$captchaValue = @substr( md5( time() ), 0, 9);
	
	$_SESSION['captcha'] = $captchaValue;

	$imageCaptcha = ImageCreateFromPNG( "images/fundocaptch.png" );

	$colorCaptchaRed = ImageColorAllocate($imageCaptcha, 255, 0, 0);
	$colorCaptchaBlue = ImageColorAllocate($imageCaptcha, 0, 0, 255);

	$colorCaptcha = imagecolorallocate($imageCaptcha, 255, 0, 0);

	$fontName = "DejaVuSans-Bold.ttf";

	$fontCaptcha = $fontsDirectory . $fontName;
    
	for( $idx = 0; $idx<9; ++$idx) {
		$code = substr($captchaValue, $idx, 1 );
    
		$angle = ( $idx % 2 == 0 ? -15 : 15);
		$offsetY = ( $idx % 2 == 0 ? -10 : +10);
    
		$res = @ImageTTFText(
			$imageCaptcha,      // Image
			20,                 // Font size
			$angle,             // Font angle
			5 + $idx*25,        // X position
			30 + $offsetY,      // Y position
			$colorCaptcha,      // Font color
			$fontCaptcha,       // Font type
			$code               // Text to write
		);

		if ( $res == false ) {
			$fontCaptcha = 4;
			@imagestring( 
				$imageCaptcha,  
				$fontCaptcha,  
				15,  
				15,  
				$captchaValue, 
				$colorCaptcha);
			break;
		}

	}
  
	header("Content-type: image/png");

	imagepng($imageCaptcha);

	imagedestroy($imageCaptcha);
?>