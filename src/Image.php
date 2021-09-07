<?php

namespace DantSu\PHPImageEditor;

/**
 * DantSu\PHPImageEditor\Image is PHP library to easily edit image with GD extension. Resize, crop, merge, draw, and many more options !
 *
 * @package DantSu\PHPImageEditor
 * @author Franck Alary
 * @access public
 * @see https://github.com/DantSu/php-image-editor
 */
class Image
{
    const ALIGN_LEFT = 'left';
    const ALIGN_CENTER = 'center';
    const ALIGN_RIGHT = 'right';
    const ALIGN_TOP = 'top';
    const ALIGN_MIDDLE = 'middle';
    const ALIGN_BOTTOM = 'bottom';


    private $image;
    private $type;
    private $width;
    private $height;


    /**
     * Return the image width
     *
     * @return int
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * Return the image height
     *
     * @return int
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * Return the image type
     * Image type : 1 GIF; 2 JPG; 3 PNG
     *
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * Return image resource
     * @return resource|\GdImage
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Return true if image is initialized
     *
     * @return bool
     */
    public function isImageDefined(): bool
    {
        return \is_resource($this->image) || (\is_object($this->image) && $this->image instanceof \GdImage);
    }

    //===============================================================================================================================
    //============================================================CREATE/DESTROY=====================================================
    //===============================================================================================================================

    /**
     * (Static method) Create a new image with transparent background
     *
     * @param int $width
     * @param int $height
     * @return Image
     */
    public static function create(int $width, int $height): Image
    {
        return (new Image)->createNew($width, $height);
    }

    /**
     * Create a new image with transparent background
     *
     * @param int $width
     * @param int $height
     * @return $this
     */
    public function createNew(int $width, int $height): Image
    {
        if (($this->image = \imagecreatetruecolor($width, $height)) === false) {
            $this->resetFields();
            return $this;
        }

        \imagealphablending($this->image, false);
        \imagesavealpha($this->image, true);
        \imagecolortransparent($this->image, \imagecolorallocate($this->image, 0, 0, 0));

        $this->width = $width;
        $this->height = $height;
        return $this;
    }

    /**
     * (Static method) Open image from local path or URL.
     *
     * @param string $path
     * @return Image
     */
    public static function fromPath(string $path): Image
    {
        return (new Image)->path($path);
    }

    /**
     * Open image from local path or URL.
     *
     * @param string $path
     * @return $this
     */
    public function path(string $path): Image
    {
        $imageSize = \getimagesize($path);

        if ($imageSize === false) {
            return $this;
        }

        list($this->width, $this->height, $this->type, $attr) = $imageSize;

        switch ($this->type) {
            case 1:
                $this->image = \imagecreatefromgif($path);
                break;
            case 2:
                $this->image = \imagecreatefromjpeg($path);
                break;
            case 3:
                $this->image = \imagecreatefrompng($path);
                break;
        }

        if ($this->image === false) {
            return $this->resetFields();
        }

        if (!\imageistruecolor($this->image)) {
            \imagepalettetotruecolor($this->image);
        }

        \imagealphablending($this->image, false);
        \imagesavealpha($this->image, true);

        return $this;
    }

    /**
     * (Static method) Open an uploaded image from html form (using $file["tmp_name"]).
     *
     * @param array $file
     * @return Image
     */
    public static function fromForm(array $file): Image
    {
        return (new Image)->form($file);
    }

    /**
     * Open an uploaded image from html form (using $file["tmp_name"]).
     *
     * @param array $file
     * @return $this
     */
    public function form(array $file): Image
    {
        if (isset($file) && isset($file["name"]) && $file["name"] != "") {
            $this->fromPath($file["tmp_name"]);
        }
        return $this;
    }

    /**
     * (Static method) Create an Image instance from image raw data.
     *
     * @param string $data
     * @return Image
     */
    public static function fromData(string $data): Image
    {
        return (new Image)->data($data);
    }

    /**
     * Create an Image instance from image raw data.
     *
     * @param string $data
     * @return $this
     */
    public function data(string $data): Image
    {
        if (($this->image = \imagecreatefromstring($data)) === false) {
            return $this->resetFields();
        }

        $this->width = \imagesx($this->image);
        $this->height = \imagesy($this->image);
        $this->type = 3;

        if (!\imageistruecolor($this->image)) {
            \imagepalettetotruecolor($this->image);
        }

        \imagealphablending($this->image, false);
        \imagesavealpha($this->image, true);

        return $this;
    }

    /**
     * (Static method) Create an Image instance from base64 image data.
     *
     * @param string $base64
     * @return Image
     */
    public static function fromBase64(string $base64): Image
    {
        return (new Image)->base64($base64);
    }

    /**
     * Create an Image instance from base64 image data.
     *
     * @param string $base64
     * @return $this
     */
    public function base64(string $base64): Image
    {
        return $this->fromData(\base64_decode($base64));
    }

    /**
     * (Static method) Open image from URL with cURL.
     *
     * @param string $url
     * @return Image
     */
    public static function fromCurl(string $url): Image
    {
        return (new Image)->curl($url);
    }

    /**
     * Open image from URL with cURL.
     *
     * @param string $url
     * @return $this
     */
    public function curl(string $url): Image
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:91.0) Gecko/20100101 Firefox/91.0');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        $image = curl_exec($curl);
        curl_close($curl);

        if ($image === false) {
            return $this->resetFields();
        }

        return $this->fromData($image);
    }


    /**
     * Destroy image
     *
     * @return $this
     */
    public function destroy(): Image
    {
        if ($this->isImageDefined()) {
            \imagedestroy($this->image);
        }
        $this->resetFields();
        return $this;
    }

    /**
     * Reset private fields
     *
     * @return $this
     */
    private function resetFields(): Image
    {
        $this->image = null;
        $this->type = null;
        $this->width = null;
        $this->height = null;
        return $this;
    }




    //===============================================================================================================================
    //============================================================UTILS==============================================================
    //===============================================================================================================================

    /**
     * Convert X position from `Image::ALIGN_...` to int position.
     *
     * @param int|string $posX
     * @param int $width
     * @return int
     */
    private function convertPosX($posX, int $width = 0): int
    {
        switch ($posX) {
            case static::ALIGN_LEFT:
                return 0;
            case static::ALIGN_CENTER:
                return \round($this->width / 2 - $width / 2);
            case static::ALIGN_RIGHT:
                return $this->width - $width;
        }
        return $posX;
    }

    /**
     * Convert Y position from `Image::ALIGN_...` to int position.
     *
     * @param int|string $posY
     * @param int $height
     * @return int
     */
    private function convertPosY($posY, int $height = 0): int
    {
        switch ($posY) {
            case static::ALIGN_TOP:
                return 0;
            case static::ALIGN_MIDDLE:
                return \round($this->height / 2 - $height / 2);
            case static::ALIGN_BOTTOM:
                return $this->height - $height;
        }
        return $posY;
    }

    //===============================================================================================================================
    //=================================================RESIZING/ROTATE/TRUNCATE======================================================
    //===============================================================================================================================


    /**
     * Rotate the image
     *
     * @param int $angle
     * @return $this
     */
    public function rotate(int $angle): Image
    {
        if (!$this->isImageDefined()) {
            return $this;
        }

        if (($image = \imagerotate($this->image, Geometry2D::degrees0to360($angle), $this->colorAllocate('#000000FF'), 0)) !== false) {
            $this->image = $image;
            $this->width = \imagesx($this->image);
            $this->height = \imagesy($this->image);
        }
        return $this;
    }

    /**
     * Resize the image keeping the proportions.
     *
     * @param int $width
     * @param int $height
     * @return $this
     */
    public function resizeProportion(int $width, int $height): Image
    {
        $finalWidth = $width;
        $finalHeight = \round($this->height * $width / $this->width);

        if ($finalHeight > $height) {
            $finalWidth = \round($this->width * $height / $this->height);
            $finalHeight = $height;
        }

        return $this->resize($finalWidth, $finalHeight);
    }

    /**
     * Downscale the image keeping the proportions.
     *
     * @param int $maxWidth
     * @param int $maxHeight
     * @return $this
     */
    public function downscaleProportion(int $maxWidth, int $maxHeight): Image
    {
        if ($this->width > $maxWidth || $this->height > $maxHeight) {
            if ($this->width > $this->height) {
                $finalHeight = \round($this->height * $maxWidth / $this->width);
                $finalWidth = $maxWidth;

                if ($finalHeight > $maxHeight) {
                    $finalWidth = \round($this->width * $maxHeight / $this->height);
                    $finalHeight = $maxHeight;
                }
            } else {
                $finalWidth = \round($this->width * $maxHeight / $this->height);
                $finalHeight = $maxHeight;
            }
        } else {
            $finalWidth = $this->width;
            $finalHeight = $this->height;
        }

        return $this->resize($finalWidth, $finalHeight);
    }

    /**
     * Resize the image.
     *
     * @param int $width
     * @param int $height
     * @return $this
     */
    public function resize(int $width, int $height): Image
    {
        if (!$this->isImageDefined()) {
            return $this;
        }

        if (
            ($image = \imagecreatetruecolor($width, $height)) !== false &&
            \imagealphablending($image, false) !== false &&
            \imagesavealpha($image, true) !== false &&
            ($transparent = $this->colorAllocate('#000000FF')) !== false &&
            \imagefill($image, 0, 0, $transparent) !== false &&
            \imagecopyresampled($image, $this->image, 0, 0, 0, 0, $width, $height, $this->width, $this->height) !== false
        ) {
            $this->image = $image;
            $this->width = $width;
            $this->height = $height;
        }
        return $this;
    }

    /**
     * Downscale the image keeping the proportions then crop to fit to $width and $height params.
     * Use $anchorX and $anchorY to select the cropping zone (You can use `Image::ALIGN_...`).
     *
     * @param int $width
     * @param int $height
     * @param int|string $anchorX
     * @param int|string $anchorY
     * @return $this
     */
    public function downscaleAndCrop(int $width, int $height, $anchorX = Image::ALIGN_CENTER, $anchorY = Image::ALIGN_MIDDLE): Image
    {
        if ($this->width < $width) {
            $width = $this->width;
        }
        if ($this->height < $height) {
            $height = $this->height;
        }


        $finalWidth = \round($this->width * $height / $this->height);
        $finalHeight = $height;

        if ($finalWidth < $width) {
            $finalHeight = \round($this->height * $width / $this->width);
            $finalWidth = $width;
        }

        if ($this->downscaleProportion($finalWidth, $finalHeight)) {
            $this->crop($width, $height, $anchorX, $anchorY);
        }

        return $this;
    }

    /**
     * Crop to fit to $width and $height params.
     * Use $anchorX and $anchorY to select the cropping zone (You can use `Image::ALIGN_...`).
     *
     * @param int $width
     * @param int $height
     * @param int|string $anchorX
     * @param int|string $anchorY
     * @return $this
     */
    public function crop(int $width, int $height, $anchorX = Image::ALIGN_CENTER, $anchorY = Image::ALIGN_MIDDLE): Image
    {
        if (!$this->isImageDefined()) {
            return $this;
        }

        if ($this->width < $width) {
            $width = $this->width;
        }
        if ($this->height < $height) {
            $height = $this->height;
        }

        //==============================================

        $anchorX = $this->convertPosX($anchorX, $width);
        $anchorY = $this->convertPosY($anchorY, $height);

        //==============================================

        if ($anchorX < 0) {
            $anchorX = 0;
        }

        if ($anchorX + $width > $this->width) {
            $anchorX = $this->width - $width;
        }

        if ($anchorY < 0) {
            $anchorY = 0;
        }

        if ($anchorY + $height > $this->height) {
            $anchorY = $this->height - $height;
        }

        //==============================================

        if (
            ($image = \imagecreatetruecolor($width, $height)) !== false &&
            \imagealphablending($image, false) !== false &&
            \imagesavealpha($image, true) !== false &&
            ($transparent = $this->colorAllocate('#000000FF')) !== false &&
            \imagefill($image, 0, 0, $transparent) !== false &&
            \imagecopyresampled($image, $this->image, 0, 0, $anchorX, $anchorY, $width, $height, $width, $height) !== false
        ) {
            $this->image = $image;
            $this->width = $width;
            $this->height = $height;
        }

        return $this;
    }

    //===============================================================================================================================
    //==========================================================COLOR================================================================
    //===============================================================================================================================

    /**
     * Format the string color.
     *
     * @param string $stringColor
     * @return string
     */
    private static function formatColor(string $stringColor): string
    {
        $stringColor = \trim(\str_replace('#', '', $stringColor));
        switch (\mb_strlen($stringColor)) {
            case 3 :
                $r = \substr($stringColor, 0, 1);
                $g = \substr($stringColor, 1, 1);
                $b = \substr($stringColor, 2, 1);
                return $r . $r . $g . $g . $b . $b . '00';
                break;
            case 6 :
                return $stringColor . '00';
                break;
            case 8 :
                return $stringColor;
                break;
            default:
                return '00000000';
        }
    }

    /**
     * Allocate a new color to the image.
     *
     * @param string $color
     * @return int
     */
    private function colorAllocate(string $color): int
    {
        $color = static::formatColor($color);
        $red = \hexdec(\substr($color, 0, 2));
        $green = \hexdec(\substr($color, 2, 2));
        $blue = \hexdec(\substr($color, 4, 2));
        $alpha = \floor(\hexdec(\substr($color, 6, 2)) / 2);

        $newColor = \imagecolorexactalpha($this->image, $red, $green, $blue, $alpha);
        if ($newColor === -1) {
            $newColor = \imagecolorallocatealpha($this->image, $red, $green, $blue, $alpha);
        }

        return $newColor;
    }


    //===============================================================================================================================
    //==========================================================PASTE================================================================
    //===============================================================================================================================

    /**
     * Paste the image at $posX and $posY position (You can use `Image::ALIGN_...`).
     *
     * @param Image $image
     * @param int|string $posX
     * @param int|string $posY
     * @return $this
     */
    public function pasteOn(Image $image, $posX = Image::ALIGN_CENTER, $posY = Image::ALIGN_CENTER): Image
    {
        if (!$this->isImageDefined() || !$image->isImageDefined()) {
            return $this;
        }

        $posX = $this->convertPosX($posX, $image->getWidth());
        $posY = $this->convertPosY($posY, $image->getHeight());

        \imagesavealpha($this->image, false);
        \imagealphablending($this->image, true);
        \imagecopy($this->image, $image->getImage(), $posX, $posY, 0, 0, $image->getWidth(), $image->getHeight());
        \imagealphablending($this->image, false);
        \imagesavealpha($this->image, true);

        return $this;
    }

    /**
     * Use a grayscale image (`$mask`) to apply transparency to the image.
     *
     * @param Image $mask
     * @return $this
     */
    public function alphaMask(Image $mask): Image
    {
        if (!$this->isImageDefined() || !$mask->isImageDefined()) {
            return $this;
        }

        $this->downscaleAndCrop($mask->getWidth(), $mask->getHeight(), static::ALIGN_CENTER, static::ALIGN_MIDDLE);

        if (($newImage = \imagecreatetruecolor($mask->getWidth(), $mask->getHeight())) === false) {
            return $this;
        }
        \imagealphablending($newImage, false);
        \imagesavealpha($newImage, true);

        for ($i = 0; $i < $this->height; $i++) {
            for ($j = 0; $j < $this->width; $j++) {
                $alpha = \floor((255 - \imagecolorat($mask->getImage(), $j, $i) & 0xFF) / 2);
                $red = 0;
                $green = 0;
                $blue = 0;

                if ($alpha != 127) {
                    $rgb = \imagecolorat($this->image, $j, $i);
                    $red = ($rgb >> 16) & 0xFF;
                    $green = ($rgb >> 8) & 0xFF;
                    $blue = $rgb & 0xFF;
                }

                $newColor = \imagecolorexactalpha($newImage, $red, $green, $blue, $alpha);
                if ($newColor === -1) {
                    $newColor = \imagecolorallocatealpha($newImage, $red, $green, $blue, $alpha);
                }

                if (!\imagesetpixel($newImage, $j, $i, $newColor)) {
                    return $this;
                }
            }
        }

        \imagedestroy($this->image);
        $this->image = $newImage;

        return $this;
    }

    //===============================================================================================================================
    //=========================================================POST PROD=============================================================
    //===============================================================================================================================

    /**
     * Apply a grayscale filter on the image.
     *
     * @return $this
     */
    public function grayscale(): Image
    {
        if (!$this->isImageDefined()) {
            return $this;
        }

        \imagefilter($this->image, IMG_FILTER_GRAYSCALE);
        return $this;
    }

    /**
     * Write text on the image.
     *
     * @param string $string
     * @param string $fontPath
     * @param int $fontSize
     * @param string $color
     * @param int|string $posX
     * @param int|string $posY
     * @param string $anchorX
     * @param string $anchorY
     * @param int $rotation
     * @return $this
     */
    public function writeText(string $string, string $fontPath, int $fontSize, string $color = '#ffffff', $posX = 0, $posY = 0, string $anchorX = Image::ALIGN_CENTER, string $anchorY = Image::ALIGN_MIDDLE, int $rotation = 0): Image
    {
        $this->writeTextAndGetBoundingBox($string, $fontPath, $fontSize, $color, $posX, $posY, $anchorX, $anchorY, $rotation);
        return $this;
    }

    /**
     * Write text on the image and get the bounding box of the text in the image.
     *
     * @param string $string
     * @param string $fontPath
     * @param int $fontSize
     * @param string $color
     * @param int|string $posX
     * @param int|string $posY
     * @param string $anchorX
     * @param string $anchorY
     * @param int $rotation
     * @return array
     */
    public function writeTextAndGetBoundingBox(string $string, string $fontPath, int $fontSize, string $color = '#ffffff', $posX = 0, $posY = 0, string $anchorX = Image::ALIGN_CENTER, string $anchorY = Image::ALIGN_MIDDLE, int $rotation = 0): array
    {
        if (!$this->isImageDefined()) {
            return [];
        }

        $posX = $this->convertPosX($posX);
        $posY = $this->convertPosY($posY);

        \imagesavealpha($this->image, false);
        \imagealphablending($this->image, true);

        $color = $this->colorAllocate($color);

        if ($color === false) {
            return [];
        }

        if (
            $anchorX == static::ALIGN_LEFT ||
            $anchorX == static::ALIGN_CENTER ||
            $anchorX == static::ALIGN_RIGHT ||
            $anchorY == static::ALIGN_TOP ||
            $anchorY == static::ALIGN_MIDDLE ||
            $anchorY == static::ALIGN_BOTTOM
        ) {
            if (
                ($newImg = \imagecreatetruecolor(1, 1)) === false ||
                ($posText = \imagettftext($newImg, $fontSize, $rotation, 0, 0, $color, $fontPath, $string)) === false
            ) {
                return [];
            }
            \imagedestroy($newImg);

            $xMin = 0;
            $xMax = 0;
            $yMin = 0;
            $yMax = 0;
            for ($i = 0; $i < 8; $i += 2) {
                if ($posText[$i] < $xMin) {
                    $xMin = $posText[$i];
                }
                if ($posText[$i] > $xMax) {
                    $xMax = $posText[$i];
                }
                if ($posText[$i + 1] < $yMin) {
                    $yMin = $posText[$i + 1];
                }
                if ($posText[$i + 1] > $yMax) {
                    $yMax = $posText[$i + 1];
                }
            }

            $sizeWidth = $xMax - $xMin;

            switch ($anchorX) {
                case static::ALIGN_LEFT :
                    $posX = $posX - $xMin;
                    break;
                case static::ALIGN_CENTER :
                    $posX = $posX - $sizeWidth / 2 - $xMin;
                    break;
                case static::ALIGN_RIGHT :
                    $posX = $posX - $sizeWidth - $xMin;
                    break;
            }
            switch ($anchorY) {
                case static::ALIGN_TOP :
                    $posY = $posY + $fontSize;
                    break;
                case static::ALIGN_MIDDLE :
                    $posY = $posY + $fontSize / 2;
                    break;
                case static::ALIGN_BOTTOM :
                    break;
            }
        }

        $posText = \imagettftext($this->image, $fontSize, $rotation, $posX, $posY, $color, $fontPath, $string);

        if ($posText === false) {
            return [];
        }

        \imagealphablending($this->image, false);
        \imagesavealpha($this->image, true);

        return [
            'top-left' => [
                'x' => $posText[6],
                'y' => $posText[7]
            ],
            'top-right' => [
                'x' => $posText[4],
                'y' => $posText[5]
            ],
            'bottom-left' => [
                'x' => $posText[0],
                'y' => $posText[1]
            ],
            'bottom-right' => [
                'x' => $posText[2],
                'y' => $posText[3]
            ],
            'baseline' => [
                'x' => $posX,
                'y' => $posY
            ]
        ];
    }

    /**
     * Draw a rectangle.
     *
     * @param int $left
     * @param int $top
     * @param int $right
     * @param int $bottom
     * @param string $color
     * @return $this
     */
    public function drawRectangle(int $left, int $top, int $right, int $bottom, string $color): Image
    {
        if (!$this->isImageDefined()) {
            return $this;
        }

        $color = $this->colorAllocate($color);

        if (($bottom - $top) <= 1.5) {
            \imageline($this->image, $left, $top, $right, $top, $color);
        } elseif (($right - $left) <= 1.5) {
            \imageline($this->image, $left, $top, $left, $bottom, $color);
        } else {
            \imagefilledrectangle($this->image, $left, $top, $right, $bottom, $color);
        }
        return $this;
    }


    /**
     * Draw a Line from `$originX, $originY` to `$dstX, $dstY`.
     *
     * @param int $originX
     * @param int $originY
     * @param int $dstX
     * @param int $dstY
     * @param int $weight
     * @param string $color
     * @return $this
     */
    public function drawLine(int $originX, int $originY, int $dstX, int $dstY, int $weight, string $color = '#000000'): Image
    {
        if (!$this->isImageDefined()) {
            return $this;
        }

        $angleAndLength = Geometry2D::getAngleAndLengthFromPoints($originX, $originY, $dstX, $dstY);
        return $this->drawLineWithAngle($originX, $originY, $angleAndLength['angle'], $angleAndLength['length'], $weight, $color);
    }

    /**
     * Draw a line using angle and length.
     *
     * @param int $originX
     * @param int $originY
     * @param int $angle
     * @param int $length
     * @param int $weight
     * @param string $color
     * @return $this
     */
    public function drawLineWithAngle(int $originX, int $originY, int $angle, int $length, int $weight, string $color = '#000000'): Image
    {
        if (!$this->isImageDefined()) {
            return $this;
        }

        $color = $this->colorAllocate($color);

        if ($color === false) {
            return $this;
        }

        $angle = Geometry2D::degrees0to360($angle);

        $points1 = Geometry2D::getDstXY($originX, $originY, Geometry2D::degrees0to360($angle - 90), floor($weight / 2));
        $points2 = Geometry2D::getDstXY($points1['x'], $points1['y'], $angle, $length);
        $points4 = Geometry2D::getDstXY($originX, $originY, Geometry2D::degrees0to360($angle + 90), floor($weight / 2));
        $points3 = Geometry2D::getDstXY($points4['x'], $points4['y'], $angle, $length);

        $points = [$points1['x'], $points1['y'], $points2['x'], $points2['y'], $points3['x'], $points3['y'], $points4['x'], $points4['y']];

        \imageantialias($this->image, true);
        \imagepolygon($this->image, $points, 4, $color);
        \imagefilledpolygon($this->image, $points, 4, $color);

        return $this;
    }

    /**
     * Draw an arrow with angle and length.
     *
     * @param int $originX
     * @param int $originY
     * @param int $angle
     * @param int $length
     * @param int $weight
     * @param string $color
     * @return $this
     */
    public function drawArrowWithAngle(int $originX, int $originY, int $angle, int $length, int $weight, string $color = '#000000'): Image
    {
        if (!$this->isImageDefined()) {
            return $this;
        }

        $headOrigin = Geometry2D::getDstXY($originX, $originY, Geometry2D::degrees0to360($angle), \round($length - $weight / 2));
        $this->drawLineWithAngle($headOrigin['x'], $headOrigin['y'], Geometry2D::degrees0to360($angle + 150), \round($length / 10), $weight, $color);
        $this->drawLineWithAngle($headOrigin['x'], $headOrigin['y'], Geometry2D::degrees0to360($angle - 150), \round($length / 10), $weight, $color);
        return $this->drawLineWithAngle($originX, $originY, $angle, $length, $weight, $color);
    }


    /**
     * Draw and arrow from `$originX, $originY` to `$dstX, $dstY`.
     *
     * @param int $originX
     * @param int $originY
     * @param int $dstX
     * @param int $dstY
     * @param int $weight
     * @param string $color
     * @return $this
     */
    public function drawArrow(int $originX, int $originY, int $dstX, int $dstY, int $weight, string $color = '#000000'): Image
    {
        if (!$this->isImageDefined()) {
            return $this;
        }

        $angleAndLength = Geometry2D::getAngleAndLengthFromPoints($originX, $originY, $dstX, $dstY);
        return $this->drawArrowWithAngle($originX, $originY, $angleAndLength['angle'], $angleAndLength['length'], $weight, $color);
    }

    /**
     * Draw a circle.
     *
     * @param int $posX
     * @param int $posY
     * @param int $diameter
     * @param string $color
     * @param string $anchorX
     * @param string $anchorY
     * @return $this
     */
    public function drawCircle(int $posX, int $posY, int $diameter, string $color = '#FFFFFF', string $anchorX = Image::ALIGN_CENTER, string $anchorY = Image::ALIGN_MIDDLE): Image
    {
        if (!$this->isImageDefined()) {
            return $this;
        }

        $color = $this->colorAllocate($color);

        if ($color === false) {
            return $this;
        }

        switch ($anchorX) {
            case static::ALIGN_LEFT :
                $posX = \round($posX + $diameter / 2);
                break;
            case static::ALIGN_CENTER :
                break;
            case static::ALIGN_RIGHT :
                $posX = \round($posX - $diameter / 2);
                break;
        }

        switch ($anchorY) {
            case static::ALIGN_TOP :
                $posY = \round($posY + $diameter / 2);
                break;
            case static::ALIGN_MIDDLE :
                break;
            case static::ALIGN_BOTTOM :
                $posY = \round($posY - $diameter / 2);
                break;
        }

        \imagefilledellipse($this->image, $posX, $posY, $diameter, $diameter, $color);
        return $this;
    }

    //===============================================================================================================================
    //=========================================================GET PICTURE===========================================================
    //===============================================================================================================================

    /**
     * Save the image to PNG file.
     *
     * @param string $path
     * @return bool
     */
    public function savePNG(string $path): bool
    {
        if (!$this->isImageDefined()) {
            return false;
        }
        return \imagepng($this->image, $path);
    }

    /**
     * Save the image to JPG file.
     *
     * @param string $path
     * @param int $quality
     * @return bool
     */
    public function saveJPG(string $path, int $quality = -1): bool
    {
        if (!$this->isImageDefined()) {
            return false;
        }
        return \imagejpeg($this->image, $path, $quality);
    }

    /**
     * Save the image to GIF file.
     *
     * @param string $path
     * @return bool
     */
    public function saveGIF(string $path): bool
    {
        if (!$this->isImageDefined()) {
            return false;
        }
        return \imagegif($this->image, $path);
    }

    /**
     * Display in PNG format.
     */
    public function displayPNG()
    {
        if ($this->isImageDefined()) {
            \imagepng($this->image);
        }
    }

    /**
     * Display in JPG format.
     *
     * @param int $quality
     */
    public function displayJPG(int $quality = -1)
    {
        if ($this->isImageDefined()) {
            \imagejpeg($this->image, null, $quality);
        }
    }

    /**
     * Display in GIF format.
     */
    public function displayGIF()
    {
        if ($this->isImageDefined()) {
            \imagegif($this->image);
        }
    }

    /**
     * Get image raw data
     *
     * @param string $nameFunction
     * @param int $quality
     * @return string
     */
    private function getData(string $nameFunction, int $quality = -1): string
    {
        if (!$this->isImageDefined()) {
            return '';
        }

        \ob_start();
        $nameFunction($this->image, null, $quality, -1);
        $image_data = \ob_get_contents();
        \ob_end_clean();

        return $image_data;
    }

    /**
     * Get image PNG raw data
     *
     * @return string
     */
    public function getDataPNG(): string
    {
        return $this->getData('imagepng');
    }

    /**
     * Get image JPG raw data
     *
     * @param int $quality
     * @return string
     */
    public function getDataJPG(int $quality = -1): string
    {
        return $this->getData('imagejpeg', $quality);
    }

    /**
     * Get image GIF raw data
     *
     * @return string
     */
    public function getDataGIF(): string
    {
        return $this->getData('imagegif');
    }

    /**
     * Get image PNG base64 data
     *
     * @return string
     */
    public function getBase64PNG(): string
    {
        return \base64_encode($this->getDataPNG());
    }

    /**
     * Get image JPG base64 data
     *
     * @param int $quality
     * @return string
     */
    public function getBase64JPG(int $quality = -1): string
    {
        return \base64_encode($this->getDataJPG($quality));
    }

    /**
     * Get image GIF base64 data
     *
     * @return string
     */
    public function getBase64GIF(): string
    {
        return \base64_encode($this->getDataGIF());
    }

    /**
     * Get image PNG base64 data for <img src=""> tag.
     *
     * @return string
     */
    public function getBase64SourcePNG(): string
    {
        return 'data:image/png;base64,' . $this->getBase64PNG();
    }

    /**
     * Get image JPG base64 data for <img src=""> tag.
     *
     * @param int $quality
     * @return string
     */
    public function getBase64SourceJPG(int $quality = -1): string
    {
        return 'data:image/jpeg;base64,' . $this->getBase64JPG($quality);
    }

    /**
     * Get image GIF base64 data for <img src=""> tag.
     *
     * @return string
     */
    public function getBase64SourceGIF(): string
    {
        return 'data:image/gif;base64,' . $this->getBase64GIF();
    }
}
