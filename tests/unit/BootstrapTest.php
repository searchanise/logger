<?php

namespace Tests\unit;

use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

final class BootstrapTest extends TestCase
{
    private $root;

    public function testEmpty(): void
    {
        $channelName = 'core';
        $logFileName = '/var/log/searchanise/core.log';

        $logger = \Searchanise\Logger\bootstrap($channelName, $logFileName, ['project' => 'core']);

        $this->assertIsObject($logger, "Logger is not an object!");
        $this->assertTrue($logger instanceof \Monolog\Logger, "Logger is not instance of Monolog\Logger!");
        $this->assertEquals($channelName, $logger->getName(), "Returned logger name is not equal {$channelName}");
    }

    public function testProcessors()
    {
        $channelName = 'core';
        $logFileName = '/var/log/searchanise/core.log';

        $logger = \Searchanise\Logger\bootstrap($channelName, $logFileName, ['project' => 'wix']);

        $this->assertTrue($logger->popProcessor() instanceof \Closure);
        $this->assertTrue($logger->popProcessor() instanceof \Monolog\Processor\MemoryUsageProcessor);
        $this->assertTrue($logger->popProcessor() instanceof \Monolog\Processor\GitProcessor);
        $this->assertTrue($logger->popProcessor() instanceof \Monolog\Processor\WebProcessor);
    }

    public function testHandler()
    {
        $channelName = 'core';
        $logFileName = '/var/log/searchanise/core.log';

        $logger = \Searchanise\Logger\bootstrap($channelName, $logFileName, ['project' => 'wix']);
        $this->assertEquals($logFileName, $logger->popHandler()->getUrl(), "Log handler stream is not equal {$logFileName}");
    }


    public function testChannel(): void
    {
        $channelName = 'core';
        $logFileName = '/var/log/searchanise/core.log';

        $logger = \Searchanise\Logger\bootstrap($channelName, $logFileName, ['project' => 'core']);

        $logger->popHandler();
        $handler = new \Monolog\Handler\TestHandler;
        $logger->pushHandler($handler);
        $logger->warning('test');

        list($record) = $handler->getRecords();
        $this->assertEquals($channelName, $record['channel']);
        $this->assertEquals('test', $record['message']);
        $this->assertEquals('core', $record['extra']['project']);
    }

    public function testLog(): void
    {
        $this->root = vfsStream::setup('var/log/searchanise');

        $channelName = 'core';
        $logFileName = 'var/log/searchanise/core.log';

        $logger = \Searchanise\Logger\bootstrap($channelName, '/' . $logFileName, ['project' => 'core']);
        $logger->popHandler();
        $logger->pushHandler(new \Monolog\Handler\StreamHandler(vfsStream::url($logFileName)));

        $logger->debug('debug');
        $logger->info('info');
        $logger->notice('notice');
        $logger->warning('warning');
        $logger->error('error');
        $logger->critical('critical');
        $logger->alert('alert');
        $logger->emergency('emergency');

        $this->assertTrue($this->root->hasChild($logFileName));

        $content = $this->root->getChild($logFileName)->getContent();
        $this->assertTrue((bool) preg_match('/core.DEBUG: debug/m', $content));
        $this->assertTrue((bool) preg_match('/core.INFO: info/m', $content));
        $this->assertTrue((bool) preg_match('/core.NOTICE: notice/m', $content));
        $this->assertTrue((bool) preg_match('/core.WARNING: warning/m', $content));
        $this->assertTrue((bool) preg_match('/core.ERROR: error/m', $content));
        $this->assertTrue((bool) preg_match('/core.CRITICAL: critical/m', $content));
        $this->assertTrue((bool) preg_match('/core.ALERT: alert/m', $content));
        $this->assertTrue((bool) preg_match('/core.EMERGENCY: emergency/m', $content));
    }
}
