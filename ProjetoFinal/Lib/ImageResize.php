<?PHP

class ImageResize {

    var $image;
    var $width;
    var $height;
    var $imageResized;

    private function openImage($imageFile) {

        $pathInfo = pathinfo($imageFile);
        $extension = strtolower($pathInfo['extension']);
        
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $img = imagecreatefromjpeg( $imageFile );
                break;
            case 'gif':
                $img = imagecreatefromgif( $imageFile );
                break;
            case 'png':
                $img = imagecreatefrompng( $imageFile );
                break;
            default:
                $img = false;
                break;
        }

        return $img;
    }

    private function getSizeByFixedHeight($newHeight) {
        $ratio = $this->width / $this->height;
        $newWidth = $newHeight * $ratio;

        return $newWidth;
    }

    private function getSizeByFixedWidth($newWidth) {
        $ratio = $this->height / $this->width;
        $newHeight = $newWidth * $ratio;

        return $newHeight;
    }

    private function getSizeByAuto($newWidth, $newHeight) {
        if ($this->height < $this->width) {
            // Image to be resized is wider (landscape)
            $optimalWidth = $newWidth;
            $optimalHeight = $this->getSizeByFixedWidth($newWidth);
        } elseif ($this->height > $this->width) {
            // Image to be resized is taller (portrait)
            $optimalWidth = $this->getSizeByFixedHeight($newHeight);
            $optimalHeight = $newHeight;
        } else {
            // Image to be resizerd is a square
            if ($newHeight < $newWidth) {
                $optimalWidth = $newWidth;
                $optimalHeight = $this->getSizeByFixedWidth($newWidth);
            } else if ($newHeight > $newWidth) {
                $optimalWidth = $this->getSizeByFixedHeight($newHeight);
                $optimalHeight = $newHeight;
            } else {
                // Sqaure being resized to a square
                $optimalWidth = $newWidth;
                $optimalHeight = $newHeight;
            }
        }

        return array('optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight);
    }

    private function getOptimalCrop($newWidth, $newHeight) {

        $heightRatio = $this->height / $newHeight;
        $widthRatio = $this->width / $newWidth;

        if ($heightRatio < $widthRatio) {
            $optimalRatio = $heightRatio;
        } else {
            $optimalRatio = $widthRatio;
        }

        $optimalHeight = $this->height / $optimalRatio;
        $optimalWidth = $this->width / $optimalRatio;

        return array('optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight);
    }

    private function getDimensions($newWidth, $newHeight, $option) {
        switch ($option) {
            case 'exact':
                $optimalWidth = $newWidth;      
                $optimalHeight = $newHeight;
                break;
            case 'portrait':
                $optimalWidth = $this->getSizeByFixedHeight($newHeight);
                $optimalHeight = $newHeight;
                break;
            case 'landscape':
                $optimalWidth = $newWidth;
                $optimalHeight = $this->getSizeByFixedWidth($newWidth);
                break;
            case 'auto':
                $optionArray = $this->getSizeByAuto($newWidth, $newHeight);
                $optimalWidth = $optionArray['optimalWidth'];
                $optimalHeight = $optionArray['optimalHeight'];
                break;
            case 'crop':
                $optionArray = $this->getOptimalCrop($newWidth, $newHeight);
                $optimalWidth = $optionArray['optimalWidth'];
                $optimalHeight = $optionArray['optimalHeight'];
                break;
        }

        return array('optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight);
    }

    private function crop($optimalWidth, $optimalHeight, $newWidth, $newHeight) {
        // Find center - this will be used for the crop
        $cropStartX = ( $optimalWidth / 2) - ( $newWidth / 2 );
        $cropStartY = ( $optimalHeight / 2) - ( $newHeight / 2 );

        $crop = $this->imageResized;

        // Now crop from center to exact requested size
        $this->imageResized = imagecreatetruecolor( $newWidth, $newHeight );
        imagecopyresampled(
                $this->imageResized, 
                $crop, 
                0, 0, 
                intval( $cropStartX ), intval( $cropStartY ), intval( $newWidth ), intval( $newHeight ), intval( $newWidth ), intval( $newHeight ) );
    }

    public function __construct($fileName) {

        if ( $fileName != null ) {
            // Open up the file
            $this->image = $this->openImage($fileName);

            // Get width and height
            $this->width = imagesx($this->image);
            $this->height = imagesy($this->image);
        }
    }

    public function setImage($imageToSet) {
        $this->image = $imageToSet;

        // Get width and height
        $this->width = imagesx($this->image);
        $this->height = imagesy($this->image);
    }

    public function getWidth() {
        return $this->width;
    }

    public function getHeight() {
        return $this->height;
    }

    public function close() {
        imagedestroy($this->image);
    }

    public function resizeImage($newWidth, $newHeight, $option = "auto") {
        // Get optimal width and height - based on $option
        $optionArray = $this->getDimensions($newWidth, $newHeight, strtolower($option));

        $optimalWidth = $optionArray['optimalWidth'];
        $optimalHeight = $optionArray['optimalHeight'];

        // Resample - create image canvas of x, y size
        $this->imageResized = imagecreatetruecolor( intval($optimalWidth), intval($optimalHeight) );
        imagecopyresampled(
                $this->imageResized, 
                $this->image, 
                0, 0, 0, 0, 
                intval($optimalWidth), intval($optimalHeight), $this->width, $this->height );

        // if option is 'crop', then crop too
        if ($option == 'crop') {
            $this->crop(
                    intval($optimalWidth), intval($optimalHeight), intval($newWidth), intval($newHeight) );
        }
    }

    public function saveImage($savePath, $extension, $imageQuality) {
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                if (imagetypes() & IMG_JPG) {   imagejpeg($this->imageResized, $savePath, $imageQuality); }
                break;
            case 'gif':
                if (imagetypes() & IMG_GIF) {   imagegif($this->imageResized, $savePath); }
                break;
            case 'png':
                $scaleQuality = round(($imageQuality / 100) * 9);   // Scale quality from 0-100 to 0-9
                $invertScaleQuality = 9 - $scaleQuality;            // Invert quality setting as 0 is best, not 9
                if (imagetypes() & IMG_PNG) { imagepng($this->imageResized, $savePath, $invertScaleQuality); }
                break;
            default:
                break;
        }
        imagedestroy($this->imageResized);
    }
}

?>
