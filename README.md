# Searchanise Logger

Monolog bootstrap with predefined formatter and registered error handler.

Additionaly will add `ENGINE_ID`, `HTTP_REFERER` and `REQUEST_URI` if exist.

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
composer require searchanise/logger:^1.0
```

# Usage

```php

$channelName = 'core';
$logFileName = '/var/log/api-processor.log';

$logger = Searchanise\Logger\bootstrap($channelName, $logFileName);

$logger->addInfo('This is info');
```
