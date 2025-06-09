<?php

declare(strict_types=1);

namespace Mcrm\LaravelConnectionStorage\DTO;

/**
 * DTO для хранения данных подключения к внешним системам
 * Конкретная реализация для проекта trigger-service
 */
class ConnectionDataDTO extends BaseConnectionDataDTO
{
    /**
     * Получает список обязательных полей для trigger-service проекта
     *
     * @return array<string> Список обязательных полей
     */
    protected static function getRequiredFields(): array
    {
        return ['db', 'rabbitmq', 'boxname'];
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
     * Получает параметры подключения к базе данных
     * Вспомогательный метод для совместимости с текущим проектом
     *
     * @return array<string, mixed> Параметры подключения к базе данных
     */
    public function getDatabaseConfig(): array
    {
        return $this->getConnectionConfig('db') ?? [];
    }

    /**
     * Получает параметры подключения к RabbitMQ
     * Вспомогательный метод для совместимости с текущим проектом
     *
     * @return array<string, mixed> Параметры подключения к RabbitMQ
     */
    public function getRabbitMQConfig(): array
    {
        return $this->getConnectionConfig('rabbitmq') ?? [];
    }
}
