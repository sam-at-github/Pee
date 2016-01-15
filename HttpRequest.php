<?php

/**
 * Extend http\Env\Request with some basic functionality and caching.
 * @todo Needs tidy up.
 * @todo Could probably avoid subclassing. See http\Env\Request::getHeader(), http\Env\Request::getParams()...
 */
class HttpRequest extends \http\Env\Request
{
  private $parsedUrl = null;
  private $parsedHeaders = [];

  public function __construct() {
    parent::__construct();
    $this->parsedUrl = parse_url($this->getRequestUrl());
  }

  public function getParsedUrl() {
    return $this->parsedUrl;
  }

  public function getHeader($name, $class = null) {
    return isset($this->parsedHeaders[$name]) ? $this->parsedHeaders[$name] : parent::getHeader($name, $class);
  }

  public function setRequestUrl($url) {
    parent::setRequestUrl($url);
    $this->parsedUrl = parse_url($this->getRequestUrl());
  }
}

/**
 * Shim to override http\Header paring for accept header.
 * Parsing is different plus caches.
 */
class AcceptHeader extends \http\Header
{
  private $params = null;

  /**
   * @override
   */
  public function getParams() {
    if(!isset($this->params)) {
      $this->params = self::parseAcceptHeader($this->value);
    }
    return $this->params;
  }

  /**
   * Parse and sort precedence of accept media types.
   * Going off the spec "q" accept param take total prec over mime specificity(? bit hard to read).
   * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.1
   */
  public static function parseAcceptHeader($accept) {
    $mimes = [];
    $parseAcceptParams = function($n) {
      $p = explode(";", $n);
      $p[0] = explode("/", trim($p[0]));
      $sp = sizeof($p);
      $pk = ['q' => 1];
      if($sp > 1) {
        for($i = 1; $i < $sp; $i++) {
          list($k,$v) = explode("=", $p[$i]);
          if($k){
            $pk[$k] = $v;
          }
        }
      }
      $p[1] = $pk;
      return $p;
    };
    $sortAcceptMedias = function($a, $b) {
      if($a[1]['q'] < $b[1]['q']) {
        return 1;
      }
      elseif($a[1]['q'] > $b[1]['q']) {
        return -1;
      }
      elseif($a[0][1] == "*") {
        if($b[0][1] != "*") {
           return 1;
        }
        elseif($a[0][0] == "*") {
          if($b[0][0] != "*") {
           return 1;
          }
        }
      }
      elseif($b[0][1] == "*") {
        return -1;
      }
      elseif($b[0][0] == "*") {
        if($a[0][0] != "*") {
          return -1;
        }
      }
      return 0;
    };
    $accept = trim($accept);
    if(!empty($accept)) {
      $mimes = array_map($parseAcceptParams, explode(",", $accept));
    }
    usort($mimes, $sortAcceptMedias);
    return $mimes;
  }
}
