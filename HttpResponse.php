<?php
// use \http\Env\Response as HttpResponse; // Unfortunately this only effects the local file.
/**
 * Don't need to overwrite any right now. But \http\Env\Response doesn't match HttpRequest..
 */
class HttpResponse extends \http\Env\Response {}
