<?php
/*
 * PHP QR Code encoder
 *
 * Config file, feel free to modify
 * since we include it from our barcode service we can easyly use its injected kerneldir
 */
    $cachedir = $this->kernelcachedir.DIRECTORY_SEPARATOR.'phpqr'.DIRECTORY_SEPARATOR;
    $logdir = $this->kernelrootdir.DIRECTORY_SEPARATOR.'logs'.DIRECTORY_SEPARATOR.'phpqr'.DIRECTORY_SEPARATOR;
    @mkdir($cachedir);
    @mkdir($logdir);

    define('QR_CACHEABLE', true);                                                               // use cache - more disk reads but less CPU power, masks and format templates are stored there
    define('QR_CACHE_DIR', $cachedir);  // used when QR_CACHEABLE === true
    define('QR_LOG_DIR', $logdir);                                // default error logs dir   
    
    define('QR_FIND_BEST_MASK', true);                                                          // if true, estimates best mask (spec. default, but extremally slow; set to false to significant performance boost but (propably) worst quality code
    define('QR_FIND_FROM_RANDOM', false);                                                       // if false, checks all masks available, otherwise value tells count of masks need to be checked, mask id are got randomly
    define('QR_DEFAULT_MASK', 2);                                                               // when QR_FIND_BEST_MASK === false
                                                  
    define('QR_PNG_MAXIMUM_SIZE',  1024);                                                       // maximum allowed png image width (in pixels), tune to make sure GD and PHP can handle such big images
                                                  