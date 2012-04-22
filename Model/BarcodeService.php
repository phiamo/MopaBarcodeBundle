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
    private $logger;

    public function __construct(ImagineInterface $imagine, $kernelcachedir, $kernelrootdir, $webdir,  Logger $logger){
        $this->types = BarcodeTypes::getTypes();
        $this->imagine = $imagine;
        $this->kernelcachedir = $kernelcachedir;
        $this->kernelrootdir = $kernelrootdir;
        $this->webdir = $webdir;
        $this->logger = $logger;
    }
    public function saveAs($type, $text, $file){
        @unlink($file);
        switch ($type){
            case $type == 'qr':
                include __DIR__."/../Resources/phpqrcode/qrlib.php";
                \QRcode::png($text, $file);
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
        $filename = $this->getAbsoluteBarcodeDir($type).$this->getBarcodeFilename($text);
        if(!file_exists($filename)){
            $this->saveAs($type, $text, $filename);
        }
        if(!$absolut){
            return "/".$this->webdir.$this->getTypeDir($type).$this->getBarcodeFilename($text);
        }
        return $filename;
    }
    protected function getTypeDir($type){
        if(is_numeric($type)){
            $type = $this->types[$type];
        }
        return $type.DIRECTORY_SEPARATOR;
    }
    protected function getBarcodeFilename($text){
        return sha1($text).".png";
    }
    protected function getAbsoluteBarcodeDir($type){
        $path = $this->getAbsolutePath().$this->getTypeDir($type);
        if(!file_exists($path)){
            mkdir($path, 0777, true);
        }
        return $path;
    }
    protected function getAbsolutePath(){
        return $this->kernelrootdir."/../web/".$this->webdir;
    }
}