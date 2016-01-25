<?php

namespace Pee;

/**
 * Simple wrapper on a nested storage structure.
 * Supports (de)serializing from a small number of baked in formats.
 * Supports array access. Interprets offset "A.B.C" as a depthful request.
 * This *only* appies to getting currently. If the key "A.B.C" exists in a loaded source or is set it takes precedence.
 * @todo not that sophisticated. Bit of a stub.
 */
class Hive implements \ArrayAccess, ConfigHive
{
  private $data = [];
  private $history = [];

  /**
   * Construct a hive from optional resource.
   * @param $resource See overlay().
   */
  public function __construct($resource = null) {
    if(isset($resource)) {
      $this->overlay($resource);
    }
  }

  public function offsetGet($offset) {
    $value = null;
    if(isset($this->data[$offset])) {
      $value = $this->data[$offset];
    }
    else {
      $offsetExpr = $this->offsetExpr($offset);
      if(eval("return isset(\$this->data$offsetExpr);")) {
        $value = eval("return \$this->data$offsetExpr;");
      }
    }
    return $value;
  }

  public function offsetSet($offset, $value) {
    if (is_null($offset)) {
      $this->data[] = $value;
    }
    else {
      $offsetExpr = $this->offsetExpr($offset);
      eval("return \$this->data$offsetExpr = \$value;");
    }
  }

  public function offsetExists($offset) {
    $e = false;
    if(isset($this->data[$offset])) {
      $e = true;
    }
    else {
      $offsetExpr = $this->offsetExpr($offset);
      $e = eval("return isset(\$this->data$offsetExpr);");
    }
    return $e;
  }

  public function offsetUnset($offset) {
    if(isset($this->data[$offset])) {
      unset($this->data[$offset]);
    }
    else {
      $offsetExpr = $this->offsetExpr($offset);
      eval("return unset(\$this->data$offsetExpr);");
    }
  }

  /**
   * Returns a PHP language offset to a nest array value.
   * Ex "A.B.C" => ["A"]["B"]["C"].
   */
  public function offsetExpr($offset) {
    $parts = explode(".", $offset);
    $expr = "";
    foreach($parts as $k) {
      $expr .= "['$k']";
    }
    return $expr;
  }

  /**
   * Overlay contents from $resource onto the current value. Values in $resource override existing values
   * If $recursive is true mergable values will be merged instead of overriding
   * If $resource is a string attempt to read and parse file content. Fails if format is not supported.
   * @param $resourceString location of file, or array to load.
   * @bug numercial indexes are not merged but appended and renumbered.
   * @bug array_merge_recursive() does not replace scalar0 with scalar1. It creates [scalar0, scalar1] ...
   */
  public function overlay($resource, $recursive = false) {
    $name = $this->getName($resource);

    if(is_string($resource)) {
      $resource = $this->load($resource);
    }
    elseif(!is_array($resource)) {
      throw new \InvalidArgumentException("Expected array. Got " . gettype($resource));
    }
    if($recursive == false) {
      $this->data = array_merge($this->data, $resource);
    }
    else {
      $this->data = array_merge_recursive($this->data, $resource); // Is not exactly what we want.
    }
    $this->history[] = $name;
  }

  /**
   * Get overlay history.
   * This can be useful if you want ot sync to a source.
   */
  public function history() {
    return $this->history;
  }

  /**
   * Write contents of hive to a file.
   * Format is detect from file extension if not provided.
   * @param $file where to serialize current contents.
   */
  public function sync($file, $type = null) {
    $type = $type ? $type : $this->mapExt($this->getExt($file));
    if(!$type) {
      throw new \InvalidArgumentException("Cannot process file '$file'. Unknown type");
    }
    call_user_func([$this, "sync$type"], $file);
  }

  public function dump() {
    return $this->data;
  }

  public function toArray() {
    return $this->data;
  }

  /**
   * Handle loading contents of a file.
   * @param $file name of existing file containing the data.
   * @return array containing deserialized data.
   */
  private function load($file, $type = null) {
    $type = $type ? $type : $this->mapExt($this->getExt($file));
    if(!$type) {
      throw new \InvalidArgumentException("Cannot process file '$file'. Unknown type");
    }
    return call_user_func([$this, "load$type"], $file);
  }

  private function loadJson($file) {
    return json_decode(file_get_contents($file));
  }

  private function loadYaml($file) {
    return yaml_parse_file($file);
  }

  private function syncJson($file) {
    file_put_contents($file, json_encode($this->data));
  }

  private function syncYaml($file) {
    yaml_emit_file($file, $this->data);
  }

  private function getExt($file) {
    $ext = null;
    if($pos = strrpos($file, ".")) {
      $ext = substr($file, $pos+1);
    }
    return $ext;
  }

  /**
   * Maybe used to det type of type is not specified.
   */
  private function mapExt($ext) {
    $type = null;
    $extMap = [
      'json' => ['jsn'],
      'yaml' => ['yml'],
      'ini'  => ['cfg']
    ];
    $ext = strtolower((string)$ext);
    foreach($extMap as $k => $exts) {
      if($ext == $k || in_array($ext, $exts)) {
        $type = $k;
        break;
      }
    }
    return $type;
  }

  private function getName($resource) {
    return @($resource . "");
  }
}
