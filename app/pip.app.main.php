<?php

/*define("ENVIRONMENT", "development");

if (defined('ENVIRONMENT'))
{
    switch (ENVIRONMENT)
    {
        case 'development':
            case 'development':
            error_reporting(E_ALL);
            ini_set('display_errors', 'Off');
            ini_set("log_errors", 1);
            ini_set("error_log", "./php-error.log");
        break;

        case 'testing':
        case 'production':
            error_reporting(0);
            ini_set('display_errors', 'Off');

        break;

        default:
            exit('The application environment is not set correctly.');
    }
}*/



//Getting / Setting the path for this main app file.
//This should be revisted with the autoload name space in the new PHP
//@var __DIR__ helps with getting into subdirectories where other files may be stored.
define('ROOT', realpath(dirname(basename(__DIR__))));

//Basic utilities to ensure that everything loads
//More info to come
require_once(dirname(__DIR__).'/config/bootstrap.php');
require_once(dirname(__DIR__).'/config/autoloader.php');

//Autoloading all dependant classes. Requires / includes not required.
spl_autoload_register('Autoloader::DatabaseLoader');
spl_autoload_register('Autoloader::DatabaseUserLoader');
spl_autoload_register('Autoloader::ConfigurationLoader');
spl_autoload_register('Autoloader::CrawlerLoader');

//CLASS PIP
//This is the main class for the entire app.
//Ideally, everything can be ran / allocated through here.
//Eventually this will server as the main object in the MVC modal. The app-delegate if you will (though it is not a delegate).
class PIP
{

  public function __construct(){

  }


}



?>
