# Searchanise Logger

Monolog bootstrap with predefined formatter and registered error handler.

Additionally, will add `ENGINE_ID`, `HTTP_REFERER` and `REQUEST_URI` if existed.

# Install

composer.json
```json
...
"repositories": [
    {
      "url": "https://github.com/searchanise/logger",
      "type": "vcs"
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

$loggerCore->info('This is info');
$loggerApi->error()('This is error');
```
