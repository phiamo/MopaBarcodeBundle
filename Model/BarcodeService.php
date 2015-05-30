<?php
namespace Mopa\Bundle\BarcodeBundle\Model;

use Monolog\Logger;
use Imagine\Gd\Image;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Box;
use Zend\Barcode\Barcode;

/**
 * Class BarcodeService
 * @package Mopa\Bundle\BarcodeBundle\Model
 */
class BarcodeService{
    /**
     * @var array
     */
    private $types;

    /**
     * @var ImagineInterface
     */
    private $imagine;

    /**
     * @var string
     */
    private $kernelcachedir;

    /**
     * @var string
     */
    private $kernelrootdir;

    /**
     * @var string
     */
    private $webdir;

    /**
     * @varstring
     */
    private $webroot;

    /**
     * @var string
     */
    private $overlayPath;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param ImagineInterface $imagine
     * @param $kernelcachedir
     * @param $kernelrootdir
     * @param $webdir
     * @param $webroot
     * @param Logger $logger
     */
    public function __construct(ImagineInterface $imagine, $kernelcachedir, $kernelrootdir, $webdir, $webroot, Logger $logger){
        $this->types = BarcodeTypes::getTypes();
        $this->imagine = $imagine;
        $this->kernelcachedir = $kernelcachedir;
        $this->kernelrootdir = $kernelrootdir;
        $this->webdir = $webdir;
        $this->webroot = $webroot;
        $this->logger = $logger;
    }

    /**
     * @param $type
     * @param $text
     * @param $file
     * @param array $options
     * @return bool
     */
    public function saveAs($type, $text, $file, $options = array()){
        @unlink($file);
        switch ($type){
            case $type == 'qr':
                include_once __DIR__.DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."Resources".DIRECTORY_SEPARATOR."phpqrcode".DIRECTORY_SEPARATOR."qrlib.php";

                $level = (isset($options['level'])) ? $options['level'] : QR_ECLEVEL_L;
                $size = (isset($options['size'])) ? $options['size'] : 3;
                $margin = (isset($options['margin'])) ? $options['margin'] : 4;
                \QRcode::png($text, $file, $level, $size, $margin);

                if (isset($options['useOverlay']) && $options['useOverlay']) {
                    $this->addOverlay($file, $size);
                }

            break;
            case is_numeric($type):
                $type = $this->types[$type];
            default:
                $barcodeOptions = array_merge(isset($options['barcodeOptions']) ? $options['barcodeOptions'] : array(), array('text' => $text));
                $rendererOptions = isset($options['rendererOptions']) ? $options['rendererOptions'] : array();
                $rendererOptions['width'] = isset($rendererOptions['width']) ? $rendererOptions['width'] : 2233;
                $rendererOptions['height'] = isset($rendererOptions['height']) ? $rendererOptions['height'] : 649;
                $image = new Image(
                    $imageResource = Barcode::factory(
                        $type, 'image', $barcodeOptions, $rendererOptions
                    )->draw()
                );
                $image->save($file);
        }
        return true;
    }

    /**
     * @param $file
     * @param $size
     */
    private function addOverlay($file, $size)
    {
        list($width) = getimagesize($file);
        $size = ($size < 1) ? 1 : $size;
        $originalLevelWidth = $width / $size;

        $overlayImagePath = $this->overlayPath . DIRECTORY_SEPARATOR . $originalLevelWidth . '.png';

        if (file_exists($overlayImagePath)) {
            $destination = imagecreatefrompng($file);
            $src = imagecreatefrompng($overlayImagePath);

            $overlayImage = new Image($src);
            $overlayImage->resize(new Box($width, $width));
            $tmpFilePath = $this->kernelcachedir . DIRECTORY_SEPARATOR . sha1(time() . rand()) . '.png';
            $overlayImage->save($tmpFilePath);

            $src = imagecreatefrompng($tmpFilePath);

            $this->imagecopymerge_alpha($destination, $src, 0, 0, 0, 0, $width, $width, 100);
            imagepng($destination, $file);
            imagedestroy($destination);
            imagedestroy($src);
            unlink($tmpFilePath);
        }
    }

    /**
     * @param $dst_im
     * @param $src_im
     * @param $dst_x
     * @param $dst_y
     * @param $src_x
     * @param $src_y
     * @param $src_w
     * @param $src_h
     * @param $pct
     */
    private function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct){
        // creating a cut resource
        $cut = imagecreatetruecolor($src_w, $src_h);

        // copying relevant section from background to the cut resource
        imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);

        // copying relevant section from watermark to the cut resource
        imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);

        // insert cut resource to destination image
        imagecopymerge($dst_im, $cut, $dst_x, $dst_y, 0, 0, $src_w, $src_h, $pct);
    }


    /**
     * Get a Barcodes Filename
     * Generates it if its not here
     *
     * @param string $type BarcodeType
     * @param string $enctext BarcodeText
     * @param boolean $absolute get absolute path, default: false
     * @param array $options Options
     * @return mixed|string
     */
    public function get($type, $enctext, $absolute = false, $options = array()){
        $text = urldecode($enctext);
        $filename = $this->getAbsoluteBarcodeDir($type).$this->getBarcodeFilename($text, $options);

        if(
            (isset($options['noCache']) && $options['noCache'])
            || !file_exists($filename)
          ) {
            $this->saveAs($type, $text, $filename, $options);
        }

        if(!$absolute){
            $path = DIRECTORY_SEPARATOR.$this->webdir.$this->getTypeDir($type).$this->getBarcodeFilename($text, $options);
            return str_replace(DIRECTORY_SEPARATOR, "/", $path);
        }

        return $filename;
    }

    /**
     * @param $type
     * @return string
     */
    protected function getTypeDir($type){
        if(is_numeric($type)){
            $type = $this->types[$type];
        }
        return $type.DIRECTORY_SEPARATOR;
    }

    /**
     * @param $text
     * @param $options
     * @return string
     */
    protected function getBarcodeFilename($text, $options){
        return sha1($text . serialize($options)).".png";
    }

    /**
     * @param $type
     * @return string
     */
    protected function getAbsoluteBarcodeDir($type){
        $path = $this->getAbsolutePath().$this->getTypeDir($type);
        if(!file_exists($path)){
            mkdir($path, 0777, true);
        }
        return $path;
    }

    /**
     * @return string
     */
    protected function getAbsolutePath(){
        return $this->webroot.DIRECTORY_SEPARATOR.$this->webdir;
    }

    /**
     * @param $path
     */
    public function setOverlayPath($path)
    {
        if ($path) {
            $this->overlayPath = $this->kernelrootdir . DIRECTORY_SEPARATOR .  $path;
        } else {
            $this->overlayPath = __DIR__ . '/../Resources/qr_overlays';
        }
    }
}
