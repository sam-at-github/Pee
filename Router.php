<?php

/**
 * Select a view based off of a HTTP Request.
 * The first Route that accepts the current request is used. Thus order is important.
 */
class Router //extends AbstractRouter
{
  private $routes = [];
  private $lastRoute = null;

  public function __construct() {
  }

  /**
   * Add a route. A route is specified via an interpreted string.
   * @param $route A Route to route.
   * @see Route
   */
  public function addRoute(AbstractRoute $route) {
    $this->routes[] = $route;
  }

  public function getRoute($i) {
    return isset($this->routes[$i]) ? $this->routes[$i] : null;
  }

  public function removeRoute($i) {
    if(isset($this->routes[$i])) {
      array_splice($this->routes, $i, 1, []);
      return true;
    }
    return false;
  }

  public function getIterator() {
    return new \ArrayIterator($this->routes);
  }

  public function getLastRoute() {
    return $this->lastRoute;
  }

  /**
   * Select a target view for request.
   * Because most route will want to do some common prepro we do it here and pass along to the route.
   * @returns AbstractView if there is a route else null
   */
  public function run(HttpRequest $request) {
    $target = null;
    foreach($this->routes as $route) {
      if($route->accept($request)) {
        $target = $route;
        break;
      }
    }
    $this->lastRoute = $target;
    return $target;
  }

  public function __toString() {
    $str = "";
    foreach($this->routes as $route) {
      $str .= $route . "\n";
    }
    return $str;
  }
}
