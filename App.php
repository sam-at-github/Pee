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
  private $logger;
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
    $this->logger = new Logger();
    $this->request =  new HttpRequest();
    $this->response = new HttpResponse();
    $this->initErrors();
    $this->loadConfig($config);
    register_shutdown_function([$this, "send"]);
    ob_start();
    $routerClass = $this['ROUTER_CLASS'] ? $this['ROUTER_CLASS'] : static::DEFAULT_ROUTER_CLASS;
    $this->router = new $routerClass();
    if(!$this['NO_SAPI_CHECK']) {
      $this->checkSapi();
    }
  }

  /**
   * Try load and then init config.
   */
  private function loadConfig($config) {
    $this->config = new Hive(); # If config load fails need this set coz Execptions uses it.
    if($config) {
      $this->config = new Hive($config);
    }
    elseif(file_exists(self::DEFAULT_CONFIG_FILE)) {
      $this->config = new Hive(self::DEFAULT_CONFIG_FILE);
    }
    $this->initConfig();
  }

  /**
   * Expose some of our environment available via the hive.
   * Note I can see ~everything slowly leaking over into the hive.. Will do it incrementally.
   */
  private function initConfig() {
    $this['BASE'] = rtrim(dirname($_SERVER['SCRIPT_NAME']), "/") . "/"; # Always end in "/".
  }

  public function getConfig() {
    return $this->config;
  }

  public function getLogger() {
    return $this->logger;
  }

  public function setLogger(\Psr\Log\AbstractLogger $logger) {
    $this->logger = $logger;
  }

  /**
   * Remap handle-able errors to exceptions, set a default global exception handler.
   * We set a global exception hadlers so we can return some sort of coherent HTTP response if it comes to it.
   * However, users may 1. override the default, 2. provide an on onException() method in route targets.
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
   * Check if invoked via CLI or web. Rebase $request path accordingly in both cases.
   * BASE is already set to dirname($_SERVER['SCRIPT_NAME']);
   */
  private function checkSapi() {
    global $argv;
    if(PHP_SAPI == "cli") {
      if(!isset($argv[1])) {
        throw new \Exception("Usage: {$argv[0]} <method> [<path=/>]");
      }
      $this->request->setRequestMethod($argv[1]);
      $uriPath = isset($argv[2]) ? ltrim($argv[2], "/") : "";
      $this->request->setRequestUrl("/$uriPath");
      stream_set_blocking(fopen('php://stdin', 'r'), false);
      $body = new \http\Message\Body();
      $body->append(file_get_contents('php://stdin'));
      $this->request->setBody($body);
      $this->logger->info("CLI mode {$this->request}");
    }
    if(PHP_SAPI != "cli") {
      $url = $this->request->getParsedUrl();
      if(strpos($url['path'], $this['BASE']) === 0) {
        $url['path'] = substr($url['path'], strlen($this['BASE'])-1);
        if(substr($url['path'], strlen($url['path'])-1) == "/") { # Fix. HttpRequest doesn't like "//"
          $url['path'] = rtrim($url['path'], "/") . "/";
        }
        if(empty($url['path'])) {  # Fix. HttpRequest doesn't like ""
          $url['path'] = "/";
        }
        $this->request->setParsedRequestUrl($url);
        $this->logger->info("Rebase {$url['path']} with {$this['BASE']}");
      }
    }
  }

  /**
   * Finish up. Send HTTP response.
   */
  public function send() {
    $this->response->getBody()->append(ob_get_clean());
    $this->response->send();
  }

  /**
   * Redirect. Use this to redirect not header().
   */
  public function redirect($location) {
    ob_clean();
    $this->response->setResponseCode(302);
    $this->response->setHeader("Location", $location);
  }

  /**
   * In built exception handler. Passes off if user defined handler is registered.
   * Don't try and map PHP exceptions to HTTP errors. Depends on layer the exception occured at.
   * But set the response code.
   */
  public function exceptionHandler($exception) {
    $code = 500;
    if($exception instanceof Exception\HttpEquivalentException) {
      $code = $exception->getCode();
    }
    $this->response->setResponseCode($code);
    call_user_func($this->getErrorHandler(), $this, $exception);
  }

  /**
   * Set custom error handler. The callback takes the same params as defaultErrorHandler().
   * @see defaultErrorHandler
   */
  public function setErrorHandler($callback) {
    if(!is_callable($callback)) {
      throw \InvalidArgumentException("Can't set callback. Argument is not callable");
    }
    $this->errorHandler = $callback;
  }

  public function getErrorHandler() {
    return isset($this->errorHandler) ? $this->errorHandler : [$this, 'defaultErrorHandler'];
  }

  /**
   * The deafult error handlers tries to do provode an appropriate response for MIME if set. Defaults to HTML.
   * @param $response HttpResponse
   * @param $code HTTP error code.
   * @param $exception Exception that caused this error. There should always be one.
   */
  public function defaultErrorHandler(\Pee\App $app, $e) {
    $this['CLEANONERROR'] && ob_clean();
    $mime = $this->response->getHeader("Content-Type");
    switch($mime) {
      case "application/json":
      case "text/json": {
        print json_encode(['error' => ['code' => $e->getCode(), 'message' => $e->getMessage()]]);
        break;
      }
      case "application/phpcli":
      default: {
        $trace = ((bool)ini_get('display_errors')) ? $exception . "" : "";
        $output = (ini_get('display_errors') === "stderr") ? fopen("php://stderr", "w") : fopen("php://output", "w");
        $title = ($e instanceof Exception\HttpEquivalentException) ? $e->getHttpMessage() : '';
        fprintf($output, "<!DOCTYPE html>
<html>
  <head><title>%d %s</title></head>
  <body>
    <h1>%s</h1>
    <pre>%s</pre>
  </body>
</html>\n", $e->getCode(), $title, $e->getMessage(), $trace);
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
    return $this->router;
  }

  /**
   * Convenience map four common Http verbs on to same named end points of a given object.
   */
  public function mapRoutesTo($controller, $prefix = '') {
    $this->router->addRoute(new Route("GET $prefix/@id", [$controller, "get"]));
    $this->router->addRoute(new Route("POST $prefix", [$controller, "post"]));
    $this->router->addRoute(new Route("PUT $prefix/@id", [$controller, "put"]));
    $this->router->addRoute(new Route("DELETE $prefix/@id", [$controller, "delete"]));
    $this->router->addRoute(new Route("GET $prefix", [$controller, "find"]));
  }

  /**
   * App takes care of routing. Had need for this basic wrapping over Router->run() so may as well do it here.
   * Call target->before|afterRoute() if exist - like Fatfree.
   * Call target->onException() on any exception in the route target. This is a convenience so the client
   * can define exception handling boiler plate once not in every end point.
   * So user can call App::run() after an exception (if they really want) catch all exceptions here
   * to avoid them bubbling to global and becoming terminal.
   */
  public function run() {
    $route = $this->router->run($this->request);
    if(!isset($route)) {
      return $this->exceptionHandler(new Exception\Http404Exception());
    }
    $target = $route->getTarget();
    if(!is_callable($target)) {
      return $this->exceptionHandler(new Exception\Http500Exception("Route found but route not callable"));
    }
    @list($obj, $method) = $target;
    $before = [$obj, 'beforeRoute'];
    $after = [$obj, 'afterRoute'];
    $tokens = $route->getTokens();
    $retval = null;
    try {
      if(is_callable($before)) {
        $retval = call_user_func($before, $this, $tokens);
      }
      $retval = call_user_func($target, $this, $route->getTokens(), $retval);
      if(is_callable($after)) {
        call_user_func($after, $this, $tokens, $retval);
      }
    }
    catch(\Exception $e) {
      if(is_callable([$obj, 'onException'])) {
        call_user_func([$obj, 'onException'], $this, $e);
      }
      else {
        $this->exceptionHandler($e);
      }
    }
  }

  public function addRoute($routeStr, callable $target) {
    $this->router->addRoute(new Route($routeStr, $target));
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
    return $this->config->offsetSet($offset, $value);
  }

  public function offsetExists($offset) {
    return $this->config->offsetExists($offset);
  }

  public function offsetUnset($offset) {
    return $this->config->offsetUnset($offset);
  }

  public function toArray() {
    return $this->config->toArray();
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
