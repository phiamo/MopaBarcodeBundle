<?php

/**
 * Twig extension for barcodes 
 *
 * @author Philipp A. Mohrenweiser<phiamo@googlemail.com>
 * @copyright 2011 Philipp Mohrenweiser
 * @license http://www.apache.org/licenses/LICENSE-2.0.html
 */

namespace Mopa\Bundle\BarcodeBundle\Twig\Extension;

use Mopa\Bundle\BarcodeBundle\Model\BarcodeService;

/**
 * Class BarcodeRenderExtension
 * @package Mopa\Bundle\BarcodeBundle\Twig\Extension
 */
class BarcodeRenderExtension extends \Twig_Extension
{

    protected $bs;

    /**
     * @param BarcodeService $bs
     * @internal param \Knp\Menu\Twig\Helper $helper
     */
    public function __construct(BarcodeService $bs)
    {
        $this->bs = $bs;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'mopa_barcode_render';
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return array(
            'mopa_barcode_url' => new \Twig_Function_Method($this, 'url'),
            'mopa_barcode_path' => new \Twig_Function_Method($this, 'path'),
        );
    }

    /**
     * @param $type
     * @param $text
     * @param array $options
     * @return mixed|string
     */
    public function url($type, $text, $options = array())
    {
        return $this->get($type, $text, false, $options);
    }

    /**
     * @param $type
     * @param $text
     * @param array $options
     * @return mixed|string
     */
    public function path($type, $text, $options = array())
    {
        return $this->get($type, $text, true, $options);
    }

    /**
     * @param $type
     * @param $text
     * @param $absolute
     * @param array $options
     * @return mixed|string
     */
    protected function get($type, $text, $absolute, $options = array())
    {
        return $this->bs->get($type, urlencode($text), $absolute, $options);
    }

}
