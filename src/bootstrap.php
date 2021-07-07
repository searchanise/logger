<?php

namespace Searchanise\Logger;

use Exception;
use InvalidArgumentException;
use Monolog\ErrorHandler;
use Monolog\Formatter\JsonFormatter;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * @return Logger
 * @throws Exception                If a missing directory is not buildable
 * @throws InvalidArgumentException If stream is not a resource or string
 */
function bootstrap($channelName, $logFileName)
{
	$log = new Logger($channelName);
	$handler = new StreamHandler($logFileName);

	$formatter = new JsonFormatter();
	$formatter->includeStacktraces(true);

	$handler->setFormatter($formatter);
	$log->pushHandler($handler);

	$log->pushProcessor(function ($record) {
		if (isset($_SERVER['REQUEST_URI'])) {
			$record['extra']['REQUEST_URI'] = $_SERVER['REQUEST_URI'];
		}
		if (isset($_SERVER['HTTP_REFERER'])) {
			$record['extra']['HTTP_REFERER'] = $_SERVER['HTTP_REFERER'];
		}
		if (isset($_SESSION['auth']['parent_engine_id'])) {
			$record['extra']['parent_engine_id'] = $_SESSION['auth']['parent_engine_id'];
		}
		if (isset($_SESSION['auth']['current_engine_id'])) {
			$record['extra']['current_engine_id'] = $_SESSION['auth']['current_engine_id'];
		}
		return $record;
	});

	ErrorHandler::register($log);

	return $log;
}
