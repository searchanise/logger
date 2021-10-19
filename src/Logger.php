<?php

namespace Searchanise;

use Monolog\ErrorHandler;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\GitProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\WebProcessor;
use Psr\Log\LogLevel;

class Logger
{
    /** @var \Monolog\Logger[] */
    private array $loggers;
    private static ?Logger $instance = null;
    private string $project;
    private string $logFile;

    private function __construct()
    {
    }
    public function __wakeup()
    {
    }
    private function __clone()
    {
    }

    /**
     * @deprecated DI should be used instead of Singleton
     */
    public function destruct(): void
    {
        self::$instance = null;
    }

    public static function getInstance(string $project): Logger
    {
        if (static::$instance === null) {
            static::$instance = new static();
            static::$instance->project = $project;
            static::$instance->logFile = "/var/log/searchanise/$project.log";
            static::$instance->registerErrorHandler();
        }

        return static::$instance;
    }

    public function getLogger(string $channel): \Monolog\Logger
    {
        if (!isset($this->loggers[$channel]) || !$this->loggers[$channel] instanceof \Monolog\Logger) {
            $this->loggers[$channel] = $this->bootstrap($channel, $this->logFile, ['project' => $this->project]);
        }

        return $this->loggers[$channel];
    }

    /**
     * @param string $channel
     * @param string $logFile
     * @param array $extra Any extra information
     * @return \Monolog\Logger
     */
    public function bootstrap(string $channel, string $logFile, array $extra = []): \Monolog\Logger
    {
        $log = new \Monolog\Logger($channel);
        $handler = new StreamHandler($logFile);

        $formatter = new JsonFormatter();
        $formatter->includeStacktraces(true);

        $handler->setFormatter($formatter);
        $log->pushHandler($handler);

        $log->pushProcessor(new WebProcessor());
        $log->pushProcessor(new MemoryUsageProcessor());

        $log->pushProcessor(function ($record) use ($extra) {
            if (isset($_SESSION['auth']['parent_engine_id']) && !isset($record['context']['parent_engine_id'])) {
                $record['context']['parent_engine_id'] = $_SESSION['auth']['parent_engine_id'];
            }
            if (isset($_SESSION['auth']['current_engine_id']) && !isset($record['context']['engine_id'])) {
                $record['context']['engine_id'] = $_SESSION['auth']['current_engine_id'];
            }

            $record['extra'] = array_merge($record['extra'], $extra);

            return $record;
        });

        return $log;
    }

    public static function toPSRLogLevel($errorLevel)
    {
        $levels = [
            E_ERROR => LogLevel::CRITICAL,
            E_WARNING => LogLevel::WARNING,
            E_PARSE => LogLevel::ALERT,
            E_NOTICE => LogLevel::NOTICE,
            E_CORE_ERROR => LogLevel::CRITICAL,
            E_CORE_WARNING => LogLevel::WARNING,
            E_COMPILE_ERROR => LogLevel::ALERT,
            E_COMPILE_WARNING => LogLevel::WARNING,
            E_USER_ERROR => LogLevel::ERROR,
            E_USER_WARNING => LogLevel::WARNING,
            E_USER_NOTICE => LogLevel::NOTICE,
            E_STRICT => LogLevel::NOTICE,
            E_RECOVERABLE_ERROR => LogLevel::ERROR,
            E_DEPRECATED => LogLevel::NOTICE,
            E_USER_DEPRECATED => LogLevel::NOTICE,

            /** @deprecated should be deleted after completed migration to Searchanise\Logger */
            'DBG' => LogLevel::DEBUG,
            'WARN' => LogLevel::WARNING,
        ];

        return $levels[$errorLevel] ?? LogLevel::NOTICE;
    }

    public static function toHumanReadableString(array $messages): string
    {
        return implode(', ', array_map(static function ($item) {return print_r($item, true);}, $messages));
    }

    private function registerErrorHandler(): void
    {
        $logger = $this->bootstrap('error', $this->logFile, ['project' => $this->project]);
        ErrorHandler::register($logger);
    }
}
