<?php

namespace Tests\unit;

use Closure;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\TestHandler;
use Monolog\Processor\GitProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\WebProcessor;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;
use function Searchanise\Logger\bootstrap;

final class BootstrapTest extends TestCase
{
    private $root;

    public function testEmpty(): void
    {
        $channelName = 'core';
        $logFileName = '/var/log/searchanise/core.log';

        $logger = bootstrap($channelName, $logFileName, ['project' => 'core']);

        $this->assertIsObject($logger, "Logger is not an object!");
        $this->assertEquals($channelName, $logger->getName(), "Returned logger name is not equal {$channelName}");
    }

    public function testProcessors()
    {
        $channelName = 'core';
        $logFileName = '/var/log/searchanise/core.log';

        $logger = bootstrap($channelName, $logFileName, ['project' => 'wix']);

        $this->assertInstanceOf(Closure::class, $logger->popProcessor());
        $this->assertInstanceOf(MemoryUsageProcessor::class, $logger->popProcessor());
        $this->assertInstanceOf(GitProcessor::class, $logger->popProcessor());
        $this->assertInstanceOf(WebProcessor::class, $logger->popProcessor());
    }

    public function testHandler()
    {
        $channelName = 'core';
        $logFileName = '/var/log/searchanise/core.log';

        $logger = bootstrap($channelName, $logFileName, ['project' => 'wix']);
        $this->assertEquals($logFileName, $logger->popHandler()->getUrl(), "Log handler stream is not equal {$logFileName}");
    }


    public function testChannel(): void
    {
        $channelName = 'core';
        $logFileName = '/var/log/searchanise/core.log';

        $logger = bootstrap($channelName, $logFileName, ['project' => 'core']);

        $logger->popHandler();
        $handler = new TestHandler;
        $logger->pushHandler($handler);
        $logger->warning('test');

        [$record] = $handler->getRecords();
        $this->assertEquals($channelName, $record['channel']);
        $this->assertEquals('test', $record['message']);
        $this->assertEquals('core', $record['extra']['project']);
    }

    public function testLog(): void
    {
        $this->root = vfsStream::setup('var/log/searchanise');

        $channelName = 'core';
        $logFileName = 'var/log/searchanise/core.log';

        $logger = bootstrap($channelName, '/' . $logFileName, ['project' => 'core']);
        $logger->popHandler();
        $logger->pushHandler(new StreamHandler(vfsStream::url($logFileName)));

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
        $this->assertTrue((bool)preg_match('/core.DEBUG: debug/m', $content));
        $this->assertTrue((bool)preg_match('/core.INFO: info/m', $content));
        $this->assertTrue((bool)preg_match('/core.NOTICE: notice/m', $content));
        $this->assertTrue((bool)preg_match('/core.WARNING: warning/m', $content));
        $this->assertTrue((bool)preg_match('/core.ERROR: error/m', $content));
        $this->assertTrue((bool)preg_match('/core.CRITICAL: critical/m', $content));
        $this->assertTrue((bool)preg_match('/core.ALERT: alert/m', $content));
        $this->assertTrue((bool)preg_match('/core.EMERGENCY: emergency/m', $content));
    }
}
