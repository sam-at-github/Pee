<?php

namespace Pee;

/**
 * What should the target of a route be ni our framework?
 * Argument: JIT instantiating a class is faster. So the route wrapper should support interpreting a string specifying the route as *well* as a callable.
 * Counter argument: For consistency just use callables. You can proxy like below.
 * Conclusions: Were always going to support callables so can be done incrementally. Just support callables for now. Add support for interpreting a string later.
 */
$app = new StdClass();
$foo = new StdClass();
class Controller
{
  function __construct($foo) {
    print __METHOD__ ."\n";
  }
  function get($app, $x, $y) {
    print __METHOD__ . "\n";
  }
}
$proxy = function($app, $x, $y) use ($foo) {$target = new Controller($foo); $target->get($app,$x, $y);};
$proxy($app, 2,3);
