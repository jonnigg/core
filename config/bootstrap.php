<?php

//Getting / Setting the path for this main app file.
//This should be revisted with the autoload name space in the new PHP
//@var __DIR__ helps with getting into subdirectories where other files may be stored.

class boostrap
{

}

//The autoloader for all other class.
//This is the best way to allocate classes in the app lifecycle.
//This should be revisted with the new autoload / name space in the new PHP PSR4-Autoloader
//For now this is a hack job to autoload
/*spl_autoload_register(function ($class) {

    $path_utilities = dirname(__DIR__).'/utilities/'.strtolower($class).'.class.php';
    $path_config = dirname(__DIR__."/config/" . $class . '.class.php');

    if (file_exists($path_utilities)) {
        require_once $path_utilities;
    } elseif (file_exists($path_config)) {
        require_once $path_config;
    }
});*/

?>
