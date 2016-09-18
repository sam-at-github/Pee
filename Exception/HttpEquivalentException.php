<?php
namespace Pee\Exception;

/**
 * Represents an error condition that is specific to HTTP transport.
 * The exception has semantics inline with the given HTTP error code.
 */
class HttpEquivalentException extends \Exception
{
  public function __construct($message = "", $code = 0, $previous = null) {
    if(empty($message) && isset(\Pee\Http::$CODES[$code])) {
      $message = \Pee\Http::$CODES[$code];
    }
    parent::__construct($message, $code, $previous);
  }
}

class Http400Exception extends HttpEquivalentException
{
  public function __construct() {
    parent::__construct(null, 400);
  }
}

class Http401Exception extends HttpEquivalentException
{
  public function __construct() {
    parent::__construct(null, 401);
  }
}

class Http402Exception extends HttpEquivalentException
{
  public function __construct() {
    parent::__construct(null, 402);
  }
}

class Http403Exception extends HttpEquivalentException
{
  public function __construct() {
    parent::__construct(null, 403);
  }
}

class Http404Exception extends HttpEquivalentException
{
  public function __construct() {
    parent::__construct(null, 404);
  }
}

class Http405Exception extends HttpEquivalentException
{
  public function __construct() {
    parent::__construct(null, 405);
  }
}

class Http406Exception extends HttpEquivalentException
{
  public function __construct() {
    parent::__construct(null, 406);
  }
}

class Http407Exception extends HttpEquivalentException
{
  public function __construct() {
    parent::__construct(null, 407);
  }
}

class Http408Exception extends HttpEquivalentException
{
  public function __construct() {
    parent::__construct(null, 408);
  }
}

class Http409Exception extends HttpEquivalentException
{
  public function __construct() {
    parent::__construct(null, 409);
  }
}

class Http410Exception extends HttpEquivalentException
{
  public function __construct() {
    parent::__construct(null, 410);
  }
}

class Http411Exception extends HttpEquivalentException
{
  public function __construct() {
    parent::__construct(null, 411);
  }
}

class Http412Exception extends HttpEquivalentException
{
  public function __construct() {
    parent::__construct(null, 412);
  }
}

class Http413Exception extends HttpEquivalentException
{
  public function __construct() {
    parent::__construct(null, 413);
  }
}

class Http414Exception extends HttpEquivalentException
{
  public function __construct() {
    parent::__construct(null, 414);
  }
}

class Http415Exception extends HttpEquivalentException
{
  public function __construct() {
    parent::__construct(null, 415);
  }
}

class Http416Exception extends HttpEquivalentException
{
  public function __construct() {
    parent::__construct(null, 416);
  }
}

class Http417Exception extends HttpEquivalentException
{
  public function __construct() {
    parent::__construct(null, 417);
  }
}

class Http500Exception extends HttpEquivalentException
{
  public function __construct() {
    parent::__construct(null, 500);
  }
}

class Http501Exception extends HttpEquivalentException
{
  public function __construct() {
    parent::__construct(null, 501);
  }
}

class Http502Exception extends HttpEquivalentException
{
  public function __construct() {
    parent::__construct(null, 502);
  }
}

class Http503Exception extends HttpEquivalentException
{
  public function __construct() {
    parent::__construct(null, 503);
  }
}

class Http504Exception extends HttpEquivalentException
{
  public function __construct() {
    parent::__construct(null, 504);
  }
}

class Http505Exception extends HttpEquivalentException
{
  public function __construct() {
    parent::__construct(null, 505);
  }
}
