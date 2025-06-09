<?php

declare(strict_types=1);

namespace Mcrm\LaravelConnectionStorage\DTO;

/**
 * Базовый абстрактный DTO для хранения данных подключения
 * Должен быть переопределен в проектах
 */
abstract class BaseConnectionDataDTO
{
    /**
     * Конструктор
     *
     * @param  string  $boxName  Название коробки
     * @param  array<string, array<string, mixed>>  $connections  Все подключения
     */
    public function __construct(
        public readonly string $boxName,
        public readonly array $connections = []
    ) {}

    /**
     * Создает DTO из массива данных
     * Должен быть переопределен в наследниках для специфичной логики
     *
     * @param  array<string, mixed>  $data  Массив данных подключения
     * @return static|null DTO или null, если данные некорректны
     */
    abstract public static function fromArray(array $data): ?static;

    /**
     * Получает список обязательных полей для данного проекта
     *
     * @return array<string> Список обязательных полей
     */
    abstract protected static function getRequiredFields(): array;

    /**
     * Преобразует DTO в массив
     *
     * @return array<string, mixed> Массив данных подключения
     */
    public function toArray(): array
    {
        $result = [
            'boxname' => $this->boxName,
        ];

        // Добавляем все подключения
        foreach ($this->connections as $key => $value) {
            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * Получает название коробки
     *
     * @return string Название коробки
     */
    public function getBoxName(): string
    {
        return $this->boxName;
    }

    /**
     * Получает конфигурацию подключения по типу
     *
     * @param  string  $type  Тип подключения
     * @return array<string, mixed>|null Конфигурация подключения или null, если не найдена
     */
    public function getConnectionConfig(string $type): ?array
    {
        return $this->connections[$type] ?? null;
    }

    /**
     * Получает все подключения
     *
     * @return array<string, array<string, mixed>> Все подключения
     */
    public function getAllConnections(): array
    {
        return $this->connections;
    }

    /**
     * Проверяет наличие подключения определенного типа
     *
     * @param  string  $type  Тип подключения
     * @return bool True, если подключение существует
     */
    public function hasConnection(string $type): bool
    {
        return isset($this->connections[$type]);
    }
} 