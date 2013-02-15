<?php
namespace Mopa\Bundle\BarcodeBundle\Model;

use Monolog\Logger;
use Imagine\Gd\Image;
use Imagine\Image\ImagineInterface;
use Zend\Barcode\Barcode;

class BarcodeService{
    private $types;
    private $imagine;
    private $kernelcachedir;
    private $kernelrootdir;
    private $webdir;
    private $webroot;
    private $logger;

    public function __construct(ImagineInterface $imagine, $kernelcachedir, $kernelrootdir, $webdir, $webroot, Logger $logger){
        $this->types = BarcodeTypes::getTypes();
        $this->imagine = $imagine;
        $this->kernelcachedir = $kernelcachedir;
        $this->kernelrootdir = $kernelrootdir;
        $this->webdir = $webdir;
        $this->webroot = $webroot;    
        $this->logger = $logger;
    }
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
                    list($width) = getimagesize($file);
                    $overlayImage = $this->kernelrootdir . '/Resources/AstinaRedirectManagerBundle/public/images/QR-j-' . $width . '.png';

                    if (file_exists($overlayImage)) {
                        $destination = imagecreatefrompng($file);
                        $src = imagecreatefrompng($overlayImage);

                        $this->imagecopymerge_alpha($destination, $src, 0, 0, 0, 0, $width, $width, 100);
                        imagepng($destination, $file);
                        imagedestroy($destination);
                        imagedestroy($src);
                    }
                }

            break;
            case is_numeric($type):
                $type = $this->types[$type];
            default:
                $barcodeOptions = array('text' => $text);
                $rendererOptions = array();
                $image = new Image(
                    $imageResource = Barcode::factory(
                        $type, 'image', $barcodeOptions, $rendererOptions, false
                    )->draw()
                );
                $image->save($file);
        }
        return true;
    }

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
     * @param string $text BarcodeText
     * @param boolean $absolute get absolute path, default: false
     * @param array $options Options
     */
    public function get($type, $enctext, $absolut = false, $options = array()){
        $text = urldecode($enctext);
        $filename = $this->getAbsoluteBarcodeDir($type).$this->getBarcodeFilename($text, $options);
        // TODO: uncomment this
        //if(!file_exists($filename)){
            $this->saveAs($type, $text, $filename, $options);
        //}
        if(!$absolut){
            $path = DIRECTORY_SEPARATOR.$this->webdir.$this->getTypeDir($type).$this->getBarcodeFilename($text, $options);
            return str_replace(DIRECTORY_SEPARATOR, "/", $path);
        }
        return $filename;
    }
    protected function getTypeDir($type){
        if(is_numeric($type)){
            $type = $this->types[$type];
        }
        return $type.DIRECTORY_SEPARATOR;
    }
    protected function getBarcodeFilename($text, $options){
        return sha1($text . serialize($options)).".png";
    }
    protected function getAbsoluteBarcodeDir($type){
        $path = $this->getAbsolutePath().$this->getTypeDir($type);
        if(!file_exists($path)){
            mkdir($path, 0777, true);
        }
        return $path;
    }
    protected function getAbsolutePath(){
        return $this->webroot.DIRECTORY_SEPARATOR.$this->webdir;
    }
}
