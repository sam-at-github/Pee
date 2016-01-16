<?php

require_once('loader.php');

/**
 * Singleton for encapsulating basic state of a HTTP request/response.
 */
class App implements \ArrayAccess
{
  private $request;
  private $response;
  private $config;
  private $errorHandlers = [];
  private static $defaultSettings = [
  ];
  const DEFAULT_CONFIG_FILE = "./config.yml";

  /**
   * Initialize everything. App now controls the response.
   */
  public function __construct($config = null) {
    if(headers_sent()) {
      throw new \RuntimeException("Can't initialize app after headers have been sent");
    }
    $this->loadConfig($config);
    $this->initErrors([$this, "defaultErrorHandler"]);
    $this->request =  new http\Env\Request();
    $this->response = new http\Env\Response();
    register_shutdown_function([$this, "send"]);
    ob_start();
  }

  private function loadConfig($config) {
    if($config) {
      $this->config = new Hive($config);
    }
    elseif(file_exists(self::DEFAULT_CONFIG_FILE)) {
      $this->config = new Hive(self::DEFAULT_CONFIG_FILE);
    }
    else {
      $this->config = new Hive();
    }
  }

  /**
   * Everything we can handle and that is defined as an error via ERRORS config, is remapped to an Exception.
   */
  private function initErrors() {
    $errors = isset($this['ERRORS']) ? $this['ERRORS'] : (E_ALL|E_STRICT) & ~(E_NOTICE|E_USER_NOTICE);
    set_error_handler(
      function($errno, $errmsg, $errfile, $errline, $errcontext) {
        throw new \ErrorException($errmsg, $errno, $errno, $errfile, $errline);
      },
      $errors
    );
    set_exception_handler([$this, 'exceptionHandler']);
  }

  /**
   * In built exception handler. Passes off if user defined handler is registered.
   */
  public function exceptionHandler($exception) {
    $code = 500;
    $message = Http::$CODES[500];
    if($exception instanceof HttpEquivalentException) {
      $code = $exception->getCode();
      $message = $exception->getMessage();
    }
    call_user_func($this->getErrorHandler(), $code, $message, $exception);
  }

  public function send() {
    $this->response->getBody()->append(ob_get_clean());
    $this->response->send();
  }

  public function setErrorHandler($callback) {
    if(!is_callable($callback)) {
      throw \InvalidArgumentException("Can't set callback. Argument is not callable");
    }
  }

  public function getErrorHandler() {
    return isset($this->errorHandler) ? $this->errorHandler : [$this, 'defaultErrorHandler'];
  }

  /**
   * The deafult error handlers tries to do provode an appropriate response for MIME if set. Defaults to HTML.
   * @param $code HTTP error code.
   * @param $message HTTP error message.
   * @param $exception Exception that caused this error if any.
   * @todo verbosity and respect display_errors
   */
  public function defaultErrorHandler($code, $message, $exception = null) {
    $mime = $this->response->getHeader("Content-Type");
    switch($mime) {
      case "application/json": {
        print json_encode(['code' => $code, 'message' => $message]);
        break;
      }
      default: {
        print "<!DOCTYPE html>
        <html>
          <head><title>$code $message</title></head>
          <body>
            <h1>$message</h1>
            <p></p>
          </body>
        </html>";
        break;
      }
    }
  }

  /**
   * Get the input request. Treat this as a singleton.
   */
  public function getRequest() {
    return $this->request;
  }

  /**
   * Get the response. Treat this as a singleton.
   */
  public function getResponse()  {
    return $this->response;
  }

  public function offsetGet($offset) {
    return $this->config->offsetGet($offset);
  }

  public function offsetSet($offset, $value) {
    return $this->config->offsetSet($offset);
  }

  public function offsetExists($offset) {
    return $this->config->offsetExists($offset);
  }

  public function offsetUnset($offset) {
    return $this->config->offsetUnset($offset);
  }
}

$a = new App();
