<?php

namespace Pee;

/**
 * Singleton for encapsulating basic global state of a web application - a place to put stuff.
 * Contains a configuration space, global error handling, the HTTP request & response singletons, a router.
 */
class App implements \ArrayAccess, ConfigHive
{
  private static $instance = null;
  private $request;
  private $response;
  private $config = [];
  private $router;
  private $errorHandlers = [];
  private static $defaultSettings = [];
  const DEFAULT_CONFIG_FILE = "./config.yml";
  const DEFAULT_ROUTER_CLASS = "Pee\Router";

  /**
   * Initialize everything. App now controls the response.
   * HttpRequest|Response are thin wrappers over \http\Env\Request|Response.
   */
  private function __construct($config = null) {
    if(headers_sent()) {
      throw new \RuntimeException("Can't initialize app after headers have been sent");
    }
    $this->initErrors();
    $this->loadConfig($config);
    $this->request =  new HttpRequest();
    $this->response = new HttpResponse();
    $routerClass = $this['ROUTER_CLASS'] ? $this['ROUTER_CLASS'] : static::DEFAULT_ROUTER_CLASS;
    $this->router = new $routerClass();
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

  public function getConfig() {
    return $this->config;
  }

  /**
   * Everything we can handle and that is defined as an error, is remapped to an Exception.
   * @todo add ERRORS config variable and override default.
   */
  private function initErrors() {
    $errors = (E_ALL) & ~(E_STRICT|E_NOTICE|E_USER_NOTICE);
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
   */
  public function defaultErrorHandler($code, $message, $exception = null) {
    ob_clean();
    $mime = $this->response->getHeader("Content-Type");
    switch($mime) {
      case "application/json": {
        print json_encode(['code' => $code, 'message' => $message]);
        break;
      }
      case "application/phpcli" :
      default: {
        $trace = ((bool)ini_get('display_errors')) ? $exception . "" : "";
        $output = ini_get('display_errors') == "stderr" ? STDERR : STDOUT;
        fprintf($output, "<!DOCTYPE html>
<html>
  <head><title>$code $message</title></head>
  <body>
    <h1>$message</h1>
    <p>$trace</p>
  </body>
</html>\n");
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

  public function getRouter() {
    return $this->router();
  }

  /**
   * Convenience map four common Http verbs on to same named end points of a given object.
   * Maybe Router should do this.
   */
  public function mapRoutesTo($controller) {
    $this->router->addRoute(new Route("GET /", [$controller, "get"]));
    $this->router->addRoute(new Route("POST /", [$controller, "post"]));
    $this->router->addRoute(new Route("PUT /", [$controller, "put"]));
    $this->router->addRoute(new Route("DELETE /", [$controller, "delete"]));
  }

  /**
   * App takes care of routing.
   * We had a need for this basic wrapping over Router->run() so may as well do it here.
   */
  public function run() {
    $route = $this->router->run($this->request);
    if(!isset($route)) {
      throw new Exception\HttpEquivalentException("", 404);
    }
    $target = $route->getTarget();
    if(!is_callable($target)) {
      throw new Exception\HttpEquivalentException("Route found but route not callable", 500);
    }
    call_user_func($target, $this, $route->getTokens());
  }

  public function addRoute($routeStr, callable $target) {
    $this->router->addRoute(new Route($routeStr));
  }

  /* Config Hive interface. Delegate to $config. */

  public function overlay($resource, $recursive = false) {
    return $this->config->overlay($resource, $recursive);
  }

  public function history() {
    return $this->config->history();
  }

  public function sync($file, $type = null) {
    return $this->config->sync($file, $type);
  }

  /** ArrayAccess interface. Delegate to $config. */

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

  /**
   * Factory for singleton App instance.
   */
  public static function instance($config = null) {
    if(!isset(self::$instance)) {
      $class = get_called_class();
      self::$instance = new $class($config);
    }
    elseif(isset($config)) {
      throw new \RuntimeException("Configuration passed, but instance already instantiated");
    }
    return self::$instance;
  }
}
