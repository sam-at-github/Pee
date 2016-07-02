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
    $this->initErrors();
    register_shutdown_function([$this, "send"]);
    ob_start();
    $this->loadConfig($config);
    $this->logger = new Logger();
    $this->request =  new HttpRequest();
    $this->response = new HttpResponse();
    $routerClass = $this['ROUTER_CLASS'] ? $this['ROUTER_CLASS'] : static::DEFAULT_ROUTER_CLASS;
    $this->router = new $routerClass();
    $this->checkSapi();
  }

  /**
   * Try load and then init config.
   */
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
   * Check if invoked via CLI. Reset $request path accordingly.
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
      $this->logger->info("CLI mode {$this->request}");
    }
    if(PHP_SAPI != "cli") {
      $url = $this->request->getParsedUrl();
      if(strpos($url['path'], $this['BASE']) === 0) {
        $url['path'] = substr($url['path'], strlen($this['BASE']));
        $url['path'] = rtrim($url['path'], "/"); # Fix. HttpRequest doesn't like "//"
        $url['path'] = empty($url['path']) ? "/" : $url['path'];
        $this->request->setParsedRequestUrl($url);
        $this->logger->info("Rebase {$url['path']} with {$this['BASE']}");
      }
    }
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
    $this->response->setResponseCode($code);
    call_user_func($this->getErrorHandler(), $this->response, $code, $message, $exception);
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
   * Set customer error handler. The callback takes the same params as defaultErrorHandler().
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
   * @param $message HTTP error message.
   * @param $exception Exception that caused this error if any.
   */
  public function defaultErrorHandler(\Pee\HttpResponse $response, $code, $message, $exception = null) {
    $this['CLEANONERROR'] && ob_clean();
    $mime = $this->response->getHeader("Content-Type");
    switch($mime) {
      case "application/json":
      case "text/json": {
        print json_encode(['code' => $code, 'message' => $message]);
        break;
      }
      case "application/phpcli" :
      default: {
        $trace = ((bool)ini_get('display_errors')) ? $exception . "" : "";
        $output = (ini_get('display_errors') === "stderr") ? fopen("php://stderr", "w") : fopen("php://output", "w");
        fprintf($output, "<!DOCTYPE html>
<html>
  <head><title>%d %s</title></head>
  <body>
    <h1>%s</h1>
    <p>%s</p>
  </body>
</html>\n", $code, $message, $message, $trace);
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
   */
  public function mapRoutesTo($controller) {
    $this->router->addRoute(new Route("GET /", [$controller, "get"]));
    $this->router->addRoute(new Route("POST /", [$controller, "post"]));
    $this->router->addRoute(new Route("PUT /", [$controller, "put"]));
    $this->router->addRoute(new Route("DELETE /", [$controller, "delete"]));
  }

  /**
   * App takes care of routing. Had need for this basic wrapping over Router->run() so may as well do it here.
   * Will call before|afterRoute() if target a class and these exist - like Fatfree.
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
    @list($obj, $method) = $target;
    $before = [$obj, 'beforeRoute'];
    $after = [$obj, 'afterRoute'];
    $tokens = $route->getTokens();
    if(is_callable($before)) {
      call_user_func($before, $this, $tokens);
    }
    call_user_func($target, $this, $route->getTokens());
    if(is_callable($after)) {
      call_user_func($after, $this, $tokens);
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
