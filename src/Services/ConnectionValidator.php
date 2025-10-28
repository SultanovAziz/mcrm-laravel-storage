<?php

declare(strict_types=1);

namespace Mcrm\LaravelConnectionStorage\Services;

use Mcrm\LaravelConnectionStorage\DTO\BaseConnectionDataDTO;
use Mcrm\LaravelConnectionStorage\Interfaces\ConnectionValidatorInterface;
use Mcrm\LaravelConnectionStorage\Interfaces\StorageDriverInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для валидации соединений с внешними системами
 */
class ConnectionValidator implements ConnectionValidatorInterface
{
    /**
     * @param  StorageDriverInterface  $storageDriver  Драйвер хранилища
     * @param  int  $validationTimeout  Таймаут валидации в секундах
     * @param  int  $blockDuration  Время блокировки в секундах
     * @param  string  $blockPrefix  Префикс ключей блокировки
     */
    public function __construct(
        private readonly StorageDriverInterface $storageDriver,
        private readonly int $validationTimeout = 5,
        private readonly int $blockDuration = 1800,
        private readonly string $blockPrefix = 'connection_block_'
    ) {}

    /**
     * Проверяет валидность соединения с базой данных
     *
     * @param  array<string, mixed>  $config  Конфигурация подключения к БД
     * @return bool True, если соединение валидно
     */
    public function validateDatabaseConnection(array $config): bool
    {
        if (!$this->hasRequiredDbParams($config)) {
            return false;
        }

        try {
            // Создаем временное соединение для проверки
            $connectionName = 'validation_' . uniqid();
            
            config([
                "database.connections.{$connectionName}" => [
                    'driver' => $config['driver'] ?? 'mysql',
                    'host' => $config['host'],
                    'port' => $config['port'],
                    'database' => $config['database'],
                    'username' => $config['username'],
                    'password' => $config['password'],
                    'charset' => $config['charset'] ?? 'utf8mb4',
                    'collation' => $config['collation'] ?? 'utf8mb4_unicode_ci',
                    'options' => [
                        \PDO::ATTR_TIMEOUT => $this->validationTimeout,
                    ],
                ],
            ]);

            $connection = DB::connection($connectionName);
            $connection->getPdo();
            
            $connection->select('SELECT 1 as test');

            DB::purge($connectionName);

            return true;

        } catch (\Throwable $e) {
           
            try {
                DB::purge($connectionName ?? '');
            } catch (\Throwable) {
                // Игнорируем ошибки очистки
            }

            return false;
        }
    }

    /**
     * Проверяет валидность всех соединений в DTO
     *
     * @param  BaseConnectionDataDTO  $connectionData  Данные подключений
     * @return bool True, если все соединения валидны
     */
    public function validateConnectionData(BaseConnectionDataDTO $connectionData): bool
    {
        $dbConfig = $connectionData->getConnectionConfig('db');
        if ($dbConfig && !$this->validateDatabaseConnection($dbConfig)) {
            return false;
        }

        // Здесь можно добавить валидацию других типов соединений
        // например, RabbitMQ, Elasticsearch и т.д.

        return true;
    }

    /**
     * Проверяет, заблокирован ли URL для запросов
     *
     * @param  string  $url  URL (название коробки)
     * @return bool True, если URL заблокирован
     */
    public function isBlocked(string $url): bool
    {
        $key = $this->getBlockKey($url);
        
        return $this->storageDriver->has($key);
    }

    /**
     * Блокирует URL на определенное время
     *
     * @param  string  $url  URL (название коробки)
     * @param  int|null  $duration  Время блокировки в секундах (null - использовать из конфига)
     * @return bool Результат операции
     */
    public function blockUrl(string $url, ?int $duration = null): bool
    {
        $key = $this->getBlockKey($url);
        $duration = $duration ?? $this->blockDuration;
        
        return $this->storageDriver->put($key, time(), $duration);
    }

    /**
     * Разблокирует URL
     *
     * @param  string  $url  URL (название коробки)
     * @return bool Результат операции
     */
    public function unblockUrl(string $url): bool
    {
        $key = $this->getBlockKey($url);
        
        return $this->storageDriver->forget($key);
    }

    /**
     * Проверяет наличие обязательных параметров для подключения к БД
     *
     * @param  array<string, mixed>  $config  Конфигурация
     * @return bool True, если все параметры присутствуют
     */
    private function hasRequiredDbParams(array $config): bool
    {
        $required = ['host', 'port', 'database', 'username', 'password'];
        
        foreach ($required as $param) {
            if (!isset($config[$param]) || empty($config[$param])) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Формирует ключ блокировки для URL
     *
     * @param  string  $url  URL (название коробки)
     * @return string Ключ блокировки
     */
    private function getBlockKey(string $url): string
    {
        return $this->blockPrefix . $url;
    }
} 