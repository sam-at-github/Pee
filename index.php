<?php

namespace Pee;

/**
 * A simple sample application of the framework.
 */
require_once 'loader.php';
ini_set('log_errors', true);
ini_set('display_errors', true);
ini_set('error_reporting', E_ALL);
$app = App::instance();
$router = new Router();
$controller = new Controller();
$app->mapRoutesTo($controller);
$request = $app->getRequest();
$request->setRequestUrl("http://foo.com/");
foreach(['GET', 'POST', 'PUT', 'DELETE'] as $method) {
  $request->setRequestMethod($method);
  print $request;
  $app->dispatchToRoute($request);
}

class Controller
{
  public function get(App $app, array $tokens) {
    print __METHOD__ . "\n";
  }

  public function post(App $app, array $tokens) {
    print __METHOD__ . "\n";
  }

  public function put(App $app, array $tokens) {
    print __METHOD__ . "\n";
  }

  public function delete(App $app, array $tokens) {
    print __METHOD__ . "\n";
  }
}
