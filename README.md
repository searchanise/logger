# Searchanise Logger

[![Tests](https://github.com/searchanise/logger/actions/workflows/run-all-tests.yml/badge.svg)](https://github.com/searchanise/logger/actions/workflows/run-all-tests.yml)

Monolog bootstrap with predefined formatter and registered error handler.

Additionally, will add `ENGINE_ID` if existed.

# Install

composer.json
```json
...
"repositories": [
    {
      "url": "https://github.com/searchanise/logger",
      "type": "github"
    }
  ]
...
```

```shell
composer require searchanise/logger
```

# Usage

```php
$loggerCore = Logger::getInstance('project-name')->getLogger('core');
$loggerApi  = Logger::getInstance('project-name')->getLogger('api');

$loggerCore->info('This is info', ['engine_id' => 8902]);
$loggerApi->error('This is error', ['parent_engine_id' => 5600]);
```

# How to pass Engine ID
**Please note - ELK stack is awaiting Engine ID in record context by fields name `engine_id` or `parent_engine_id`** 

Already built-in passing from `$_SESSION` during bootstrap:

```php
$record['context']['parent_engine_id'] = $_SESSION['auth']['parent_engine_id'];
$record['context']['engine_id'] = $_SESSION['auth']['current_engine_id'];
```

Or explicitly by context for each record:

```php
Logger::getInstance('project-name')->getLogger('api')->critical('DB is gone', ['engine_id' => 8902]);
Logger::getInstance('project-name')->getLogger('api')->critical('DB is gone', ['parent_engine_id' => 5600]);
```

# Tests

```shell
composer test
```
