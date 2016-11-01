<?php

//Getting / Setting the path for this main app file.
//This should be revisted with the autoload name space in the new PHP
//@var __DIR__ helps with getting into subdirectories where other files may be stored.
//define('ROOT', realpath(dirname(basename(__DIR__))));

class Autoloader
{
  public static function DatabaseLoader($class){
    $path = dirname(__DIR__).'/utilities/db/'.strtolower($class).'.class.php';
    if(file_exists($path)){require_once $path;}
  }

  public static function DatabaseUserLoader($class){
    $path = dirname(__DIR__).'/utilities/db/'.strtolower($class).'.class.php';
    if(file_exists($path)){require_once $path;}
  }

  public static function ConfigurationLoader($class){
    $path = dirname(__DIR__).'/config/'.strtolower($class).'.class.php';
    if(file_exists($path)){require_once $path;}

  }

  public static function CrawlerLoader($class){
  $path = dirname(__DIR__).'/utilities/crawler/'.strtolower($class).'.class.php';
  if(file_exists($path)){require_once $path;}

}

}

?>
