<?php

namespace tests;

use Monolog\Handler\TestHandler;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Searchanise\Logger;

class LoggerTest extends TestCase
{
    private Logger $loggerSingleton;

    public function testUniquenessLoggerInstance(): void
    {
        $firstCall = Logger::getInstance('test');
        $secondCall = Logger::getInstance('useless');
        $thirdCall = Logger::getInstance('will be test anyway');

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

        $this->assertEquals('core', $firstCall->getName());
        $this->assertEquals('api', $secondCall->getName());
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

        $content = json_decode((string) $content, true, 512, JSON_THROW_ON_ERROR);
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

    public function testLoggerUseEngineIdFromSession(): void
    {
        $_SESSION['auth']['parent_engine_id'] = 8908;
        $_SESSION['auth']['current_engine_id'] = 1001;

        $logger = $this->loggerSingleton->getLogger('test');

        $logger->popHandler();
        $handler = new TestHandler;
        $logger->pushHandler($handler);

        $logger->info('info record');

        [$record] = $handler->getRecords();
        $this->assertEquals(8908, $record['context']['parent_engine_id']);
        $this->assertEquals(1001, $record['context']['engine_id']);
    }

    public function testLoggerWillOverwriteEngineIdFromSessionByContext(): void
    {
        $_SESSION['auth']['parent_engine_id'] = 8908;
        $_SESSION['auth']['current_engine_id'] = 1001;

        $logger = $this->loggerSingleton->getLogger('test');

        $logger->popHandler();
        $handler = new TestHandler;
        $logger->pushHandler($handler);

        $logger->info('info record', ['engine_id' => 6006]);

        [$record] = $handler->getRecords();
        $this->assertEquals(8908, $record['context']['parent_engine_id']);
        $this->assertEquals(6006, $record['context']['engine_id']);
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
