<?php

// define plugin root folder to be used by other classess
define('CCC_ROOT_DIR', dirname(__FILE__));

// register autoload function
spl_autoload_register(function ($className) {
  if (strpos($className,'CryptocurrencyCharts')!==FALSE) {
    require_once 'classes/' . str_replace('\\', '/', $className) . '.php';
  }
});