<?php

/**
 * A route to a view.
 */
class Route extends AbstractRoute
{
  public $target;
  private $methodMatch;
  private $acceptMatch;
  private $pathMatch;
  private $tokens = [];

  /**
   * Construct Route by interpreting $route string.
   * A route string has three components <method> <path> <accept>.
   * The accept defaults to *\/*, meaning that the route will only be taken when the UA accepts *\/* - which it does by default.
   * The path may contain tokens (@token or @@token to match >1 path segment, and the wildcard (*).
   * Tokens are collected on accept and available if the route accepts a request via getTokens().
   * Examples: "GET / *\/*", "ANY / text/*", "GET /products", "GET / text/json".
   * @param $route String interpreted route.
   * @param $target the callable target.
   * @todo Support constuction via array and move this to astatic factory method. Faster.
   */
  public function __construct($route, $target) {
    $this->target = $target;
    $parts = [];
    preg_match('#^\s*([a-zA-Z]+)\s+(/[^\s]*)(\s+([^/]+)/([^/]+))?\s*$#', $route, $parts);
    $this->methodMatch = isset($parts[1]) ? $parts[1] : null;
    $this->pathMatch = isset($parts[2]) ? $parts[2] : null;
    $this->acceptMatch = (isset($parts[4]) && isset($parts[5])) ? [$parts[4],$parts[5]] : null;
    if(!(isset($this->methodMatch) && isset($this->pathMatch))) {
      throw new InvalidArgumentException("Failed parsing route '$route'. Require a method and path part. Syntax: <method> <path> [<accept>]");
    }
    $this->pathMatch = explode("/", rtrim($this->pathMatch, "/"));
    //array_shift($this->pathMatch);
    $this->parsePath();
  }

  private function parsePath() {
    $dups = [];
    foreach($this->pathMatch as $i => $part) {
      if(isset($part[0]) && $part[0] == "@") {
        if(isset($part[1]) && $part[1] == "@") {
          if(strlen($part) == 2) {
            throw new InvalidArgumentException("Invalid token in path '@'");
          }
          if($i != (sizeof($this->pathMatch)-1)) {
            throw new InvalidArgumentException("Invalid token in path. '@@' must come last");
          }
        }
        elseif(strlen($part) == 1) {
          throw new InvalidArgumentException("Invalid token in path '@@'");
        }
        elseif(isset($dups[$part])) {
          throw new InvalidArgumentException("Duplicate token in path '$part'");
        }
        $dups[] = $part;
      }
    }
  }

  /**
   * Test whether this route accepts the request.
   */
  public function accept($request) {
    defined('DEBUG') && print __METHOD__ . "\n";
    if(!($request instanceof HttpRequest)) {
      throw new \InvalidArgumentException("Argument 1 passed to " . __METHOD__ . " must be an instance of HttpRequest, " . gettype($request) . " given");
    }

    $match = true;
    // Method.
    if($this->methodMatch != "ANY" && $this->methodMatch != $request->getRequestMethod()) {
       $match = false;
    }
    defined('DEBUG') && var_dump("After Method: $match");
    // Path
    if($match) {
      $url = $request->getParsedUrl();
      $path = empty($url["path"]) ? "/" : $url["path"]; // if not empty we should get an abs path.
      $path = explode("/", rtrim($url["path"], "/"));
      //array_shift($path);
      $tokens = $this->tokenMatch($this->pathMatch, $path);
      if(isset($tokens)) {
        $this->tokens = $tokens;
      }
      else {
        $match = false;
      }
    }
    defined('DEBUG') && var_dump("After Path: $match");
    // Mime
    if($match) {
      $accepts = $request->getHeader("Accept", "\AcceptHeader");
      $accepts = empty($accepts) ? null : $accepts->getParams();
      if(!empty($accepts)) {
        $match = false;
        foreach($accepts as $accept) {
          if($accept[0][0] == "*" && $accept[0][1] == "*") {
            $match = true;
            break;
          }
          elseif ($accept[0][0] == $this->acceptMatch[0]) {
            if($accept[0][1] == "*") {
              $match = true;
              break;
            }
            elseif ($accept[0][1] == $this->acceptMatch[1]) {
              $match = true;
              break;
            }
          }
        }
      }
    }
    defined('DEBUG') && var_dump("After MIME: $match");
    return $match;
  }

  /**
   * See if path matches route spec path, and collect tokens while at it.
   */
  protected function tokenMatch(array $tokenPath, array $path) {
    defined('DEBUG') && print __METHOD__ . "\n" && var_dump($tokenPath, $path);
    $match = false;
    $matchedTokens = [];
    if(sizeof($path) >= sizeof($tokenPath)) {
      $match = true;
      foreach($path as $i => $part) {
        if($i >= sizeof($tokenPath)) {
          $match = false;
          break;
        }
        $token = $tokenPath[$i];
        if(isset($token[0]) && $token[0] == "@") {
           if($token[1] == "@") {
              $matchedTokens[ltrim($token, "@")] = implode("/", array_slice($path, $i));
              break;
           }
           else {
             $matchedTokens[ltrim($token, "@")] = $path[$i];
           }
        }
        elseif($token != $path[$i]) {
          $match = false;
          break;
        }
      }
    }
    return $match ? $matchedTokens : null;
  }

  public function getTokens() {
    return $this->tokens;
  }

  public function getTarget() {
    return $this->target;
  }

  public function __toString() {
    return "{$this->methodMatch} " . implode('/', $this->acceptMatch) . " {$this->pathMatch}";
  }
}
