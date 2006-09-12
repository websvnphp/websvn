<?php

if (version_compare(phpversion(), '5.0.0', 'lt')) {

// Configure necessary functions here
$funcs = array(
   'stripos',
   'http_build_query',
);

// End configuration

// JB: PHP_Compat expects to be able to find functions by including
// 'PHP/Compat/Functions/functionname.php', but that hardcoded path
// isn't relative to the standard include path '.' (it's instead relative
// to './include'). So rather than hack PHP_Compat to work, just add
// './include' to our include path.
ini_set("include_path", ini_get("include_path").$config->$pathSeparator."./include");

foreach ($funcs as $fn) {
    if (PHP_Compat::loadFunction($fn) != true)
       error_log('Could not load function `'.$fn.'\' as required by PHP Compat.');
}

}
?>
