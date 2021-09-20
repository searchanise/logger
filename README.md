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
composer require searchanise/logger:^2.0
```

# Usage

```php
$channelName = 'core';
$logFileName = '/var/log/searchanise/core.log';

$logger = Searchanise\Logger\bootstrap($channelName, $logFileName, ['project' => 'wix']);

$logger->addInfo('This is info');
```
