<?php
/**
 * @file
 *   DebuggingLoggerListener.inc
 *
 * @author: marand
 *
 * @copyright (c) 2014 Ouest Systèmes Informatiques (OSInet).
 *
 * @license General Public License version 2 or later
 */

namespace Buzz\Listener;

use Buzz\Message\MessageInterface;
use Buzz\Message\RequestInterface;

/**
 * Class DebuggingLoggerListener: slight variant on LoggerListener.
 */
class DebuggingLoggerListener extends LoggerListener {

  protected $reported = null;

  public function __construct($logger, $prefix, array $reported = null) {
    parent::__construct($logger, $prefix);
    $this->reported = $reported;
  }

  protected function groupHeaders(array $raw) {
    $cooked = array();
    foreach ($raw as $index => $raw_item) {
      if (!$index) {
        $header = 'StatusCode';
        sscanf($raw_item, 'HTTP/%d.%d %d %s', $http_major, $http_minor, $value, $extra);
        assert('$http_major === 1');
        assert('$http_minor === 0 || $http_minor === 1');
      }
      else {
        list($header, $value) = @explode(':', $raw_item);
      }
      $value = trim($value);
      if (!isset($cooked[$header])) {
        $cooked[$header] = $value;
      }
      elseif (!is_scalar($cooked[$header])) {
        $cooked[$header] = array($cooked[$header]);
      }
      else {
        $cooked[$header][] = $value;
      }
    }

    if (isset($this->reported)) {
      $filtered = array();
      foreach ($cooked as $header => $value) {
        if (in_array($header, $this->reported)) {
          $filtered[$header] = $value;
        }
      }
    }
    else {
      $filtered = $cooked;
    }

    return $filtered;
  }

  public function postSend(RequestInterface $request, MessageInterface $response) {
    $seconds = microtime(TRUE) - $this->startTime;
    $args = array(
      $this->prefix,
      $request->getMethod(),
      $request->getHost(),
      $request->getResource(),
      round($seconds * 1000),
    );

    if (isset($this->reported) && empty($this->reported)) {
      $format = '%sSent "%s %s%s" in %dms.';
    }
    else {
      $args[] = print_r($this->groupHeaders($response->getHeaders()), TRUE);
      $format = '%sSent "%s %s%s" in %dms. Got headers: %s';
    }
    array_unshift($args, $format);
    call_user_func($this->logger, call_user_func_array('sprintf', $args));
  }
}
