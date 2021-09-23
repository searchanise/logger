<?php

namespace tests\unit;

use Closure;
use Monolog\Handler\TestHandler;
use Monolog\Logger as MonologLogger;
use Monolog\Processor\GitProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\WebProcessor;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Searchanise\Logger;

final class LoggerTest extends TestCase
{
    private Logger $loggerSingleton;

    public function testUniquenessLoggerInstance(): void
    {
        $firstCall = Logger::getInstance('test');
        $secondCall = Logger::getInstance('useless');
        $thirdCall = Logger::getInstance('will be test anyway');

        $this->assertInstanceOf(Logger::class, $firstCall);
        $this->assertSame($firstCall, $secondCall);
        $this->assertSame($secondCall, $thirdCall);

        $firstCall->destruct();
        $secondCall->destruct();
        $thirdCall->destruct();
    }

    public function testLoggerHaveMultipleMonologLoggers(): void
    {
        $firstCall = $this->loggerSingleton->getLogger('core');
        $secondCall = $this->loggerSingleton->getLogger('api');

        $this->assertInstanceOf(MonologLogger::class, $firstCall);
        $this->assertInstanceOf(MonologLogger::class, $secondCall);

        $this->assertEquals('core', $firstCall->getName());
        $this->assertEquals('api', $secondCall->getName());
    }

    public function testProcessorsExists(): void
    {
        $logger = $this->loggerSingleton->getLogger('core');

        $this->assertInstanceOf(Closure::class, $logger->popProcessor());
        $this->assertInstanceOf(MemoryUsageProcessor::class, $logger->popProcessor());
        $this->assertInstanceOf(GitProcessor::class, $logger->popProcessor());
        $this->assertInstanceOf(WebProcessor::class, $logger->popProcessor());
    }

    public function testLogFileNameIsBasedOnTheProject(): void
    {
        $logger = $this->loggerSingleton->getLogger('core');
        $this->assertEquals('/var/log/searchanise/test.log', $logger->popHandler()->getUrl());
    }

    public function testAdditionalInfoIsStored(): void
    {
        $channelName = 'core';
        $logger = $this->loggerSingleton->getLogger($channelName);

        $logger->popHandler();
        $handler = new TestHandler;
        $logger->pushHandler($handler);
        $logger->warning('warning record');

        [$record] = $handler->getRecords();
        $this->assertEquals($channelName, $record['channel']);
        $this->assertEquals('warning record', $record['message']);
        $this->assertEquals('test', $record['extra']['project']);
    }

    public function testMessagesStoredAsJsonIntoLogFile(): void
    {
        $root = vfsStream::setup('var/log/searchanise');
        $logFileName = 'var/log/searchanise/test.log';

        $logger = $this->loggerSingleton->bootstrap('core', vfsStream::url($logFileName), ['project' => 'core']);
        $logger->debug('debug');

        $this->assertTrue($root->hasChild($logFileName));

        $content = $root->getChild($logFileName)->getContent();
        $this->assertJson($content);

        $content = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        unset($content['extra']['git'], $content['extra']['memory_usage'], $content['datetime']);

        $this->assertEquals(
            [
                'message' => 'debug',
                'context' => [],
                'level' => 100,
                'level_name' => 'DEBUG',
                'channel' => 'core',
                'extra' => [
                    'project' => 'core'
                ],
            ],
            $content
        );
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->loggerSingleton = Logger::getInstance('test');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->loggerSingleton->destruct();
    }
}
