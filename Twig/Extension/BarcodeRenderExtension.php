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

class BarcodeRenderExtension extends \Twig_Extension
{

    protected $bs;

    /**
     * @param \Knp\Menu\Twig\Helper $helper
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

    public function getFunctions()
    {
        return array(
            'mopa_barcode_url' => new \Twig_Function_Method($this, 'url'),
            'mopa_barcode_path' => new \Twig_Function_Method($this, 'path'),
        );
    }

    public function url($type, $text, $options)
    {
        return $this->get($type, $text, false, $options);
    }

    public function path($type, $text, $options)
    {
        return $this->get($type, $text, true, $options);
    }

    protected function get($type, $text, $absolute, $options)
    {
        return $this->bs->get($type, urlencode($text), $absolute, $options);
    }

}
