# Laravel Connection Storage

Flexible Laravel package for managing external database connections with Redis cache and HTTP API fallback. Supports customizable DTOs and multiple connection types for microservices architecture.

## Features

- ✅ **Redis Caching** - automatic connection data caching
- ✅ **HTTP API Integration** - external API data retrieval
- ✅ **Connection Validation** - database availability checks
- ✅ **Notification System** - automatic error notifications
- ✅ **Connection Blocking** - temporary blocking after failures
- ✅ **Flexible DTOs** - customizable data classes for different projects
- ✅ **Multi-service Architecture** - service-specific data extraction support

## Installation

### 1. Install Package

```bash
composer require mcrm/laravel-connection-storage
```

### 2. Publish Configuration (REQUIRED)

```bash
php artisan vendor:publish --tag=connection-storage-config
```

This command will copy the configuration file to `config/connection-storage.php` in your project.

### 3. Environment Variables Setup

Add to your `.env` file:

```env
# Cache settings
CONNECTION_STORAGE_CACHE_STORE=redis
CONNECTION_STORAGE_CACHE_TTL=86400
CONNECTION_STORAGE_CACHE_PREFIX=connection_data_

# External API settings
CONNECTION_STORAGE_API_URL=http://your-api-server.com
CONNECTION_STORAGE_API_TIMEOUT=10
CONNECTION_STORAGE_NOTIFICATION_URL=http://your-api-server.com/api/connection-issues
CONNECTION_STORAGE_API_TOKEN=your-api-token

# DTO settings (if using custom DTO)
CONNECTION_STORAGE_DTO_CLASS=App\\DTOs\\YourCustomConnectionDTO

# Service settings
CONNECTION_STORAGE_SERVICE_KEY=your_service_name
CONNECTION_STORAGE_SERVICE_NAME=your-service

# Validation settings
CONNECTION_STORAGE_VALIDATION_ENABLED=true
CONNECTION_STORAGE_VALIDATION_TIMEOUT=5
CONNECTION_STORAGE_BLOCK_DURATION=1800
CONNECTION_STORAGE_BLOCK_PREFIX=connection_block_

# Notification settings
CONNECTION_STORAGE_NOTIFICATIONS_ENABLED=true
CONNECTION_STORAGE_NOTIFICATIONS_TIMEOUT=10
CONNECTION_STORAGE_NOTIFICATIONS_RETRY=false
```

## Basic Usage

### Getting Connection Data

```php
use Mcrm\LaravelConnectionStorage\Interfaces\ConnectionDataProviderInterface;

class DatabaseConnectionManager
{
    public function __construct(
        private ConnectionDataProviderInterface $connectionProvider
    ) {}

    public function getConnection(string $boxName): ?array
    {
        $connectionData = $this->connectionProvider->getConnectionData($boxName);
        
        if ($connectionData === null) {
            return null;
        }

        return $connectionData->getConnectionConfig('db');
    }
}
```

### Manual Data Caching

```php
use Mcrm\LaravelConnectionStorage\Interfaces\ConnectionDataProviderInterface;
use App\DTOs\YourCustomConnectionDTO;

$connectionData = YourCustomConnectionDTO::fromArray([
    'db' => [
        'host' => 'localhost',
        'database' => 'your_db',
        'username' => 'user',
        'password' => 'pass'
    ]
]);

$this->connectionProvider->cacheConnectionData('box-name', $connectionData);
```

## Creating Custom DTO

### 1. Create Your DTO Class

```php
<?php

namespace App\DTOs;

use Mcrm\LaravelConnectionStorage\DTO\BaseConnectionDataDTO;

class YourCustomConnectionDTO extends BaseConnectionDataDTO
{
    public function __construct(
        array $db = [],
        array $rabbitmq = [],
        array $elasticsearch = [],
        // Add your custom fields
        public readonly array $customField = []
    ) {
        parent::__construct($db, $rabbitmq);
    }

    public static function fromArray(array $data): ?static
    {
        return new static(
            db: $data['db'] ?? [],
            rabbitmq: $data['rabbitmq'] ?? [],
            elasticsearch: $data['elasticsearch'] ?? [],
            customField: $data['custom_field'] ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'db' => $this->db,
            'rabbitmq' => $this->rabbitmq,
            'elasticsearch' => $this->elasticsearch,
            'custom_field' => $this->customField,
        ];
    }

    public function getConnectionConfig(string $type): ?array
    {
        return match ($type) {
            'db' => $this->db,
            'rabbitmq' => $this->rabbitmq,
            'elasticsearch' => $this->elasticsearch,
            'custom' => $this->customField,
            default => null,
        };
    }
}
```

### 2. Update Configuration

In `config/connection-storage.php`:

```php
'dto' => [
    'class' => App\DTOs\YourCustomConnectionDTO::class,
],
```

## Data Architecture

### External API Data Format

The package expects the external API to return data in the following format:

```json
{
  "service1": {
    "db": {
      "host": "localhost",
      "database": "service1_db",
      "username": "user",
      "password": "pass"
    },
    "rabbitmq": {
      "host": "rabbitmq-host",
      "port": 5672,
      "username": "rabbit_user",
      "password": "rabbit_pass"
    }
  },
  "service2": {
    "db": {
      "host": "localhost", 
      "database": "service2_db",
      "username": "user2",
      "password": "pass2"
    }
  }
}
```

The package will automatically extract data for your service based on `CONNECTION_STORAGE_SERVICE_KEY`.

## Validation and Notifications

### Automatic Validation

The package automatically checks:
- Database connection availability
- Database existence

On errors:
- HTTP notifications are sent to `CONNECTION_STORAGE_NOTIFICATION_URL`
- Connection is temporarily blocked for `CONNECTION_STORAGE_BLOCK_DURATION`

### Disabling Validation

```env
CONNECTION_STORAGE_VALIDATION_ENABLED=false
```

## Interfaces

### ConnectionDataProviderInterface

Main interface for working with connection data:

```php
interface ConnectionDataProviderInterface
{
    public function getConnectionData(string $identifier): ?BaseConnectionDataDTO;
    public function cacheConnectionData(string $identifier, BaseConnectionDataDTO $connectionData): bool;
}
```

### ConnectionCacheServiceInterface

Interface for cache operations:

```php
interface ConnectionCacheServiceInterface
{
    public function getConnectionData(string $url): ?BaseConnectionDataDTO;
    public function getRawConnectionData(string $url): ?array;
    public function cacheConnectionData(string $url, BaseConnectionDataDTO $connectionData): bool;
    public function set(string $url, BaseConnectionDataDTO $connectionData, ?int $ttl = null): bool;
    public function forget(string $url): bool;
    public function remember(string $url, callable $callback, ?int $ttl = null): ?BaseConnectionDataDTO;
}
```

## License

MIT License

## Support

For questions and support, contact the MCRM development team. 