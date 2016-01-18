<?php
/**
 * This is a sample application of the framework.
 */
require_once 'App.php';
ini_set('log_errors', true);
ini_set('display_errors', true);
ini_set('error_reporting', E_ALL);
$app = App::instance();
echo "Foo\n";
trigger_error("User notice", E_USER_NOTICE);
trigger_error("User warning", E_USER_WARNING);
throw new Exception("asdsada");
