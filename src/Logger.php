<?php
declare (strict_types=1);

namespace Searchanise;

use Monolog\ErrorHandler;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Psr\Log\LogLevel;

class Logger
{
    /** @var \Monolog\Logger[] */
    private array $loggers;
    private static ?Logger $instance = null;

    /**
     * Current project (api / core / wix and etc)
     *
     * @var string
     */
    private string $project;

    /**
     * Current log file including full path
     *
     * @var string
     */
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
            $this->loggers[$channel] = $this->bootstrap($channel, $this->getLogFile(), ['project' => $this->project]);
        }

        return $this->loggers[$channel];
    }

    public function getLogFile(): string
    {
        return $this->logFile;
    }

    /**
     * @param array $extra Any extra information
     */
    public function bootstrap(string $channel, string $logFile, array $extra = []): \Monolog\Logger
    {
        $log = new \Monolog\Logger($channel);
        $handler = new StreamHandler($logFile);

        $formatter = new JsonFormatter();
        $formatter->includeStacktraces(true);

        $handler->setFormatter($formatter);
        $log->pushHandler($handler);

        $this->setProcessors($log, $extra);

        return $log;
    }

    public static function toPSRLogLevel($errorLevel): string
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
        return implode(', ', array_map(static fn($item) => print_r($item, true), $messages));
    }

    protected function registerErrorHandler(): void
    {
        $logger = $this->bootstrap('error', $this->getLogFile(), ['project' => $this->project]);
        ErrorHandler::register($logger);
    }

    protected function setProcessors(\Monolog\Logger $log, array $extra = []): void
    {
        $log->pushProcessor(function ($record) use ($extra) {
            $context = $record->context;

            if (class_exists('Registry') && method_exists('Registry', 'getLogContext')) {
                foreach (\Registry::getLogContext() as $key => $value) {
                    $context[$key] ??= $value;
                }
            }

            $record = $record->with(context: $context, extra: array_merge($record['extra'], $extra));

            return $record;
        });
    }
}
