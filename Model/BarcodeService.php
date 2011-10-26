<?php
namespace Mopa\BarcodeBundle\Model;


use Monolog\Logger;

use Imagine\Gd\Image;

use Imagine\Image\ImagineInterface;
use Zend\Barcode\Barcode;

class BarcodeService{
    private $imagine;
    private $types;
    private $logger;
    private $kernelcachedir;
    private $kernelrootdir;
    
    public function __construct(ImagineInterface $imagine, $kernelcachedir, $kernelrootdir,  Logger $logger){
        $this->imagine = $imagine;
        $this->types = BarcodeTypes::getTypes();
        $this->logger = $logger;
        $this->kernelcachedir = $kernelcachedir;
        $this->kernelrootdir = $kernelrootdir;
    }
    public function saveAs($type, $text, $file){
        @unlink($file);
        switch ($type){
            case is_numeric($type):
                $barcodeOptions = array('text' => $text);
                $rendererOptions = array();
                $image = new Image(
                    $imageResource = Barcode::factory(
                        $this->types[$type], 'image', $barcodeOptions, $rendererOptions, false
                    )->draw()
                );
                $image->save($file);
            break;
            case ($type == 'qr'):
                include __DIR__."/../Resources/phpqrcode/qrlib.php";
                \QRcode::png($text, $file);
            break;
            default:
                throw new \Exception("Renderer Not Defined");
        }
        return true;
    }
}