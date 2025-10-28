<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Драйвер хранилища
    |--------------------------------------------------------------------------
    | Поддерживаемые драйверы: 'redis', 'vault'
    */
    'storage_driver' => env('CONNECTION_STORAGE_DRIVER', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Настройки кеша
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'store' => env('CONNECTION_STORAGE_CACHE_STORE', 'redis'),
        'ttl' => env('CONNECTION_STORAGE_CACHE_TTL', 86400), // 24 часа
        'prefix' => env('CONNECTION_STORAGE_CACHE_PREFIX', 'connection_data_'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Настройки HashiCorp Vault
    |--------------------------------------------------------------------------
    */
    'vault' => [
        'url' => env('VAULT_URL', 'http://localhost:8200'),
        'login' => env('VAULT_LOGIN', 'mcrm'),
        'password' => env('VAULT_PASS', ''),
        'mount_path' => env('VAULT_MOUNT_PATH', 'secret'),
        'timeout' => env('VAULT_TIMEOUT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Настройки внешнего API
    |--------------------------------------------------------------------------
    */
    'external_api' => [
        'url' => env('CONNECTION_STORAGE_API_URL', 'http://localhost:8080'),
        'timeout' => env('CONNECTION_STORAGE_API_TIMEOUT', 10),
        'notification_url' => env('CONNECTION_STORAGE_NOTIFICATION_URL', 'http://localhost:8080/api/connection-issues'),
        'token' => env('CONNECTION_STORAGE_API_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Настройки DTO
    |--------------------------------------------------------------------------
    */
    'dto' => [
        // Класс DTO для конкретного проекта
        'class' => env('CONNECTION_STORAGE_DTO_CLASS', \Mcrm\LaravelConnectionStorage\DTO\ConnectionDataDTO::class),
    ],

    /*
    |--------------------------------------------------------------------------
    | Настройки сервиса
    |--------------------------------------------------------------------------
    */
    'service' => [
        // Ключ текущего сервиса для получения данных из общего массива соединений
        'key' => env('CONNECTION_STORAGE_SERVICE_KEY', 'trigger_service'),
        
        // Название текущего сервиса для уведомлений
        'name' => env('CONNECTION_STORAGE_SERVICE_NAME', 'trigger-service'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Настройки валидации соединений
    |--------------------------------------------------------------------------
    */
    'validation' => [
        // Включить/выключить валидацию соединений
        'enabled' => env('CONNECTION_STORAGE_VALIDATION_ENABLED', true),
        
        // Таймаут для проверки соединения в секундах
        'timeout' => env('CONNECTION_STORAGE_VALIDATION_TIMEOUT', 5),
        
        // Время блокировки запросов после ошибки в секундах (30 минут)
        'block_duration' => env('CONNECTION_STORAGE_BLOCK_DURATION', 1800),
        
        // Префикс ключей блокировки в Redis
        'block_prefix' => env('CONNECTION_STORAGE_BLOCK_PREFIX', 'connection_block_'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Настройки уведомлений
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        // Включить/выключить уведомления о проблемах
        'enabled' => env('CONNECTION_STORAGE_NOTIFICATIONS_ENABLED', true),
        
        // Таймаут для отправки уведомлений в секундах
        'timeout' => env('CONNECTION_STORAGE_NOTIFICATIONS_TIMEOUT', 10),
        
        // Повторять ли уведомления при повторных ошибках
        'retry_enabled' => env('CONNECTION_STORAGE_NOTIFICATIONS_RETRY', false),
    ],
]; 