## Requirements

The following versions of PHP are supported.

* PHP 7.1+

## Installation

To install, use composer:

```
php composer.phar require pe/component-cronos-schedule
```

## Usage

Server usage

```php
<?php

namespace PE\Component\Cronos;

use PE\Component\Cronos\Core\ServerInterface;
use PE\Component\Cronos\Schedule\Storage\PersisterInterface;
use PE\Component\Cronos\Schedule\Storage\ProviderInterface;
use PE\Component\Cronos\Schedule\ScheduleModule;

// Initialize server somewhere and use some of options below (one or more)
/* @var $server ServerInterface */
$server = '...';

// Create provider storage
/* @var $storage1 ProviderInterface */
$storage1 = '...';

// Create persister storage
/* @var $storage2 PersisterInterface */
$storage2 = '...';

// Attach module with one of storage
$module = new ScheduleModule($storage1 ?? $storage2);
$server->attachModule('monitor', $module);
```

Client usage

```php
<?php

namespace PE\Component\Cronos;

use PE\Component\Cronos\Core\ClientInterface;
use PE\Component\Cronos\Core\TaskInterface;
use PE\Component\Cronos\Schedule\ScheduleAPI;

// Initialize client
/* @var $client ClientInterface */
$client = '...';

// Create API instance
$api = new ScheduleAPI($client);

// Initialize task object
/* @var $task TaskInterface */
$task = '...';

// Call for insert task on remote
$api->insertTask($task);// -> Inserted TaskInterface instance

// Call for update task on remote
$api->updateTask($task);// -> Updated TaskInterface instance

// Call for remove task on remote
$api->removeTask($task);// -> Deleted TaskInterface instance
```
