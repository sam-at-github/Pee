<?php
namespace Pee;

/** Unfortunately this only effects the local file. */
// use \http\Env\Response as HttpResponse;

/**
 * Don't need to overwrite any right now, but \http\Env\Response and HttpRequest .. ew.
 */
class HttpResponse extends \http\Env\Response {}
