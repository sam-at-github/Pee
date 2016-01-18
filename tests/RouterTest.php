<?php

/**
 * Basic tests.
 */
class RouterTest extends PHPUnit_Framework_TestCase
{
  /**
   */
  public function testRouteMethod() {
    $request = new HttpRequest();
    $request->setRequestUrl("http://foo.com");
    $request->setRequestMethod("GET");
    $router = new Router();
    $router->addRoute(new Route("PUT /", false));
    $router->addRoute(new Route("POST /", false));
    $router->addRoute(new Route("GET /", true));
    $router->addRoute(new Route("GET /", false));
    $router->addRoute(new Route("ANY /", false));
    $target = $router->run($request);
    $this->assertTrue($target->getTarget());
  }
}
