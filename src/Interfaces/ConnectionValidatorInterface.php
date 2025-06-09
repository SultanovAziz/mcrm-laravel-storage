<?php

declare(strict_types=1);

namespace Mcrm\LaravelConnectionStorage\Interfaces;

use Mcrm\LaravelConnectionStorage\DTO\BaseConnectionDataDTO;

/**
 * Интерфейс для валидации соединений с внешними системами
 */
interface ConnectionValidatorInterface
{
    /**
     * Проверяет валидность соединения с базой данных
     *
     * @param  array<string, mixed>  $config  Конфигурация подключения к БД
     * @return bool True, если соединение валидно
     */
    public function validateDatabaseConnection(array $config): bool;

    /**
     * Проверяет валидность всех соединений в DTO
     *
     * @param  BaseConnectionDataDTO  $connectionData  Данные подключений
     * @return bool True, если все соединения валидны
     */
    public function validateConnectionData(BaseConnectionDataDTO $connectionData): bool;

    /**
     * Проверяет, заблокирован ли URL для запросов
     *
     * @param  string  $url  URL (название коробки)
     * @return bool True, если URL заблокирован
     */
    public function isBlocked(string $url): bool;

    /**
     * Блокирует URL на определенное время
     *
     * @param  string  $url  URL (название коробки)
     * @param  int|null  $duration  Время блокировки в секундах (null - использовать из конфига)
     * @return bool Результат операции
     */
    public function blockUrl(string $url, ?int $duration = null): bool;

    /**
     * Разблокирует URL
     *
     * @param  string  $url  URL (название коробки)
     * @return bool Результат операции
     */
    public function unblockUrl(string $url): bool;
} 