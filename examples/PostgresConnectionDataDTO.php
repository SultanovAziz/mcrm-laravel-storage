<?php

declare(strict_types=1);

namespace Mcrm\LaravelConnectionStorage\Examples;

use Mcrm\LaravelConnectionStorage\DTO\BaseConnectionDataDTO;

/**
 * Пример DTO для проекта с множественными PostgreSQL подключениями
 * 
 * Структура данных:
 * {
 *   "postgres": {
 *     "main": {"host": "localhost", "port": 5432, "database": "main_db", "username": "user", "password": "pass"},
 *     "analytics": {"host": "analytics.db", "port": 5432, "database": "analytics", "username": "user", "password": "pass"},
 *     "logs": {"host": "logs.db", "port": 5432, "database": "logs", "username": "user", "password": "pass"}
 *   },
 *   "elasticsearch": {"host": "localhost", "port": 9200, "index": "main"},
 *   "rabbitmq": {"host": "localhost", "port": 5672, "username": "guest", "password": "guest"},
 *   "boxname": "example-box"
 * }
 */
class PostgresConnectionDataDTO extends BaseConnectionDataDTO
{
    /**
     * Получает список обязательных полей для данного проекта
     *
     * @return array<string> Список обязательных полей
     */
    protected static function getRequiredFields(): array
    {
        return ['postgres', 'elasticsearch', 'rabbitmq', 'boxname'];
    }

    /**
     * Создает DTO из массива данных
     *
     * @param  array<string, mixed>  $data  Массив данных подключения
     * @return static|null DTO или null, если данные некорректны
     */
    public static function fromArray(array $data): ?static
    {
        $requiredFields = static::getRequiredFields();
        
        // Проверяем наличие всех обязательных полей
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                return null;
            }
        }

        // Дополнительная валидация для postgres - должен быть массивом соединений
        if (!is_array($data['postgres']) || empty($data['postgres'])) {
            return null;
        }

        /** @var string $boxName */
        $boxName = $data['boxname'];

        // Извлекаем все подключения, исключая boxname
        $connections = [];
        foreach ($data as $key => $value) {
            if ($key !== 'boxname' && is_array($value)) {
                $connections[$key] = $value;
            }
        }

        return new static(
            boxName: $boxName,
            connections: $connections
        );
    }

    /**
     * Получает конфигурацию конкретного PostgreSQL подключения
     *
     * @param  string  $connectionName  Название подключения (main, analytics, logs)
     * @return array<string, mixed>|null Конфигурация подключения или null, если не найдена
     */
    public function getPostgresConfig(string $connectionName): ?array
    {
        $postgres = $this->getConnectionConfig('postgres');
        
        if (!is_array($postgres)) {
            return null;
        }

        return $postgres[$connectionName] ?? null;
    }

    /**
     * Получает все PostgreSQL подключения
     *
     * @return array<string, array<string, mixed>> Все PostgreSQL подключения
     */
    public function getAllPostgresConnections(): array
    {
        return $this->getConnectionConfig('postgres') ?? [];
    }

    /**
     * Получает конфигурацию Elasticsearch
     *
     * @return array<string, mixed> Конфигурация Elasticsearch
     */
    public function getElasticsearchConfig(): array
    {
        return $this->getConnectionConfig('elasticsearch') ?? [];
    }

    /**
     * Получает конфигурацию RabbitMQ
     *
     * @return array<string, mixed> Конфигурация RabbitMQ
     */
    public function getRabbitMQConfig(): array
    {
        return $this->getConnectionConfig('rabbitmq') ?? [];
    }
} 