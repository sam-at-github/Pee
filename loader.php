<?php
/**
 * Convenience loader. Use very optional.
 */

/**
 * Simple class loader.
 */
class MyLoader
{
  protected $bases = [];

  public function __construct(array $bases) {
    $this->bases = $bases;
  }

  /**
   * @input $name FQ classname, without a leading "\".
   */
  function load($name) {
    foreach($this->bases as $prefix => $basePath) {
      $path = null;
      if(strpos($name, $prefix) === 0) {
        $name = substr($name, strlen($prefix));
        $path = $basePath . "/" . str_replace("\\", "/", $name) . ".php";
      }
      else if($prefix === "\\") {
        $path = $basePath . "/" . str_replace("\\", "/", $name) . ".php";
      }
      if($path && file_exists($path)) {
        include_once $path;
        break;
      }
    }
  }
}

$myPath = dirname(realpath(__FILE__));
$myLoader = new MyLoader([
  "\\" => $myPath
]);
spl_autoload_register([$myLoader, "load"]);
