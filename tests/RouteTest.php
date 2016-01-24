<?php

/**
 * Basic tests.
 */
class RouteTest extends PHPUnit_Framework_TestCase
{
  public function routeMethodTestProvider() {
    return [
      ["GET", "http://foo.com/", "GET /", true],
      ["GET", "http://foo.com/", "ANY /", true],
      ["GET", "http://foo.com/", "PUT /", false],
    ];
  }

  /**
   * @dataProvider routeMethodTestProvider
   */
  public function testRouteMethod($method, $url, $route, $match) {
    $request = new \Pee\HttpRequest();
    $request->setRequestUrl($url);
    $request->setRequestMethod($method);
    $route = new \Pee\Route($route, "");
    $this->assertEquals($route->accept($request), $match);
  }

  public function routePathTestProvider() {
    return [
      ["http://foo.com/a/b/c", "ANY /", false],
      ["http://foo.com/a/b/c", "ANY /a", false],
      ["http://foo.com/a/b/c", "ANY /a/b", false],
      ["http://foo.com/a/b/c", "ANY /a/d/c/d", false],
      ["http://foo.com/a/b/c", "ANY /a/b/c", true],
      ["http://foo.com/a/b/c/", "ANY /a/b/c", true],
      ["http://foo.com/a/b/c", "ANY /a/b/c/", true],
      ["http://foo.com/a/b/c/", "ANY /a/b/c/", true],
      ["http://foo.com/a/b/c", "ANY /a/b/c */*", true],
      ["http://foo.com/a/b/c", "ANY /a/b/c text/*", true],
      ["http://foo.com/a/b/c", "ANY /a/b/@c text/*", true],
      ["http://foo.com/a/b/c", "ANY /a/@b/@c text/*", true],
      ["http://foo.com/a/b/c", "ANY /@a/@b/@c text/*", true],
      ["http://foo.com/a/b/c", "ANY /a/@b/c text/*", true],
      ["http://foo.com/a/b/c", "ANY /@@a text/*", true],
      ["http://foo.com/a/b/", "ANY /@a/@b/@c", false],
    ];
  }

  /**
   * @dataProvider routePathTestProvider
   */
  public function testRoutePath($url, $route, $match) {
    $request = new \Pee\HttpRequest();
    $request->setRequestUrl($url);
    $route = new \Pee\Route($route, "");
    $this->assertEquals($route->accept($request), $match);
  }

  public function routeAcceptHeaderTestProvider() {
    return [
      ["text/plain; q=0.5, text/html", "http://foo.com/", "ANY / */*", false],
      ["text/plain; q=0.5, text/html", "http://foo.com/", "ANY / text/*", false],
      ["text/plain; q=0.5, text/html", "http://foo.com/", "ANY / text/html", true],
      ["text/plain; q=0.5, text/html", "http://foo.com/", "ANY / text/plain", true],
    ];
  }

  /**
   * text/plain; q=0.5, text/html,
   * @dataProvider routeAcceptHeaderTestProvider
   */
  public function testRouteAcceptHeader($acceptHeader, $url, $route, $match) {
    $request = new \Pee\HttpRequest();
    $request->setRequestUrl($url);
    $request->setHeader("Accept", $acceptHeader);
    $route = new \Pee\Route($route, "");
    $this->assertEquals($route->accept($request), $match);
  }

}
