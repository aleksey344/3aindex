<?php

namespace CryptocurrencyCharts;

/**
 * Class Helper - helper functions
 * @package CryptocurrencyCharts
 */
class Helper {
  const LOG_FILE = 'trace.log';
  /**
   * Print message or array/object
   * @param $msg
   */
  public static function p($msg) {
    if (is_array($msg) || is_object($msg)) {
      print '<pre>'.print_r($msg, TRUE).'</pre>';
    } else {
      print $msg;
    }
  }

  public static function loadJSON($fileName) {
    return file_exists(CCC_ROOT_DIR . '/' . $fileName) ?
      json_decode(file_get_contents(CCC_ROOT_DIR . '/' . $fileName)) :
      '';
  }

  public static function saveJSON($fileName, $contents) {
    return file_put_contents(CCC_ROOT_DIR . '/' . $fileName, json_encode($contents, JSON_PRETTY_PRINT));
  }

  public static function log($msg) {
    file_put_contents(CCC_ROOT_DIR . '/' . self::LOG_FILE, sprintf("[%s]: %s\n", date(DATE_ATOM), is_string($msg) ? $msg : print_r($msg, TRUE)), FILE_APPEND);
  }

  public static function protocol() {
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443 ? 'https' : 'http';
  }

  public static function cleanString($jsonString) {
    if (!is_string($jsonString) || !$jsonString) return '';

    // Remove unsupported characters
    // Check http://www.php.net/chr for details
    for ($i = 0; $i <= 31; ++$i)
      $jsonString = str_replace(chr($i), "", $jsonString);

    $jsonString = str_replace(chr(127), "", $jsonString);

    // Remove the BOM (Byte Order Mark)
    // It's the most common that some file begins with 'efbbbf' to mark the beginning of the file. (binary level)
    // Here we detect it and we remove it, basically it's the first 3 characters.
    if (0 === strpos(bin2hex($jsonString), 'efbbbf')) $jsonString = substr($jsonString, 3);

    return $jsonString;
  }
}