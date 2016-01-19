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

  public function testRoutePath() {
    $request = new HttpRequest();
    $request->setRequestUrl("http://foo.com/bah?x=y");
    $request->setRequestMethod("GET");
    $router = new Router();
    $router->addRoute(new Route("PUT /", false));
    $router->addRoute(new Route("POST /", false));
    $router->addRoute(new Route("GET /", false));
    $router->addRoute(new Route("GET /bah", true));
    $router->addRoute(new Route("ANY /", false));
    $target = $router->run($request);
    $this->assertTrue($target->getTarget());
  }

  public function testRouteAccept() {
    $request = new HttpRequest();
    $request->setRequestUrl("http://foo.com/bah?x=y");
    $request->setHeader("Accept", "*/*");
    $request->setRequestMethod("GET");
    $router = new Router();
    $router->addRoute(new Route("GET /", 0));
    $router->addRoute(new Route("GET /bah", 1));
    $router->addRoute(new Route("GET /bah */*", 2));
    $router->addRoute(new Route("GET /bah */*", 3));
    $router->addRoute(new Route("ANY /", 4));
    // Should find 2nd.
    $this->assertEquals($router->run($request)->getTarget(), 1);
    $router->removeRoute(1);
    $this->assertEquals($router->run($request)->getTarget(), 2);
    $router->removeRoute(1);
    $this->assertEquals($router->run($request)->getTarget(), 3);
    $router->removeRoute(1);
    $target = $router->run($request);
    $this->assertNull($target);
  }

  public function testRouteAcceptMore() {
    $request = new HttpRequest();
    $request->setRequestUrl("http://foo.com/bah?x=y");
    $request->setHeader("Accept", "thing/*");
    $request->setRequestMethod("GET");
    $router = new Router();
    $router->addRoute(new Route("GET /", 0));
    $router->addRoute(new Route("GET /bah", 1));
    $router->addRoute(new Route("GET /bah */*", 2));
    $router->addRoute(new Route("GET /bah thing/*", 3));
    $router->addRoute(new Route("GET /bah thing/bong", 4));
    $router->addRoute(new Route("ANY /", 5));
    // Should find 4th.
    $this->assertEquals($router->run($request)->getTarget(), 3);
    $request->setHeader("Accept", "thing/bong");
    $this->assertEquals($router->run($request)->getTarget(), 4);
  }

  public function testRouteNoRoute() {
    $request = new HttpRequest();
    $request->setRequestUrl("http://foo.com/bah/lah?x=y");
    $request->setRequestMethod("GET");
    $router = new Router();
    $router->addRoute(new Route("GET /", false));
    $router->addRoute(new Route("GET /bah", false));
    $router->addRoute(new Route("ANY /", false));
    $target = $router->run($request);
    $this->assertNull($target);
  }
}
