<?php
namespace Pee\Exception;

/**
 * Represents an error condition that is specific to HTTP transport.
 * The exception has semantics inline with the given HTTP error code.
 * Don't instantiate this class directly.
 */
class HttpEquivalentException extends \Exception
{
  private $httpMessage = '';

  /**
   * HTTP error responses have ~3 error realted field, code, codemessage, a body,
   * Exception have two code, message.
   */
  public function __construct($code = 0, $message, $previous = null) {
    $_message = [];
    if(isset(\Pee\Http::$CODES[$code])) {
      $this->httpMessage = \Pee\Http::$CODES[$code];
      $_message[] = $this->httpMessage;
    }
    if($message) {
      $_message[] = $message;
    }
    parent::__construct(implode(' - ', $_message), $code, $previous);
  }

  public function getHttpMessage() {
    return $this->httpMessage;
  }
}

class Http400Exception extends HttpEquivalentException
{
  public function __construct($message = '') {
    parent::__construct(400, $message);
  }
}

class Http401Exception extends HttpEquivalentException
{
  public function __construct($message = '') {
    parent::__construct(401, $message);
  }
}

class Http402Exception extends HttpEquivalentException
{
  public function __construct($message = '') {
    parent::__construct(402, $message);
  }
}

class Http403Exception extends HttpEquivalentException
{
  public function __construct($message = '') {
    parent::__construct(403, $message);
  }
}

class Http404Exception extends HttpEquivalentException
{
  public function __construct($message = '') {
    parent::__construct(404, $message);
  }
}

class Http405Exception extends HttpEquivalentException
{
  public function __construct($message = '') {
    parent::__construct(405, $message);
  }
}

class Http406Exception extends HttpEquivalentException
{
  public function __construct($message = '') {
    parent::__construct(406, $message);
  }
}

class Http407Exception extends HttpEquivalentException
{
  public function __construct($message = '') {
    parent::__construct(407, $message);
  }
}

class Http408Exception extends HttpEquivalentException
{
  public function __construct($message = '') {
    parent::__construct(408, $message);
  }
}

class Http409Exception extends HttpEquivalentException
{
  public function __construct($message = '') {
    parent::__construct(409, $message);
  }
}

class Http410Exception extends HttpEquivalentException
{
  public function __construct($message = '') {
    parent::__construct(410, $message);
  }
}

class Http411Exception extends HttpEquivalentException
{
  public function __construct($message = '') {
    parent::__construct(411, $message);
  }
}

class Http412Exception extends HttpEquivalentException
{
  public function __construct($message = '') {
    parent::__construct(412, $message);
  }
}

class Http413Exception extends HttpEquivalentException
{
  public function __construct($message = '') {
    parent::__construct(413, $message);
  }
}

class Http414Exception extends HttpEquivalentException
{
  public function __construct($message = '') {
    parent::__construct(414, $message);
  }
}

class Http415Exception extends HttpEquivalentException
{
  public function __construct($message = '') {
    parent::__construct(415, $message);
  }
}

class Http416Exception extends HttpEquivalentException
{
  public function __construct($message = '') {
    parent::__construct(416, $message);
  }
}

class Http417Exception extends HttpEquivalentException
{
  public function __construct($message = '') {
    parent::__construct(417, $message);
  }
}

class Http500Exception extends HttpEquivalentException
{
  public function __construct($message = '') {
    parent::__construct(500, $message);
  }
}

class Http501Exception extends HttpEquivalentException
{
  public function __construct($message = '') {
    parent::__construct(501, $message);
  }
}

class Http502Exception extends HttpEquivalentException
{
  public function __construct($message = '') {
    parent::__construct(502, $message);
  }
}

class Http503Exception extends HttpEquivalentException
{
  public function __construct($message = '') {
    parent::__construct(503, $message);
  }
}

class Http504Exception extends HttpEquivalentException
{
  public function __construct($message = '') {
    parent::__construct(504, $message);
  }
}

class Http505Exception extends HttpEquivalentException
{
  public function __construct($message = '') {
    parent::__construct(505, $message);
  }
}




/*
foreach(Http::$CODES as $c => $m) {
  if($c >= 400) {
    print "class Http{$c}Exception extends HttpEquivalentException
{
  public function __construct(\$message = '') {
    parent::__construct($c, \$message);
  }
}

";
  }
}
*/
