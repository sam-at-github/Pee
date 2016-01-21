<?php
/**
 * Example of a PSR compatiable logger.
 * PSR-3 defines an common interface for logger objects - Psr\Log\LoggerInterface.
 * There is a package that defines some jhelper classes specifically \Psr\Log\AbstractLogger. You extend and implement log();
 * See http://www.php-fig.org/psr/psr-3/.
 */
namespace Psr\Log;
require_once 'vendor/autoload.php';

/**
 * Psr Logger.
 */
class Logger extends \Psr\Log\AbstractLogger
{
  private static $levelMap = [
    "debug" => 0,
    "info" => 1,
    "notice" => 2,
    "warning" => 3,
    "error" => 4,
    "critical" => 5,
    "alert" => 6,
    "emergency" => 7
  ];
  private $level = "notice";

  /**
   * \Psr\Log\AbstractLogger calls back this method with the required level.
   */
  public function log($level, $message, array $context = []) {
    if(!isset(self::$levelMap[$level])) {
      throw new \Psr\Log\InvalidArgumentException();
    }
    $message = self::interpolate($message, $context);
    switch($level) {
      case \Psr\Log\LogLevel::EMERGENCY: case \Psr\Log\LogLevel::ALERT: case \Psr\Log\LogLevel::CRITICAL:
        error_log($this->format($level, $message, debug_backtrace()));
        break;
      default:
        if(self::$levelMap[$this->level] <= self::$levelMap[$level])
          error_log($this->format($level, $message, debug_backtrace()));
        break;
    }
  }

  /**
   * Set this log level of this logger.
   */
  public function level($level = null) {
    if($level !== null) {
      if(!isset(self::$levelMap[$level])) {
        throw new \Psr\Log\InvalidArgumentException();
      }
      $this->level = $level;
    }
  }

  /**
   * PSR-3 requires {key} to be replaced from the context.
   * C&P from http://www.php-fig.org/psr/psr-3/.
   */
  public static function interpolate($message, array $context = array())
  {
    $replace = array();
    foreach ($context as $key => $val) {
        $replace['{' . $key . '}'] = $val;
    }
    return strtr($message, $replace);
  }

  /**
   * Format a log message. This specifically for use by this class.
   * Unfortunately debug_backtrace() does not contain a level for the main context!
   * Thus currently if you call Logger::info() say from main context we can't find the file, line context.
   */
  public static function format($level, $message, $trace, $showTrace = true) {
    $str = "";
    if(sizeof($trace)<=2) {
      $str = sprintf("%s %s in <somefile> on line <someline>", "[$level]:", $message);
    }
    else {
      $str = sprintf("%s %s in %s on line %d", "[$level]:", $message, $trace[1]['file'], $trace[1]['line']);
    }
    if($showTrace) {
      for($i = 2; $i < sizeof($trace); $i++) {
        $class = isset($trace[$i]['class']) ? "{$trace[$i]['class']}::" : "";
        $str .= "\n" . str_repeat(" ", $i) . "{$trace[$i]['file']}::$class{$trace[$i]['function']}";
      }
    }
    return $str;
  }
}
