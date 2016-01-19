<?php
namespace Exception;

/**
 * Represents an error condition that is specific to HTTP transport.
 * The exception has semantics inline with the given HTTP error code.
 */
class HttpEquivalentException extends \Exception {

  public function __construct($message = "", $code = 0, $previous = null) {
    if(empty($message) && isset(Http::$CODES[$code])) {
      $message = Http::$CODES[$code];
    }
    parent::__construct($message, $code, $previous);
  }
}
