<?php
require_once 'loader.php';
use Pee\Route;
use Pee\HttpRequest;

$route = new Route("ANY /z/x// */*", "");
exit();
try {
  $route = new Route("ANY / */*", "");
  $route = new Route("ANY /", "");
  $route = new Route("ANY /x/@y/z */*", "");
}
catch(Exception $e) {
}
define("DEBUG", 1);

$t = [
  ["*/*", "http://foo.com/a/b/c", "ANY /", false],
  ["*/*", "http://foo.com/a/b/c", "ANY /a", false],
  ["*/*", "http://foo.com/a/b/c", "ANY /a/b", false],
  ["*/*", "http://foo.com/a/b/c", "ANY /a/d/c/d", false],
  ["*/*", "http://foo.com/a/b/c", "ANY /a/b/c", true],
  ["*/*", "http://foo.com/a/b/c", "ANY /a/b/c */*", true],
  ["*/*", "http://foo.com/a/b/c", "ANY /a/b/c text/*", true],
  ["*/*", "http://foo.com/a/b/c", "ANY /a/b/@c text/*", true],
  ["*/*", "http://foo.com/a/b/c", "ANY /a/@b/@c text/*", true],
  ["*/*", "http://foo.com/a/b/c", "ANY /@a/@b/@c text/*", true],
  ["*/*", "http://foo.com/a/b/c", "ANY /a/@b/c text/*", true],
  ["*/*", "http://foo.com/a/b/c", "ANY /@@a text/*", true],
  ["text/plain; q=0.5, text/html", "http://foo.com/", "ANY / */*", false],
  ["text/plain; q=0.5, text/html", "http://foo.com/", "ANY / text/*", false],
  ["text/plain; q=0.5, text/html", "http://foo.com/", "ANY / text/html", true],
  ["text/plain; q=0.5, text/html", "http://foo.com/", "ANY / text/plain", true],
];

foreach($t as $s) {
  print "---- {$s[0]} {$s[1]}\n";
  $request = new HttpRequest();
  $request->setRequestUrl($s[1]);
  $request->setHeader("Accept", $s[0]);
  print $request;
  $route = new Route($s[2], "");
  var_dump($route->accept($request), $s[2]);
  print "\n";
}
