<?php

if (version_compare(phpversion(), '5.0.0', 'lt')) {

# XXX: these includes shouldn't be necessary!
require_once 'include/configclass.inc';
$config = new Config;
require_once 'include/config.inc';
require_once($config->getPHPCompatFile());

// Configure necessary functions here
$funcs = array(
   'stripos',
   'http_build_query',
);

// End configuration

foreach ($funcs as $fn) {
    if (PHP_Compat::loadFunction($fn) != true)
       error_log('Could not load function `'.$fn.'\' as required by PHP Compat.');
}

}
?>
